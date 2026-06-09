<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of system activity logs.
     */
    public function index(Request $request)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            abort(403, 'Only Super Admins can access System Activity Logs.');
        }

        $userId = $request->input('user_id');
        $action = $request->input('action');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        $query = ActivityLog::with('user');

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($action) {
            $query->where('action', $action);
        }
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('payload', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        
        $users = User::all();
        $actions = ActivityLog::distinct()->pluck('action');

        return view('system.activity_logs', compact('logs', 'users', 'actions'));
    }
}
