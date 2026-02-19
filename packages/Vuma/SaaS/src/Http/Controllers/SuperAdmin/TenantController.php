<?php

namespace Vuma\SaaS\Http\Controllers\SuperAdmin;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Vuma\SaaS\Models\Tenant;
use Vuma\SaaS\Models\TenantDomain;
use Vuma\SaaS\Models\Plan;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::with('plan')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('slug', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(30);

        $plans = Plan::where('is_active', true)->get();

        return view('saas::super-admin.tenants.index', compact('tenants', 'plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'slug'    => 'required|alpha_dash|max:50|unique:tenants,slug',
            'email'   => 'required|email',
            'phone'   => 'nullable|string|max:20',
            'country' => 'required|string|size:2',
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenant = Tenant::create([
            ...$data,
            'status'   => 'trial',
            'currency' => config("regions.{$data['country']}.currency", 'USD'),
            'timezone' => 'UTC',
        ]);

        // Provision default subdomain
        $host = $tenant->slug . '.' . config('saas.base_domain', 'shops.vumacloud.com');
        TenantDomain::create([
            'tenant_id'  => $tenant->id,
            'host'       => $host,
            'is_primary' => true,
        ]);

        return redirect()->route('super-admin.tenants.index')
            ->with('success', "Tenant [{$tenant->name}] created at {$host}");
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['plan', 'domains', 'subscriptions', 'invoices' => fn ($q) => $q->latest()->limit(10)]);
        return view('saas::super-admin.tenants.show', compact('tenant'));
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->suspend();
        $this->flushTenantCache($tenant);
        return back()->with('success', "Tenant [{$tenant->name}] suspended.");
    }

    public function reactivate(Tenant $tenant)
    {
        $tenant->reactivate();
        $this->flushTenantCache($tenant);
        return back()->with('success', "Tenant [{$tenant->name}] reactivated.");
    }

    public function addDomain(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'host' => 'required|string|max:253|unique:tenant_domains,host',
        ]);

        TenantDomain::create([
            'tenant_id'  => $tenant->id,
            'host'       => strtolower($data['host']),
            'is_primary' => false,
        ]);

        $this->flushTenantCache($tenant);

        return back()->with('success', "Domain [{$data['host']}] added.");
    }

    public function removeDomain(TenantDomain $domain)
    {
        $tenant = $domain->tenant;
        $domain->delete();
        $this->flushTenantCache($tenant);
        return back()->with('success', "Domain removed.");
    }

    private function flushTenantCache(Tenant $tenant): void
    {
        foreach ($tenant->domains as $domain) {
            Cache::forget("tenant_host:{$domain->host}");
        }
    }
}
