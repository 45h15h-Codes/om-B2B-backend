<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiamondController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JeweleryController;
use App\Http\Controllers\UnifiedInventoryController;
use App\Http\Controllers\InventoryRequestController;
use App\Http\Controllers\InventoryHistoryController;
use App\Http\Controllers\NotificationController;
use App\Services\ShopifyService;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth Routes (Publicly Accessible)
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');
Route::get('set-password', [AuthController::class, 'showSetPasswordForm'])->name('password.set');
Route::post('set-password', [AuthController::class, 'setPassword'])->name('password.set.post');

// Protected Admin Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('home');
    });

    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::resource('categories', CategoryController::class);

    // Admin Users Management & Impersonation
    Route::post('admins/{admin}/impersonate', [\App\Http\Controllers\AdminUserController::class, 'impersonate'])->name('admins.impersonate');
    Route::post('admins/stop-impersonate', [\App\Http\Controllers\AdminUserController::class, 'stopImpersonate'])->name('admins.stop-impersonate');
    Route::patch('admins/{admin}/permissions', [\App\Http\Controllers\AdminUserController::class, 'updatePermissions'])->name('admins.permissions.update');
    Route::resource('admins', \App\Http\Controllers\AdminUserController::class);

    Route::post('toggle-role', function () {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }
        $currentRole = session('admin_role', 'normal_admin');
        $newRole = $currentRole === 'super_admin' ? 'normal_admin' : 'super_admin';
        session(['admin_role' => $newRole]);
        return back()->with('success', 'Role switched to ' . ($newRole === 'super_admin' ? 'Super Admin' : 'Normal Admin'));
    })->name('toggle-role');

    Route::post('diamonds/{diamond}/approve', [DiamondController::class, 'approve'])->name('diamonds.approve');
    Route::post('diamonds/{diamond}/reject', [DiamondController::class, 'reject'])->name('diamonds.reject');
    Route::post('diamonds/import', [DiamondController::class, 'import'])->name('diamonds.import');
    Route::post('diamonds/bulk-delete', [DiamondController::class, 'bulkDestroy'])->name('diamonds.bulk-delete');
    Route::get('diamonds/export', [DiamondController::class, 'export'])->name('diamonds.export');
    Route::post('diamonds/rebuild-index', [DiamondController::class, 'rebuildIndex'])->name('diamonds.rebuild-index');

    Route::get('/chat', function () {
        return view('chat');
    })->name('chat');

    Route::resource('diamonds', DiamondController::class);
    Route::post('jewelery/{jewelery}/approve', [JeweleryController::class, 'approve'])->name('jewelery.approve');
    Route::post('jewelery/{jewelery}/reject', [JeweleryController::class, 'reject'])->name('jewelery.reject');
    Route::post('jewelery/import', [JeweleryController::class, 'import'])->name('jewelery.import');
    Route::post('jewelery/bulk-delete', [JeweleryController::class, 'bulkDestroy'])->name('jewelery.bulk-delete');
    Route::resource('jewelery', JeweleryController::class);

    // Shopify Integration Routes
    Route::get('/shopify', [\App\Http\Controllers\ShopifyController::class, 'index'])->name('shopify.dashboard');
    Route::get('/shopify/sync-center', [\App\Http\Controllers\ShopifySyncCenterController::class, 'index'])->name('shopify.sync-center.index');
    Route::post('/shopify/connect', [\App\Http\Controllers\ShopifyController::class, 'connect'])->name('shopify.connect');
    Route::post('/shopify/sync-all', [\App\Http\Controllers\ShopifyController::class, 'syncAll'])->name('shopify.sync-all');
    Route::post('/shopify/retry-failed', [\App\Http\Controllers\ShopifyController::class, 'retryFailed'])->name('shopify.retry-failed');
    Route::post('/shopify/retry/{id}', [\App\Http\Controllers\ShopifyController::class, 'retrySingle'])->name('shopify.retry');
    Route::delete('/shopify/sync/{id}', [\App\Http\Controllers\ShopifyController::class, 'deleteSync'])->name('shopify.delete-sync');
    Route::post('/shopify/publish-diamond/{diamond}', [\App\Http\Controllers\ShopifyController::class, 'publishDiamond'])->name('shopify.publish-diamond');
    Route::post('/shopify/publish-jewelry/{jewelry}', [\App\Http\Controllers\ShopifyController::class, 'publishJewelry'])->name('shopify.publish-jewelry');

    // Multi-store Management Routes
    Route::get('/shopify/stores', [\App\Http\Controllers\ShopifyController::class, 'stores'])->name('shopify.stores');
    Route::post('/shopify/stores/connect', [\App\Http\Controllers\ShopifyController::class, 'connectStore'])->name('shopify.connect-store');
    Route::get('/shopify/callback', [\App\Http\Controllers\ShopifyController::class, 'oauthCallback'])->name('shopify.callback');
    Route::post('/shopify/stores/{store}/set-active', [\App\Http\Controllers\ShopifyController::class, 'setActiveStore'])->name('shopify.set-active-store');
    Route::delete('/shopify/stores/{store}', [\App\Http\Controllers\ShopifyController::class, 'deleteStore'])->name('shopify.delete-store');

    // Orders Routes
    Route::middleware('permission:view_shopify_orders')->group(function () {
        Route::get('/admin/shopify/orders', [\App\Http\Controllers\ShopifyOrderController::class, 'index'])->name('admin.shopify.orders');
        Route::get('/admin/shopify/orders/export', [\App\Http\Controllers\ShopifyOrderController::class, 'export'])->name('admin.shopify.orders.export');
        Route::get('/admin/shopify/orders/{id}', [\App\Http\Controllers\ShopifyOrderController::class, 'show'])->name('admin.shopify.orders.show');
        Route::get('/admin/shopify/orders/{id}/invoice', [\App\Http\Controllers\ShopifyOrderController::class, 'viewInvoice'])->name('shopify.orders.invoice');
        Route::post('/admin/shopify/orders/sync-recovery', [\App\Http\Controllers\ShopifyOrderController::class, 'runRecovery'])->name('admin.shopify.orders.sync-recovery');
    });
    
    Route::middleware('permission:approve_orders')->group(function () {
        Route::post('orders/{order}/approve', [\App\Http\Controllers\OrderController::class, 'approve'])->name('orders.approve');
        Route::post('orders/{order}/send-invoice', [\App\Http\Controllers\OrderController::class, 'sendInvoice'])->name('orders.send-invoice');
        Route::post('orders/{order}/retry', [\App\Http\Controllers\OrderController::class, 'retry'])->name('orders.retry');
        Route::post('orders/{order}/complete', [\App\Http\Controllers\OrderController::class, 'complete'])->name('orders.complete');
        Route::post('orders/{id}/restore', [\App\Http\Controllers\OrderController::class, 'restore'])->name('orders.restore');
        Route::delete('orders/{id}/force-delete', [\App\Http\Controllers\OrderController::class, 'forceDelete'])->name('orders.force-delete');
    });

    Route::middleware('permission:view_orders')->group(function () {
        Route::get('/admin/orders/{order}/invoice', [\App\Http\Controllers\OrderController::class, 'viewInvoice'])->name('orders.invoice');
    });

    Route::resource('orders', \App\Http\Controllers\OrderController::class)->only(['index', 'show'])->middleware('permission:view_orders')->names([
        'index' => 'orders.index',
        'show' => 'orders.show',
    ]);
    Route::resource('orders', \App\Http\Controllers\OrderController::class)->except(['index', 'show'])->middleware('permission:create_orders')->names([
        'create' => 'orders.create',
        'store' => 'orders.store',
        'edit' => 'orders.edit',
        'update' => 'orders.update',
        'destroy' => 'orders.destroy',
    ]);

    // Unified Inventory Routes
    Route::middleware('permission:view_inventory')->group(function () {
        Route::get('/inventory', [UnifiedInventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/sync/{productType}/{productId}', [UnifiedInventoryController::class, 'sync'])->name('inventory.sync');
        Route::post('/inventory/bulk-sync', [UnifiedInventoryController::class, 'bulkSync'])->name('inventory.bulk-sync');
        Route::post('/inventory/bulk-assign', [UnifiedInventoryController::class, 'bulkAssign'])->name('inventory.bulk-assign');
    });

    Route::middleware('permission:hold_inventory')->group(function () {
        Route::post('/inventory/hold/{productType}/{productId}', [UnifiedInventoryController::class, 'hold'])->name('inventory.hold');
        Route::post('/inventory/bulk-hold', [UnifiedInventoryController::class, 'bulkHold'])->name('inventory.bulk-hold');
    });

    Route::middleware('permission:release_inventory')->group(function () {
        Route::post('/inventory/release/{productType}/{productId}', [UnifiedInventoryController::class, 'release'])->name('inventory.release');
        Route::post('/inventory/bulk-release', [UnifiedInventoryController::class, 'bulkRelease'])->name('inventory.bulk-release');
    });

    Route::get('/bulk-operations/status/{id}', [\App\Http\Controllers\BulkOperationsController::class, 'status'])->name('bulk-operations.status');

    // Workflow Requests Routes
    Route::get('/my-requests', [InventoryRequestController::class, 'myRequests'])->name('my-requests');
    Route::get('/all-requests', [InventoryRequestController::class, 'allRequests'])->name('all-requests');
    Route::post('/inventory/request', [InventoryRequestController::class, 'store'])->name('inventory.request.store');
    Route::post('/inventory/request/{id}/approve', [InventoryRequestController::class, 'approve'])->name('inventory.request.approve');
    Route::post('/inventory/request/{id}/reject', [InventoryRequestController::class, 'reject'])->name('inventory.request.reject');

    // Inventory History Routes
    Route::middleware('permission:view_inventory_history')->group(function () {
        Route::get('/inventory-history', [InventoryHistoryController::class, 'index'])->name('inventory-history.index');
        Route::get('/inventory/timeline', [\App\Http\Controllers\InventoryTimelineController::class, 'index'])->name('inventory.timeline');
    });

    // Notifications Routes
    Route::middleware('permission:view_notifications')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/notifications/read/{id}', [NotificationController::class, 'readSingle'])->name('notifications.read-single');
        Route::get('/notifications/api/latest', [NotificationController::class, 'latest'])->name('notifications.api.latest');
        Route::post('/notifications/api/read-all', [NotificationController::class, 'readAllAjax'])->name('notifications.api.read-all');
        Route::post('/notifications/api/read/{id}', [NotificationController::class, 'readSingleAjax'])->name('notifications.api.read-single-ajax');

        Route::post('/notifications/read/{id}', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
        Route::post('/notifications/unread/{id}', [NotificationController::class, 'markUnread'])->name('notifications.mark-unread');
        Route::post('/notifications/delete/{id}', [NotificationController::class, 'delete'])->name('notifications.delete');
        Route::post('/notifications/delete-all', [NotificationController::class, 'deleteAll'])->name('notifications.delete-all');
        Route::post('/notifications/delete-read', [NotificationController::class, 'deleteRead'])->name('notifications.delete-read');
        Route::post('/notifications/delete-multiple', [NotificationController::class, 'deleteMultiple'])->name('notifications.delete-multiple');
        Route::post('/notifications/mark-read-multiple', [NotificationController::class, 'markReadMultiple'])->name('notifications.mark-read-multiple');
    });

    // Revenue Analytics Dashboard Route
    Route::get('/analytics/revenue', [\App\Http\Controllers\RevenueAnalyticsController::class, 'index'])->name('analytics.revenue')
        ->middleware('permission:view_revenue');

    // Reporting Module Routes
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index')
        ->middleware('permission:view_reports');
    Route::get('/reports/export', [\App\Http\Controllers\ReportController::class, 'exportCsv'])->name('reports.export')
        ->middleware('permission:export_reports');

    // System Health Dashboard Route
    Route::get('/system/health', [\App\Http\Controllers\SystemHealthController::class, 'index'])->name('system.health')
        ->middleware('permission:view_system_health');
    Route::get('/system/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('system.activity-logs.index')
        ->middleware('permission:view_audit_logs');

    // Import History Route
    Route::get('/system/imports-history', [\App\Http\Controllers\ImportHistoryController::class, 'index'])->name('system.imports-history.index');

    // Backup Management Routes
    Route::get('/system/backups', [\App\Http\Controllers\BackupController::class, 'index'])->name('system.backups.index');
    Route::post('/system/backups/create', [\App\Http\Controllers\BackupController::class, 'create'])->name('system.backups.create');
    Route::get('/system/backups/download/{filename}', [\App\Http\Controllers\BackupController::class, 'download'])->name('system.backups.download');
    Route::post('/system/backups/restore/{filename}', [\App\Http\Controllers\BackupController::class, 'restore'])->name('system.backups.restore');
    Route::delete('/system/backups/delete/{filename}', [\App\Http\Controllers\BackupController::class, 'destroy'])->name('system.backups.delete');

    // Failed Job Panel Routes
    Route::get('/system/failed-jobs', [\App\Http\Controllers\FailedJobController::class, 'index'])->name('system.failed-jobs.index');
    Route::post('/system/failed-jobs/retry/{id}', [\App\Http\Controllers\FailedJobController::class, 'retry'])->name('system.failed-jobs.retry');
    Route::delete('/system/failed-jobs/delete/{id}', [\App\Http\Controllers\FailedJobController::class, 'destroy'])->name('system.failed-jobs.delete');
    Route::post('/system/failed-jobs/retry-multiple', [\App\Http\Controllers\FailedJobController::class, 'retryMultiple'])->name('system.failed-jobs.retry-multiple');
    Route::delete('/system/failed-jobs/delete-multiple', [\App\Http\Controllers\FailedJobController::class, 'destroyMultiple'])->name('system.failed-jobs.delete-multiple');
    Route::post('/system/failed-jobs/retry-all', [\App\Http\Controllers\FailedJobController::class, 'retryAll'])->name('system.failed-jobs.retry-all');
    // B2B Partnership Requests Routes
    Route::resource('partnership-requests', \App\Http\Controllers\PartnershipRequestController::class)->only(['index', 'show']);
    Route::post('partnership-requests/{id}/approve', [\App\Http\Controllers\PartnershipRequestController::class, 'approve'])->name('partnership-requests.approve');
    Route::post('partnership-requests/{id}/reject', [\App\Http\Controllers\PartnershipRequestController::class, 'reject'])->name('partnership-requests.reject');
});


