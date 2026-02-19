<?php

namespace Vuma\SaaS\Http\Controllers\Webhooks;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Jobs\Paystack\HandleTransactionSuccess;
use Vuma\SaaS\Jobs\Paystack\HandleSubscriptionEvent;
use Vuma\SaaS\Services\Payments\PaystackService;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackService $paystack)
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('x-paystack-signature', '');

        if (!$paystack->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('Paystack webhook: invalid signature', [
                'ip' => $request->ip(),
            ]);
            return response('Unauthorized', 401);
        }

        $payload   = $request->json()->all();
        $event     = $payload['event'] ?? '';
        $reference = data_get($payload, 'data.reference')
            ?? data_get($payload, 'data.subscription_code')
            ?? uniqid('ps_', true);

        Log::info('Paystack webhook received', ['event' => $event, 'reference' => $reference]);

        match (true) {
            str_starts_with($event, 'charge.success') =>
                HandleTransactionSuccess::dispatch($reference, $payload)->onQueue('webhooks'),

            str_starts_with($event, 'subscription.') =>
                HandleSubscriptionEvent::dispatch($reference . ':' . $event, $payload)->onQueue('webhooks'),

            str_starts_with($event, 'invoice.') =>
                HandleSubscriptionEvent::dispatch($reference . ':' . $event, $payload)->onQueue('webhooks'),

            default => Log::info('Paystack webhook: unhandled event', ['event' => $event]),
        };

        return response()->json(['status' => 'ok']);
    }
}
