<?php

namespace Vuma\SaaS\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AirtelMoneyService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl;
    protected string $country;
    protected string $currency;

    public function __construct(
        ?string $clientId     = null,
        ?string $clientSecret = null,
        ?string $country      = null,
        ?string $currency     = null,
        ?string $baseUrl      = null
    ) {
        $this->clientId     = $clientId     ?? config('services.airtel.client_id',     env('AIRTEL_CLIENT_ID'));
        $this->clientSecret = $clientSecret ?? config('services.airtel.client_secret', env('AIRTEL_CLIENT_SECRET'));
        $this->country      = $country      ?? config('services.airtel.country',       env('AIRTEL_COUNTRY', 'KE'));
        $this->currency     = $currency     ?? config('services.airtel.currency',      env('AIRTEL_CURRENCY', 'KES'));
        $this->baseUrl      = rtrim($baseUrl ?? config('services.airtel.base_url',     env('AIRTEL_BASE_URL', 'https://openapi.airtel.africa')), '/');
    }

    /**
     * Get OAuth2 access token (cached for 55 minutes).
     */
    public function accessToken(): string
    {
        $cacheKey = 'airtel_token_' . md5($this->clientId . $this->country);
        return Cache::remember($cacheKey, 55 * 60, function () {
            $resp = Http::acceptJson()->post($this->baseUrl . '/auth/oauth2/token', [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type'    => 'client_credentials',
            ]);
            $resp->throw();
            return $resp->json('access_token');
        });
    }

    /**
     * Initiate a payment collection.
     *
     * @param string $msisdn    Subscriber MSISDN without country code prefix (e.g. "712345678")
     * @param string $amount    Amount as string (e.g. "100.00")
     * @param string $reference Unique transaction reference
     */
    public function collect(string $msisdn, string $amount, string $reference): array
    {
        $resp = Http::withToken($this->accessToken())
            ->withHeaders([
                'X-Country'  => $this->country,
                'X-Currency' => $this->currency,
            ])
            ->acceptJson()
            ->post($this->baseUrl . '/standard/v1/payments', [
                'reference' => $reference,
                'subscriber' => [
                    'country'  => $this->country,
                    'currency' => $this->currency,
                    'msisdn'   => $msisdn,
                ],
                'transaction' => [
                    'amount'   => $amount,
                    'country'  => $this->country,
                    'currency' => $this->currency,
                    'id'       => $reference,
                ],
            ]);
        $resp->throw();
        return $resp->json();
    }

    /**
     * Enquire about a transaction status.
     */
    public function enquire(string $transactionId): array
    {
        $resp = Http::withToken($this->accessToken())
            ->withHeaders([
                'X-Country'  => $this->country,
                'X-Currency' => $this->currency,
            ])
            ->acceptJson()
            ->get($this->baseUrl . '/standard/v1/payments/' . urlencode($transactionId));
        $resp->throw();
        return $resp->json();
    }
}
