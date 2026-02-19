<?php

namespace Vuma\SaaS\Services\Idempotency;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IdempotencyService
{
    protected int $ttlSeconds;

    public function __construct(int $ttlSeconds = 86400) // 24 hours default
    {
        $this->ttlSeconds = $ttlSeconds;
    }

    /**
     * Execute a callable only once per idempotency key.
     * Returns true if executed, false if already seen (duplicate).
     *
     * @throws \Throwable Re-throws exceptions from the callable.
     */
    public function run(string $key, callable $callback): bool
    {
        $cacheKey = 'idempotency:' . $key;

        // Atomic check-and-set using Cache::add (returns false if key already exists)
        if (!Cache::add($cacheKey, 1, $this->ttlSeconds)) {
            Log::info('Idempotency: duplicate key skipped', ['key' => $key]);
            return false;
        }

        try {
            $callback();
            return true;
        } catch (\Throwable $e) {
            // If the callback failed, delete the key so it can be retried
            Cache::forget($cacheKey);
            throw $e;
        }
    }

    /**
     * Check if a key has already been processed without executing anything.
     */
    public function isDuplicate(string $key): bool
    {
        return Cache::has('idempotency:' . $key);
    }

    /**
     * Manually mark a key as processed.
     */
    public function mark(string $key): void
    {
        Cache::put('idempotency:' . $key, 1, $this->ttlSeconds);
    }
}
