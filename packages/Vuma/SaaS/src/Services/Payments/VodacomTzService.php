<?php

namespace Vuma\SaaS\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Vodacom Tanzania M-Pesa Open API (C2B Collections).
 *
 * Docs: https://openapiportal.m-pesa.com
 * Auth: Uses API Key + Public Key encryption to generate a session key.
 */
class VodacomTzService
{
    protected string $apiKey;
    protected string $publicKey;
    protected string $serviceProviderCode;
    protected string $baseUrl;

    public function __construct(
        ?string $apiKey              = null,
        ?string $publicKey           = null,
        ?string $serviceProviderCode = null,
        ?string $baseUrl             = null
    ) {
        $this->apiKey              = $apiKey              ?? config('services.vodacom_tz.api_key',               env('VODACOM_TZ_API_KEY'));
        $this->publicKey           = $publicKey           ?? config('services.vodacom_tz.public_key',            env('VODACOM_TZ_PUBLIC_KEY'));
        $this->serviceProviderCode = $serviceProviderCode ?? config('services.vodacom_tz.service_provider_code', env('VODACOM_TZ_SERVICE_PROVIDER_CODE'));
        $this->baseUrl             = rtrim($baseUrl       ?? config('services.vodacom_tz.base_url',              env('VODACOM_TZ_BASE_URL', 'https://openapi.m-pesa.com')), '/');
    }

    /**
     * Generate a session key by encrypting the API key with the Vodacom public key.
     * The session key is then used as the Bearer token for all API calls.
     * Cached for 23 hours (Vodacom keys typically expire in 24h).
     */
    public function sessionKey(): string
    {
        return Cache::remember('vodacom_tz_session', 23 * 3600, function () {
            // RSA encrypt the API key using the public key (PKCS1 v1.5 padding)
            $pubKey = "-----BEGIN PUBLIC KEY-----\n" .
                chunk_split(base64_decode($this->publicKey), 64, "\n") .
                "-----END PUBLIC KEY-----";

            $keyResource = openssl_pkey_get_public($pubKey);
            if (!$keyResource) {
                throw new \RuntimeException('Vodacom TZ: invalid public key.');
            }

            openssl_public_encrypt($this->apiKey, $encrypted, $keyResource, OPENSSL_PKCS1_PADDING);
            return base64_encode($encrypted);
        });
    }

    /**
     * Initiate a C2B payment request (customer pays merchant).
     *
     * @param string $msisdn         Customer MSISDN e.g. "255712345678"
     * @param string $amount         Amount as string e.g. "1000"
     * @param string $reference      Unique transaction reference (ThirdPartyConversationID)
     * @param string $callbackChannel Callback channel identifier (default: "0")
     */
    public function c2bPayment(
        string $msisdn,
        string $amount,
        string $reference,
        string $callbackChannel = '0'
    ): array {
        $resp = Http::withToken($this->sessionKey())
            ->withHeaders([
                'Origin'       => config('app.url', 'https://shops.vumacloud.com'),
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . '/sandbox/ipg/v2/vodacomTZ/c2bPayment/singleStage/', [
                'input_Amount'                    => $amount,
                'input_Country'                   => 'TZA',
                'input_Currency'                  => 'TZS',
                'input_CustomerMSISDN'            => $msisdn,
                'input_ServiceProviderCode'       => $this->serviceProviderCode,
                'input_ThirdPartyConversationID'  => $reference,
                'input_TransactionReference'      => $reference,
                'input_PurchasedItemsDesc'        => 'Order payment',
                'input_CallBackChannel'           => $callbackChannel,
            ]);
        $resp->throw();
        return $resp->json();
    }

    /**
     * Query a transaction status.
     */
    public function queryTransaction(string $queryReference, string $conversationId): array
    {
        $resp = Http::withToken($this->sessionKey())
            ->withHeaders(['Origin' => config('app.url')])
            ->get($this->baseUrl . '/sandbox/ipg/v2/vodacomTZ/queryTransactionStatus/', [
                'input_QueryReference'           => $queryReference,
                'input_ServiceProviderCode'      => $this->serviceProviderCode,
                'input_ThirdPartyConversationID' => $conversationId,
                'input_Country'                  => 'TZA',
            ]);
        $resp->throw();
        return $resp->json();
    }
}
