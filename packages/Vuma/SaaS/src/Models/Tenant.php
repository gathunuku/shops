<?php

namespace Vuma\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',       // active | suspended | trial | cancelled
        'plan_id',
        'email',
        'phone',
        'country',
        'timezone',
        'currency',
        'trial_ends_at',
        'suspended_at',
        'meta',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'suspended_at'  => 'datetime',
        'meta'          => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function primaryDomain(): ?TenantDomain
    {
        return $this->domains()->where('is_primary', true)->first()
            ?? $this->domains()->first();
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->latest('id')
            ->first();
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended', 'suspended_at' => now()]);
    }

    public function reactivate(): void
    {
        $this->update(['status' => 'active', 'suspended_at' => null]);
    }
}
