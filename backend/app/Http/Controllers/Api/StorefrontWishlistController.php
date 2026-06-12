<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerWishlist;
use App\Http\Resources\StorefrontWishlistResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class StorefrontWishlistController extends Controller
{
    /**
     * StorefrontWishlistController constructor.
     * Enforces customer role separation middleware.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!$request->user() || !$request->user() instanceof Customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action. Customers only.'
                ], 403);
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the customer's wishlist items.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $wishlist = $request->user()->wishlists;

        return response()->json([
            'success' => true,
            'data' => StorefrontWishlistResource::collection($wishlist),
        ]);
    }

    /**
     * Store a newly created wishlist item.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_type' => 'required|string|in:diamond,jewellery',
            'product_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->input('product_type');
        $id = $request->input('product_id');

        // Verify that product exists in the DB
        $exists = false;
        if ($type === 'diamond') {
            $exists = \App\Models\Diamond::where('id', $id)->exists();
        } elseif ($type === 'jewellery') {
            $exists = \App\Models\Jewelery::where('id', $id)->exists();
        }

        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => 'Product does not exist.'
            ], 422);
        }

        // Prevent duplicates
        $customerId = $request->user()->id;
        $duplicate = CustomerWishlist::where('customer_id', $customerId)
            ->where('product_type', $type)
            ->where('product_id', $id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Product is already in your wishlist.'
            ], 422);
        }

        CustomerWishlist::create([
            'customer_id' => $customerId,
            'product_type' => $type,
            'product_id' => $id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Added to wishlist'
        ]);
    }

    /**
     * Remove the specified wishlist item.
     *
     * @param  Request  $request
     * @param  CustomerWishlist  $wishlist
     * @return JsonResponse
     */
    public function destroy(Request $request, CustomerWishlist $wishlist): JsonResponse
    {
        // Deletion allowed only by owner
        if ((int) $wishlist->customer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed from wishlist'
        ]);
    }

    /**
     * Return the count of wishlist items.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function count(Request $request): JsonResponse
    {
        $count = $request->user()->wishlists()->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
