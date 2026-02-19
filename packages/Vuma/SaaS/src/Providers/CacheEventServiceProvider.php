<?php

namespace Vuma\SaaS\Providers;

use Illuminate\Support\ServiceProvider;
use Vuma\SaaS\Observers\ProductObserver;
use Vuma\SaaS\Observers\CategoryObserver;
use Vuma\SaaS\Observers\CmsPageObserver;

class CacheEventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (class_exists(\Webkul\Product\Models\Product::class)) {
            \Webkul\Product\Models\Product::observe(ProductObserver::class);
        }
        if (class_exists(\Webkul\Category\Models\Category::class)) {
            \Webkul\Category\Models\Category::observe(CategoryObserver::class);
        }
        if (class_exists(\Webkul\CMS\Models\CmsPage::class)) {
            \Webkul\CMS\Models\CmsPage::observe(CmsPageObserver::class);
        }
    }
}
