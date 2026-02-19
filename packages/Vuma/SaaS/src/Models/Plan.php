<?php

namespace Vuma\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'price_cents',
        'interval',   // monthly | yearly
        'currency',
        'limits',
        'is_active',
        'paystack_plan_code',  // e.g. PLN_xxxx from Paystack
        'trial_days',
    ];

    protected $casts = [
        'limits'    => 'array',
        'is_active' => 'boolean',
        'price_cents' => 'integer',
        'trial_days'  => 'integer',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_cents / 100, 2);
    }
}
