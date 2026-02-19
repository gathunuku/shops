<?php

use Illuminate\Support\Facades\Route;
use Vuma\SaaS\Http\Controllers\Webhooks\PaystackWebhookController;
use Vuma\SaaS\Http\Controllers\Webhooks\MpesaKeWebhookController;
use Vuma\SaaS\Http\Controllers\Webhooks\MtnMomoWebhookController;
use Vuma\SaaS\Http\Controllers\Webhooks\AirtelMoneyWebhookController;
use Vuma\SaaS\Http\Controllers\Webhooks\VodacomTzWebhookController;

/*
|--------------------------------------------------------------------------
| Webhook Routes — exempt from CSRF and session middleware
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks')->middleware(['throttle:120,1'])->group(function () {
    Route::post('paystack',     PaystackWebhookController::class)->name('webhooks.paystack');
    Route::post('mpesa/ke',     MpesaKeWebhookController::class)->name('webhooks.mpesa.ke');
    Route::post('mtn-momo',     MtnMomoWebhookController::class)->name('webhooks.mtn-momo');
    Route::post('airtel-money', AirtelMoneyWebhookController::class)->name('webhooks.airtel-money');
    Route::post('vodacom-tz',   VodacomTzWebhookController::class)->name('webhooks.vodacom-tz');
});
