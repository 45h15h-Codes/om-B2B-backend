<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Http\Resources\V1\CustomerResource;
use App\Http\Resources\V1\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerProfileController extends Controller
{
    /**
     * PUT /api/v1/profile/update
     * Update customer profile details.
     */
    public function updateProfile(Request $request)
    {
        // Enforce role isolation
        if (!$request->user() instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. Customers only.'
            ], 403);
        }

        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer->update([
            'name' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'customer' => new CustomerResource($customer)
        ], 200);
    }

    /**
     * POST /api/v1/change-password
     * Change password for the customer.
     */
    public function changePassword(Request $request)
    {
        // Enforce role isolation
        if (!$request->user() instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. Customers only.'
            ], 403);
        }

        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify current password
        if (!Hash::check($request->current_password, $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided current password does not match our records.'
            ], 400);
        }

        // Update with hashed new password
        $customer->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.'
        ], 200);
    }

    /**
     * GET /api/v1/orders
     * Fetch order history of the authenticated customer.
     */
    public function orders(Request $request)
    {
        // Enforce role isolation
        if (!$request->user() instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. Customers only.'
            ], 403);
        }

        // Only load orders matching the customer_id of the authenticated customer instance
        $orders = Order::where('customer_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'orders' => OrderResource::collection($orders)
        ], 200);
    }
}
