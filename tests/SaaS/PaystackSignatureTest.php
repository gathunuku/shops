<?php

namespace Tests\SaaS;

use PHPUnit\Framework\TestCase;
use Vuma\SaaS\Services\Payments\PaystackService;

class PaystackSignatureTest extends TestCase
{
    public function test_valid_signature_is_accepted(): void
    {
        $secret  = 'test_webhook_secret';
        $payload = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'test_ref']]);
        $sig     = hash_hmac('sha512', $payload, $secret);

        $svc = new PaystackService('sk_test_dummy');

        // Inject secret via config (in tests, set env)
        putenv("PAYSTACK_WEBHOOK_SECRET={$secret}");

        $this->assertTrue($svc->verifyWebhookSignature($payload, $sig));
    }

    public function test_invalid_signature_is_rejected(): void
    {
        putenv('PAYSTACK_WEBHOOK_SECRET=test_webhook_secret');

        $svc = new PaystackService('sk_test_dummy');
        $this->assertFalse($svc->verifyWebhookSignature('{}', 'bad_signature'));
    }
}
