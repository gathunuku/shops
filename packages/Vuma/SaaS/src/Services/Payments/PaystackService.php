<?php

namespace Vuma\SaaS\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class PaystackService
{
    protected string $secret;
    protected string $baseUrl;

    public function __construct(?string $secret = null, ?string $baseUrl = null)
    {
        $this->secret  = $secret ?? config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));
        $this->baseUrl = rtrim($baseUrl ?? 'https://api.paystack.co', '/');
    }

    // ── Transactions ──────────────────────────────────────────────

    /**
     * Verify a transaction by reference before fulfilling an order.
     */
    public function verifyTransaction(string $reference): array
    {
        $resp = $this->get('/transaction/verify/' . urlencode($reference));
        return $resp->json();
    }

    /**
     * Initialize a transaction and get an authorization URL.
     */
    public function initializeTransaction(array $payload): array
    {
        $resp = $this->post('/transaction/initialize', $payload);
        return $resp->json();
    }

    // ── Subscriptions (Tenant Billing) ────────────────────────────

    /**
     * Create a subscription for a customer on a plan.
     */
    public function createSubscription(string $customerEmail, string $planCode, ?string $authorizationCode = null): array
    {
        $payload = [
            'customer' => $customerEmail,
            'plan'     => $planCode,
        ];
        if ($authorizationCode) {
            $payload['authorization'] = $authorizationCode;
        }
        return $this->post('/subscription', $payload)->json();
    }

    /**
     * Fetch a subscription by code.
     */
    public function fetchSubscription(string $subscriptionCode): array
    {
        return $this->get('/subscription/' . urlencode($subscriptionCode))->json();
    }

    /**
     * Disable (cancel) a subscription.
     */
    public function cancelSubscription(string $subscriptionCode, string $emailToken): array
    {
        return $this->post('/subscription/disable', [
            'code'  => $subscriptionCode,
            'token' => $emailToken,
        ])->json();
    }

    /**
     * Create or retrieve a Paystack customer.
     */
    public function createCustomer(string $email, string $firstName, string $lastName, ?string $phone = null): array
    {
        $payload = ['email' => $email, 'first_name' => $firstName, 'last_name' => $lastName];
        if ($phone) $payload['phone'] = $phone;
        return $this->post('/customer', $payload)->json();
    }

    // ── Signature Verification ────────────────────────────────────

    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        $secret = config('services.paystack.webhook_secret', env('PAYSTACK_WEBHOOK_SECRET', ''));
        $computed = hash_hmac('sha512', $rawBody, $secret);
        return hash_equals($computed, $signature);
    }

    // ── Internals ─────────────────────────────────────────────────

    protected function get(string $path): Response
    {
        $resp = Http::withToken($this->secret)
            ->acceptJson()
            ->get($this->baseUrl . $path);
        $resp->throw();
        return $resp;
    }

    protected function post(string $path, array $payload = []): Response
    {
        $resp = Http::withToken($this->secret)
            ->acceptJson()
            ->post($this->baseUrl . $path, $payload);
        $resp->throw();
        return $resp;
    }
}
