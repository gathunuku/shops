<?php

namespace Vuma\SaaS\Http\Controllers\Webhooks;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Jobs\Vodacom\HandleVodacomCallback;

class VodacomTzWebhookController extends Controller
{
    /**
     * Vodacom Tanzania M-Pesa callback.
     * POST /webhooks/vodacom-tz
     */
    public function __invoke(Request $request)
    {
        $body = $request->json()->all();

        Log::info('Vodacom TZ webhook received', ['body' => $body]);

        $conversationId = data_get($body, 'input_ConversationID')
            ?? data_get($body, 'output_ConversationID')
            ?? uniqid('vodacom_', true);

        $idempotencyKey = 'vodacom_tz:' . $conversationId;

        HandleVodacomCallback::dispatch($idempotencyKey, $body)->onQueue('webhooks');

        return response()->json(['output_ResponseCode' => 'INS-0', 'output_ResponseDesc' => 'Request processed successfully']);
    }
}
