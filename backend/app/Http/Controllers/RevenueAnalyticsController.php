<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ShopifyOrder;
use App\Models\Order;
use App\Models\ShopifyStore;
use App\Models\Diamond;
use App\Models\Jewelery;
use Carbon\Carbon;

class RevenueAnalyticsController extends Controller
{
    /**
     * Display the revenue analytics dashboard.
     */
    public function index(Request $request)
    {
        $role = session('admin_role', Auth::user()->role ?? 'normal_admin');
        $isSuper = ($role === 'super_admin');

        $userStoreIds = [];
        if (!$isSuper) {
            $userStoreIds = ShopifyStore::where('user_id', Auth::id())->pluck('id')->toArray();
        }

        // Get date ranges
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfYear = Carbon::now()->startOfYear();

        // Base Queries with role scoping and Paid Status Only check
        $shopifyOrderQuery = ShopifyOrder::where('financial_status', 'paid');
        $invoiceOrderQuery = Order::whereIn('status', ['completed', 'paid']);

        if (!$isSuper) {
            $shopifyOrderQuery->whereIn('shopify_store_id', $userStoreIds);
            $invoiceOrderQuery->whereIn('shopify_store_id', $userStoreIds);
        }

        // 1. Shopify Store Revenue Stats (Paid status only)
        $shopifyToday = (clone $shopifyOrderQuery)->whereDate('created_at', $today)->sum('total_price');
        $shopifyMonth = (clone $shopifyOrderQuery)->where('created_at', '>=', $startOfMonth)->sum('total_price');
        $shopifyYear = (clone $shopifyOrderQuery)->where('created_at', '>=', $startOfYear)->sum('total_price');
        $shopifyTotal = (clone $shopifyOrderQuery)->sum('total_price');
        $shopifyCount = (clone $shopifyOrderQuery)->count();

        // 2. Direct Invoice Revenue Stats (approved orders)
        $invoiceToday = (clone $invoiceOrderQuery)->whereDate('created_at', $today)->sum('total');
        $invoiceMonth = (clone $invoiceOrderQuery)->where('created_at', '>=', $startOfMonth)->sum('total');
        $invoiceYear = (clone $invoiceOrderQuery)->where('created_at', '>=', $startOfYear)->sum('total');
        $invoiceTotal = (clone $invoiceOrderQuery)->sum('total');
        $invoiceCount = (clone $invoiceOrderQuery)->count();

        // Combined statistics
        $totalToday = $shopifyToday + $invoiceToday;
        $totalMonth = $shopifyMonth + $invoiceMonth;
        $totalYear = $shopifyYear + $invoiceYear;
        $grandTotal = $shopifyTotal + $invoiceTotal;
        $totalCount = $shopifyCount + $invoiceCount;

        // 3. Shopify Revenue by Store
        $stores = $isSuper ? ShopifyStore::all() : ShopifyStore::whereIn('id', $userStoreIds)->get();
        $storeNames = [];
        $storeSales = [];
        foreach ($stores as $store) {
            $storeNames[] = $store->store_name;
            $storeSales[] = (float) ShopifyOrder::where('shopify_store_id', $store->id)
                ->where('financial_status', 'paid')
                ->sum('total_price');
        }

        // 4. Monthly Revenue Trend (Last 6 Months)
        $monthlyLabels = [];
        $monthlyShopifyData = [];
        $monthlyInvoiceData = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $monthlyLabels[] = $monthStart->format('M Y');

            $mShopify = ShopifyOrder::where('financial_status', 'paid')->whereBetween('created_at', [$monthStart, $monthEnd]);
            $mInvoice = Order::whereIn('status', ['completed', 'paid'])->whereBetween('created_at', [$monthStart, $monthEnd]);

            if (!$isSuper) {
                $mShopify->whereIn('shopify_store_id', $userStoreIds);
                $mInvoice->whereIn('shopify_store_id', $userStoreIds);
            }

            $monthlyShopifyData[] = (float) $mShopify->sum('total_price');
            $monthlyInvoiceData[] = (float) $mInvoice->sum('total');
        }

        // 5. Product Category Sales Breakdown (sold items from inventory)
        if (!$isSuper) {
            $soldDiamonds = Diamond::where('inventory_status', 'sold')->whereIn('sold_store_id', $userStoreIds)->count();
            $soldJewelry = Jewelery::where('inventory_status', 'sold')->whereIn('sold_store_id', $userStoreIds)->count();
        } else {
            $soldDiamonds = Diamond::where('inventory_status', 'sold')->count();
            $soldJewelry = Jewelery::where('inventory_status', 'sold')->count();
        }

        // 6. Super Admin Store Performance Rankings
        $rankings = [];
        if ($isSuper) {
            foreach (ShopifyStore::all() as $st) {
                $paidOrders = ShopifyOrder::where('shopify_store_id', $st->id)->where('financial_status', 'paid')->count();
                $totalOrders = ShopifyOrder::where('shopify_store_id', $st->id)->count();
                $rev = (float) ShopifyOrder::where('shopify_store_id', $st->id)->where('financial_status', 'paid')->sum('total_price');
                
                $diamondsCount = Diamond::where('sold_store_id', $st->id)->where('inventory_status', 'sold')->count();
                $jewelryCount = Jewelery::where('sold_store_id', $st->id)->where('inventory_status', 'sold')->count();
                
                $convRate = $totalOrders > 0 ? round(($paidOrders / $totalOrders) * 100, 2) : 0.00;
                
                $rankings[] = [
                    'store_name' => $st->store_name,
                    'revenue' => $rev,
                    'orders_count' => $paidOrders,
                    'diamonds_sold' => $diamondsCount,
                    'jewelry_sold' => $jewelryCount,
                    'conversion_rate' => $convRate,
                ];
            }

            // Sort rankings by revenue descending
            usort($rankings, function($a, $b) {
                return $b['revenue'] <=> $a['revenue'];
            });
        }

        return view('analytics.revenue', compact(
            'shopifyToday', 'shopifyMonth', 'shopifyYear', 'shopifyTotal', 'shopifyCount',
            'invoiceToday', 'invoiceMonth', 'invoiceYear', 'invoiceTotal', 'invoiceCount',
            'totalToday', 'totalMonth', 'totalYear', 'grandTotal', 'totalCount',
            'storeNames', 'storeSales',
            'monthlyLabels', 'monthlyShopifyData', 'monthlyInvoiceData',
            'soldDiamonds', 'soldJewelry', 'rankings', 'isSuper'
        ));
    }
}
