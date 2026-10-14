<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            if (empty($model->tenant_id)) {
                $tenantId = TenantContext::getId();
                if ($tenantId !== null) {
                    $model->tenant_id = $tenantId;
                } elseif ($model instanceof User) {
                    $tenant = Tenant::create([
                        'name' => ($model->name ?: 'Customer')."'s Workspace",
                        'status' => 'trial',
                    ]);
                    $model->tenant_id = $tenant->id;
                }
            }
        });

        static::created(function ($model): void {
            if ($model instanceof User && $model->tenant_id) {
                Tenant::where('id', $model->tenant_id)
                    ->whereNull('owner_user_id')
                    ->update(['owner_user_id' => $model->id]);
            }
        });
    }

    /**
     * Get the tenant that owns this model.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
