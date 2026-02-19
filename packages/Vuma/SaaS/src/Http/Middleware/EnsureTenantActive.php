<?php

namespace Vuma\SaaS\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = app('tenant');

        if (!$tenant) {
            abort(404, 'Store not found.');
        }

        if ($tenant->isSuspended()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'This store has been suspended.'], 402);
            }
            return response()->view('saas::errors.suspended', ['tenant' => $tenant], 402);
        }

        return $next($request);
    }
}
