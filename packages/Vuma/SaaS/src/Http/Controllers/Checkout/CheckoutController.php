<?php

namespace Vuma\SaaS\Http\Controllers\Checkout;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Services\Payments\MpesaDarajaService;
use Vuma\SaaS\Services\Payments\MtnMomoService;
use Vuma\SaaS\Services\Payments\AirtelMoneyService;
use Vuma\SaaS\Services\Payments\VodacomTzService;
use Vuma\SaaS\Services\Regions\RegionService;

class CheckoutController extends Controller
{
    public function __construct(
        protected RegionService      $regionService,
        protected MpesaDarajaService $mpesa,
        protected MtnMomoService     $mtnMomo,
        protected AirtelMoneyService $airtel,
        protected VodacomTzService   $vodacom
    ) {}

    /**
     * Initiate a mobile money payment for an order.
     * POST /checkout/mobile-pay
     */
    public function initiateMobilePay(Request $request)
    {
        $request->validate([
            'order_increment_id' => 'required|string',
            'channel'            => 'required|string',
            'msisdn'             => 'required|string',
            'amount_cents'       => 'required|integer|min:1',
        ]);

        $tenant       = app('tenant');
        $country      = $tenant->country ?? 'KE';
        $channel      = $request->channel;
        $msisdn       = preg_replace('/\D/', '', $request->msisdn);
        $amountCents  = (int) $request->amount_cents;
        $reference    = $request->order_increment_id;
        $callbackBase = config('app.url');

        try {
            $result = match ($channel) {
                'mpesa_ke' => $this->mpesa->stkPush(
                    $msisdn,
                    (int) ceil($amountCents / 100),
                    $reference,
                    'Order ' . $reference,
                    $callbackBase . '/webhooks/mpesa/ke'
                ),
                'mtn_momo' => (function () use ($msisdn, $amountCents, $reference, $tenant, $callbackBase) {
                    $refId = (string) Str::uuid();
                    $this->mtnMomo->requestToPay(
                        $refId, $msisdn,
                        number_format($amountCents / 100, 2, '.', ''),
                        $this->regionService->currency($tenant->country ?? 'GH'),
                        $callbackBase . '/webhooks/mtn-momo',
                        'Order ' . $reference
                    );
                    return ['reference_id' => $refId];
                })(),
                'airtel_money' => $this->airtel->collect(
                    $msisdn,
                    number_format($amountCents / 100, 2, '.', ''),
                    $reference
                ),
                'vodacom_tz' => $this->vodacom->c2bPayment(
                    $msisdn,
                    (string) ceil($amountCents / 100),
                    $reference
                ),
                default => throw new \InvalidArgumentException("Unsupported channel: {$channel}"),
            };

            return response()->json(['status' => 'initiated', 'data' => $result]);

        } catch (\Throwable $e) {
            Log::error('CheckoutController: payment initiation failed', [
                'channel'   => $channel,
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Payment initiation failed. Please try again.'], 422);
        }
    }

    /**
     * Return available payment channels for the current tenant's country.
     * GET /checkout/payment-channels
     */
    public function channels()
    {
        $tenant   = app('tenant');
        $country  = $tenant->country ?? 'KE';
        $channels = $this->regionService->defaultChannels($country);

        return response()->json([
            'country'  => $country,
            'currency' => $this->regionService->currency($country),
            'channels' => $channels,
        ]);
    }
}
