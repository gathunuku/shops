<?php

namespace Vuma\SaaS\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Vuma\SaaS\Models\TenantDomain;

class ResolveTenantByHost
{
    public function handle(Request $request, Closure $next): mixed
    {
        $host = strtolower($request->getHost());

        // Skip resolution for internal health-check paths
        if ($request->is('_health', '_ping')) {
            return $next($request);
        }

        $tenant = Cache::remember("tenant_host:{$host}", 300, function () use ($host) {
            $domain = TenantDomain::with('tenant.plan')->where('host', $host)->first();
            return $domain?->tenant;
        });

        if (!$tenant) {
            Log::info('Tenant not found for host', ['host' => $host]);
            abort(404, 'Store not found.');
        }

        // Bind tenant into the container for the duration of this request
        app()->instance('tenant', $tenant);

        // Set Bagisto channel/locale context if applicable
        if (app()->bound('channel') && method_exists(app('channel'), 'setCurrent')) {
            try {
                app('channel')->setCurrent($tenant->slug);
            } catch (\Throwable $e) {
                // Non-fatal; Bagisto may use default channel
            }
        }

        return $next($request);
    }
}
