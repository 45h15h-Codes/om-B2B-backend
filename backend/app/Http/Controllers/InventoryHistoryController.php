<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\InventoryHistory;
use Illuminate\Http\Request;

class InventoryHistoryController extends Controller
{
    /**
     * Display a listing of inventory histories with filters.
     */
    public function index(Request $request)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            abort(403, 'Only Super Admins can access inventory history logs.');
        }

        $productType = $request->input('product_type');
        $action = $request->input('action');
        $userFilter = $request->input('user_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = InventoryHistory::with(['user', 'product']);

        if ($productType) {
            $query->where('product_type', $productType);
        }
        if ($action) {
            $query->where('action', $action);
        }
        if ($userFilter) {
            $query->where('user_id', $userFilter);
        }
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $histories = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $users = User::all();

        return view('inventory_history.index', compact('histories', 'users'));
    }
}
