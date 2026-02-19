<?php

use Illuminate\Support\Facades\Route;
use Vuma\SaaS\Http\Controllers\Billing\BillingController;
use Vuma\SaaS\Http\Controllers\Checkout\CheckoutController;

/*
|--------------------------------------------------------------------------
| Tenant-scoped routes
| All routes here require tenant middleware (host resolved to a tenant).
|--------------------------------------------------------------------------
*/

// Health check (no tenant required)
Route::get('_ping', fn () => response()->json(['status' => 'ok']))->name('ping');

// Billing (tenant must be authenticated as their own admin)
Route::prefix('billing')
    ->middleware(['tenant', 'tenant.active', 'web'])
    ->name('saas.billing.')
    ->group(function () {
        Route::get('plans',              [BillingController::class, 'plans'])->name('plans');
        Route::post('subscribe',         [BillingController::class, 'subscribe'])->name('subscribe');
        Route::get('paystack/callback',  [BillingController::class, 'paystackCallback'])->name('paystack.callback');
    });

// Checkout (mobile money payment initiation — called from JS on storefront)
Route::prefix('checkout')
    ->middleware(['tenant', 'tenant.active', 'api'])
    ->name('saas.checkout.')
    ->group(function () {
        Route::post('mobile-pay',     [CheckoutController::class, 'initiateMobilePay'])->name('mobile-pay');
        Route::get('channels',        [CheckoutController::class, 'channels'])->name('channels');
    });
