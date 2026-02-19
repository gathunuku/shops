<?php

namespace Vuma\SaaS\Jobs\Vodacom;

use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Jobs\BaseJob;
use Vuma\SaaS\Services\Orders\OrderPaymentUpdater;
use Vuma\SaaS\Models\Invoice;
use Vuma\SaaS\Events\InvoicePaid;

class HandleVodacomCallback extends BaseJob
{
    public function __construct(string $idempotencyKey, public array $payload)
    {
        parent::__construct($idempotencyKey);
    }

    public function handle(OrderPaymentUpdater $orderUpdater): void
    {
        $this->withIdempotency(function () use ($orderUpdater) {
            $responseCode   = $this->payload['output_ResponseCode']         ?? '';
            $transactionId  = $this->payload['output_TransactionID']        ?? '';
            $conversationId = $this->payload['output_ConversationID']       ?? '';
            $reference      = $this->payload['input_ThirdPartyConversationID'] ?? $conversationId;
            $amount         = (int) round((float) ($this->payload['input_Amount'] ?? 0) * 100);

            Log::info('HandleVodacomCallback', compact('responseCode', 'transactionId', 'reference'));

            // INS-0 = success
            if ($responseCode !== 'INS-0') {
                Log::info('Vodacom TZ payment not successful', ['responseCode' => $responseCode]);
                return;
            }

            if (str_starts_with($reference, 'INV-')) {
                $invoiceId = (int) str_replace('INV-', '', $reference);
                $invoice   = Invoice::find($invoiceId);
                if ($invoice) {
                    $invoice->markPaid($transactionId);
                    event(new InvoicePaid($invoice));
                }
                return;
            }

            $orderUpdater->markPaid($reference, 'vodacom_tz', $transactionId, $amount);
        });
    }
}
