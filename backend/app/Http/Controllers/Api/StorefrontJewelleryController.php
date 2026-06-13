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
            'images',
            'videos',
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
            $categoryInput = $request->input('category');
            $categories = is_array($categoryInput)
                ? $categoryInput
                : array_map('trim', explode(',', $categoryInput));

            // Load master category mappings
            $masterOptions = [];
            $categoryRow = \App\Models\Category::where('type', 'jewelery_type')->first();
            if ($categoryRow && is_array($categoryRow->names)) {
                foreach ($categoryRow->names as $item) {
                    $name = is_array($item) ? ($item['name'] ?? '') : $item;
                    if ($name !== '') {
                        $slug = \Illuminate\Support\Str::slug($name);
                        $masterOptions[strtolower($name)] = $name;
                        $masterOptions[$slug] = $name;
                    }
                }
            }

            // Also load active specifications categories to support case-insensitive/slug queries for custom/legacy categories
            $activeSpecCats = Jewelery::query()
                ->where('status', Jewelery::STATUS_APPROVED)
                ->where('inventory_status', 'available')
                ->whereNotNull('specifications')
                ->pluck('specifications')
                ->map(fn($spec) => $spec['category'] ?? null)
                ->filter()
                ->unique();

            foreach ($activeSpecCats as $catName) {
                $slug = \Illuminate\Support\Str::slug($catName);
                $masterOptions[strtolower($catName)] = $catName;
                $masterOptions[$slug] = $catName;
            }

            $searchNames = [];
            foreach ($categories as $catVal) {
                $lowerVal = strtolower($catVal);
                if (isset($masterOptions[$lowerVal])) {
                    $searchNames[] = $masterOptions[$lowerVal];
                } else {
                    $searchNames[] = $catVal; // Fallback to raw value
                }
            }

            $query->where(function ($q) use ($searchNames, $categories) {
                // Search in physical type column
                $q->whereIn('type', $searchNames);
                // Also search in virtual specifications->category field (supporting both the mapped master names and raw input values for backwards compatibility)
                $allSpecSearch = array_unique(array_merge($searchNames, $categories));
                $q->orWhereIn('specifications->category', $allSpecSearch);
            });
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
     * Retrieve available jewellery categories from the master category table.
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getJewelleryCategories(),
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

        $categories = $this->getJewelleryCategories();

        // Retrieve specifications from DB and parse in PHP
        $specifications = (clone $baseQuery)->whereNotNull('specifications')->pluck('specifications')->all();
        $metalsList = [];
        foreach ($specifications as $spec) {
            $metal = $spec['metal_type'] ?? null;
            if ($metal !== null && $metal !== '') {
                $metalsList[] = $metal;
            }
        }
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
     * Get jewellery categories populated from the category master table with products count.
     *
     * @return array
     */
    private function getJewelleryCategories(): array
    {
        $categoryRow = \App\Models\Category::where('type', 'jewelery_type')->first();
        $categories = [];
        $index = 1;

        if ($categoryRow && is_array($categoryRow->names)) {
            foreach ($categoryRow->names as $item) {
                $name = is_array($item) ? ($item['name'] ?? '') : $item;
                if ($name === '') {
                    continue;
                }
                $image = is_array($item) ? ($item['image'] ?? null) : null;
                if (!$image) {
                    $image = \App\Models\Category::findLocalIcon($name);
                }

                // Count approved and available products matching this category (either by type column or specifications->category field)
                $productsCount = Jewelery::query()
                    ->where('status', Jewelery::STATUS_APPROVED)
                    ->where('inventory_status', 'available')
                    ->where(function ($q) use ($name) {
                        $q->where('type', $name)
                          ->orWhere('specifications->category', $name);
                    })
                    ->count();

                $categories[] = [
                    'id' => $index++,
                    'name' => $name,
                    'slug' => \Illuminate\Support\Str::slug($name),
                    'image' => $image ? (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') ? $image : asset($image)) : null,
                    'products_count' => $productsCount,
                ];
            }
        }

        return $categories;
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
