<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\Category;
use App\Http\Resources\V1\DiamondResource;
use App\Http\Resources\V1\JeweleryResource;
use App\Services\DiamondFilterService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * GET /api/v1/home
     * Returns featured jewelry list and available counts statistics.
     */
    public function home()
    {
        $totalDiamonds = Diamond::where('status', Diamond::STATUS_APPROVED)
            ->where('inventory_status', 'available')
            ->count();

        $totalJewelry = Jewelery::where('status', Jewelery::STATUS_APPROVED)
            ->where('inventory_status', 'available')
            ->count();

        $featuredJewelry = Jewelery::where('status', Jewelery::STATUS_APPROVED)
            ->where('inventory_status', 'available')
            ->latest()
            ->take(8)
            ->get();

        return response()->json([
            'success' => true,
            'featured_jewelry' => JeweleryResource::collection($featuredJewelry),
            'stats' => [
                'total_diamonds_available' => $totalDiamonds,
                'total_jewelry_available' => $totalJewelry,
            ]
        ]);
    }

    /**
     * GET /api/v1/categories
     * Returns dynamic option lists fetched directly from the categories database table.
     */
    public function categories()
    {
        // Query the database to retrieve all distinct category types that exist
        $categoryTypes = Category::pluck('type')->unique()->toArray();

        $filters = [];
        foreach ($categoryTypes as $type) {
            $filters[$type] = Category::getNames($type);
        }

        return response()->json([
            'success' => true,
            'filters' => $filters,
        ]);
    }

    /**
     * GET /api/v1/diamonds
     * Returns paginated approved available diamonds. Reuses DiamondFilterService.
     */
    public function diamondsIndex(Request $request)
    {
        // 1. Preprocess frontend parameter formats to match DiamondFilterService expectations
        $this->preprocessDiamondParameters($request);

        // Enforce public storefront inventory rules
        $request->merge([
            'inventory_status' => 'available',
        ]);

        $query = Diamond::query()
            ->where('status', Diamond::STATUS_APPROVED)
            ->where('inventory_status', 'available');

        // 2. Temporarily elevate session state to Super Admin so DiamondFilterService bypasses role scopes
        $originalRole = session('admin_role');
        session(['admin_role' => 'super_admin']);

        try {
            $filterService = new DiamondFilterService();
            $query = $filterService->applyFilters($query, $request);
        } finally {
            if ($originalRole !== null) {
                session(['admin_role' => $originalRole]);
            } else {
                session()->forget('admin_role');
            }
        }

        // Apply price range custom mapping if passed (fallback checks ask_price then cash_price)
        if ($request->filled('price_min')) {
            $priceMin = floatval($request->input('price_min'));
            $query->where(function($q) use ($priceMin) {
                $q->where('asking_price', '>=', $priceMin)
                  ->orWhere(function($sub) use ($priceMin) {
                      $sub->whereNull('asking_price')->where('cash_price', '>=', $priceMin);
                  });
            });
        }
        if ($request->filled('price_max')) {
            $priceMax = floatval($request->input('price_max'));
            $query->where(function($q) use ($priceMax) {
                $q->where('asking_price', '<=', $priceMax)
                  ->orWhere(function($sub) use ($priceMax) {
                      $sub->whereNull('asking_price')->where('cash_price', '<=', $priceMax);
                  });
            });
        }

        // Paginate at 15 items per page
        $diamonds = $query->paginate(15);

        return DiamondResource::collection($diamonds);
    }

    /**
     * GET /api/v1/diamonds/{id}
     * Returns single approved available diamond detail.
     */
    public function diamondsShow($id)
    {
        $diamond = Diamond::where('status', Diamond::STATUS_APPROVED)
            ->where('inventory_status', 'available')
            ->findOrFail($id);

        return new DiamondResource($diamond);
    }

    /**
     * GET /api/v1/jewelry
     * Returns paginated approved available jewelry list.
     */
    public function jewelryIndex(Request $request)
    {
        $query = Jewelery::query()
            ->where('status', Jewelery::STATUS_APPROVED)
            ->where('inventory_status', 'available');

        // Filter by jewelry type style (exact or array match)
        if ($request->filled('type')) {
            $types = $request->input('type');
            if (is_array($types)) {
                $query->whereIn('type', $types);
            } else {
                $query->whereIn('type', explode(',', $types));
            }
        }

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->input('location')}%");
        }

        // Keyword Search matching sku or name
        if ($request->filled('keyword')) {
            $kw = $request->input('keyword');
            $query->where(function($q) use ($kw) {
                $q->where('sku', 'like', "%{$kw}%")
                  ->orWhere('name', 'like', "%{$kw}%");
            });
        }

        // Paginate at 12 items per page
        $jewelry = $query->latest()->paginate(12);

        return JeweleryResource::collection($jewelry);
    }

    /**
     * GET /api/v1/jewelry/{id}
     * Returns single approved available jewelry detail.
     */
    public function jewelryShow($id)
    {
        $jewelry = Jewelery::where('status', Jewelery::STATUS_APPROVED)
            ->where('inventory_status', 'available')
            ->findOrFail($id);

        return new JeweleryResource($jewelry);
    }

    /**
     * Map clean frontend params to match admin-panel range and select filters array formats.
     */
    private function preprocessDiamondParameters(Request $request)
    {
        // 1. Map keyword search
        if ($request->filled('keyword') && !$request->filled('search')) {
            $request->merge(['search' => $request->input('keyword')]);
        }

        // 2. Map range filters
        if ($request->filled('price_min')) {
            $request->merge(['price_ct_from' => $request->input('price_min')]);
        }
        if ($request->filled('price_max')) {
            $request->merge(['price_ct_to' => $request->input('price_max')]);
        }
        if ($request->filled('size_min')) {
            $request->merge(['size_from' => $request->input('size_min')]);
        }
        if ($request->filled('size_max')) {
            $request->merge(['size_to' => $request->input('size_max')]);
        }

        // 3. Normalize single value and array filters for shape, color, clarity
        $arrayFieldsMap = [
            'shape' => 'shapes',
            'shapes' => 'shapes',
            'color' => 'colors',
            'colors' => 'colors',
            'clarity' => 'clarities',
            'clarities' => 'clarities',
        ];

        foreach ($arrayFieldsMap as $param => $targetKey) {
            if ($request->has($param)) {
                $value = $request->input($param);
                $arrayValue = is_array($value) ? $value : explode(',', $value);
                
                // Merge into the targets
                $existing = $request->input($targetKey, []);
                $merged = array_unique(array_merge(is_array($existing) ? $existing : [$existing], $arrayValue));
                $request->merge([$targetKey => $merged]);
            }
        }
    }
}
