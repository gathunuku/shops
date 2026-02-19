<?php

namespace Vuma\SaaS\Services\Billing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Vuma\SaaS\Models\Invoice;
use Vuma\SaaS\Models\Tenant;
use Vuma\SaaS\Services\Payments\MpesaDarajaService;
use Vuma\SaaS\Services\Payments\MtnMomoService;
use Vuma\SaaS\Services\Payments\AirtelMoneyService;

class DunningService
{
    public function __construct(
        protected MpesaDarajaService $mpesa,
        protected MtnMomoService     $mtnMomo,
        protected AirtelMoneyService $airtel
    ) {}

    /**
     * Main dunning cycle — called hourly by scheduler.
     * Processes up to 200 overdue invoices per run.
     */
    public function runCycle(): void
    {
        $now = now();
        $invoices = Invoice::with('tenant')
            ->whereIn('status', ['pending', 'sent', 'failed'])
            ->where(function ($q) use ($now) {
                $q->whereNull('due_at')->orWhere('due_at', '<=', $now);
            })
            ->orderBy('id')
            ->limit(200)
            ->get();

        foreach ($invoices as $invoice) {
            try {
                $this->processInvoice($invoice);
            } catch (\Throwable $e) {
                Log::error('Dunning cycle error on invoice', [
                    'invoice_id' => $invoice->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        Log::info('Dunning cycle completed', ['processed' => $invoices->count()]);
    }

    protected function processInvoice(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $invoice->refresh(); // Re-read inside transaction
            $tenant = $invoice->tenant;

            if (!$tenant) {
                Log::warning('Dunning: invoice has no tenant', ['invoice_id' => $invoice->id]);
                return;
            }

            // Step 1: First touch → send notification
            if ($invoice->status === 'pending') {
                $this->sendNotification($tenant, $invoice);
                $invoice->status          = 'sent';
                $invoice->attempts        = ($invoice->attempts ?? 0) + 1;
                $invoice->last_attempt_at = now();
                $invoice->save();
                return;
            }

            // Step 2: Already notified → trigger payment request
            if (in_array($invoice->status, ['sent', 'failed'])) {
                $maxAttempts = (int) config('saas.dunning.max_attempts', 5);

                if (($invoice->attempts ?? 0) >= $maxAttempts) {
                    $this->checkGraceAndSuspend($tenant, $invoice);
                    return;
                }

                $triggered = $this->triggerPayment($invoice, $tenant);
                $invoice->status          = $triggered ? 'sent' : 'failed';
                $invoice->attempts        = ($invoice->attempts ?? 0) + 1;
                $invoice->last_attempt_at = now();
                $invoice->save();

                if (!$triggered) {
                    $this->checkGraceAndSuspend($tenant, $invoice);
                }
            }
        });
    }

    protected function sendNotification(Tenant $tenant, Invoice $invoice): void
    {
        // Email via Mailgun
        try {
            if ($tenant->email) {
                Mail::send(
                    'saas::mail.invoice-due',
                    ['invoice' => $invoice, 'tenant' => $tenant],
                    fn ($m) => $m->to($tenant->email)->subject('Your VumaShops invoice is due')
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Dunning email failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
        }

        // SMS via Africa's Talking
        try {
            $apiKey   = config('services.africastalking.api_key',  env('AFRICASTALKING_API_KEY'));
            $username = config('services.africastalking.username', env('AFRICASTALKING_USERNAME'));

            if ($tenant->phone && $apiKey && $username) {
                $message = view('saas::sms.invoice_due', ['invoice' => $invoice, 'tenant' => $tenant])->render();
                Http::asForm()
                    ->withHeaders(['apiKey' => $apiKey])
                    ->post('https://api.africastalking.com/version1/messaging', [
                        'username' => $username,
                        'to'       => $tenant->phone,
                        'message'  => $message,
                    ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Dunning SMS failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
        }
    }

    protected function triggerPayment(Invoice $invoice, Tenant $tenant): bool
    {
        $amount    = (int) $invoice->amount_cents;
        $msisdn    = data_get($invoice->meta, 'msisdn');
        $reference = 'INV-' . $invoice->id;

        if (!$msisdn) {
            Log::warning('Dunning: no msisdn on invoice meta', ['invoice_id' => $invoice->id]);
            return false;
        }

        try {
            return match ($invoice->channel) {
                'mpesa_ke' => $this->triggerMpesaKe($invoice, $msisdn, $amount, $reference),
                'mtn_momo' => $this->triggerMtnMomo($invoice, $msisdn, $amount, $reference),
                'airtel_money' => $this->triggerAirtel($invoice, $msisdn, $amount, $reference),
                default => false,
            };
        } catch (\Throwable $e) {
            Log::warning('Dunning payment trigger failed', [
                'invoice_id' => $invoice->id,
                'channel'    => $invoice->channel,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function triggerMpesaKe(Invoice $invoice, string $msisdn, int $amountCents, string $ref): bool
    {
        $this->mpesa->stkPush(
            $msisdn,
            (int) ceil($amountCents / 100),
            $ref,
            'VumaShops subscription renewal',
            url('/webhooks/mpesa/ke')
        );
        return true;
    }

    private function triggerMtnMomo(Invoice $invoice, string $msisdn, int $amountCents, string $ref): bool
    {
        $referenceId = (string) Str::uuid();
        $this->mtnMomo->requestToPay(
            $referenceId,
            $msisdn,
            number_format($amountCents / 100, 2, '.', ''),
            $invoice->currency,
            url('/webhooks/mtn-momo'),
            'VumaShops renewal'
        );
        $invoice->provider_reference = $referenceId;
        $invoice->save();
        return true;
    }

    private function triggerAirtel(Invoice $invoice, string $msisdn, int $amountCents, string $ref): bool
    {
        $this->airtel->collect(
            $msisdn,
            number_format($amountCents / 100, 2, '.', ''),
            $ref
        );
        return true;
    }

    protected function checkGraceAndSuspend(Tenant $tenant, Invoice $invoice): void
    {
        if (!$invoice->due_at) {
            return;
        }

        $graceDays = (int) config('saas.grace_days', 7);
        $daysPast  = (int) now()->diffInDays($invoice->due_at, false);

        if ($daysPast < -$graceDays) {
            Log::info('Dunning: suspending tenant after grace period', [
                'tenant_id'  => $tenant->id,
                'invoice_id' => $invoice->id,
                'days_past'  => abs($daysPast),
            ]);
            $tenant->suspend();
        }
    }
}
