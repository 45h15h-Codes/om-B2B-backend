<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ShopifyOrder;
use App\Models\Order;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyStore;
use App\Services\AuditService;

class ReportController extends Controller
{
    /**
     * Display reporting center dashboard with summary aggregates.
     */
    public function index(Request $request)
    {
        $activeRole = session('admin_role', auth()->user()->role ?? 'normal_admin');
        $isSuper = ($activeRole === 'super_admin');

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $storeId = $request->input('shopify_store_id');

        $userStoreIds = [];
        if (!$isSuper) {
            $userStoreIds = ShopifyStore::where('user_id', Auth::id())->pluck('id')->toArray();
            $stores = ShopifyStore::whereIn('id', $userStoreIds)->get();
        } else {
            $stores = ShopifyStore::all();
        }

        // Compile Store Performance Stats
        $storeReports = [];
        foreach ($stores as $store) {
            if (!$isSuper && !in_array($store->id, $userStoreIds)) {
                continue;
            }
            $shopifyQuery = ShopifyOrder::where('shopify_store_id', $store->id);
            if ($startDate) $shopifyQuery->whereDate('created_at', '>=', $startDate);
            if ($endDate) $shopifyQuery->whereDate('created_at', '<=', $endDate);

            $storeReports[] = [
                'store_name' => $store->store_name,
                'domain' => $store->shop_domain,
                'orders_count' => $shopifyQuery->count(),
                'revenue' => (float) $shopifyQuery->sum('total_price'),
                'is_active' => $store->is_active
            ];
        }

        // Inventory scoping helper queries
        if (!$isSuper) {
            $diamondsQuery = Diamond::where(function($q) use ($userStoreIds) {
                $q->whereIn('id', function($sub) use ($userStoreIds) {
                    $sub->select('diamond_id')->from('diamond_store_assignments')->whereIn('shopify_store_id', $userStoreIds);
                })
                ->orWhereIn('id', function($sub) use ($userStoreIds) {
                    $sub->select('product_id')->from('shopify_products')->where('product_type', 'diamond')->whereIn('shopify_store_id', $userStoreIds);
                });
            });

            $jewelryQuery = Jewelery::where(function($q) use ($userStoreIds) {
                $q->whereIn('id', function($sub) use ($userStoreIds) {
                    $sub->select('product_id')->from('shopify_products')->where('product_type', 'jewelry')->whereIn('shopify_store_id', $userStoreIds);
                });
            });
        } else {
            $diamondsQuery = Diamond::query();
            $jewelryQuery = Jewelery::query();
        }

        // Compile Inventory Counts Stats
        $inventoryStats = [
            'diamonds' => [
                'total' => (clone $diamondsQuery)->count(),
                'available' => (clone $diamondsQuery)->where(function($q){$q->whereNull('inventory_status')->orWhere('inventory_status', 'available');})->count(),
                'on_hold' => (clone $diamondsQuery)->where('inventory_status', 'on_hold')->count(),
                'sold' => (clone $diamondsQuery)->where('inventory_status', 'sold')->count(),
            ],
            'jewelry' => [
                'total' => (clone $jewelryQuery)->count(),
                'available' => (clone $jewelryQuery)->where(function($q){$q->whereNull('inventory_status')->orWhere('inventory_status', 'available');})->count(),
                'on_hold' => (clone $jewelryQuery)->where('inventory_status', 'on_hold')->count(),
                'sold' => (clone $jewelryQuery)->where('inventory_status', 'sold')->count(),
            ]
        ];

        // Compile Sales Summary (recent 10 sales)
        $shopifyOrdersQuery = ShopifyOrder::with('shopifyStore');
        if ($startDate) $shopifyOrdersQuery->whereDate('created_at', '>=', $startDate);
        if ($endDate) $shopifyOrdersQuery->whereDate('created_at', '<=', $endDate);
        
        if ($storeId) {
            if (!$isSuper && !in_array($storeId, $userStoreIds)) {
                abort(403, 'Unauthorized store filter.');
            }
            $shopifyOrdersQuery->where('shopify_store_id', $storeId);
        } elseif (!$isSuper) {
            $shopifyOrdersQuery->whereIn('shopify_store_id', $userStoreIds);
        }

        $recentShopifySales = $shopifyOrdersQuery->orderBy('created_at', 'desc')->limit(10)->get();

        return view('reports.index', compact('storeReports', 'inventoryStats', 'recentShopifySales', 'stores', 'isSuper'));
    }

