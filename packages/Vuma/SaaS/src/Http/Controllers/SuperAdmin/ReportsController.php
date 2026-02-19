<?php

namespace Vuma\SaaS\Http\Controllers\SuperAdmin;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Vuma\SaaS\Models\KpiDaily;
use Vuma\SaaS\Models\Tenant;
use Vuma\SaaS\Models\Invoice;

class ReportsController extends Controller
{
    public function index()
    {
        $kpis = KpiDaily::orderByDesc('date')->limit(30)->get()->reverse()->values();

        $summary = [
            'active_tenants'    => Tenant::where('status', 'active')->count(),
            'suspended_tenants' => Tenant::where('status', 'suspended')->count(),
            'trial_tenants'     => Tenant::where('status', 'trial')->count(),
            'mrr_cents'         => Invoice::where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->sum('amount_cents'),
        ];

        return view('saas::super-admin.reports', compact('kpis', 'summary'));
    }

    public function kpisJson(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $kpis = KpiDaily::where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get();

        return response()->json($kpis);
    }
}
