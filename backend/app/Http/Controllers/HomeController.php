<?php

namespace App\Http\Controllers;

use App\Models\Diamond;
use App\Models\Jewelery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Show the application Home dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $diamondQuery = Diamond::query();
        $jewelryQuery = Jewelery::query();

        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            $diamondQuery->where('assigned_admin_id', Auth::id());
            $jewelryQuery->where('assigned_admin_id', Auth::id());
        }

        $diamondsCount = $diamondQuery->count();
        $jewelryCount = $jewelryQuery->count();

        $availableDiamonds = (clone $diamondQuery)->where(function($q) {
            $q->whereNull('inventory_status')->orWhere('inventory_status', 'available');
        })->count();
        $availableJewelry = (clone $jewelryQuery)->where(function($q) {
            $q->whereNull('inventory_status')->orWhere('inventory_status', 'available');
        })->count();
        $availableCount = $availableDiamonds + $availableJewelry;

        $onHoldDiamonds = (clone $diamondQuery)->whereIn('inventory_status', ['hold', 'on_hold'])->count();
        $onHoldJewelry = (clone $jewelryQuery)->whereIn('inventory_status', ['hold', 'on_hold'])->count();
        $onHoldCount = $onHoldDiamonds + $onHoldJewelry;

        $soldDiamonds = (clone $diamondQuery)->where('inventory_status', 'sold')->count();
        $soldJewelry = (clone $jewelryQuery)->where('inventory_status', 'sold')->count();
        $soldCount = $soldDiamonds + $soldJewelry;
        
        $stats = [
            'diamonds_count' => $diamondsCount,
            'jewelry_count' => $jewelryCount,
            'available_count' => $availableCount,
            'on_hold_count' => $onHoldCount,
            'sold_count' => $soldCount,
            'active_buy_trades' => 0,
            'active_sell_trades' => 0,
            'unread_messages' => 0,
        ];

        // Define home categories
        $categoriesData = [
            'rings' => [
                'name' => 'Rings',
                'count' => '20,965 items',
                'local' => 'category/rings.png',
            ],
            'bracelets' => [
                'name' => 'Bracelets',
                'count' => '3,724 items',
                'local' => 'category/bracelets.png',
            ],
            'earrings' => [
                'name' => 'Earrings',
                'count' => '7,965 items',
                'local' => 'category/earrings.png',
            ],
            'necklaces' => [
                'name' => 'Necklaces',
                'count' => '2,951 items',
                'local' => 'category/necklaces.png',
            ],
            'watches' => [
                'name' => 'Watches',
                'count' => '599 items',
                'local' => 'category/watches.png',
            ],
        ];

        $typeMapping = [
            'rings' => 'Ring',
            'bracelets' => 'Bracelet',
            'earrings' => 'Earings',
            'necklaces' => 'Necklace',
            'watches' => 'Watch',
        ];

        $categories = [];
        foreach ($categoriesData as $key => $cat) {
            $categories[$key] = [
                'name' => $cat['name'],
                'count' => $cat['count'],
                'image' => $this->getCategoryImageUrl($key, $cat['local']),
                'type' => $typeMapping[$key] ?? $cat['name'],
            ];
        }

        return view('home', compact('stats', 'categories'));
    }

    /**
     * Resolve Category Image from Cloudinary or local path.
     */
    private function getCategoryImageUrl($key, $localPath)
    {
        $cacheKey = "category_image_url_{$key}";
        
        try {
            $url = cache($cacheKey);
            if ($url) {
                return $url;
            }
        } catch (\Exception $e) {
            // Cache not available or misconfigured
        }

        $fullPath = public_path($localPath);
        if (file_exists($fullPath)) {
            $cloudinaryUrl = \App\Services\CloudinaryService::upload($fullPath);
            if ($cloudinaryUrl) {
                try {
                    cache([$cacheKey => $cloudinaryUrl], now()->addDays(30));
                } catch (\Exception $e) {
                    // Ignore cache write failure
                }
                return $cloudinaryUrl;
            }
        }

        return asset($localPath);
    }
}
