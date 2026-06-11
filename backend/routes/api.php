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


