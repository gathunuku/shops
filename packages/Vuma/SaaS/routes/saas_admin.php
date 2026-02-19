<?php

use Illuminate\Support\Facades\Route;
use Vuma\SaaS\Http\Controllers\SuperAdmin\TenantController;
use Vuma\SaaS\Http\Controllers\SuperAdmin\ReportsController;

Route::prefix('super-admin/saas')
    ->middleware(['web', 'auth:admin'])
    ->name('super-admin.')
    ->group(function () {

        // Tenants
        Route::get('tenants',                         [TenantController::class, 'index'])->name('tenants.index');
        Route::post('tenants',                        [TenantController::class, 'store'])->name('tenants.store');
        Route::get('tenants/{tenant}',                [TenantController::class, 'show'])->name('tenants.show');
        Route::post('tenants/{tenant}/suspend',       [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/reactivate',    [TenantController::class, 'reactivate'])->name('tenants.reactivate');
        Route::post('tenants/{tenant}/domains',       [TenantController::class, 'addDomain'])->name('tenants.domains.add');
        Route::delete('domains/{domain}',             [TenantController::class, 'removeDomain'])->name('tenants.domains.remove');

        // Reports
        Route::get('reports',                         [ReportsController::class, 'index'])->name('reports.index');
        Route::get('api/reports/kpis',                [ReportsController::class, 'kpisJson'])->name('reports.kpis');
    });
