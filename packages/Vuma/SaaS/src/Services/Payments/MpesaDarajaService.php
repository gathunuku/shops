<?php

namespace Vuma\SaaS\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MpesaDarajaService
{
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $shortCode;
    protected string $passKey;
    protected string $baseUrl;

    public function __construct(
        ?string $consumerKey    = null,
        ?string $consumerSecret = null,
        ?string $shortCode      = null,
        ?string $passKey        = null,
        ?string $baseUrl        = null
    ) {
        $this->consumerKey    = $consumerKey    ?? config('services.mpesa_ke.consumer_key',    env('MPESA_KE_CONSUMER_KEY'));
        $this->consumerSecret = $consumerSecret ?? config('services.mpesa_ke.consumer_secret', env('MPESA_KE_CONSUMER_SECRET'));
        $this->shortCode      = $shortCode      ?? config('services.mpesa_ke.shortcode',       env('MPESA_KE_SHORTCODE'));
        $this->passKey        = $passKey        ?? config('services.mpesa_ke.passkey',         env('MPESA_KE_PASSKEY'));
        $this->baseUrl        = rtrim($baseUrl  ?? config('services.mpesa_ke.base_url',        env('MPESA_KE_BASE_URL', 'https://sandbox.safaricom.co.ke')), '/');
    }

    /**
     * Get OAuth access token (cached for 50 minutes).
     */
    public function accessToken(): string
    {
        return Cache::remember('mpesa_ke_token', 50 * 60, function () {
            $resp = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->acceptJson()
                ->get($this->baseUrl . '/oauth/v1/generate', ['grant_type' => 'client_credentials']);
            $resp->throw();
            return $resp->json('access_token');
        });
    }

    /**
     * Initiate STK Push (Lipa Na M-Pesa Online).
     *
     * @param string $msisdn    E.164 or 2547XXXXXXXX
     * @param int    $amount    Amount in KES (whole number)
     * @param string $reference Account reference (order ID, invoice number)
     * @param string $description Transaction description
     * @param string $callbackUrl Your callback URL (must be HTTPS)
     */
    public function stkPush(string $msisdn, int $amount, string $reference, string $description, string $callbackUrl): array
    {
        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($this->shortCode . $this->passKey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->shortCode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => $amount,
            'PartyA'            => $msisdn,
            'PartyB'            => $this->shortCode,
            'PhoneNumber'       => $msisdn,
            'CallBackURL'       => $callbackUrl,
            'AccountReference'  => substr($reference, 0, 12),  // Max 12 chars
            'TransactionDesc'   => substr($description, 0, 13), // Max 13 chars
        ];

        $resp = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', $payload);
        $resp->throw();
        return $resp->json();
    }

    /**
     * Query STK Push status.
     */
    public function stkQuery(string $checkoutRequestId): array
    {
        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($this->shortCode . $this->passKey . $timestamp);

        $resp = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->baseUrl . '/mpesa/stkpushquery/v1/query', [
                'BusinessShortCode' => $this->shortCode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);
        $resp->throw();
        return $resp->json();
    }
}
