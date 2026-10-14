<?php

use App\Http\Controllers\V1\AppConfigController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CartController;
use App\Http\Controllers\V1\CatalogController;
use App\Http\Controllers\V1\DeviceController;
use App\Http\Controllers\V1\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Engine API Routes (/v1/*)
|--------------------------------------------------------------------------
|
| Scoped by X-App-Key header to the specific client app & tenant.
| Protected by ResolveAppKey middleware and app-scoped Sanctum tokens.
|
*/

Route::middleware(['resolve.app_key', 'throttle:mobile_api'])->group(function (): void {
    // App Config
    Route::get('/app-config', [AppConfigController::class, 'show']);

    // Catalog & Search
    Route::get('/products', [CatalogController::class, 'index']);
    Route::get('/products/{id}', [CatalogController::class, 'show']);
    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::get('/search', [CatalogController::class, 'search']);

    // Auth Flows
    Route::post('/auth/otp/request', [AuthController::class, 'requestOtp']);
    Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp']);
    Route::post('/auth/social/google', [AuthController::class, 'googleLogin']);

    // Cart Checkout URL
    Route::post('/cart/checkout-url', [CartController::class, 'checkoutUrl']);

    // Push Devices
    Route::post('/devices', [DeviceController::class, 'store']);
    Route::delete('/devices', [DeviceController::class, 'destroy']);

    // Authenticated customer routes with token scope verification
    Route::middleware(['auth:sanctum', 'token.scope'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::delete('/account', [AuthController::class, 'deleteAccount']);

        // Customer orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
    });
});
