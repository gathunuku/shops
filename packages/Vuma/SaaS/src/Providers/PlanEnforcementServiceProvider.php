<?php

namespace Vuma\SaaS\Providers;

use Illuminate\Support\ServiceProvider;

class PlanEnforcementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Middleware is already registered via SaasServiceProvider alias 'saas.plan'
        // This provider exists to allow separate registration if needed.
    }
}
