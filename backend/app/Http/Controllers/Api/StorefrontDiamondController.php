<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diamond;
use App\Http\Resources\StorefrontDiamondResource;
use App\Http\Resources\StorefrontDiamondDetailResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StorefrontDiamondController extends Controller
{
    /**
     * Display a paginated listing of approved, available diamonds.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Retrieve and enforce storefront visibility rules dynamically from production constants
        $query = Diamond::query()
            ->where('status', Diamond::STATUS_APPROVED)
            ->where('inventory_status', 'available');

        // Optimize database fetch by selecting only required storefront fields
        $query->select([
            'id',
            'stock_no',
            'shape',
            'size',
            'color',
            'clarity',
            'asking_price',
            'cash_price',
            'status',
            'inventory_status',
            'specifications',
            'images',
            'videos',
            'created_at'
        ]);

        // 1. Filter: Shape (single or multi-select)
        if ($request->filled('shape')) {
            $shape = $request->input('shape');
            if (is_array($shape)) {
                $query->whereIn('shape', $shape);
            } elseif (str_contains($shape, ',')) {
                $query->whereIn('shape', array_map('trim', explode(',', $shape)));
            } else {
                $query->where('shape', $shape);
            }
        }

        // 2. Filter: Color (single or multi-select)
        if ($request->filled('color')) {
            $color = $request->input('color');
            if (is_array($color)) {
                $query->whereIn('color', $color);
            } elseif (str_contains($color, ',')) {
                $query->whereIn('color', array_map('trim', explode(',', $color)));
            } else {
                $query->where('color', $color);
            }
        }

        // 3. Filter: Clarity (single or multi-select)
        if ($request->filled('clarity')) {
            $clarity = $request->input('clarity');
            if (is_array($clarity)) {
                $query->whereIn('clarity', $clarity);
            } elseif (str_contains($clarity, ',')) {
                $query->whereIn('clarity', array_map('trim', explode(',', $clarity)));
            } else {
                $query->where('clarity', $clarity);
            }
        }

        // 4. Filter: Cut (single or multi-select JSON specifications field)
        if ($request->filled('cut')) {
            $cut = $request->input('cut');
            if (is_array($cut)) {
                $query->whereIn('specifications->cut', $cut);
            } elseif (str_contains($cut, ',')) {
                $query->whereIn('specifications->cut', array_map('trim', explode(',', $cut)));
            } else {
                $query->where('specifications->cut', $cut);
            }
        }

        // 5. Filter: Size/Carat Range
        if ($request->filled('carat_min')) {
            $query->where('size', '>=', floatval($request->input('carat_min')));
        }
        if ($request->filled('carat_max')) {
            $query->where('size', '<=', floatval($request->input('carat_max')));
        }

        // 6. Filter: Price Range (fall back from asking_price to cash_price)
        if ($request->filled('price_min')) {
            $priceMin = floatval($request->input('price_min'));
            $query->where(function ($q) use ($priceMin) {
                $q->where('asking_price', '>=', $priceMin)
                  ->orWhere(function ($sub) use ($priceMin) {
                      $sub->whereNull('asking_price')->where('cash_price', '>=', $priceMin);
                  });
            });
        }
        if ($request->filled('price_max')) {
            $priceMax = floatval($request->input('price_max'));
            $query->where(function ($q) use ($priceMax) {
                $q->where('asking_price', '<=', $priceMax)
                  ->orWhere(function ($sub) use ($priceMax) {
                      $sub->whereNull('asking_price')->where('cash_price', '<=', $priceMax);
                  });
            });
        }

        // 7. Apply Sorting
        $sort = $request->input('sort');
        switch ($sort) {
            case 'price_low_high':
                $query->orderByRaw('COALESCE(asking_price, cash_price, 0) ASC');
                break;
            case 'price_high_low':
                $query->orderByRaw('COALESCE(asking_price, cash_price, 0) DESC');
                break;
            case 'carat_low_high':
                $query->orderBy('size', 'asc');
                break;
            case 'carat_high_low':
                $query->orderBy('size', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // 8. Paginate (default 20 items per page)
        $perPage = intval($request->input('per_page', 20));
        if ($perPage <= 0 || $perPage > 100) {
            $perPage = 20;
        }
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => StorefrontDiamondResource::collection($paginated->items()),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ]
        ]);
    }

    /**
     * Retrieve available listing filters dynamically.
     *
     * @return JsonResponse
     */
    public function filters(): JsonResponse
    {
        $baseQuery = Diamond::query()
            ->where('status', Diamond::STATUS_APPROVED)
            ->where('inventory_status', 'available');

        $shapes = (clone $baseQuery)->whereNotNull('shape')->pluck('shape')->unique()->sort()->values()->all();
        $colors = (clone $baseQuery)->whereNotNull('color')->pluck('color')->unique()->sort()->values()->all();
        $clarities = (clone $baseQuery)->whereNotNull('clarity')->pluck('clarity')->unique()->sort()->values()->all();

        // Retrieve cuts from specifications JSON in PHP
        $specifications = (clone $baseQuery)->whereNotNull('specifications')->pluck('specifications')->all();
        $cutsList = [];
        foreach ($specifications as $spec) {
            $cut = $spec['cut'] ?? null;
            if ($cut !== null && $cut !== '') {
                $cutsList[] = $cut;
            }
        }
        $cuts = array_values(array_unique($cutsList));
        sort($cuts);

        $caratMin = (clone $baseQuery)->min('size');
        $caratMax = (clone $baseQuery)->max('size');

        $priceMin = (clone $baseQuery)->min(\DB::raw('COALESCE(asking_price, cash_price, 0)'));
        $priceMax = (clone $baseQuery)->max(\DB::raw('COALESCE(asking_price, cash_price, 0)'));

        return response()->json([
            'success' => true,
            'data' => [
                'shapes' => $shapes,
                'colors' => $colors,
                'clarities' => $clarities,
                'cuts' => $cuts,
                'carat_range' => [
                    'min' => $caratMin ? floatval($caratMin) : 0.0,
                    'max' => $caratMax ? floatval($caratMax) : 0.0,
                ],
                'price_range' => [
                    'min' => $priceMin ? floatval($priceMin) : 0.0,
                    'max' => $priceMax ? floatval($priceMax) : 0.0,
                ]
            ]
        ]);
    }

    /**
     * Display the specified approved and available storefront diamond.
     *
     * @param  Diamond  $diamond
     * @return JsonResponse
     */
    public function show(Diamond $diamond): JsonResponse
    {
        if (
            $diamond->status !== Diamond::STATUS_APPROVED ||
            $diamond->inventory_status !== 'available'
        ) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'data' => new StorefrontDiamondDetailResource($diamond),
        ]);
    }
}
