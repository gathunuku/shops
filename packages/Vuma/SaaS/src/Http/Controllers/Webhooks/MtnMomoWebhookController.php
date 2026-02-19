<?php

namespace Vuma\SaaS\Http\Controllers\Webhooks;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Jobs\Mtn\HandleMomoCallback;

class MtnMomoWebhookController extends Controller
{
    /**
     * MTN MoMo callback (RequestToPay result notification).
     * MTN posts to: POST /webhooks/mtn-momo
     */
    public function __invoke(Request $request)
    {
        $body = $request->json()->all();

        Log::info('MTN MoMo webhook received', ['body' => $body]);

        // MTN sends: financialTransactionId, externalId, amount, currency, payer, status, reason
        $referenceId = $request->header('X-Reference-Id')
            ?? data_get($body, 'externalId')
            ?? data_get($body, 'financialTransactionId')
            ?? uniqid('mtn_', true);

        $idempotencyKey = 'mtn_momo:' . $referenceId;

        HandleMomoCallback::dispatch($idempotencyKey, $body)->onQueue('webhooks');

        return response()->json(['status' => 'ok']);
    }
}
