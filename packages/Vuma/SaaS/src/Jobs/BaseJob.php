<?php

namespace Vuma\SaaS\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Vuma\SaaS\Services\Idempotency\IdempotencyService;

abstract class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30; // seconds between retries

    public function __construct(protected string $idempotencyKey) {}

    /**
     * Wrap logic in idempotency guard.
     * Returns true if executed, false if duplicate.
     */
    protected function withIdempotency(callable $callback): bool
    {
        return app(IdempotencyService::class)->run($this->idempotencyKey, $callback);
    }
}
