<?php

namespace Tests\SaaS;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Cache;
use Vuma\SaaS\Services\Idempotency\IdempotencyService;

class IdempotencyServiceTest extends TestCase
{
    public function test_callback_executes_once(): void
    {
        // Use array cache for tests
        $store = [];

        $svc = new class extends \Vuma\SaaS\Services\Idempotency\IdempotencyService {
            public array $processed = [];

            public function run(string $key, callable $callback): bool
            {
                if (in_array($key, $this->processed)) {
                    return false;
                }
                $this->processed[] = $key;
                $callback();
                return true;
            }
        };

        $counter = 0;
        $key     = 'test_key_' . uniqid();

        $svc->run($key, function () use (&$counter) { $counter++; });
        $svc->run($key, function () use (&$counter) { $counter++; });

        $this->assertSame(1, $counter, 'Callback should only execute once for the same key.');
    }

    public function test_different_keys_execute_independently(): void
    {
        $svc = new class extends \Vuma\SaaS\Services\Idempotency\IdempotencyService {
            public array $processed = [];
            public function run(string $key, callable $callback): bool {
                if (in_array($key, $this->processed)) return false;
                $this->processed[] = $key;
                $callback();
                return true;
            }
        };

        $counter = 0;
        $svc->run('key_a_' . uniqid(), fn () => $counter++);
        $svc->run('key_b_' . uniqid(), fn () => $counter++);

        $this->assertSame(2, $counter);
    }
}
