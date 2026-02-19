<?php

namespace Vuma\SaaS\Jobs\Paystack;

use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Jobs\BaseJob;
use Vuma\SaaS\Services\Orders\OrderPaymentUpdater;
use Vuma\SaaS\Models\Invoice;

class HandleTransactionSuccess extends BaseJob
{
    public function __construct(string $idempotencyKey, public array $payload)
    {
        parent::__construct($idempotencyKey);
    }

    public function handle(OrderPaymentUpdater $orderUpdater): void
    {
        $this->withIdempotency(function () use ($orderUpdater) {
            $data      = $this->payload['data'] ?? [];
            $reference = $data['reference']  ?? '';
            $metadata  = $data['metadata']   ?? [];
            $amount    = (int) ($data['amount'] ?? 0); // in kobo/pesewas

            Log::info('HandleTransactionSuccess', ['reference' => $reference]);

            // If this is a storefront order payment
            $orderIncrementId = $metadata['order_increment_id'] ?? null;
            if ($orderIncrementId) {
                $orderUpdater->markPaid($orderIncrementId, 'paystack', $reference, $amount);
            }

            // If this is a tenant billing payment (subscription initialization)
            $tenantId = $metadata['tenant_id'] ?? null;
            $planId   = $metadata['plan_id']   ?? null;
            if ($tenantId && $planId) {
                $this->activateTenantSubscription((int) $tenantId, (int) $planId, $data);
            }
        });
    }

    private function activateTenantSubscription(int $tenantId, int $planId, array $data): void
    {
        $tenant = \Vuma\SaaS\Models\Tenant::find($tenantId);
        $plan   = \Vuma\SaaS\Models\Plan::find($planId);

        if (!$tenant || !$plan) {
            Log::warning('HandleTransactionSuccess: tenant or plan not found', compact('tenantId', 'planId'));
            return;
        }

        app(\Vuma\SaaS\Services\Payments\PaystackSubscriptionService::class)
            ->activate($tenant, $plan, $data);
    }
}
