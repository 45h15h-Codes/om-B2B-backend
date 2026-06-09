<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * Display a listing of normal admins.
     */
    public function index()
    {
        // Enforce super admin only
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        $admins = User::where('role', 'normal_admin')->latest()->get();
        return view('admins.index', compact('admins'));
    }

    /**
     * Show the form for creating a new normal admin.
     */
    public function create()
    {
        // Enforce super admin only
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        return view('admins.create');
    }

    /**
     * Store a newly created normal admin in storage.
     */
    public function store(Request $request)
    {
        // Enforce super admin only
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'normal_admin',
        ]);

        return redirect()->route('admins.index')->with('success', 'Normal admin created successfully!');
    }

    /**
     * Impersonate a normal admin user.
     */
    public function impersonate(User $admin)
    {
        // Enforce super admin only (only the real logged in user is checked)
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        if ($admin->role !== 'normal_admin') {
            return back()->with('error', 'You can only impersonate normal admins.');
        }

        // Store original super admin user ID in session
        session(['super_admin_user_id' => Auth::id()]);
        
        // Log in as the normal admin
        Auth::login($admin);

        // Update the active session role to normal admin
        session(['admin_role' => 'normal_admin']);

        return redirect()->route('home')->with('success', "Logged in as Normal Admin: {$admin->name}");
    }

    /**
     * Stop impersonating and return to super admin panel.
     */
    public function stopImpersonate()
    {
        if (!session()->has('super_admin_user_id')) {
            abort(403, 'No impersonation session active.');
        }

        $superAdminId = session('super_admin_user_id');
        $superAdmin = User::find($superAdminId);

        if (!$superAdmin || $superAdmin->role !== 'super_admin') {
            abort(403, 'Invalid super admin account.');
        }

        // Log back in as the super admin
        Auth::login($superAdmin);

        // Clear the impersonation session variables
        session()->forget('super_admin_user_id');

        // Restore active session role to super admin
        session(['admin_role' => 'super_admin']);

        return redirect()->route('home')->with('success', 'Returned to Super Admin Panel.');
    }

    /**
     * Show the form for editing the specified normal admin.
     */
    public function edit(User $admin)
    {
        // Enforce super admin only
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        if ($admin->role !== 'normal_admin') {
            return back()->with('error', 'You can only edit normal admin users.');
        }

        return view('admins.edit', compact('admin'));
    }

    /**
     * Update the specified normal admin in storage.
     */
    public function update(Request $request, User $admin)
    {
        // Enforce super admin only
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        if ($admin->role !== 'normal_admin') {
            return back()->with('error', 'You can only edit normal admin users.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $admin->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $admin->name = $request->name;
        $admin->email = $request->email;
        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }
        $admin->save();

        return redirect()->route('admins.index')->with('success', 'Normal admin updated successfully!');
    }

    /**
     * Remove the specified admin user.
     */
    public function destroy(User $admin)
    {
        // Enforce super admin only
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        if ($admin->role !== 'normal_admin') {
            return back()->with('error', 'You can only delete normal admin users.');
        }

        $admin->delete();

        return redirect()->route('admins.index')->with('success', 'Normal admin deleted successfully.');
    }
}
