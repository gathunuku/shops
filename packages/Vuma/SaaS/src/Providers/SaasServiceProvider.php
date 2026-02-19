<?php

namespace Vuma\SaaS\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Vuma\SaaS\Http\Middleware\ResolveTenantByHost;
use Vuma\SaaS\Http\Middleware\EnforceTenantPlanLimits;
use Vuma\SaaS\Http\Middleware\EnsureTenantActive;
use Vuma\SaaS\Services\Payments\PaystackService;
use Vuma\SaaS\Services\Payments\MpesaDarajaService;
use Vuma\SaaS\Services\Payments\MtnMomoService;
use Vuma\SaaS\Services\Payments\AirtelMoneyService;
use Vuma\SaaS\Services\Payments\VodacomTzService;
use Vuma\SaaS\Services\Payments\PaystackSubscriptionService;
use Vuma\SaaS\Services\Regions\RegionService;
use Vuma\SaaS\Services\Idempotency\IdempotencyService;

class SaasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/saas.php', 'saas');
        $this->mergeConfigFrom(__DIR__ . '/../../config/regions.php', 'regions');

        // Bind payment services
        $this->app->singleton(PaystackService::class, fn () => new PaystackService());
        $this->app->singleton(PaystackSubscriptionService::class, fn () => new PaystackSubscriptionService());
        $this->app->singleton(MpesaDarajaService::class, fn () => new MpesaDarajaService());
        $this->app->singleton(MtnMomoService::class, fn () => new MtnMomoService());
        $this->app->singleton(AirtelMoneyService::class, fn () => new AirtelMoneyService());
        $this->app->singleton(VodacomTzService::class, fn () => new VodacomTzService());
        $this->app->singleton(RegionService::class, fn () => new RegionService());
        $this->app->singleton(IdempotencyService::class, fn () => new IdempotencyService());
    }

    public function boot(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('tenant', ResolveTenantByHost::class);
        $router->aliasMiddleware('tenant.active', EnsureTenantActive::class);
        $router->aliasMiddleware('saas.plan', EnforceTenantPlanLimits::class);

        $this->loadRoutesFrom(__DIR__ . '/../../routes/saas_admin.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/tenant.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/webhooks.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'saas');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->publishes([
            __DIR__ . '/../../config/saas.php' => config_path('saas.php'),
            __DIR__ . '/../../config/regions.php' => config_path('regions.php'),
        ], 'saas-config');

        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/saas'),
        ], 'saas-views');
    }
}
