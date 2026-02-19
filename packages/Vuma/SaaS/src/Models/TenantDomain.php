<?php

namespace Vuma\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDomain extends Model
{
    protected $fillable = ['tenant_id', 'host', 'is_primary', 'ssl_provisioned'];

    protected $casts = [
        'is_primary'       => 'boolean',
        'ssl_provisioned'  => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
