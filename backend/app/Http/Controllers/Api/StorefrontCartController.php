<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCartItem;
use App\Http\Resources\StorefrontCartItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class StorefrontCartController extends Controller
{
    /**
     * StorefrontCartController constructor.
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
     * Display a listing of the customer's cart items with a summary.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $cartItems = $request->user()->cartItems()->get();
        $subtotal = 0.0;
        $totalItems = 0;

        foreach ($cartItems as $item) {
            $price = 0.0;
            if ($item->product_type === 'diamond') {
                $diamond = \App\Models\Diamond::find($item->product_id);
                if ($diamond) {
                    $price = floatval($diamond->asking_price ?? $diamond->cash_price ?? 0.0);
                }
                $subtotal += $price * 1;
                $totalItems += 1;
            } elseif ($item->product_type === 'jewellery') {
                $jewellery = \App\Models\Jewelery::find($item->product_id);
                if ($jewellery) {
                    $price = floatval($jewellery->price ?? 0.0);
                }
                $subtotal += $price * intval($item->quantity);
                $totalItems += intval($item->quantity);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => StorefrontCartItemResource::collection($cartItems),
                'summary' => [
                    'subtotal' => $subtotal,
                    'total_items' => $totalItems,
                ],
            ],
        ]);
    }

    /**
     * Store a newly created or updated cart item.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_type' => 'required|string|in:diamond,jewellery',
            'product_id' => 'required|integer',
            'quantity' => 'nullable|integer|min:1',
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
        $quantity = $request->input('quantity', 1) ?? 1;

        // Verify product existence and visibility
        $product = null;
        if ($type === 'diamond') {
            $product = \App\Models\Diamond::where('id', $id)
                ->where('status', \App\Models\Diamond::STATUS_APPROVED)
                ->where('inventory_status', 'available')
                ->first();
        } elseif ($type === 'jewellery') {
            $product = \App\Models\Jewelery::where('id', $id)
                ->where('status', \App\Models\Jewelery::STATUS_APPROVED)
                ->where('inventory_status', 'available')
                ->first();
        }

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product is not available for purchase.'
            ], 422);
        }

        $customerId = $request->user()->id;

        if ($type === 'diamond') {
            // Diamonds are unique. Force quantity to 1.
            $existing = CustomerCartItem::where('customer_id', $customerId)
                ->where('product_type', 'diamond')
                ->where('product_id', $id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Diamond already exists in cart.'
                ]);
            }

            $cartItem = CustomerCartItem::create([
                'customer_id' => $customerId,
                'product_type' => 'diamond',
                'product_id' => $id,
                'quantity' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Added to cart',
                'data' => new StorefrontCartItemResource($cartItem)
            ]);
        } else {
            // Jewellery supports quantities
            $existing = CustomerCartItem::where('customer_id', $customerId)
                ->where('product_type', 'jewellery')
                ->where('product_id', $id)
                ->first();

            if ($existing) {
                $existing->quantity += $quantity;
                $existing->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Cart item quantity updated.',
                    'data' => new StorefrontCartItemResource($existing)
                ]);
            }

            $cartItem = CustomerCartItem::create([
                'customer_id' => $customerId,
                'product_type' => 'jewellery',
                'product_id' => $id,
                'quantity' => $quantity,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Added to cart',
                'data' => new StorefrontCartItemResource($cartItem)
            ]);
        }
    }

    /**
     * Update the quantity of the cart item.
     *
     * @param  Request  $request
     * @param  CustomerCartItem  $cartItem
     * @return JsonResponse
     */
    public function update(Request $request, CustomerCartItem $cartItem): JsonResponse
    {
        // Ownership validation
        if ((int) $cartItem->customer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Diamond quantity cannot be modified
        if ($cartItem->product_type === 'diamond') {
            return response()->json([
                'success' => false,
                'message' => 'Diamond quantity cannot be modified.'
            ], 422);
        }

        $cartItem->quantity = intval($request->input('quantity'));
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated.',
            'data' => new StorefrontCartItemResource($cartItem)
        ]);
    }

    /**
     * Remove the item from the cart.
     *
     * @param  Request  $request
     * @param  CustomerCartItem  $cartItem
     * @return JsonResponse
     */
    public function destroy(Request $request, CustomerCartItem $cartItem): JsonResponse
    {
        // Ownership validation
        if ((int) $cartItem->customer_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.'
        ]);
    }

    /**
     * Return the count of items in the cart.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function count(Request $request): JsonResponse
    {
        $cartItems = $request->user()->cartItems()->get();
        $count = 0;

        foreach ($cartItems as $item) {
            if ($item->product_type === 'diamond') {
                $count += 1;
            } else {
                $count += intval($item->quantity);
            }
        }

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}
