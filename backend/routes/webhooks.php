<?php

use App\Http\Controllers\Webhooks\RazorpayWebhookController;
use App\Http\Controllers\Webhooks\WooWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks Routes
|--------------------------------------------------------------------------
|
| Signature-verified incoming webhooks from WooCommerce, Shopify, and Razorpay.
|
*/

Route::prefix('webhooks')->group(function (): void {
    Route::post('/woo/{ulid}', [WooWebhookController::class, 'handle'])->name('webhooks.woo');
    Route::post('/razorpay', [RazorpayWebhookController::class, 'handle'])->name('webhooks.razorpay');
    Route::get('/whatsapp', [\App\Http\Controllers\Webhooks\WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
    Route::post('/whatsapp', [\App\Http\Controllers\Webhooks\WhatsAppWebhookController::class, 'handle'])->name('webhooks.whatsapp');
});
