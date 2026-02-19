<?php

namespace Vuma\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

class KpiDaily extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'date',
        'active_tenants',
        'new_tenants',
        'suspended_tenants',
        'gmv_cents',
        'mrr_cents',
        'invoices_paid',
        'invoices_failed',
    ];

    protected $casts = [
        'date'               => 'date',
        'active_tenants'     => 'integer',
        'new_tenants'        => 'integer',
        'suspended_tenants'  => 'integer',
        'gmv_cents'          => 'integer',
        'mrr_cents'          => 'integer',
        'invoices_paid'      => 'integer',
        'invoices_failed'    => 'integer',
    ];
}
