<?php

namespace Vuma\SaaS\Jobs\Mtn;

use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Jobs\BaseJob;
use Vuma\SaaS\Services\Orders\OrderPaymentUpdater;
use Vuma\SaaS\Models\Invoice;
use Vuma\SaaS\Events\InvoicePaid;

class HandleMomoCallback extends BaseJob
{
    public function __construct(string $idempotencyKey, public array $payload)
    {
        parent::__construct($idempotencyKey);
    }

    public function handle(OrderPaymentUpdater $orderUpdater): void
    {
        $this->withIdempotency(function () use ($orderUpdater) {
            $status       = strtoupper($this->payload['status'] ?? '');
            $externalId   = $this->payload['externalId']            ?? '';
            $financialId  = $this->payload['financialTransactionId'] ?? '';
            $amount       = (int) round((float) ($this->payload['amount'] ?? 0) * 100);

            Log::info('HandleMomoCallback', compact('status', 'externalId', 'financialId'));

            if ($status !== 'SUCCESSFUL') {
                Log::info('MTN MoMo payment not successful', ['status' => $status, 'externalId' => $externalId]);
                return;
            }

            // Check if this is a billing invoice payment
            if (str_starts_with($externalId, 'INV-')) {
                $invoiceId = (int) str_replace('INV-', '', $externalId);
                $invoice   = Invoice::find($invoiceId);

                if ($invoice) {
                    // Also update the provider_reference from UUID to actual financial ID
                    $invoice->provider_reference = $financialId ?: $externalId;
                    $invoice->save();
                    $invoice->markPaid($financialId ?: $externalId);
                    event(new InvoicePaid($invoice));
                }
                return;
            }

            // Storefront order payment
            $orderUpdater->markPaid($externalId, 'mtn_momo', $financialId ?: $externalId, $amount);
        });
    }
}
