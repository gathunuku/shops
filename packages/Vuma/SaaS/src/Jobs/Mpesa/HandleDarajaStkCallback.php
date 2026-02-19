<?php

namespace Vuma\SaaS\Jobs\Mpesa;

use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Jobs\BaseJob;
use Vuma\SaaS\Services\Orders\OrderPaymentUpdater;
use Vuma\SaaS\Models\Invoice;
use Vuma\SaaS\Events\InvoicePaid;

class HandleDarajaStkCallback extends BaseJob
{
    public function __construct(string $idempotencyKey, public array $payload)
    {
        parent::__construct($idempotencyKey);
    }

    public function handle(OrderPaymentUpdater $orderUpdater): void
    {
        $this->withIdempotency(function () use ($orderUpdater) {
            $callback   = $this->payload['Body']['stkCallback'] ?? [];
            $resultCode = $callback['ResultCode'] ?? -1;

            Log::info('HandleDarajaStkCallback', [
                'resultCode'        => $resultCode,
                'checkoutRequestId' => $callback['CheckoutRequestID'] ?? null,
            ]);

            if ((int) $resultCode !== 0) {
                // Payment failed or cancelled by user
                Log::info('M-Pesa STK: payment not completed', ['resultCode' => $resultCode, 'desc' => $callback['ResultDesc'] ?? '']);
                return;
            }

            // Parse callback metadata items
            $items     = collect($callback['CallbackMetadata']['Item'] ?? []);
            $getValue  = fn (string $name) => $items->firstWhere('Name', $name)['Value'] ?? null;

            $mpesaRef    = $getValue('MpesaReceiptNumber');
            $amount      = (int) round((float) $getValue('Amount') * 100); // to cents
            $msisdn      = $getValue('PhoneNumber');
            $accountRef  = $getValue('AccountReference') ?? '';

            Log::info('M-Pesa STK success', compact('mpesaRef', 'amount', 'msisdn', 'accountRef'));

            // Determine if storefront order or billing invoice
            if (str_starts_with($accountRef, 'INV-')) {
                $this->handleBillingPayment($accountRef, $mpesaRef);
            } else {
                $orderUpdater->markPaid($accountRef, 'mpesa_ke', $mpesaRef, $amount);
            }
        });
    }

    private function handleBillingPayment(string $accountRef, string $mpesaRef): void
    {
        $invoiceId = (int) str_replace('INV-', '', $accountRef);
        $invoice   = Invoice::find($invoiceId);

        if (!$invoice) {
            Log::warning('M-Pesa: billing invoice not found', ['accountRef' => $accountRef]);
            return;
        }

        $invoice->markPaid($mpesaRef);
        event(new InvoicePaid($invoice));
    }
}
