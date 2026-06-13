<?php

namespace App\Http\Controllers;

use App\Models\PartnershipRequest;
use App\Models\User;
use App\Http\Resources\V1\PartnershipRequestResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PartnershipRequestController extends Controller
{
    /**
     * Set up role-based authorization for all actions.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::check()) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
                }
                return redirect()->route('login');
            }

            $activeRole = session('admin_role', Auth::user()->role);
            if ($activeRole !== 'super_admin') {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized action. Super Admin access only.'], 403);
                }
                abort(403, 'Unauthorized action. Super Admin access only.');
            }

            return $next($request);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Web Controller Actions (Blade UI)
    |--------------------------------------------------------------------------
    */

    /**
     * Display a listing of partnership requests.
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $search = $request->input('search');

        $query = PartnershipRequest::query();

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('partnership_requests.index', compact('requests'));
    }

    /**
     * Display the specified partnership request details.
     */
    public function show($id)
    {
        $partnershipRequest = PartnershipRequest::with(['approvedBy', 'rejectedBy', 'convertedUser'])->findOrFail($id);
        $emailConflict = User::where('email', $partnershipRequest->email)->exists();

        return view('partnership_requests.show', compact('partnershipRequest', 'emailConflict'));
    }

    /**
     * Approve the partnership request (Web form post).
     */
    public function approve(Request $request, $id)
    {
        try {
            $notes = $request->input('notes');
            $result = $this->performApproval($id, $notes, $request->ip());

            if (!$result['mail_success']) {
                return redirect()->route('partnership-requests.show', $id)
                    ->with('warning', 'Account created successfully but onboarding email could not be delivered.');
            }

            return redirect()->route('partnership-requests.show', $id)
                ->with('success', 'Partner account created successfully. Password setup email sent.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Approval failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Reject the partnership request (Web form post).
     */
    public function reject(Request $request, $id)
    {
        try {
            $notes = $request->input('notes');
            $this->performRejection($id, $notes);

            return redirect()->route('partnership-requests.show', $id)
                ->with('success', 'Partnership request rejected.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Rejection failed: ' . $e->getMessage())->withInput();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | API Controller Actions (JSON APIs)
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/admin/partnership-requests
     */
    public function apiIndex(Request $request)
    {
        $status = $request->input('status');
        $search = $request->input('search');

        $query = PartnershipRequest::query();

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        return PartnershipRequestResource::collection($requests);
    }

    /**
     * GET /api/admin/partnership-requests/{id}
     */
    public function apiShow($id)
    {
        $partnershipRequest = PartnershipRequest::with(['approvedBy', 'rejectedBy', 'convertedUser'])->findOrFail($id);
        $emailConflict = User::where('email', $partnershipRequest->email)->exists();

        return (new PartnershipRequestResource($partnershipRequest))->additional([
            'email_conflict' => $emailConflict,
            'warning_message' => $emailConflict ? 'A user account with this email address already exists.' : null
        ]);
    }

    /**
     * POST /api/admin/partnership-requests/{id}/approve
     */
    public function apiApprove(Request $request, $id)
    {
        try {
            $notes = $request->input('notes');
            $result = $this->performApproval($id, $notes, $request->ip());

            $message = $result['mail_success']
                ? 'Partner account created successfully. Password setup email sent.'
                : 'Account created successfully but onboarding email could not be delivered.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'mail_success' => $result['mail_success'],
                'data' => new PartnershipRequestResource($result['request']),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Approval failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * POST /api/admin/partnership-requests/{id}/reject
     */
    public function apiReject(Request $request, $id)
    {
        try {
            $notes = $request->input('notes');
            $partnershipRequest = $this->performRejection($id, $notes);

            return response()->json([
                'success' => true,
                'message' => 'Partnership request rejected successfully.',
                'data' => new PartnershipRequestResource($partnershipRequest),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rejection failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Shared Core Business Logic
    |--------------------------------------------------------------------------
    */

    /**
     * Execute approval workflow inside a database transaction with write lock.
     */
    private function performApproval($id, $notes, $requestIp)
    {
        $result = DB::transaction(function () use ($id, $notes, $requestIp) {
            $partnershipRequest = PartnershipRequest::lockForUpdate()->findOrFail($id);

            if ($partnershipRequest->status !== 'Pending') {
                throw new \Exception('This request is not pending approval.');
            }

            if ($partnershipRequest->converted_to_user_id) {
                throw new \Exception('A user account has already been created for this request.');
            }

            // Check if user already exists with the same email
            if (User::where('email', $partnershipRequest->email)->exists()) {
                throw new \Exception('A user with this email address already exists.');
            }

            // Create normal admin account with a secure locked password (never exposed)
            $user = User::create([
                'name' => $partnershipRequest->full_name,
                'email' => $partnershipRequest->email,
                'password' => Hash::make(Str::random(32)),
                'role' => 'normal_admin',
            ]);

            // Seed normal admin permissions
            $permissions = [
                'view_orders',
                'view_shopify_orders',
                'create_orders',
                'approve_orders',
                'view_inventory',
                'view_inventory_history',
                'hold_inventory',
                'release_inventory',
                'view_revenue',
                'export_revenue',
                'view_reports',
                'export_reports',
                'view_notifications',
            ];

            foreach ($permissions as $perm) {
                \App\Models\AdminPermission::create([
                    'user_id' => $user->id,
                    'permission' => $perm,
                ]);
            }
            $user->refreshPermissionsCache();

            // Update request details and audit trail
            $partnershipRequest->update([
                'status' => 'Approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'converted_to_user_id' => $user->id,
                'notes' => $notes ?: $partnershipRequest->notes,
            ]);

            // Record system audit log
            \App\Services\AuditService::log('partnership_request_approved', PartnershipRequest::class, $partnershipRequest->id);

            return [
                'user' => $user,
                'request' => $partnershipRequest
            ];
        });

        // Generate Laravel password reset token and onboarding setup URL
        $token = Password::broker()->createToken($result['user']);
        $setupPasswordUrl = config('services.partner_portal_url')
            . '/set-password?token=' . $token
            . '&email=' . urlencode($result['user']->email);

        // Send email credentials outside the transaction so failures do not cause rollback
        $mailSuccess = true;
        try {
            Mail::to($result['request']->email)->send(new \App\Mail\PartnershipApprovedMail(
                $result['request']->full_name,
                $result['request']->email,
                $setupPasswordUrl
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Partnership approval email sending failed: ' . $e->getMessage(), [
                'email' => $result['request']->email,
                'exception' => $e
            ]);
            $mailSuccess = false;
        }

        return [
            'user' => $result['user'],
            'request' => $result['request'],
            'mail_success' => $mailSuccess
        ];
    }

    /**
     * Execute rejection workflow inside a database transaction with write lock.
     */
    private function performRejection($id, $notes)
    {
        return DB::transaction(function () use ($id, $notes) {
            $partnershipRequest = PartnershipRequest::lockForUpdate()->findOrFail($id);

            if ($partnershipRequest->status !== 'Pending') {
                throw new \Exception('This request is not pending rejection.');
            }

            // Update request details and audit trail
            $partnershipRequest->update([
                'status' => 'Rejected',
                'rejected_at' => now(),
                'rejected_by' => Auth::id(),
                'notes' => $notes ?: $partnershipRequest->notes,
            ]);

            // Record system audit log
            \App\Services\AuditService::log('partnership_request_rejected', PartnershipRequest::class, $partnershipRequest->id);

            // Send polite rejection email
            Mail::to($partnershipRequest->email)->send(new \App\Mail\PartnershipRejectedMail(
                $partnershipRequest->full_name,
                $notes
            ));

            return $partnershipRequest;
        });
    }
}
