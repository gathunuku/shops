<?php

namespace Vuma\SaaS\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MtnMomoService
{
    protected string $subscriptionKey;
    protected string $apiUser;
    protected string $apiKey;
    protected string $targetEnv;
    protected string $baseUrl;

    public function __construct(
        ?string $subscriptionKey = null,
        ?string $apiUser         = null,
        ?string $apiKey          = null,
        ?string $targetEnv       = null,
        ?string $baseUrl         = null
    ) {
        $this->subscriptionKey = $subscriptionKey ?? config('services.mtn_momo.subscription_key', env('MTN_MOMO_COLLECTION_SUB_KEY'));
        $this->apiUser         = $apiUser         ?? config('services.mtn_momo.api_user',         env('MTN_MOMO_API_USER'));
        $this->apiKey          = $apiKey          ?? config('services.mtn_momo.api_key',          env('MTN_MOMO_API_KEY'));
        $this->targetEnv       = $targetEnv       ?? config('services.mtn_momo.env',              env('MTN_MOMO_ENV', 'sandbox'));
        $this->baseUrl         = rtrim($baseUrl   ?? config('services.mtn_momo.base_url',         env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com/collection')), '/');
    }

    /**
     * Get access token (cached for 50 minutes).
     */
    public function accessToken(): string
    {
        $cacheKey = 'mtn_momo_token_' . md5($this->apiUser);
        return Cache::remember($cacheKey, 50 * 60, function () {
            $auth = base64_encode($this->apiUser . ':' . $this->apiKey);
            $resp = Http::withHeaders([
                'Authorization'              => 'Basic ' . $auth,
                'Ocp-Apim-Subscription-Key'  => $this->subscriptionKey,
            ])->post($this->baseUrl . '/token/');
            $resp->throw();
            return $resp->json('access_token');
        });
    }

    /**
     * Request To Pay (Collections).
     *
     * @param string $referenceId UUID v4 — your correlation ID
     * @param string $msisdn      Payer MSISDN (e.g. 256771234567)
     * @param string $amount      Decimal string e.g. "10.00"
     * @param string $currency    ISO 4217 e.g. "UGX"
     * @param string $callbackUrl HTTPS URL for result notification
     * @param string $payerNote   Short note to payer
     */
    public function requestToPay(
        string $referenceId,
        string $msisdn,
        string $amount,
        string $currency,
        string $callbackUrl,
        string $payerNote = 'Payment'
    ): int {
        $resp = Http::withHeaders([
            'Authorization'              => 'Bearer ' . $this->accessToken(),
            'X-Reference-Id'             => $referenceId,
            'X-Target-Environment'       => $this->targetEnv,
            'Ocp-Apim-Subscription-Key'  => $this->subscriptionKey,
            'Content-Type'               => 'application/json',
        ])->post($this->baseUrl . '/v1_0/requesttopay', [
            'amount'      => $amount,
            'currency'    => $currency,
            'externalId'  => $referenceId,
            'payer'       => ['partyIdType' => 'MSISDN', 'partyId' => $msisdn],
            'payerMessage' => $payerNote,
            'payeeNote'    => $payerNote,
            'callbackUrl'  => $callbackUrl,
        ]);

        // MTN returns 202 Accepted with no body on success
        if ($resp->status() >= 400) {
            $resp->throw();
        }

        return $resp->status();
    }

    /**
     * Get the status of a Request To Pay transaction.
     */
    public function getTransactionStatus(string $referenceId): array
    {
        $resp = Http::withHeaders([
            'Authorization'              => 'Bearer ' . $this->accessToken(),
            'X-Target-Environment'       => $this->targetEnv,
            'Ocp-Apim-Subscription-Key'  => $this->subscriptionKey,
        ])->get($this->baseUrl . '/v1_0/requesttopay/' . urlencode($referenceId));
        $resp->throw();
        return $resp->json();
    }
}
