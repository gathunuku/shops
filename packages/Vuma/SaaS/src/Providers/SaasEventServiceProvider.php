<?php

namespace Vuma\SaaS\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Vuma\SaaS\Events\InvoicePaid;
use Vuma\SaaS\Listeners\ReactivateTenantOnInvoicePaid;

class SaasEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoicePaid::class => [
            ReactivateTenantOnInvoicePaid::class,
        ],
    ];
}
