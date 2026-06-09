<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ShopifyStore;
use App\Models\Diamond;
use App\Models\Jewelery;

class InventoryTimelineController extends Controller
{
    /**
     * Display a listing of unified inventory timeline events.
     */
    public function index(Request $request)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            abort(403, 'Only Super Admins can access inventory timeline.');
        }

        $search = $request->input('search');
        $productType = $request->input('product_type');
        $action = $request->input('action');

        // Build History Query
        $historyQuery = DB::table('inventory_histories')
            ->select([
                DB::raw("'history' as log_type"),
                'inventory_histories.id',
                'inventory_histories.created_at',
                'inventory_histories.product_type',
                'inventory_histories.product_id',
                'inventory_histories.action',
                'inventory_histories.old_value',
                'inventory_histories.new_value',
                'inventory_histories.user_id',
                'inventory_histories.remarks as description',
                'inventory_histories.ip_address as extra',
                DB::raw("NULL as shopify_store_id"),
                DB::raw("NULL as stock_no")
            ]);

        if ($productType) {
            $historyQuery->where('product_type', $productType === 'diamond' ? Diamond::class : Jewelery::class);
        }
        if ($action) {
            $historyQuery->where('action', $action);
        }
        if ($search) {
            $historyQuery->where(function($q) use ($search) {
                $q->where('remarks', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhereExists(function ($qe) use ($search) {
                      $qe->select(DB::raw(1))
                         ->from('diamonds')
                         ->whereRaw('diamonds.id = inventory_histories.product_id')
                         ->where('inventory_histories.product_type', Diamond::class)
                         ->where('diamonds.stock_no', 'like', "%{$search}%");
                  })
                  ->orWhereExists(function ($qe) use ($search) {
                      $qe->select(DB::raw(1))
                         ->from('jeweleries')
                         ->whereRaw('jeweleries.id = inventory_histories.product_id')
                         ->where('inventory_histories.product_type', Jewelery::class)
                         ->where('jeweleries.sku', 'like', "%{$search}%");
                  });
            });
        }

        // Build Shopify Audit Query
        $auditQuery = DB::table('shopify_inventory_audits')
            ->select([
                DB::raw("'audit' as log_type"),
                'shopify_inventory_audits.id',
                'shopify_inventory_audits.created_at',
                DB::raw("CASE WHEN diamond_id IS NOT NULL THEN 'App\\\\Models\\\\Diamond' ELSE 'App\\\\Models\\\\Jewelery' END as product_type"),
                DB::raw("COALESCE(diamond_id, jewelry_id) as product_id"),
                'shopify_inventory_audits.action',
                'shopify_inventory_audits.previous_quantity as old_value',
                'shopify_inventory_audits.new_quantity as new_value',
                DB::raw("NULL as user_id"),
                'shopify_inventory_audits.error_message as description',
                'shopify_inventory_audits.shopify_product_id as extra',
                'shopify_inventory_audits.shopify_store_id',
                'shopify_inventory_audits.stock_no'
            ]);

        if ($productType) {
            if ($productType === 'diamond') {
                $auditQuery->whereNotNull('diamond_id');
            } else {
                $auditQuery->whereNotNull('jewelry_id');
            }
        }
        if ($action) {
            $auditQuery->where('action', $action);
        }
        if ($search) {
            $auditQuery->where(function($q) use ($search) {
                $q->where('error_message', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('stock_no', 'like', "%{$search}%");
            });
        }

        // Combine queries and paginate
        $unionQuery = DB::query()->fromSub($historyQuery->unionAll($auditQuery), 'unified_timeline');
        $timeline = $unionQuery->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Hydrate relations to avoid N+1 queries
        $userIds = [];
        $storeIds = [];
        $diamondIds = [];
        $jewelryIds = [];

        foreach ($timeline->items() as $item) {
            if ($item->user_id) {
                $userIds[] = $item->user_id;
            }
            if ($item->shopify_store_id) {
                $storeIds[] = $item->shopify_store_id;
            }
            if ($item->product_type === Diamond::class) {
                $diamondIds[] = $item->product_id;
            } elseif ($item->product_type === Jewelery::class) {
                $jewelryIds[] = $item->product_id;
            }
        }

        $users = User::whereIn('id', array_unique($userIds))->get()->keyBy('id');
        $stores = ShopifyStore::whereIn('id', array_unique($storeIds))->get()->keyBy('id');
        $diamonds = Diamond::whereIn('id', array_unique($diamondIds))->get()->keyBy('id');
        $jewelry = Jewelery::whereIn('id', array_unique($jewelryIds))->get()->keyBy('id');

        foreach ($timeline->items() as $item) {
            $item->user = $item->user_id ? $users->get($item->user_id) : null;
            $item->store = $item->shopify_store_id ? $stores->get($item->shopify_store_id) : null;
            if ($item->product_type === Diamond::class) {
                $item->product = $diamonds->get($item->product_id);
            } elseif ($item->product_type === Jewelery::class) {
                $item->product = $jewelry->get($item->product_id);
            } else {
                $item->product = null;
            }
        }

        return view('inventory.timeline', compact('timeline'));
    }
}
