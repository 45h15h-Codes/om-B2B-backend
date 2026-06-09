<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SyncJobHistory;
use App\Models\ShopifyStore;
use App\Models\ShopifyProduct;

class ShopifySyncCenterController extends Controller
{
    /**
     * Display the Shopify Sync Center interface.
     */
    public function index(Request $request)
    {
        $activeRole = session('admin_role', auth()->user()->role ?? 'normal_admin');
        $isSuper = ($activeRole === 'super_admin');

        $status = $request->input('status');
        $storeId = $request->input('shopify_store_id');
        $search = $request->input('search');

        $query = SyncJobHistory::with('shopifyStore');

        $userStoreIds = [];
        if (!$isSuper) {
            $userStoreIds = ShopifyStore::where('user_id', Auth::id())->pluck('id')->toArray();
            $query->whereIn('shopify_store_id', $userStoreIds);
            $stores = ShopifyStore::whereIn('id', $userStoreIds)->get();
        } else {
            $stores = ShopifyStore::all();
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($storeId) {
            if (!$isSuper && !in_array($storeId, $userStoreIds)) {
                abort(403, 'Unauthorized store access.');
            }
            $query->where('shopify_store_id', $storeId);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('job_type', 'like', "%{$search}%")
                  ->orWhere('errors', 'like', "%{$search}%");
            });
        }

        $syncHistories = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Get active processing sync jobs
        $activeSyncQuery = SyncJobHistory::where('status', 'processing')->with('shopifyStore');
        if (!$isSuper) {
            $activeSyncQuery->whereIn('shopify_store_id', $userStoreIds);
        }
        $activeSyncs = $activeSyncQuery->get();

        // Count of failed shopify product syncs
        $failedProductsQuery = ShopifyProduct::whereIn('sync_status', ['failed', 'error']);
        if (!$isSuper) {
            $failedProductsQuery->whereIn('shopify_store_id', $userStoreIds);
        }
        $failedProductsCount = $failedProductsQuery->count();

        return view('shopify.sync_center', compact('syncHistories', 'stores', 'activeSyncs', 'failedProductsCount', 'isSuper'));
    }
}
