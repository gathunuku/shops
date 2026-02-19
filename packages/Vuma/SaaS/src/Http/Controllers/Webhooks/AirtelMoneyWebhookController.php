<?php

namespace Vuma\SaaS\Http\Controllers\Webhooks;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Jobs\Airtel\HandleAirtelCallback;

class AirtelMoneyWebhookController extends Controller
{
    /**
     * Airtel Money payment callback.
     * POST /webhooks/airtel-money
     */
    public function __invoke(Request $request)
    {
        $body = $request->json()->all();

        Log::info('Airtel Money webhook received', ['body' => $body]);

        $transactionId  = data_get($body, 'transaction.id')
            ?? data_get($body, 'id')
            ?? uniqid('airtel_', true);

        $idempotencyKey = 'airtel:' . $transactionId;

        HandleAirtelCallback::dispatch($idempotencyKey, $body)->onQueue('webhooks');

        return response()->json(['status' => 'ok']);
    }
}
