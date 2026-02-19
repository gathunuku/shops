<?php

namespace Vuma\SaaS\Observers;

use Vuma\SaaS\Support\ResponseCacheInvalidator;

class CategoryObserver
{
    public function saved($model): void   { app(ResponseCacheInvalidator::class)->forCategory($model); }
    public function deleted($model): void { app(ResponseCacheInvalidator::class)->forCategory($model); }
}
