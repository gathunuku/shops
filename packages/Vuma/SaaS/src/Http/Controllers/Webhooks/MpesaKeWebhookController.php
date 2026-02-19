<?php

namespace Vuma\SaaS\Http\Controllers\Webhooks;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Jobs\Mpesa\HandleDarajaStkCallback;

class MpesaKeWebhookController extends Controller
{
    /**
     * Daraja STK Push callback.
     * Safaricom posts to: POST /webhooks/mpesa/ke
     */
    public function __invoke(Request $request)
    {
        $body = $request->json()->all();

        Log::info('M-Pesa KE webhook received', ['body' => $body]);

        $callback         = data_get($body, 'Body.stkCallback', []);
        $resultCode       = data_get($callback, 'ResultCode');
        $checkoutRequestId = data_get($callback, 'CheckoutRequestID');
        $merchantRequestId = data_get($callback, 'MerchantRequestID');

        if (!$checkoutRequestId) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $idempotencyKey = 'mpesa_ke:' . $checkoutRequestId;

        HandleDarajaStkCallback::dispatch($idempotencyKey, $body)->onQueue('webhooks');

        // Safaricom expects this exact acknowledgement
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