    /**
     * Stream chunk-based CSV export for reports.
     */
    public function exportCsv(Request $request)
    {
        $activeRole = session('admin_role', auth()->user()->role ?? 'normal_admin');
        $isSuper = ($activeRole === 'super_admin');

        $type = $request->input('type', 'sales');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $storeId = $request->input('shopify_store_id');

        $userStoreIds = [];
        if (!$isSuper) {
            $userStoreIds = ShopifyStore::where('user_id', Auth::id())->pluck('id')->toArray();
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $type . '_report_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function() use ($type, $startDate, $endDate, $storeId, $isSuper, $userStoreIds) {
            $file = fopen('php://output', 'w');

            if ($type === 'sales') {
                fputcsv($file, ['Order #', 'Source', 'Customer Name', 'Store / Channel', 'Status', 'Date', 'Subtotal', 'Discount', 'Total']);

                // Query Shopify Orders
                $shopifyQuery = ShopifyOrder::with('shopifyStore');
                if ($startDate) $shopifyQuery->whereDate('created_at', '>=', $startDate);
                if ($endDate) $shopifyQuery->whereDate('created_at', '<=', $endDate);
                
                if ($storeId) {
                    if (!$isSuper && !in_array($storeId, $userStoreIds)) {
                        fclose($file);
                        abort(403);
                    }
                    $shopifyQuery->where('shopify_store_id', $storeId);
                } elseif (!$isSuper) {
                    $shopifyQuery->whereIn('shopify_store_id', $userStoreIds);
                }
                
                $shopifyQuery->chunk(100, function($orders) use ($file) {
                    foreach ($orders as $order) {
                        fputcsv($file, [
                            $order->order_number,
                            'Shopify',
                            $order->customer_name,
                            $order->shopifyStore ? $order->shopifyStore->store_name : 'Default Store',
                            $order->financial_status,
                            $order->created_at->format('Y-m-d H:i:s'),
                            $order->total_price,
                            0,
                            $order->total_price
                        ]);
                    }
                });

                // Query Direct Invoices
                $invoiceQuery = Order::with('shopifyStore')->where('status', 'completed');
                if ($startDate) $invoiceQuery->whereDate('created_at', '>=', $startDate);
                if ($endDate) $invoiceQuery->whereDate('created_at', '<=', $endDate);
                
                if ($storeId) {
                    $invoiceQuery->where('shopify_store_id', $storeId);
                } elseif (!$isSuper) {
                    $invoiceQuery->whereIn('shopify_store_id', $userStoreIds);
                }

                $invoiceQuery->chunk(100, function($orders) use ($file) {
                    foreach ($orders as $order) {
                        fputcsv($file, [
                            $order->shopify_order_number ?: ($order->uuid ?: $order->id),
                            'Direct Invoice',
                            $order->customer_name,
                            $order->shopifyStore ? $order->shopifyStore->store_name : 'Direct Invoicing',
                            $order->status,
                            $order->created_at->format('Y-m-d H:i:s'),
                            $order->subtotal,
                            $order->discount,
                            $order->total
                        ]);
                    }
                });

            } elseif ($type === 'inventory') {
                fputcsv($file, ['Product Category', 'Stock No / SKU', 'Type / Shape', 'Carat / Size', 'Price', 'Inventory Status', 'Assigned Admin', 'Created At']);

                // Diamonds Query with scoping
                if (!$isSuper) {
                    $diamondQuery = Diamond::where(function($q) use ($userStoreIds) {
                        $q->whereIn('id', function($sub) use ($userStoreIds) {
                            $sub->select('diamond_id')->from('diamond_store_assignments')->whereIn('shopify_store_id', $userStoreIds);
                        })
                        ->orWhereIn('id', function($sub) use ($userStoreIds) {
                            $sub->select('product_id')->from('shopify_products')->where('product_type', 'diamond')->whereIn('shopify_store_id', $userStoreIds);
                        });
                    })->with('assignedAdmin');
                } else {
                    $diamondQuery = Diamond::with('assignedAdmin');
                }

                $diamondQuery->chunk(100, function($diamonds) use ($file) {
                    foreach ($diamonds as $d) {
                        fputcsv($file, [
                            'Diamond',
                            $d->stock_no,
                            $d->shape,
                            $d->size,
                            $d->asking_price,
                            $d->inventory_status,
                            $d->assignedAdmin ? $d->assignedAdmin->name : 'Unassigned',
                            $d->created_at->format('Y-m-d')
                        ]);
                    }
                });

                // Jewelry Query with scoping
                if (!$isSuper) {
                    $jewelryQuery = Jewelery::where(function($q) use ($userStoreIds) {
                        $q->whereIn('id', function($sub) use ($userStoreIds) {
                            $sub->select('product_id')->from('shopify_products')->where('product_type', 'jewelry')->whereIn('shopify_store_id', $userStoreIds);
                        });
                    })->with('assignedAdmin');
                } else {
                    $jewelryQuery = Jewelery::with('assignedAdmin');
                }

                $jewelryQuery->chunk(100, function($jewelery) use ($file) {
                    foreach ($jewelery as $j) {
                        fputcsv($file, [
                            'Jewelry',
                            $j->sku,
                            $j->type,
                            '-',
                            $j->price,
                            $j->inventory_status,
                            $j->assignedAdmin ? $j->assignedAdmin->name : 'Unassigned',
                            $j->created_at->format('Y-m-d')
                        ]);
                    }
                });

            } elseif ($type === 'stores') {
                fputcsv($file, ['Store Name', 'Shopify Domain', 'Total Ingested Revenue', 'Total Orders Synced', 'Active Status']);

                $stores = $isSuper ? ShopifyStore::all() : ShopifyStore::whereIn('id', $userStoreIds)->get();
                foreach ($stores as $store) {
                    $sales = ShopifyOrder::where('shopify_store_id', $store->id)->sum('total_price');
                    $orders = ShopifyOrder::where('shopify_store_id', $store->id)->count();

                    fputcsv($file, [
                        $store->store_name,
                        $store->shop_domain,
                        $sales,
                        $orders,
                        $store->is_active ? 'Active' : 'Inactive'
                    ]);
                }
            }

            fclose($file);
        };

        app(\App\Services\AuditService::class)->log(
            'export_report_csv',
            null,
            null,
            ['type' => $type]
        );

        return response()->stream($callback, 200, $headers);
    }
}
