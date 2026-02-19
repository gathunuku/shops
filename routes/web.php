<?php

use Illuminate\Support\Facades\Route;

// Bagisto registers its own routes via service providers.
// VumaShops routes are loaded via SaasServiceProvider.
// This file intentionally minimal.

Route::get('/', function () {
    return redirect('/');
});
