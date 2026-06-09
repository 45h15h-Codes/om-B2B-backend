<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ImportHistory;

class ImportHistoryController extends Controller
{
    /**
     * Display a listing of CSV import histories.
     */
    public function index()
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            abort(403, 'Only Super Admins can access Import History.');
        }

        $imports = ImportHistory::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('system.import_history', compact('imports'));
    }
}
