<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mark all unread notifications for the user as read.
     */
    public function readAll(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Mark a single notification as read and redirect if there is a target.
     */
    public function readSingle(Request $request, $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $data = $notification->data;

        // 1. System Alerts
        if ($notification->type === 'App\Notifications\SystemAlertNotification') {
            return redirect()->route('system.health');
        }

        // 2. Sync Alerts
        if ($notification->type === 'App\Notifications\SyncCompletedNotification' || $notification->type === 'App\Notifications\SyncFailedNotification') {
            return redirect()->route('shopify.sync-center.index');
        }

        // 3. Shopify Order/Paid Alerts
        if (isset($data['shopify_order_id'])) {
            $shopifyOrder = \App\Models\ShopifyOrder::where('shopify_order_id', $data['shopify_order_id'])->first();
            if ($shopifyOrder) {
                return redirect()->route('admin.shopify.orders.show', $shopifyOrder->id);
            }
        }

        // 4. Product detail alerts
        if (isset($data['product_type']) && isset($data['product_id'])) {
            if ($data['product_type'] === 'diamond') {
                return redirect()->route('diamonds.show', $data['product_id']);
            } elseif ($data['product_type'] === 'jewelry') {
                return redirect()->route('jewelery.show', $data['product_id']);
            }
        }

        // 5. Default action_url if exists
        if (isset($data['action_url'])) {
            return redirect($data['action_url']);
        }

        if (isset($data['request_id'])) {
            $isSuperAdmin = session('admin_role', auth()->user()->role) === 'super_admin';
            $route = $isSuperAdmin ? 'all-requests' : 'my-requests';
            return redirect()->route($route);
        }

        return back();
    }

    /**
     * Get latest notifications since a given timestamp, or latest 20 notifications if empty.
     */
    public function latest(Request $request)
    {
        $user = auth()->user();
        $query = $user->notifications();

        if ($request->filled('since')) {
            $query->where('created_at', '>', $request->input('since'));
        }

        $notifications = $query->take(20)->get();
        $unreadCount = $user->unreadNotifications()->count();

        $lastTimestamp = $notifications->max('created_at');
        if (!$lastTimestamp) {
            $lastTimestamp = $request->input('since') ?? now()->toDateTimeString();
        } else {
            $lastTimestamp = $lastTimestamp->toDateTimeString();
        }

        return response()->json([
            'notifications' => $notifications->map(function($n) {
                return [
                    'id' => $n->id,
                    'read_at' => $n->read_at,
                    'created_at_human' => $n->created_at->diffForHumans(),
                    'title' => $n->data['title'] ?? 'Notification',
                    'message' => $n->data['message'] ?? '',
                    'action_url' => $n->data['action_url'] ?? route('notifications.read-single', $n->id),
                    'related_type' => $n->data['related_type'] ?? null,
                    'related_id' => $n->data['related_id'] ?? null,
                ];
            }),
            'unread_count' => $unreadCount,
            'last_timestamp' => $lastTimestamp,
        ]);
    }

    /**
     * Mark single notification read via AJAX.
     */
    public function readSingleAjax(Request $request, $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $unreadCount = auth()->user()->unreadNotifications()->count();

        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'action_url' => $notification->data['action_url'] ?? route('notifications.read-single', $notification->id),
        ]);
    }

    /**
     * Mark all notifications read via AJAX.
     */
    public function readAllAjax(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();

        $recentNotifications = auth()->user()->notifications()->take(20)->get();

        return response()->json([
            'status' => 'success',
            'unread_count' => 0,
            'notifications' => $this->formatNotificationsForJson($recentNotifications)
        ]);
    }

    /**
     * Mark a single notification as read via AJAX.
     */
    public function markRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $unreadCount = auth()->user()->unreadNotifications()->count();
        $recentNotifications = auth()->user()->notifications()->take(20)->get();

        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'notifications' => $this->formatNotificationsForJson($recentNotifications)
        ]);
    }

    /**
     * Mark a single notification as unread via AJAX.
     */
    public function markUnread($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->read_at = null;
        $notification->save();

        $unreadCount = auth()->user()->unreadNotifications()->count();
        $recentNotifications = auth()->user()->notifications()->take(20)->get();

        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'notifications' => $this->formatNotificationsForJson($recentNotifications)
        ]);
    }

    /**
     * Delete a single notification via AJAX.
     */
    public function delete($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->delete();

        $unreadCount = auth()->user()->unreadNotifications()->count();
        $recentNotifications = auth()->user()->notifications()->take(20)->get();

        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'notifications' => $this->formatNotificationsForJson($recentNotifications)
        ]);
    }

    /**
     * Delete all notifications for the logged in user via AJAX.
     */
    public function deleteAll()
    {
        auth()->user()->notifications()->delete();

        return response()->json([
            'status' => 'success',
            'unread_count' => 0,
            'notifications' => []
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $query = auth()->user()->notifications();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($filter === 'orders' || $filter === 'order') {
            $query->whereIn('type', [
                'App\Notifications\NewShopifyOrderNotification',
                'App\Notifications\NewRequestNotification',
                'App\Notifications\RequestStatusChangedNotification',
            ]);
        } elseif ($filter === 'diamond_sales') {
            $query->where('type', 'App\Notifications\DiamondSoldNotification');
        } elseif ($filter === 'jewelry_sales') {
            $query->where('type', 'App\Notifications\JewelrySoldNotification');
        } elseif ($filter === 'sale') {
            $query->whereIn('type', [
                'App\Notifications\DiamondSoldNotification',
                'App\Notifications\JewelrySoldNotification',
            ]);
        } elseif ($filter === 'sync') {
            $query->whereIn('type', [
                'App\Notifications\SyncCompletedNotification',
                'App\Notifications\SyncFailedNotification',
            ]);
        } elseif ($filter === 'system' || $filter === 'alert') {
            $query->whereIn('type', [
                'App\Notifications\SystemAlertNotification',
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('data', 'like', "%{$search}%");
        }

        $notifications = $query->latest()->paginate(20);
        $unreadCount = auth()->user()->unreadNotifications()->count();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'html' => view('notifications._list_items', compact('notifications'))->render(),
                'pagination' => view('notifications._pagination', compact('notifications'))->render(),
                'unread_count' => $unreadCount,
            ]);
        }

        return view('notifications.index', compact('notifications', 'filter', 'unreadCount'));
    }

    /**
     * Delete multiple notifications.
     */
    public function deleteMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            auth()->user()->notifications()->whereIn('id', $ids)->delete();
        }

        $unreadCount = auth()->user()->unreadNotifications()->count();
        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark multiple notifications as read.
     */
    public function markReadMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            auth()->user()->notifications()->whereIn('id', $ids)->update(['read_at' => now()]);
        }

        $unreadCount = auth()->user()->unreadNotifications()->count();
        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Helper to format notifications for JSON responses.
     */
    protected function formatNotificationsForJson($notifications)
    {
        return $notifications->map(function($n) {
            return [
                'id' => $n->id,
                'read_at' => $n->read_at,
                'created_at_human' => $n->created_at->diffForHumans(),
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'action_url' => $n->data['action_url'] ?? route('notifications.read-single', $n->id),
                'related_type' => $n->data['related_type'] ?? null,
                'related_id' => $n->data['related_id'] ?? null,
            ];
        })->toArray();
    }

    /**
     * Delete all read notifications of the logged in user.
     */
    public function deleteRead(Request $request)
    {
        auth()->user()->notifications()->whereNotNull('read_at')->delete();
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'unread_count' => auth()->user()->unreadNotifications()->count(),
                'notifications' => $this->formatNotificationsForJson(auth()->user()->notifications()->take(20)->get())
            ]);
        }
        return back()->with('success', 'All read notifications deleted.');
    }
}
