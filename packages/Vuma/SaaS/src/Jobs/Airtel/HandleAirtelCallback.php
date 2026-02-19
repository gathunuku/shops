<?php

namespace Vuma\SaaS\Jobs\Airtel;

use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Jobs\BaseJob;
use Vuma\SaaS\Services\Orders\OrderPaymentUpdater;
use Vuma\SaaS\Models\Invoice;
use Vuma\SaaS\Events\InvoicePaid;

class HandleAirtelCallback extends BaseJob
{
    public function __construct(string $idempotencyKey, public array $payload)
    {
        parent::__construct($idempotencyKey);
    }

    public function handle(OrderPaymentUpdater $orderUpdater): void
    {
        $this->withIdempotency(function () use ($orderUpdater) {
            $status        = strtoupper(data_get($this->payload, 'transaction.status', ''));
            $transactionId = data_get($this->payload, 'transaction.id', '');
            $reference     = data_get($this->payload, 'transaction.message', '')
                ?? data_get($this->payload, 'reference', '');
            $amount        = (int) round((float) data_get($this->payload, 'transaction.amount', 0) * 100);

            Log::info('HandleAirtelCallback', compact('status', 'transactionId', 'reference'));

            if (!in_array($status, ['TS', 'SUCCESS', 'SUCCESSFUL'])) {
                Log::info('Airtel payment not successful', ['status' => $status]);
                return;
            }

            // Billing invoice?
            if (str_starts_with($reference, 'INV-')) {
                $invoiceId = (int) str_replace('INV-', '', $reference);
                $invoice   = Invoice::find($invoiceId);
                if ($invoice) {
                    $invoice->markPaid($transactionId);
                    event(new InvoicePaid($invoice));
                }
                return;
            }

            $orderUpdater->markPaid($reference, 'airtel_money', $transactionId, $amount);
        });
    }
}
