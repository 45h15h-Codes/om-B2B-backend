<?php

namespace App\Services;

use App\Models\Diamond;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DiamondFilterService
{
    /**
     * Apply all search filters to the diamond query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyFilters(Builder $query, Request $request): Builder
    {
        // 1. Role-based scoping
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            $userId = Auth::id();
            $storeIds = \App\Models\ShopifyStore::where('user_id', $userId)->pluck('id')->toArray();

            $query->where(function ($q) use ($userId, $storeIds) {
                $q->where('assigned_admin_id', $userId)
                  ->orWhere('user_id', $userId)
                  ->orWhereHas('storeAssignments', function ($sub) use ($storeIds) {
                      $sub->whereIn('shopify_store_id', $storeIds);
                  });
            });
        }

        if ($request->filled('inventory_status')) {
            $query->where('inventory_status', $request->inventory_status);
        }

        // 2. Tab selection: Search (Single) vs Parcel
        $activeTab = $request->input('tab', 'search');
        $query->where('is_parcel', $activeTab === 'parcel');

        // 3. Keyword Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('stock_no', 'like', "%{$search}%")
                  ->orWhere('specifications->report_no', 'like', "%{$search}%")
                  ->orWhere('shape', 'like', "%{$search}%")
                  ->orWhere('specifications->lab', 'like', "%{$search}%");
            });
        }

        // 4. Multi-select filters
        $this->applyMultiSelectFilters($query, $request);

        // 5. Range filters
        $this->applyRangeFilters($query, $request);

        // 6. Show Only (Primary Suppliers toggle)
        if ($request->has('primary_suppliers') && $request->primary_suppliers == '1') {
            $query->where('show_on_OM', true);
        }

        // 7. Exact match string filters
        $this->applyExactMatches($query, $request);

        // 8. Show Only types (matched pair, parcel)
        if ($request->has('show_only_types') && is_array($request->show_only_types)) {
            foreach ($request->show_only_types as $type) {
                if ($type === 'Star') {
                    $query->where('is_matched_pair', true);
                } elseif ($type === 'Parcel') {
                    $query->where('is_parcel', true);
                }
            }
        }

        return $query;
    }

    /**
     * Apply multi-select filters using an array and loop.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function applyMultiSelectFilters(Builder $query, Request $request): void
    {
        foreach (Diamond::getMultiSelectFilters() as $requestKey => $column) {
            if ($request->has($requestKey) && is_array($request->$requestKey)) {
                $dbField = in_array($column, Diamond::PHYSICAL_COLUMNS)
                    ? $column
                    : "specifications->{$column}";
                $query->whereIn($dbField, $request->$requestKey);
            }
        }
    }

    /**
     * Apply range filters using an array and loop.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function applyRangeFilters(Builder $query, Request $request): void
    {
        foreach (Diamond::getRangeFilters() as $prefix => $column) {
            $dbField = in_array($column, Diamond::PHYSICAL_COLUMNS)
                ? $column
                : "specifications->{$column}";
            if ($request->filled("{$prefix}_from")) {
                $query->where($dbField, '>=', $request->input("{$prefix}_from"));
            }
            if ($request->filled("{$prefix}_to")) {
                $query->where($dbField, '<=', $request->input("{$prefix}_to"));
            }
        }
    }

    /**
     * Apply exact match filters using an array and loop.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function applyExactMatches(Builder $query, Request $request): void
    {
        $exactMatches = [
            'eye_clean' => 'culet_condition',
            'shade' => 'treatment',
            'location' => 'country',
        ];

        foreach ($exactMatches as $requestKey => $column) {
            if ($request->filled($requestKey)) {
                $dbField = in_array($column, Diamond::PHYSICAL_COLUMNS)
                    ? $column
                    : "specifications->{$column}";
                $query->where($dbField, $request->input($requestKey));
            }
        }
    }
}
