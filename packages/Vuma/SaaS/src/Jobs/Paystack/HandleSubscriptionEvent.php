<?php

namespace Vuma\SaaS\Jobs\Paystack;

use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Jobs\BaseJob;
use Vuma\SaaS\Models\Subscription;
use Vuma\SaaS\Models\Invoice;
use Vuma\SaaS\Events\InvoicePaid;
use Vuma\SaaS\Services\Payments\PaystackSubscriptionService;

class HandleSubscriptionEvent extends BaseJob
{
    public function __construct(string $idempotencyKey, public array $payload)
    {
        parent::__construct($idempotencyKey);
    }

    public function handle(PaystackSubscriptionService $subscriptionService): void
    {
        $this->withIdempotency(function () use ($subscriptionService) {
            $event            = $this->payload['event']     ?? '';
            $data             = $this->payload['data']      ?? [];
            $subscriptionCode = $data['subscription_code']  ?? ($data['code'] ?? '');

            Log::info('HandleSubscriptionEvent', ['event' => $event, 'code' => $subscriptionCode]);

            match (true) {
                // Subscription created or renewed successfully
                in_array($event, ['subscription.create', 'invoice.payment_success']) =>
                    $this->handleRenewal($data, $subscriptionCode, $subscriptionService),

                // Renewal failed
                in_array($event, ['subscription.not_renew', 'invoice.payment_failed']) =>
                    $subscriptionService->handleRenewalFailure($subscriptionCode),

                // Subscription cancelled/disabled
                in_array($event, ['subscription.disable', 'subscription.expiry_card_close']) =>
                    $subscriptionService->cancel($subscriptionCode),

                default => Log::info('Unhandled Paystack subscription event', ['event' => $event]),
            };
        });
    }

    private function handleRenewal(array $data, string $subscriptionCode, PaystackSubscriptionService $svc): void
    {
        $sub = Subscription::where('external_id', $subscriptionCode)
            ->where('provider', 'paystack')
            ->first();

        if (!$sub) {
            Log::warning('HandleSubscriptionEvent: subscription not found', ['code' => $subscriptionCode]);
            return;
        }

        $sub->update([
            'status'               => 'active',
            'current_period_start' => now(),
            'current_period_end'   => now()->addMonth(),
            'meta'                 => array_merge($sub->meta ?? [], $data),
        ]);

        $sub->tenant?->reactivate();

        // Record invoice as paid if present
        $invoiceRef = data_get($data, 'invoice.reference');
        if ($invoiceRef) {
            $invoice = Invoice::where('tenant_id', $sub->tenant_id)
                ->where('status', '!=', 'paid')
                ->latest()
                ->first();

            if ($invoice) {
                $invoice->markPaid($invoiceRef);
                event(new InvoicePaid($invoice));
            }
        }
    }
}
