<?php

namespace Vuma\SaaS\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResponseCacheInvalidator
{
    /**
     * Clear the full-page cache for a product.
     * Works with spatie/laravel-responsecache or falls back to tagged cache.
     */
    public function forProduct($product): void
    {
        $tenantId = $product->tenant_id ?? null;
        $this->clearByTags(['fpc', 'products', "tenant:{$tenantId}"]);
        $this->clearSpatieCache();
    }

    public function forCategory($category): void
    {
        $tenantId = $category->tenant_id ?? null;
        $this->clearByTags(['fpc', 'categories', "tenant:{$tenantId}"]);
        $this->clearSpatieCache();
    }

    public function forCmsPage($page): void
    {
        $tenantId = $page->tenant_id ?? null;
        $this->clearByTags(['fpc', 'cms', "tenant:{$tenantId}"]);
        $this->clearSpatieCache();
    }

    protected function clearByTags(array $tags): void
    {
        try {
            Cache::tags(array_filter($tags))->flush();
        } catch (\Throwable $e) {
            // Redis tags not supported on some drivers; non-fatal
            Log::debug('ResponseCacheInvalidator: tag flush failed', ['error' => $e->getMessage()]);
        }
    }

    protected function clearSpatieCache(): void
    {
        try {
            if (class_exists(\Spatie\ResponseCache\ResponseCache::class)) {
                app(\Spatie\ResponseCache\ResponseCache::class)->clear();
            }
        } catch (\Throwable $e) {
            Log::debug('ResponseCacheInvalidator: Spatie clear failed', ['error' => $e->getMessage()]);
        }
    }
}
