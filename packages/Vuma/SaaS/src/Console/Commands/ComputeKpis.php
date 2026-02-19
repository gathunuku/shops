<?php

namespace Vuma\SaaS\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Vuma\SaaS\Models\KpiDaily;
use Vuma\SaaS\Models\Tenant;
use Vuma\SaaS\Models\Invoice;

class ComputeKpis extends Command
{
    protected $signature   = 'saas:compute-kpis {--date= : Date to compute (Y-m-d, default: yesterday)}';
    protected $description = 'Compute daily KPI snapshots for the reporting dashboard';

    public function handle(): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : now()->subDay();

        $dateStr = $date->toDateString();

        $this->info("Computing KPIs for {$dateStr}...");

        $activeTenants    = Tenant::where('status', 'active')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();
        $newTenants       = Tenant::whereDate('created_at', $dateStr)->count();

        $invoicesPaid   = Invoice::where('status', 'paid')->whereDate('paid_at', $dateStr)->count();
        $invoicesFailed = Invoice::where('status', 'failed')->whereDate('last_attempt_at', $dateStr)->count();
        $mrrCents       = Invoice::where('status', 'paid')->whereDate('paid_at', $dateStr)->sum('amount_cents');

        // GMV: sum of Bagisto orders if model exists
        $gmvCents = 0;
        if (class_exists(\Webkul\Sales\Models\Order::class)) {
            $gmvCents = (int) \Webkul\Sales\Models\Order::query()
                ->whereDate('created_at', $dateStr)
                ->sum(DB::raw('base_grand_total * 100'));
        }

        KpiDaily::updateOrCreate(
            ['date' => $dateStr],
            [
                'active_tenants'    => $activeTenants,
                'new_tenants'       => $newTenants,
                'suspended_tenants' => $suspendedTenants,
                'gmv_cents'         => $gmvCents,
                'mrr_cents'         => (int) $mrrCents,
                'invoices_paid'     => $invoicesPaid,
                'invoices_failed'   => $invoicesFailed,
            ]
        );

        $this->info("KPIs saved for {$dateStr}: {$activeTenants} active tenants, MRR: " . number_format($mrrCents / 100, 2));

        return self::SUCCESS;
    }
}
