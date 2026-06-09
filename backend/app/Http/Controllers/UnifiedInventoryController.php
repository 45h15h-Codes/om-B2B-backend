<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Services\InventoryManager;
use App\Jobs\BulkHoldInventoryJob;
use App\Jobs\BulkReleaseInventoryJob;
use App\Jobs\BulkSyncInventoryJob;
use App\Jobs\BulkAssignAdminJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UnifiedInventoryController extends Controller
{
    protected InventoryManager $inventoryManager;

    public function __construct(InventoryManager $inventoryManager)
    {
        $this->inventoryManager = $inventoryManager;
    }

    /**
     * Display a listing of the unified inventory.
     */
    public function index(Request $request)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        $isSuperAdmin = $activeRole === 'super_admin';

        $stock = $request->input('stock_no');
        $status = $request->input('inventory_status');
        $hold = $request->input('hold_status');
        $assignedAdmin = $request->input('assigned_admin_id');
        $syncStatus = $request->input('shopify_sync_status');
        $productTypeFilter = $request->input('product_type');

        // Subquery for diamonds mapping count
        $diamondMappingCount = DB::table('shopify_products')
            ->selectRaw('count(*)')
            ->whereColumn('shopify_products.product_id', '=', 'diamonds.id')
            ->where('shopify_products.product_type', '=', 'diamond');

        // Subquery for diamonds mapping failed status
        $diamondFailedCount = DB::table('shopify_products')
            ->selectRaw('count(*)')
            ->whereColumn('shopify_products.product_id', '=', 'diamonds.id')
            ->where('shopify_products.product_type', '=', 'diamond')
            ->whereIn('shopify_products.sync_status', ['failed', 'error']);

        // Subquery for jeweleries mapping count
        $jewelryMappingCount = DB::table('shopify_products')
            ->selectRaw('count(*)')
            ->whereColumn('shopify_products.product_id', '=', 'jeweleries.id')
            ->where('shopify_products.product_type', '=', 'jewelry');

        // Subquery for jeweleries mapping failed status
        $jewelryFailedCount = DB::table('shopify_products')
            ->selectRaw('count(*)')
            ->whereColumn('shopify_products.product_id', '=', 'jeweleries.id')
            ->where('shopify_products.product_type', '=', 'jewelry')
            ->whereIn('shopify_products.sync_status', ['failed', 'error']);

        // Base Diamond query
        $diamonds = DB::table('diamonds')
            ->leftJoin('users as admins', 'diamonds.assigned_admin_id', '=', 'admins.id')
            ->select([
                'diamonds.id',
                'diamonds.stock_no',
                DB::raw("'diamond' as product_type"),
                'diamonds.shape',
                'diamonds.size as carat',
                'diamonds.color',
                'diamonds.clarity',
                'diamonds.asking_price as price',
                'diamonds.inventory_status',
                'diamonds.assigned_admin_id',
                'admins.name as assigned_admin_name',
                'diamonds.hold_by',
                'diamonds.hold_reason',
                'diamonds.hold_at',
                'diamonds.created_at',
                DB::raw('(' . $diamondMappingCount->toSql() . ') as store_mapping_count'),
                DB::raw('(' . $diamondFailedCount->toSql() . ') as failed_mapping_count')
            ]);
        $diamonds->mergeBindings($diamondMappingCount);
        $diamonds->mergeBindings($diamondFailedCount);

        // Base Jewelry query
        $jewelry = DB::table('jeweleries')
            ->leftJoin('users as admins', 'jeweleries.assigned_admin_id', '=', 'admins.id')
            ->select([
                'jeweleries.id',
                'jeweleries.sku as stock_no',
                DB::raw("'jewelry' as product_type"),
                DB::raw("NULL as shape"),
                DB::raw("NULL as carat"),
                DB::raw("NULL as color"),
                DB::raw("NULL as clarity"),
                'jeweleries.price',
                'jeweleries.inventory_status',
                'jeweleries.assigned_admin_id',
                'admins.name as assigned_admin_name',
                'jeweleries.hold_by',
                'jeweleries.hold_reason',
                'jeweleries.hold_at',
                'jeweleries.created_at',
                DB::raw('(' . $jewelryMappingCount->toSql() . ') as store_mapping_count'),
                DB::raw('(' . $jewelryFailedCount->toSql() . ') as failed_mapping_count')
            ]);
        $jewelry->mergeBindings($jewelryMappingCount);
        $jewelry->mergeBindings($jewelryFailedCount);

        // Enforce normal admin visibility (own assigned items only)
        if (!$isSuperAdmin) {
            $diamonds->where('diamonds.assigned_admin_id', auth()->id());
            $jewelry->where('jeweleries.assigned_admin_id', auth()->id());
        }

        // Apply filters to Diamonds
        if ($stock) {
            $diamonds->where('diamonds.stock_no', 'like', "%{$stock}%");
        }
        if ($status) {
            $diamonds->where('diamonds.inventory_status', $status);
        }
        if ($hold) {
            if ($hold === 'held' || $hold === 'hold') {
                $diamonds->where('diamonds.inventory_status', 'on_hold');
            } elseif ($hold === 'active') {
                $diamonds->where('diamonds.inventory_status', 'available');
            }
        }
        if ($assignedAdmin) {
            $diamonds->where('diamonds.assigned_admin_id', $assignedAdmin);
        }

        // Apply filters to Jewelry
        if ($stock) {
            $jewelry->where('jeweleries.sku', 'like', "%{$stock}%");
        }
        if ($status) {
            $jewelry->where('jeweleries.inventory_status', $status);
        }
        if ($hold) {
            if ($hold === 'held' || $hold === 'hold') {
                $jewelry->where('jeweleries.inventory_status', 'on_hold');
            } elseif ($hold === 'active') {
                $jewelry->where('jeweleries.inventory_status', 'available');
            }
        }
        if ($assignedAdmin) {
            $jewelry->where('jeweleries.assigned_admin_id', $assignedAdmin);
        }

        // Union the queries or filter by type
        if ($productTypeFilter === 'diamond') {
            $query = $diamonds;
        } elseif ($productTypeFilter === 'jewelry') {
            $query = $jewelry;
        } else {
            $query = $diamonds->union($jewelry);
        }

        // Wrap the union in a subquery to support global sorting and mapping sync filters
        $unionSql = $query->toSql();
        $bindings = $query->getBindings();

        $wrapperQuery = DB::table(DB::raw("({$unionSql}) as unified_inv"))
            ->select('*');
        $wrapperQuery->setBindings($bindings);

        // Apply Shopify Sync filters on the wrapper
        if ($syncStatus) {
            if ($syncStatus === 'unmapped') {
                $wrapperQuery->where('store_mapping_count', 0);
            } elseif ($syncStatus === 'failed') {
                $wrapperQuery->where('failed_mapping_count', '>', 0);
            } elseif ($syncStatus === 'synced') {
                $wrapperQuery->where('store_mapping_count', '>', 0)
                             ->where('failed_mapping_count', 0);
            }
        }

        $items = $wrapperQuery->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Get admin users for assignment
        $admins = User::where('role', 'normal_admin')->get();

        return view('unified_inventory.index', compact('items', 'admins'));
    }

    /**
     * Handle manual single hold.
     */
    public function hold(Request $request, $productType, $productId)
    {
        $product = $this->inventoryManager->resolveProduct($productType, $productId);
        if (!$product) {
            return back()->with('error', 'Inventory item not found.');
        }

        $this->authorize('hold', $product);

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->inventoryManager->hold($product, auth()->id(), $request->input('reason'), $request->ip());
            return back()->with('success', 'Hold successfully applied.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Hold failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle manual single release.
     */
    public function release(Request $request, $productType, $productId)
    {
        $product = $this->inventoryManager->resolveProduct($productType, $productId);
        if (!$product) {
            return back()->with('error', 'Inventory item not found.');
        }

        $this->authorize('release', $product);

        try {
            $this->inventoryManager->release($product, auth()->id(), $request->input('remarks'), $request->ip());
            return back()->with('success', 'Hold successfully released.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Release failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle manual single Shopify sync.
     */
    public function sync(Request $request, $productType, $productId)
    {
        $product = $this->inventoryManager->resolveProduct($productType, $productId);
        if (!$product) {
            return back()->with('error', 'Inventory item not found.');
        }

        $this->authorize('sync', $product);

        try {
            $this->inventoryManager->sync($product, auth()->id(), 'Manual sync triggered', $request->ip());
            return back()->with('success', 'Shopify synchronization completed successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle bulk hold.
     */
    public function bulkHold(Request $request)
    {
        $request->validate([
            'product_type' => 'required|string|in:diamond,jewelry',
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer',
            'reason' => 'required|string|max:255',
        ]);

        $productType = $request->input('product_type');
        $ids = $request->input('product_ids');

        foreach ($ids as $id) {
            $product = $this->inventoryManager->resolveProduct($productType, $id);
            if ($product) {
                $this->authorize('hold', $product);
            }
        }

        // Create background job record
        $jobName = 'Bulk Hold ' . ucfirst($productType);
        $backgroundJob = \App\Models\BackgroundJob::create([
            'user_id' => auth()->id(),
            'job_name' => $jobName,
            'job_type' => 'hold',
            'entity_type' => $productType === 'diamond' ? Diamond::class : Jewelery::class,
            'status' => 'pending',
            'message' => json_encode(['processed' => 0, 'total' => count($ids), 'percent' => 0, 'errors' => []])
        ]);

        \App\Jobs\BulkOperationJob::dispatch(
            'hold',
            $productType,
            $ids,
            auth()->id(),
            ['reason' => $request->input('reason')],
            $request->ip(),
            $backgroundJob->id
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Bulk hold job queued successfully.',
                'job_id' => $backgroundJob->id
            ]);
        }

        return back()->with('success', 'Bulk hold job queued. Processing in background.');
    }

    /**
     * Handle bulk release.
     */
    public function bulkRelease(Request $request)
    {
        $request->validate([
            'product_type' => 'required|string|in:diamond,jewelry',
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer',
            'remarks' => 'nullable|string|max:255',
        ]);

        $productType = $request->input('product_type');
        $ids = $request->input('product_ids');

        foreach ($ids as $id) {
            $product = $this->inventoryManager->resolveProduct($productType, $id);
            if ($product) {
                $this->authorize('release', $product);
            }
        }

        // Create background job record
        $jobName = 'Bulk Release ' . ucfirst($productType);
        $backgroundJob = \App\Models\BackgroundJob::create([
            'user_id' => auth()->id(),
            'job_name' => $jobName,
            'job_type' => 'release',
            'entity_type' => $productType === 'diamond' ? Diamond::class : Jewelery::class,
            'status' => 'pending',
            'message' => json_encode(['processed' => 0, 'total' => count($ids), 'percent' => 0, 'errors' => []])
        ]);

        \App\Jobs\BulkOperationJob::dispatch(
            'release',
            $productType,
            $ids,
            auth()->id(),
            ['remarks' => $request->input('remarks')],
            $request->ip(),
            $backgroundJob->id
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Bulk release job queued successfully.',
                'job_id' => $backgroundJob->id
            ]);
        }

        return back()->with('success', 'Bulk release job queued. Processing in background.');
    }

    /**
     * Handle bulk Shopify sync.
     */
    public function bulkSync(Request $request)
    {
        $request->validate([
            'product_type' => 'required|string|in:diamond,jewelry',
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer',
        ]);

        $productType = $request->input('product_type');
        $ids = $request->input('product_ids');

        foreach ($ids as $id) {
            $product = $this->inventoryManager->resolveProduct($productType, $id);
            if ($product) {
                $this->authorize('sync', $product);
            }
        }

        // Create background job record
        $jobName = 'Bulk Shopify Sync ' . ucfirst($productType);
        $backgroundJob = \App\Models\BackgroundJob::create([
            'user_id' => auth()->id(),
            'job_name' => $jobName,
            'job_type' => 'sync',
            'entity_type' => $productType === 'diamond' ? Diamond::class : Jewelery::class,
            'status' => 'pending',
            'message' => json_encode(['processed' => 0, 'total' => count($ids), 'percent' => 0, 'errors' => []])
        ]);

        \App\Jobs\BulkOperationJob::dispatch(
            'sync',
            $productType,
            $ids,
            auth()->id(),
            [],
            $request->ip(),
            $backgroundJob->id
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Bulk Shopify sync job queued successfully.',
                'job_id' => $backgroundJob->id
            ]);
        }

        return back()->with('success', 'Bulk Shopify sync job queued. Processing in background.');
    }

    /**
     * Handle bulk assign admin.
     */
    public function bulkAssign(Request $request)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            abort(403, 'Only Super Admins can assign inventory.');
        }

        $request->validate([
            'product_type' => 'required|string|in:diamond,jewelry',
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer',
            'assigned_admin_id' => 'required|exists:users,id',
        ]);

        $productType = $request->input('product_type');
        $ids = $request->input('product_ids');

        // Create background job record
        $jobName = 'Bulk Assign Admin ' . ucfirst($productType);
        $backgroundJob = \App\Models\BackgroundJob::create([
            'user_id' => auth()->id(),
            'job_name' => $jobName,
            'job_type' => 'assign',
            'entity_type' => $productType === 'diamond' ? Diamond::class : Jewelery::class,
            'status' => 'pending',
            'message' => json_encode(['processed' => 0, 'total' => count($ids), 'percent' => 0, 'errors' => []])
        ]);

        \App\Jobs\BulkOperationJob::dispatch(
            'assign',
            $productType,
            $ids,
            auth()->id(),
            ['assigned_admin_id' => $request->input('assigned_admin_id')],
            $request->ip(),
            $backgroundJob->id
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Bulk assign admin job queued successfully.',
                'job_id' => $backgroundJob->id
            ]);
        }

        return back()->with('success', 'Bulk assign admin job queued. Processing in background.');
    }
}
