<?php

namespace App\Models\Scopes;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ($model instanceof User) {
            return;
        }

        $tenantId = TenantContext::getId();

        if ($tenantId !== null) {
            $builder->where($model->qualifyColumn('tenant_id'), '=', $tenantId);
        }
    }
}
