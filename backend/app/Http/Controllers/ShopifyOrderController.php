<?php

namespace App\Http\Controllers;

use App\Models\ShopifyOrder;
use App\Models\ShopifyStore;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\Order;
use App\Models\InventoryHistory;
use App\Models\ShopifyRecoveryHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShopifyOrderController extends Controller
{
    /**
     * Display a listing of Shopify synced orders.
     */
    public function index(Request $request)
    {
        $role = session('admin_role', Auth::user()->role ?? 'normal_admin');
        $isSuper = ($role === 'super_admin');

        $query = ShopifyOrder::query()->with(['shopifyStore', 'localOrder']);

        // Scoping for Normal Admins
        if (!$isSuper) {
            $userStoreIds = ShopifyStore::where('user_id', Auth::id())->pluck('id')->toArray();
            $query->whereIn('shopify_store_id', $userStoreIds);
            $stores = ShopifyStore::where('is_active', true)->whereIn('id', $userStoreIds)->get();
        } else {
            $stores = ShopifyStore::where('is_active', true)->get();
        }

        // Search by order number, customer email, or customer name
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Filter by SKU or Stock Number in line items
        if ($request->filled('sku_stock')) {
            $skuStock = $request->input('sku_stock');
            $shopifyOrderIds = Diamond::where('stock_no', 'like', "%{$skuStock}%")
                ->whereNotNull('shopify_order_id')
                ->pluck('shopify_order_id')
                ->merge(
                    Jewelery::where('sku', 'like', "%{$skuStock}%")
                        ->whereNotNull('shopify_order_id')
                        ->pluck('shopify_order_id')
                )->unique()->toArray();
            $query->whereIn('shopify_order_id', $shopifyOrderIds);
        }

        // Filter by financial_status (Payment Status)
        if ($request->filled('financial_status')) {
            $query->where('financial_status', $request->input('financial_status'));
        }

        // Filter by fulfillment_status
        if ($request->filled('fulfillment_status')) {
            $fStatus = $request->input('fulfillment_status');
            if ($fStatus === 'unfulfilled') {
                $query->whereNull('fulfillment_status');
            } else {
                $query->where('fulfillment_status', $fStatus);
            }
        }

        // Filter by store
        if ($request->filled('store_id')) {
            $query->where('shopify_store_id', $request->input('store_id'));
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        // Show latest orders first
        $orders = $query->orderBy('created_at', 'desc')->paginate(15)->appends($request->query());

        // Handle AJAX polling / real-time updates
        if ($request->ajax()) {
            return response()->json([
                'html' => view('shopify.orders.partials.table', compact('orders', 'isSuper'))->render(),
                'total' => $orders->total(),
            ]);
        }

        return view('shopify.orders.index', compact('orders', 'stores', 'isSuper'));
    }

    /**
     * Display the specified Shopify Order detail.
     */
    public function show($id)
    {
        $role = session('admin_role', Auth::user()->role ?? 'normal_admin');
        $isSuper = ($role === 'super_admin');

        $order = ShopifyOrder::with('shopifyStore')->findOrFail($id);

        // Ownership validation for normal admin
        if (!$isSuper) {
            $userStoreIds = ShopifyStore::where('user_id', Auth::id())->pluck('id')->toArray();
            if (!in_array($order->shopify_store_id, $userStoreIds)) {
                abort(403, 'Unauthorized action. You do not have permission to view this order.');
            }
        }

        // Retrieve products linked to this order
        $diamonds = Diamond::where('shopify_order_id', $order->shopify_order_id)->get();
        $jewelry = Jewelery::where('shopify_order_id', $order->shopify_order_id)->get();

        // Retrieve local draft/invoice order if matches
        $localOrder = Order::where('shopify_order_id', $order->shopify_order_id)->first();

        // Generate unified, sorted timeline logs
        $timeline = [];
        if ($localOrder) {
            foreach ($localOrder->logs as $log) {
                $timeline[] = [
                    'title' => $log->action,
                    'description' => $log->message,
                    'time' => $log->created_at,
                    'icon' => 'shopping-cart',
                ];
            }
        }

        $productIds = $diamonds->pluck('id')->toArray();
        $jewelryIds = $jewelry->pluck('id')->toArray();

        $timelineLogs = InventoryHistory::where(function($q) use ($productIds) {
                $q->where('product_type', 'diamond')->whereIn('product_id', $productIds);
            })
            ->orWhere(function($q) use ($jewelryIds) {
                $q->where('product_type', 'jewelry')->whereIn('product_id', $jewelryIds);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($timelineLogs as $hist) {
            $remarks = $hist->remarks ?: "Status changed from {$hist->old_value} to {$hist->new_value}";
            $timeline[] = [
                'title' => "Inventory: " . ucfirst($hist->action),
                'description' => "Product " . ($hist->product_type === 'diamond' ? 'Diamond' : 'Jewelry') . " ID #{$hist->product_id}: {$remarks}",
                'time' => $hist->created_at,
                'icon' => 'box',
            ];
        }

        // Sort timeline descending/ascending as desired. Let's do ascending for a chronological flow.
        usort($timeline, function($a, $b) {
            return $a['time'] <=> $b['time'];
        });

        return view('shopify.orders.show', compact('order', 'diamonds', 'jewelry', 'localOrder', 'timeline', 'isSuper'));
    }

    /**
     * Export matching shopify orders as CSV.
     */
    public function export(Request $request)
    {
        $role = session('admin_role', Auth::user()->role ?? 'normal_admin');
        $isSuper = ($role === 'super_admin');

        $query = ShopifyOrder::query()->with('shopifyStore');

        // Scoping for Normal Admins
        if (!$isSuper) {
            $userStoreIds = ShopifyStore::where('user_id', Auth::id())->pluck('id')->toArray();
            $query->whereIn('shopify_store_id', $userStoreIds);
        }

        // Search by order number, customer email, or customer name
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Filter by SKU or Stock Number in line items
        if ($request->filled('sku_stock')) {
            $skuStock = $request->input('sku_stock');
            $shopifyOrderIds = Diamond::where('stock_no', 'like', "%{$skuStock}%")
                ->whereNotNull('shopify_order_id')
                ->pluck('shopify_order_id')
                ->merge(
                    Jewelery::where('sku', 'like', "%{$skuStock}%")
                        ->whereNotNull('shopify_order_id')
                        ->pluck('shopify_order_id')
                )->unique()->toArray();
            $query->whereIn('shopify_order_id', $shopifyOrderIds);
        }

        // Filter by financial_status
        if ($request->filled('financial_status')) {
            $query->where('financial_status', $request->input('financial_status'));
        }

        // Filter by fulfillment_status
        if ($request->filled('fulfillment_status')) {
            $fStatus = $request->input('fulfillment_status');
            if ($fStatus === 'unfulfilled') {
                $query->whereNull('fulfillment_status');
            } else {
                $query->where('fulfillment_status', $fStatus);
            }
        }

        // Filter by store
        if ($request->filled('store_id')) {
            $query->where('shopify_store_id', $request->input('store_id'));
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=shopify_orders_export_" . date('Ymd_His') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Order Number', 'Customer Name', 'Customer Email', 'Store Name', 'Total Price', 'Currency', 'Payment Status', 'Fulfillment Status', 'Date Created'];

        $callback = function() use($orders, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->customer_name,
                    $order->customer_email,
                    $order->shopifyStore ? $order->shopifyStore->store_name : 'N/A',
                    $order->total_price,
                    $order->currency,
                    $order->financial_status,
                    $order->fulfillment_status ?: 'unfulfilled',
                    $order->created_at->toDateTimeString(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Run manual reconciliation/recovery.
     */
    public function runRecovery(Request $request)
    {
        $role = session('admin_role', Auth::user()->role ?? 'normal_admin');
        $isSuper = ($role === 'super_admin');

        $request->validate([
            'store_id' => 'nullable|exists:shopify_stores,id'
        ]);

        $storeId = $request->input('store_id');

        // Normal admin ownership checks
        if (!$isSuper) {
            if (!$storeId) {
                abort(403, 'Unauthorized action. Normal Admin cannot recover all stores.');
            }
            $userStoreIds = ShopifyStore::where('user_id', Auth::id())->pluck('id')->toArray();
            if (!in_array($storeId, $userStoreIds)) {
                abort(403, 'Unauthorized action. You do not own this store.');
            }
        }

        $storesCount = $storeId ? 1 : ShopifyStore::where('is_active', true)->count();

        // Create recovery log in shopify_recovery_histories
        $recoveryLog = ShopifyRecoveryHistory::create([
            'user_id' => Auth::id() ?? 1,
            'stores_scanned' => $storesCount,
            'products_checked' => 0,
            'issues_fixed' => 0,
            'drafted_count' => 0,
            'republished_count' => 0,
            'status' => 'pending',
        ]);

        try {
            $syncService = app(\App\Services\ShopifySyncService::class);
            $totalProcessed = 0;
            $ordersProcessed = 0;
            $productsProcessed = 0;

            if ($storeId) {
                $res = $syncService->reconcileRecovery($storeId);
                $ordersProcessed = $res['orders_processed'] ?? 0;
                $productsProcessed = $res['products_processed'] ?? 0;
                $totalProcessed = $res['total_processed'] ?? 0;
                $message = "Recovery sync completed! Total processed: {$totalProcessed} (Orders: {$ordersProcessed}, Products: {$productsProcessed}).";
            } else {
                $activeStores = ShopifyStore::where('is_active', true)->get();
                foreach ($activeStores as $store) {
                    $res = $syncService->reconcileRecovery($store->id);
                    $totalProcessed += $res['total_processed'] ?? 0;
                    $ordersProcessed += $res['orders_processed'] ?? 0;
                    $productsProcessed += $res['products_processed'] ?? 0;
                }
                $message = "Recovery sync completed for all active stores! Total processed: {$totalProcessed} (Orders: {$ordersProcessed}, Products: {$productsProcessed}).";
            }

            // Estimate metrics for recovery logging
            $recoveryLog->update([
                'status' => 'completed',
                'products_checked' => $productsProcessed,
                'issues_fixed' => $ordersProcessed,
                'drafted_count' => 0,
                'republished_count' => $productsProcessed,
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);
        } catch (\Throwable $e) {
            $recoveryLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage() . "\n" . $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => "Recovery sync failed: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View custom professional invoice for Shopify synced orders.
     */
    public function viewInvoice($id)
    {
        $role = session('admin_role', Auth::user()->role ?? 'normal_admin');
        $isSuper = ($role === 'super_admin');

        $order = ShopifyOrder::with('shopifyStore')->findOrFail($id);

        // Ownership validation for normal admin
        if (!$isSuper) {
            $userStoreIds = ShopifyStore::where('user_id', Auth::id())->pluck('id')->toArray();
            if (!in_array($order->shopify_store_id, $userStoreIds)) {
                abort(403, 'Unauthorized action. You do not have permission to view this invoice.');
            }
        }

        // Retrieve local draft/invoice order if matches
        $localOrder = Order::where('shopify_order_id', $order->shopify_order_id)->first();

        $isWebhook = true;
        return view('shopify.orders.invoice', compact('order', 'isWebhook', 'localOrder'));
    }
}
