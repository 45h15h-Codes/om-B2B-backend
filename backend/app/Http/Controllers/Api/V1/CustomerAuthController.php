<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Http\Resources\V1\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerAuthController extends Controller
{
    /**
     * POST /api/v1/register
     * Register a new customer and return a Sanctum token.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Customer::create([
            'name' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'status' => 'active',
        ]);

        $token = $customer->createToken('customer_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully.',
            'token' => $token,
            'customer' => new CustomerResource($customer)
        ], 201);
    }

    /**
     * POST /api/v1/login
     * Authenticate customer credentials and return a Sanctum token.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Customer::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password credentials.'
            ], 401);
        }

        if ($customer->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is currently ' . $customer->status . '.'
            ], 403);
        }

        $token = $customer->createToken('customer_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'customer' => new CustomerResource($customer)
        ], 200);
    }

    /**
     * POST /api/v1/logout
     * Revoke the customer's current token.
     */
    public function logout(Request $request)
    {
        // Enforce role separation (deny admin users using admin tokens)
        if (!$request->user() instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. Customers only.'
            ], 403);
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.'
        ], 200);
    }

    /**
     * GET /api/v1/profile
     * Get the authenticated customer's profile.
     */
    public function profile(Request $request)
    {
        // Enforce role separation (deny admin users using admin tokens)
        if (!$request->user() instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. Customers only.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'customer' => new CustomerResource($request->user())
        ], 200);
    }
}
