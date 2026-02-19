<?php

namespace Vuma\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'amount_cents',
        'currency',
        'channel',              // mpesa_ke | mtn_momo | airtel_money | paystack
        'status',               // pending | sent | paid | failed | void
        'due_at',
        'paid_at',
        'attempts',
        'last_attempt_at',
        'provider_reference',
        'meta',                 // { msisdn, narration, ... }
    ];

    protected $casts = [
        'due_at'          => 'datetime',
        'paid_at'         => 'datetime',
        'last_attempt_at' => 'datetime',
        'meta'            => 'array',
        'amount_cents'    => 'integer',
        'attempts'        => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function getAmountFormattedAttribute(): string
    {
        return number_format($this->amount_cents / 100, 2);
    }

    public function markPaid(string $providerReference): void
    {
        $this->update([
            'status'             => 'paid',
            'paid_at'            => now(),
            'provider_reference' => $providerReference,
        ]);
    }
}
