<?php

namespace Vuma\SaaS\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Events\InvoicePaid;

class ReactivateTenantOnInvoicePaid
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        $tenant  = $invoice->tenant;

        if (!$tenant) {
            Log::warning('ReactivateTenant: no tenant on invoice', ['invoice_id' => $invoice->id]);
            return;
        }

        if ($tenant->isSuspended()) {
            $tenant->reactivate();

            // Flush tenant host cache so the reactivation is reflected immediately
            foreach ($tenant->domains as $domain) {
                Cache::forget("tenant_host:{$domain->host}");
            }

            Log::info('Tenant reactivated after invoice paid', [
                'tenant_id'  => $tenant->id,
                'invoice_id' => $invoice->id,
            ]);
        }
    }
}
