<?php

namespace App\Services\Billing;

use App\Models\App;
use App\Models\Tenant;
use App\Models\UsageEvent;
use Illuminate\Support\Carbon;

class UsageEventService
{
    /**
     * Record a granular usage event for tenant cost tracking.
     */
    public function record(
        Tenant|int $tenant,
        string $type,
        int $qty = 1,
        float $cost = 0.0000,
        ?App $app = null
    ): UsageEvent {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return UsageEvent::create([
            'tenant_id' => $tenantId,
            'app_id' => $app?->id,
            'type' => $type,
            'qty' => max(1, $qty),
            'cost' => $cost,
            'created_at' => now(),
        ]);
    }

    /**
     * Get aggregate usage count for a tenant and event type.
     */
    public function getUsageCount(Tenant $tenant, string $type, ?Carbon $since = null): int
    {
        $query = UsageEvent::where('tenant_id', $tenant->id)
            ->where('type', $type);

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return (int) $query->sum('qty');
    }

    /**
     * Get aggregate cost for a tenant.
     */
    public function getTotalCost(Tenant $tenant, ?Carbon $since = null): float
    {
        $query = UsageEvent::where('tenant_id', $tenant->id);

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return (float) $query->sum('cost');
    }
}
