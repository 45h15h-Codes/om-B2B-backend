<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ShopifyStore;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Services\OrderService;
use App\Services\ShopifyOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $isSuper = (session('admin_role', 'normal_admin') === 'super_admin');
        
        $query = Order::query()->with(['shopifyStore', 'creator', 'approver']);

        if (!$isSuper) {
            // Normal Admin: only view orders belonging to their stores
            $storeIds = ShopifyStore::where('user_id', Auth::id())->pluck('id')->toArray();
            $query->whereIn('shopify_store_id', $storeIds);
        }

        // Search by order number, customer email, or customer name
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function($q) use ($search) {
                $q->where('shopify_order_number', 'like', "%{$search}%")
                  ->orWhere('shopify_draft_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Filter by store
        if ($request->filled('store_id')) {
            $query->where('shopify_store_id', $request->input('store_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->orderBy('id', 'desc')->paginate(15)->appends($request->query());

        // Fetch stores to resolve "Undefined variable $stores"
        if ($isSuper) {
            $stores = ShopifyStore::where('is_active', true)->get();
        } else {
            $stores = ShopifyStore::where('user_id', Auth::id())->where('is_active', true)->get();
        }

        return view('shopify.orders.index', compact('orders', 'isSuper', 'stores'))->with('isLocalOrders', true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $isSuper = (session('admin_role', 'normal_admin') === 'super_admin');
        
        if ($isSuper) {
            $stores = ShopifyStore::where('is_active', true)->get();
        } else {
            // Normal Admin: create only for their stores
            $stores = ShopifyStore::where('user_id', Auth::id())->where('is_active', true)->get();
        }

        // Load diamonds with role-based filtering
        $diamondsQuery = Diamond::where('status', 'Approved');
        if (!$isSuper) {
            $diamondsQuery->where('user_id', Auth::id());
        }
        $diamondsSql = $diamondsQuery->toSql();
        $diamondsBindings = $diamondsQuery->getBindings();
        $diamonds = $diamondsQuery->orderBy('id', 'desc')->get();

        // Load jewelry with role-based filtering
        $jewelryQuery = Jewelery::where('status', 'Approved');
        if (!$isSuper) {
            $jewelryQuery->where('user_id', Auth::id());
        }
        $jewelrySql = $jewelryQuery->toSql();
        $jewelryBindings = $jewelryQuery->getBindings();
        $jewelry = $jewelryQuery->orderBy('id', 'desc')->get();

        // Add temporary debug logging as requested
        Log::info('Order Create Debug Logging:', [
            'auth_user_id' => Auth::id(),
            'admin_role' => session('admin_role', 'normal_admin'),
            'diamonds_count' => $diamonds->count(),
            'jewelry_count' => $jewelry->count(),
            'diamonds_query_sql' => $diamondsSql,
            'diamonds_query_bindings' => $diamondsBindings,
            'jewelry_query_sql' => $jewelrySql,
            'jewelry_query_bindings' => $jewelryBindings,
        ]);

        return view('shopify.orders.create', compact('stores', 'diamonds', 'jewelry', 'isSuper'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isSuper = (session('admin_role', 'normal_admin') === 'super_admin');
        
        $request->validate([
            'shopify_store_id' => 'required|exists:shopify_stores,id',
            'email' => 'nullable|email',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_type' => 'required|in:diamond,jewelry',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price_snapshot' => 'required|numeric|min:0',
        ]);

        $storeId = $request->input('shopify_store_id');
        if (!$isSuper) {
            // Normal Admin: validate ownership
            $ownsStore = ShopifyStore::where('id', $storeId)->where('user_id', Auth::id())->exists();
            if (!$ownsStore) {
                abort(403, 'Unauthorized action. You can only create orders for your own stores.');
            }
        }

        try {
            $order = $this->orderService->createOrder($request->all(), Auth::id());
            return redirect()->route('orders.show', $order->id)
                ->with('success', 'Order created successfully in the system. Awaiting approval.');
        } catch (\Throwable $e) {
            Log::error('Order local creation failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to create Order: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $isSuper = (session('admin_role', 'normal_admin') === 'super_admin');
        
        // Normal Admin: Cannot access another store's order via URL
        if (!$isSuper && $order->shopifyStore->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action. You do not have permission to view this order.');
        }

        $logs = $order->logs()->with('user')->orderBy('id', 'desc')->get();

        $timeline = [];
        foreach ($logs as $log) {
            $timeline[] = [
                'title' => $log->action,
                'description' => $log->message,
                'time' => $log->created_at,
                'icon' => 'shopping-cart',
            ];
        }

        if ($order->shopify_order_id) {
            $diamonds = \App\Models\Diamond::where('shopify_order_id', $order->shopify_order_id)->get();
            $jewelry = \App\Models\Jewelery::where('shopify_order_id', $order->shopify_order_id)->get();

            $productIds = $diamonds->pluck('id')->toArray();
            $jewelryIds = $jewelry->pluck('id')->toArray();

            $timelineLogs = \App\Models\InventoryHistory::where(function($q) use ($productIds) {
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

            usort($timeline, function($a, $b) {
                return $a['time'] <=> $b['time'];
            });
        }

        return view('shopify.orders.show', compact('order', 'logs', 'timeline', 'isSuper'));
    }

    /**
     * Approve the Order and trigger background sync.
     */
    public function approve(Order $order)
    {
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            abort(403, 'Unauthorized action. Only Super Admin can approve orders.');
        }

        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending orders can be approved.');
        }

        try {
            $this->orderService->approveOrder($order, Auth::id());
            return redirect()->route('orders.show', $order->id)
                ->with('success', 'Order approved successfully! Sync job has been queued in the background.');
        } catch (\Throwable $e) {
            Log::error('Order approval/sync dispatch failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to approve Order: ' . $e->getMessage());
        }
    }

    /**
     * Send Shopify Invoice to Customer.
     */
    public function sendInvoice(Request $request, Order $order)
    {
        $isSuper = (session('admin_role', 'normal_admin') === 'super_admin');
        
        if (!$isSuper) {
            abort(403, 'Unauthorized action. Only Super Admin can send invoices.');
        }

        if (!$order->shopify_draft_id) {
            return redirect()->back()->with('error', 'Order must be synced to Shopify before sending invoice.');
        }

        $isResend = $request->has('resend');

        // Prevent duplicate invoice sending using invoice_sent_at validation unless resending
        if ($order->invoice_sent_at && !$isResend) {
            return redirect()->back()->with('error', 'Invoice has already been sent for this order at ' . $order->invoice_sent_at->format('M d, Y H:i') . '.');
        }

        try {
            $connector = app(ShopifyOrderService::class);
            $connector->sendInvoice($order);

            $order->update([
                'status' => 'invoice_sent',
                'invoice_sent_at' => now(),
            ]);

            $order->logs()->create([
                'user_id' => Auth::id(),
                'action' => $isResend ? 'Invoice Resent' : 'Invoice Sent',
                'message' => $isResend ? 'Shopify draft order invoice resent to customer.' : 'Shopify draft order invoice sent to customer.',
            ]);

            return redirect()->back()
                ->with('success', $isResend ? 'Shopify Draft Order invoice resent successfully!' : 'Shopify Draft Order invoice sent successfully!');
        } catch (\Throwable $e) {
            Log::error('Send Shopify Invoice failed: ' . $e->getMessage());

            $order->logs()->create([
                'user_id' => Auth::id(),
                'action' => $isResend ? 'Invoice Resend Failed' : 'Invoice Failed',
                'message' => ($isResend ? 'Failed to resend Shopify invoice: ' : 'Failed to send Shopify invoice: ') . $e->getMessage(),
            ]);

            return redirect()->back()->with('error', ($isResend ? 'Failed to resend invoice via Shopify: ' : 'Failed to send invoice via Shopify: ') . $e->getMessage());
        }
    }

    /**
     * Retry failed orders sync.
     */
    public function retry(Order $order)
    {
        $isSuper = (session('admin_role', 'normal_admin') === 'super_admin');
        
        // Ownership check
        if (!$isSuper && $order->shopifyStore->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Only retry if status = failed
        if ($order->status !== 'failed') {
            return redirect()->back()->with('error', 'Only failed orders can be retried.');
        }

        try {
            $order->update(['status' => 'syncing']);
            
            $order->logs()->create([
                'user_id' => Auth::id(),
                'action' => 'Shopify Sync Started',
                'message' => 'Retry sync job has been queued in the background.',
            ]);

            \App\Jobs\SyncOrderToShopifyJob::dispatch($order->id);

            return redirect()->route('orders.show', $order->id)
                ->with('success', 'Order sync job queued for retry.');
        } catch (\Throwable $e) {
            Log::error('Retry Order Sync failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to retry order sync: ' . $e->getMessage());
        }
    }

    /**
     * Complete the Shopify Draft Order (convert it to a real Shopify Order and mark as paid).
     */
    public function complete(Order $order)
    {
        $isSuper = (session('admin_role', 'normal_admin') === 'super_admin');
        
        if (!$isSuper) {
            abort(403, 'Unauthorized action. Only Super Admin can complete orders.');
        }

        if (!$order->shopify_draft_id) {
            return redirect()->back()->with('error', 'Order must be synced to Shopify before it can be completed.');
        }

        if ($order->status === 'paid' || $order->status === 'completed') {
            return redirect()->back()->with('error', 'Order is already completed/paid.');
        }

        try {
            $connector = app(ShopifyOrderService::class);
            $result = $connector->completeDraftOrder($order);

            $shopifyDraftOrder = $result['draft_order'] ?? [];
            $shopifyOrderId = isset($shopifyDraftOrder['order_id']) ? (string)$shopifyDraftOrder['order_id'] : null;

            $updateData = [
                'status' => 'paid',
                'shopify_response' => array_merge($order->shopify_response ?? [], $result),
            ];

            if ($shopifyOrderId) {
                $updateData['shopify_order_id'] = $shopifyOrderId;
                $shopDomain = $order->shopifyStore->shop_domain ?? '';
                $updateData['shopify_order_admin_url'] = "https://{$shopDomain}/admin/orders/{$shopifyOrderId}";
            }

            $order->update($updateData);

            $order->logs()->create([
                'user_id' => Auth::id(),
                'action' => 'Order Completed',
                'message' => 'Completed the Shopify draft order. Converted to a real Shopify Order.',
                'payload' => $result,
            ]);

            return redirect()->route('orders.show', $order->id)
                ->with('success', 'Order completed successfully on Shopify!');
        } catch (\Throwable $e) {
            Log::error('Complete Shopify Order failed: ' . $e->getMessage());

            $order->logs()->create([
                'user_id' => Auth::id(),
                'action' => 'Completion Failed',
                'message' => 'Failed to complete Shopify order: ' . $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to complete order via Shopify: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete orders.
     */
    public function destroy(Order $order)
    {
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Find any active reservations in 'hold' status for this order
            $reservations = \App\Models\ShopifyInventoryReservation::where(function ($query) use ($order) {
                $query->where('order_id', $order->id);
                if ($order->shopify_order_id) {
                    $query->orWhere('shopify_order_id', $order->shopify_order_id);
                }
            })
            ->where('status', 'hold')
            ->get();

            $inventoryService = app(\App\Services\InventoryService::class);

            foreach ($reservations as $reservation) {
                $product = $reservation->product;
                if ($product) {
                    // Check if the product itself is in 'hold' status before reverting
                    if (in_array($product->inventory_status, ['hold', 'on_hold'])) {
                        $inventoryService->updateInventoryStatus(
                            $product,
                            'available',
                            $reservation->shopify_store_id,
                            $reservation->shopify_order_id,
                            $order->id
                        );
                    }
                }
            }

            // Create an audit log record before soft deleting
            $order->logs()->create([
                'user_id' => Auth::id(),
                'action' => 'Order Deleted',
                'message' => 'Order soft deleted by Super Admin. Active holds reverted to available.',
            ]);

            $order->delete();

            return redirect()->route('orders.index')->with('success', 'Order deleted successfully and inventory holds reverted.');
        } catch (\Throwable $e) {
            Log::error('Order deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete Order: ' . $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted order.
     */
    public function restore($id)
    {
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            abort(403, 'Unauthorized action. Only Super Admin can restore orders.');
        }

        try {
            $order = Order::onlyTrashed()->findOrFail($id);
            $order->restore();

            $order->logs()->create([
                'user_id' => Auth::id(),
                'action' => 'Order Restored',
                'message' => 'Order restored from trash by Super Admin.',
            ]);

            return redirect()->route('orders.index')->with('success', 'Order restored successfully.');
        } catch (\Throwable $e) {
            Log::error('Order restore failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to restore Order: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete an order.
     */
    /**
     * Permanent delete an order.
     */
    public function forceDelete($id)
    {
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            abort(403, 'Unauthorized action. Only Super Admin can permanently delete orders.');
        }

        try {
            $order = Order::withTrashed()->findOrFail($id);
            $order->forceDelete();

            return redirect()->route('orders.index')->with('success', 'Order permanently deleted.');
        } catch (\Throwable $e) {
            Log::error('Order permanent deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to permanently delete Order: ' . $e->getMessage());
        }
    }

    /**
     * View custom professional invoice.
     */
    public function viewInvoice(Order $order)
    {
        $isSuper = (session('admin_role', 'normal_admin') === 'super_admin');

        // Normal Admin: Cannot access another store's order invoice via URL
        if (!$isSuper && $order->shopifyStore->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action. You do not have permission to view this invoice.');
        }

        $isWebhook = false;
        return view('shopify.orders.invoice', compact('order', 'isWebhook'));
    }
}

