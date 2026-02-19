/**
 * VumaShops – Cloudflare Worker
 * Edge HTML caching for public storefront pages.
 * Deploy to your Cloudflare zone and apply to: *.shops.vumacloud.com/*
 */

const CACHEABLE_METHODS = ['GET', 'HEAD'];

// Paths that should never be cached
const BYPASS_PATTERNS = [
    /^\/admin/,
    /^\/super-admin/,
    /^\/billing/,
    /^\/checkout/,
    /^\/webhooks/,
    /^\/cart/,
    /^\/account/,
    /^\/login/,
    /^\/register/,
    /\?.*$/,     // Any query string = bypass (handles search, filters)
];

const CACHEABLE_EXTENSIONS = /\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf)$/i;

export default {
    async fetch(request, env, ctx) {
        const url    = new URL(request.url);
        const path   = url.pathname;
        const method = request.method;

        // Always pass through non-GET requests
        if (!CACHEABLE_METHODS.includes(method)) {
            return fetch(request);
        }

        // Static assets: long cache, bypass worker logic
        if (CACHEABLE_EXTENSIONS.test(path)) {
            return fetch(request);
        }

        // Bypass dynamic paths
        const shouldBypass = BYPASS_PATTERNS.some(p => p.test(path));
        if (shouldBypass) {
            return fetch(request);
        }

        // Check Cloudflare cache
        const cache    = caches.default;
        const cacheKey = new Request(url.toString(), request);
        let response   = await cache.match(cacheKey);

        if (response) {
            // Cache hit
            const headers = new Headers(response.headers);
            headers.set('X-Cache', 'HIT');
            return new Response(response.body, { ...response, headers });
        }

        // Cache miss — fetch from origin
        response = await fetch(request);

        // Only cache successful HTML responses
        const contentType = response.headers.get('content-type') ?? '';
        const cacheControl = response.headers.get('cache-control') ?? '';

        if (
            response.ok &&
            contentType.includes('text/html') &&
            !cacheControl.includes('no-store') &&
            !cacheControl.includes('private')
        ) {
            const responseToCache = new Response(response.clone().body, {
                status:  response.status,
                headers: response.headers,
            });

            // Override cache headers for edge: 5 minutes
            const newHeaders = new Headers(responseToCache.headers);
            newHeaders.set('Cache-Control', 'public, max-age=300, s-maxage=300');
            newHeaders.set('X-Cache', 'MISS');

            const cachedResponse = new Response(responseToCache.body, {
                status:  responseToCache.status,
                headers: newHeaders,
            });

            ctx.waitUntil(cache.put(cacheKey, cachedResponse.clone()));
            return cachedResponse;
        }

        return response;
    }
};
