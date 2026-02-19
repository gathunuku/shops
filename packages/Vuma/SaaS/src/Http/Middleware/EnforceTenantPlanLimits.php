<?php

namespace Vuma\SaaS\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnforceTenantPlanLimits
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = app('tenant');
        if (!$tenant) {
            return $next($request);
        }

        $limits = (array) ($tenant->plan->limits ?? []);

        try {
            // SKU limit
            if (isset($limits['sku_max']) && class_exists(\Webkul\Product\Models\Product::class)) {
                $count = \Webkul\Product\Models\Product::query()
                    ->where('tenant_id', $tenant->id)
                    ->count();
                if ($count >= (int) $limits['sku_max']) {
                    return $this->limitResponse($request, 'SKU limit reached on your current plan. Please upgrade.');
                }
            }

            // Monthly orders limit
            if (isset($limits['orders_per_month']) && class_exists(\Webkul\Sales\Models\Order::class)) {
                $count = \Webkul\Sales\Models\Order::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count();
                if ($count >= (int) $limits['orders_per_month']) {
                    return $this->limitResponse($request, 'Monthly order limit reached. Please upgrade.');
                }
            }

            // Staff seats limit
            if (isset($limits['staff_max']) && class_exists(\App\Models\User::class)) {
                $count = \App\Models\User::query()
                    ->where('tenant_id', $tenant->id)
                    ->count();
                if ($count >= (int) $limits['staff_max']) {
                    return $this->limitResponse($request, 'Staff seat limit reached. Please upgrade.');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Plan enforcement check failed', ['error' => $e->getMessage()]);
        }

        return $next($request);
    }

    private function limitResponse(Request $request, string $message): mixed
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message, 'upgrade_required' => true], 402);
        }
        return response($message, 402);
    }
}
