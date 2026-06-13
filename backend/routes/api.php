<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/shopify/webhooks', [\App\Http\Controllers\ShopifyWebhookController::class, 'handle'])
    ->middleware('shopify.webhook');
Route::post('/shopify/webhooks/orders/create', [\App\Http\Controllers\ShopifyWebhookController::class, 'handleWebhookOrderCreate'])
    ->middleware('shopify.webhook');

Route::get('/test', function () {
    return response()->json([
        'message' => 'Backend Connected Successfully'
    ]);
});

Route::get('/storefront/navigation', [\App\Http\Controllers\Api\StorefrontNavigationController::class, 'index'])
    ->name('api.storefront.navigation');

Route::get('/storefront/diamonds', [\App\Http\Controllers\Api\StorefrontDiamondController::class, 'index'])
    ->name('api.storefront.diamonds.index');

Route::get('/storefront/diamonds/filters', [\App\Http\Controllers\Api\StorefrontDiamondController::class, 'filters'])
    ->name('api.storefront.diamonds.filters');

Route::get('/storefront/diamonds/{diamond}', [\App\Http\Controllers\Api\StorefrontDiamondController::class, 'show'])
    ->name('api.storefront.diamonds.show');

Route::get('/storefront/jewellery', [\App\Http\Controllers\Api\StorefrontJewelleryController::class, 'index'])
    ->name('api.storefront.jewellery.index');

Route::get('/storefront/jewellery/categories', [\App\Http\Controllers\Api\StorefrontJewelleryController::class, 'categories'])
    ->name('api.storefront.jewellery.categories');

Route::get('/storefront/jewellery/filters', [\App\Http\Controllers\Api\StorefrontJewelleryController::class, 'filters'])
    ->name('api.storefront.jewellery.filters');

Route::get('/storefront/jewellery/{jewellery}', [\App\Http\Controllers\Api\StorefrontJewelleryController::class, 'show'])
    ->name('api.storefront.jewellery.show');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/storefront/wishlist', [\App\Http\Controllers\Api\StorefrontWishlistController::class, 'index'])
        ->name('api.storefront.wishlist.index');
    Route::post('/storefront/wishlist', [\App\Http\Controllers\Api\StorefrontWishlistController::class, 'store'])
        ->name('api.storefront.wishlist.store');
    Route::delete('/storefront/wishlist/{wishlist}', [\App\Http\Controllers\Api\StorefrontWishlistController::class, 'destroy'])
        ->name('api.storefront.wishlist.destroy');
    Route::get('/storefront/wishlist/count', [\App\Http\Controllers\Api\StorefrontWishlistController::class, 'count'])
        ->name('api.storefront.wishlist.count');

    Route::get('/storefront/cart', [\App\Http\Controllers\Api\StorefrontCartController::class, 'index'])
        ->name('api.storefront.cart.index');
    Route::post('/storefront/cart', [\App\Http\Controllers\Api\StorefrontCartController::class, 'store'])
        ->name('api.storefront.cart.store');
    Route::put('/storefront/cart/{cartItem}', [\App\Http\Controllers\Api\StorefrontCartController::class, 'update'])
        ->name('api.storefront.cart.update');
    Route::delete('/storefront/cart/{cartItem}', [\App\Http\Controllers\Api\StorefrontCartController::class, 'destroy'])
        ->name('api.storefront.cart.destroy');
    Route::get('/storefront/cart/count', [\App\Http\Controllers\Api\StorefrontCartController::class, 'count'])
        ->name('api.storefront.cart.count');
});

/*
|--------------------------------------------------------------------------
| Storefront Public Catalog API Routes (V1)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::get('/home', [\App\Http\Controllers\Api\V1\CatalogController::class, 'home'])->name('api.home');
    Route::get('/categories', [\App\Http\Controllers\Api\V1\CatalogController::class, 'categories'])->name('api.categories');
    
    Route::get('/diamonds', [\App\Http\Controllers\Api\V1\CatalogController::class, 'diamondsIndex'])->name('api.diamonds.index');
    Route::get('/diamonds/{id}', [\App\Http\Controllers\Api\V1\CatalogController::class, 'diamondsShow'])->name('api.diamonds.show');
    
    Route::get('/jewelry', [\App\Http\Controllers\Api\V1\CatalogController::class, 'jewelryIndex'])->name('api.jewelry.index');
    Route::get('/jewelry/{id}', [\App\Http\Controllers\Api\V1\CatalogController::class, 'jewelryShow'])->name('api.jewelry.show');

    // Public B2B Partnership Inquiry Route
    Route::post('/partnership-requests', [\App\Http\Controllers\Api\V1\PartnershipController::class, 'store'])->name('api.partnership-requests.store');

    // Customer Guest Auth
    Route::post('/register', [\App\Http\Controllers\Api\V1\CustomerAuthController::class, 'register'])->name('api.customer.register');
    Route::post('/login', [\App\Http\Controllers\Api\V1\CustomerAuthController::class, 'login'])->name('api.customer.login');

    // Customer Protected Actions
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\V1\CustomerAuthController::class, 'logout'])->name('api.customer.logout');
        Route::get('/profile', [\App\Http\Controllers\Api\V1\CustomerAuthController::class, 'profile'])->name('api.customer.profile');
        
        // Customer Profile Account Management
        Route::put('/profile/update', [\App\Http\Controllers\Api\V1\CustomerProfileController::class, 'updateProfile'])->name('api.customer.profile.update');
        Route::post('/change-password', [\App\Http\Controllers\Api\V1\CustomerProfileController::class, 'changePassword'])->name('api.customer.change-password');
        Route::get('/orders', [\App\Http\Controllers\Api\V1\CustomerProfileController::class, 'orders'])->name('api.customer.orders');
    });
});

// Admin APIs for Partnership Requests (Super Admin role restricted in controller)
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/partnership-requests', [\App\Http\Controllers\PartnershipRequestController::class, 'apiIndex'])->name('api.admin.partnership-requests.index');
    Route::get('/partnership-requests/{id}', [\App\Http\Controllers\PartnershipRequestController::class, 'apiShow'])->name('api.admin.partnership-requests.show');
    Route::post('/partnership-requests/{id}/approve', [\App\Http\Controllers\PartnershipRequestController::class, 'apiApprove'])->name('api.admin.partnership-requests.approve');
    Route::post('/partnership-requests/{id}/reject', [\App\Http\Controllers\PartnershipRequestController::class, 'apiReject'])->name('api.admin.partnership-requests.reject');
});


