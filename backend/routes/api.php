<?php

use App\Http\Controllers\Api\AppController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BuildController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\ConnectionController;
use App\Http\Controllers\Api\NotificationCampaignController;
use App\Http\Controllers\Api\PreviewController;
use App\Http\Controllers\Api\SetupStateController;
use App\Http\Controllers\Api\ThemeController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Dashboard API routes (/api/*). Scoped to authenticated tenant context.
|
*/

Route::get('/health', [HealthController::class, 'check']);

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');

    Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
    });
});

Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
    Route::get('/apps', [AppController::class, 'index']);
    Route::post('/apps', [AppController::class, 'store']);
    Route::get('/apps/{app}', [AppController::class, 'show']);

    // Connection endpoints
    Route::post('/apps/{app}/connection/verify', [ConnectionController::class, 'verify']);
    Route::get('/apps/{app}/connection', [ConnectionController::class, 'show']);

    // Setup state machine & rollbacks
    Route::get('/apps/{app}/setup-state', [SetupStateController::class, 'show']);
    Route::put('/apps/{app}/setup-step', [SetupStateController::class, 'update']);

    // Design Themes gallery
    Route::get('/themes', [ThemeController::class, 'index']);
    Route::get('/themes/{key}', [ThemeController::class, 'show']);

    // Configuration endpoints guarded by step middleware
    Route::get('/apps/{app}/config', [ConfigController::class, 'show']);
    Route::put('/apps/{app}/config/draft', [ConfigController::class, 'saveDraft'])->middleware('step:theme');
    Route::post('/apps/{app}/config/publish', [ConfigController::class, 'publish'])->middleware('step:theme');

    // Mobile simulator preview data guarded by step middleware
    Route::get('/apps/{app}/preview-data', [PreviewController::class, 'show'])->middleware('step:preview');

    // Build pipeline endpoints
    Route::get('/apps/{app}/builds', [BuildController::class, 'index']);
    Route::post('/apps/{app}/builds', [BuildController::class, 'store']);
    Route::get('/apps/{app}/builds/{build}', [BuildController::class, 'show']);
    Route::get('/apps/{app}/builds/{build}/logs', [BuildController::class, 'logs']);
    Route::get('/apps/{app}/builds/{build}/download-url', [BuildController::class, 'downloadUrl']);

    // Billing & checkout
    Route::post('/apps/{app}/checkout', [CheckoutController::class, 'checkout']);

    // Notification campaigns
    Route::get('/apps/{app}/notifications/campaigns', [NotificationCampaignController::class, 'index']);
    Route::post('/apps/{app}/notifications/campaigns', [NotificationCampaignController::class, 'store']);

    // WhatsApp Cloud API integrations
    Route::post('/apps/{app}/integrations/whatsapp/test', [WhatsAppController::class, 'test']);
    Route::get('/apps/{app}/integrations/whatsapp/wallet', [WhatsAppController::class, 'wallet']);
});

// Expiring signed artifact download route (verified by 'signed' middleware)
Route::get('/builds/download/{ulid}', [BuildController::class, 'download'])
    ->name('builds.download')
    ->middleware('signed');
