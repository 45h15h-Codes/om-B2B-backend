<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PartnershipRequest;
use App\Models\User;
use App\Notifications\NewPartnershipRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;

class PartnershipController extends Controller
{
    /**
     * Store a newly created partnership request.
     *
     * POST /api/v1/partnership-requests
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone_number' => 'required|string|max:20',
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
            'purpose' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if there is already a Pending request with the same email
        $pendingExists = PartnershipRequest::where('email', $request->email)
            ->where('status', 'Pending')
            ->exists();

        if ($pendingExists) {
            return response()->json([
                'success' => false,
                'message' => 'A pending partnership request already exists for this email address.'
            ], 422);
        }

        $partnershipRequest = PartnershipRequest::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'business_name' => $request->business_name,
            'business_type' => $request->business_type,
            'purpose' => $request->purpose,
            'status' => 'Pending',
        ]);

        // Notify all Super Admins
        $superAdmins = User::where('role', 'super_admin')->get();
        Notification::send($superAdmins, new NewPartnershipRequestNotification($partnershipRequest));

        return response()->json([
            'success' => true,
            'message' => 'Partnership request submitted successfully.'
        ], 200);
    }
}
