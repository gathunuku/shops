<?php

return [
    App\Providers\AppServiceProvider::class,

    // ── VumaShops SaaS Layer ────────────────────────────────────────
    Vuma\SaaS\Providers\SaasServiceProvider::class,
    Vuma\SaaS\Providers\SaasEventServiceProvider::class,
    Vuma\SaaS\Providers\CacheEventServiceProvider::class,
    Vuma\SaaS\Providers\SchedulerServiceProvider::class,
];
