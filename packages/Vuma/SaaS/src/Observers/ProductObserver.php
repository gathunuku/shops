<?php

namespace Vuma\SaaS\Observers;

use Vuma\SaaS\Support\ResponseCacheInvalidator;

class ProductObserver
{
    public function saved($model): void    { app(ResponseCacheInvalidator::class)->forProduct($model); }
    public function deleted($model): void  { app(ResponseCacheInvalidator::class)->forProduct($model); }
}
