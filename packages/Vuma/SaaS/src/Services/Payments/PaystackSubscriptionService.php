<?php

namespace Vuma\SaaS\Services\Payments;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Models\Tenant;
use Vuma\SaaS\Models\Plan;
use Vuma\SaaS\Models\Subscription;

class PaystackSubscriptionService
{
    public function __construct(protected PaystackService $paystack) {}

    /**
     * Subscribe a tenant to a plan via Paystack.
     * Returns the authorization URL to redirect the tenant to.
     */
    public function initiate(Tenant $tenant, Plan $plan): array
    {
        if (!$plan->paystack_plan_code) {
            throw new \RuntimeException("Plan [{$plan->code}] has no Paystack plan code configured.");
        }

        // Initialize a transaction to collect card authorization
        $resp = $this->paystack->initializeTransaction([
            'email'        => $tenant->email,
            'amount'       => $plan->price_cents,
            'currency'     => $plan->currency,
            'plan'         => $plan->paystack_plan_code,
            'metadata'     => [
                'tenant_id' => $tenant->id,
                'plan_id'   => $plan->id,
            ],
            'callback_url' => route('saas.billing.paystack.callback'),
        ]);

        return $resp['data'] ?? [];
    }

    /**
     * Called after Paystack's charge.success or subscription.create webhook.
     * Creates/updates the Subscription record and activates the tenant.
     */
    public function activate(Tenant $tenant, Plan $plan, array $paystackData): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $paystackData) {
            $sub = Subscription::updateOrCreate(
                ['tenant_id' => $tenant->id, 'provider' => 'paystack'],
                [
                    'plan_id'              => $plan->id,
                    'external_id'          => $paystackData['subscription_code'] ?? ($paystackData['reference'] ?? null),
                    'status'               => 'active',
                    'current_period_start' => now(),
                    'current_period_end'   => now()->addMonth(),
                    'meta'                 => $paystackData,
                ]
            );

            $tenant->reactivate();

            Log::info('Tenant subscription activated via Paystack', [
                'tenant_id'         => $tenant->id,
                'subscription_code' => $sub->external_id,
            ]);

            return $sub;
        });
    }

    /**
     * Called on subscription.not_renew or invoice.payment_failed webhooks.
     */
    public function handleRenewalFailure(string $subscriptionCode): void
    {
        $sub = Subscription::where('external_id', $subscriptionCode)
            ->where('provider', 'paystack')
            ->first();

        if (!$sub) {
            Log::warning('Subscription not found for renewal failure', compact('subscriptionCode'));
            return;
        }

        $sub->update(['status' => 'past_due']);

        Log::info('Paystack subscription renewal failed; marked past_due', [
            'subscription_code' => $subscriptionCode,
            'tenant_id'         => $sub->tenant_id,
        ]);
    }

    /**
     * Called on subscription.disable webhook or manual cancellation.
     */
    public function cancel(string $subscriptionCode): void
    {
        $sub = Subscription::where('external_id', $subscriptionCode)
            ->where('provider', 'paystack')
            ->first();

        if (!$sub) {
            return;
        }

        $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        Log::info('Paystack subscription cancelled', [
            'subscription_code' => $subscriptionCode,
            'tenant_id'         => $sub->tenant_id,
        ]);
    }
}
