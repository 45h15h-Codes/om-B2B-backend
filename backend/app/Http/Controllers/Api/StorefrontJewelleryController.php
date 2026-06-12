<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jewelery;
use App\Http\Resources\StorefrontJewelleryResource;
use App\Http\Resources\StorefrontJewelleryDetailResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StorefrontJewelleryController extends Controller
{
    /**
     * Display a paginated listing of approved, available jewellery.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Fetch only approved, available items
        $query = Jewelery::query()
            ->where('status', Jewelery::STATUS_APPROVED)
            ->where('inventory_status', 'available');

        // Select only required fields to optimize memory and speed
        $query->select([
            'id',
            'sku',
            'name',
            'type',
            'price',
            'image_url',
            'status',
            'inventory_status',
            'specifications',
            'created_at'
        ]);

        // 1. Filter: Type (single or multi-select)
        if ($request->filled('type')) {
            $type = $request->input('type');
            if (is_array($type)) {
                $query->whereIn('type', $type);
            } elseif (str_contains($type, ',')) {
                $query->whereIn('type', array_map('trim', explode(',', $type)));
            } else {
                $query->where('type', $type);
            }
        }

        // 2. Filter: Category (single or multi-select virtual specifications field)
        if ($request->filled('category')) {
            $category = $request->input('category');
            if (is_array($category)) {
                $query->whereIn('specifications->category', $category);
            } elseif (str_contains($category, ',')) {
                $query->whereIn('specifications->category', array_map('trim', explode(',', $category)));
            } else {
                $query->where('specifications->category', $category);
            }
        }

        // 3. Filter: Metal (single or multi-select virtual specifications->metal_type field)
        if ($request->filled('metal')) {
            $metal = $request->input('metal');
            if (is_array($metal)) {
                $query->whereIn('specifications->metal_type', $metal);
            } elseif (str_contains($metal, ',')) {
                $query->whereIn('specifications->metal_type', array_map('trim', explode(',', $metal)));
            } else {
                $query->where('specifications->metal_type', $metal);
            }
        }

        // 4. Filter: Price Range
        if ($request->filled('price_min')) {
            $query->where('price', '>=', floatval($request->input('price_min')));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', floatval($request->input('price_max')));
        }

        // 5. Apply Sorting
        $sort = $request->input('sort');
        switch ($sort) {
            case 'price_low_high':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high_low':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // 6. Paginate (default 20 items per page)
        $perPage = intval($request->input('per_page', 20));
        if ($perPage <= 0 || $perPage > 100) {
            $perPage = 20;
        }
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => StorefrontJewelleryResource::collection($paginated->items()),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ]
        ]);
    }

    /**
     * Retrieve available jewellery listing filters dynamically.
     *
     * @return JsonResponse
     */
    public function filters(): JsonResponse
    {
        $baseQuery = Jewelery::query()
            ->where('status', Jewelery::STATUS_APPROVED)
            ->where('inventory_status', 'available');

        $types = (clone $baseQuery)->whereNotNull('type')->pluck('type')->unique()->sort()->values()->all();

        // Retrieve specifications from DB and parse in PHP
        $specifications = (clone $baseQuery)->whereNotNull('specifications')->pluck('specifications')->all();
        $categoriesList = [];
        $metalsList = [];
        foreach ($specifications as $spec) {
            $cat = $spec['category'] ?? null;
            if ($cat !== null && $cat !== '') {
                $categoriesList[] = $cat;
            }
            $metal = $spec['metal_type'] ?? null;
            if ($metal !== null && $metal !== '') {
                $metalsList[] = $metal;
            }
        }
        $categories = array_values(array_unique($categoriesList));
        sort($categories);
        $metals = array_values(array_unique($metalsList));
        sort($metals);

        $priceMin = (clone $baseQuery)->min('price');
        $priceMax = (clone $baseQuery)->max('price');

        return response()->json([
            'success' => true,
            'data' => [
                'types' => $types,
                'categories' => $categories,
                'metals' => $metals,
                'price_range' => [
                    'min' => $priceMin ? floatval($priceMin) : 0.0,
                    'max' => $priceMax ? floatval($priceMax) : 0.0,
                ]
            ]
        ]);
    }

    /**
     * Display the specified approved and available storefront jewellery item.
     *
     * @param  Jewelery  $jewellery
     * @return JsonResponse
     */
    public function show(Jewelery $jewellery): JsonResponse
    {
        if (
            $jewellery->status !== Jewelery::STATUS_APPROVED ||
            $jewellery->inventory_status !== 'available'
        ) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'data' => new StorefrontJewelleryDetailResource($jewellery),
        ]);
    }
}
