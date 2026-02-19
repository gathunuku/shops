<?php

namespace Vuma\SaaS\Observers;

use Vuma\SaaS\Support\ResponseCacheInvalidator;

class CmsPageObserver
{
    public function saved($model): void   { app(ResponseCacheInvalidator::class)->forCmsPage($model); }
    public function deleted($model): void { app(ResponseCacheInvalidator::class)->forCmsPage($model); }
}
