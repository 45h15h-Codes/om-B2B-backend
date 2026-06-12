<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class StorefrontNavigationController extends Controller
{
    /**
     * Display the storefront navigation tree.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // 1. Build Diamonds dropdown dynamically from categories where type = 'shape'
        $shapeOptions = Category::getOptionsByType('shape');
        $shapeChildren = $shapeOptions->map(function ($option) {
            return [
                'id' => $option->id,
                'title' => $option->name,
                'url' => '/diamonds?shape=' . urlencode($option->name),
                'metadata' => [
                    'image' => $option->image,
                    'group' => $option->group,
                ],
            ];
        })->values()->all();

        // 2. Build Jewellery dropdown dynamically from categories where type = 'jewelery_type'
        $jewelryOptions = Category::getOptionsByType('jewelery_type');
        $jewelryChildren = $jewelryOptions->map(function ($option) {
            return [
                'id' => $option->id,
                'title' => $option->name,
                'url' => '/jewelry?type=' . urlencode($option->name),
                'metadata' => [
                    'image' => $option->image,
                    'group' => $option->group,
                ],
            ];
        })->values()->all();

        // 3. Assemble full React-friendly navigation menu
        $menu = [
            [
                'id' => 'home',
                'title' => 'Home',
                'url' => '/',
                'type' => 'link',
                'children' => [],
            ],
            [
                'id' => 'diamonds',
                'title' => 'Diamonds',
                'url' => '/diamonds',
                'type' => 'dropdown',
                'children' => $shapeChildren,
            ],
            [
                'id' => 'jewelery',
                'title' => 'Jewellery',
                'url' => '/jewelry',
                'type' => 'dropdown',
                'children' => $jewelryChildren,
            ],
            [
                'id' => 'collections',
                'title' => 'Collections',
                'url' => '/collections',
                'type' => 'link',
                'children' => [],
            ],
            [
                'id' => 'about',
                'title' => 'About',
                'url' => '/about',
                'type' => 'link',
                'children' => [],
            ],
            [
                'id' => 'contact',
                'title' => 'Contact',
                'url' => '/contact',
                'type' => 'link',
                'children' => [],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $menu,
        ]);
    }
}
