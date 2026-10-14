<?php

namespace App\Services\Billing;

use App\Exceptions\EntitlementException;
use App\Models\App;
use App\Models\Build;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\UsageEvent;
use Illuminate\Support\Carbon;

class EntitlementService
{
    public const GRACE_PERIOD_DAYS = 7;

    /**
     * Check if a tenant has entitlement for a feature or limit.
     */
    public function can(Tenant $tenant, string $feature, ?App $app = null): bool
    {
        // 1. Tenant suspension check
        if ($tenant->status === 'suspended') {
            return false;
        }

        // 2. Fetch active or relevant subscription
        $sub = $this->resolveSubscription($tenant, $app);

        // 3. If no subscription, check if tenant is on trial
        if (! $sub) {
            if ($tenant->status === 'trial') {
                return $this->checkTrialEntitlement($feature, $tenant);
            }

            return false;
        }

        // 4. Expiry and Grace period evaluation
        if ($this->isFeatureLocked($tenant, $app)) {
            return false;
        }

        $plan = $sub->plan;
        if (! $plan) {
            return false;
        }

        $features = $plan->features ?? [];
        $limits = $plan->limits ?? [];

        // 5. Evaluate specific features and limits
        return match ($feature) {
            'build', 'builds', 'builds_per_month' => $this->canBuild($tenant, $limits, $sub),
            'push', 'push_notifications', 'push_per_month' => $this->canPush($tenant, $features, $limits, $sub),
            'apps', 'max_apps', 'create_app' => $this->canCreateApp($tenant, $limits),
            default => (bool) ($features[$feature] ?? false),
        };
    }

    /**
     * Assert entitlement or throw an exception.
     *
     * @throws EntitlementException
     */
    public function assertCan(Tenant $tenant, string $feature, ?App $app = null): void
    {
        if (! $this->can($tenant, $feature, $app)) {
            if ($this->isFeatureLocked($tenant, $app)) {
                throw new EntitlementException(
                    'Subscription has expired and grace period has ended. Please renew your plan.',
                    'FEATURE_LOCKED'
                );
            }

            throw new EntitlementException(
                "Access to feature or limit [{$feature}] is not permitted on your current plan.",
                'LIMIT_EXCEEDED'
            );
        }
    }

    /**
     * Check if the subscription is currently within its 7-day grace period.
     */
    public function isInGracePeriod(Tenant $tenant, ?App $app = null): bool
    {
        $sub = $this->resolveSubscription($tenant, $app);
        if (! $sub || ! $sub->current_period_end) {
            return false;
        }

        $now = now();
        $end = $sub->current_period_end;

        return $now->isAfter($end) && $now->isBefore($end->copy()->addDays(self::GRACE_PERIOD_DAYS));
    }

    /**
     * Check if subscription has expired past the 7-day grace period (feature lock).
     */
    public function isFeatureLocked(Tenant $tenant, ?App $app = null): bool
    {
        $sub = $this->resolveSubscription($tenant, $app);
        if (! $sub) {
            return $tenant->status !== 'trial';
        }

        if (in_array($sub->status, ['cancelled', 'expired'], true)) {
            return true;
        }

        if (! $sub->current_period_end) {
            return $sub->status !== 'active';
        }

        $graceEnd = $sub->current_period_end->copy()->addDays(self::GRACE_PERIOD_DAYS);

        return now()->isAfter($graceEnd);
    }

    /**
     * Resolve the applicable subscription for tenant/app.
     */
    public function resolveSubscription(Tenant $tenant, ?App $app = null): ?Subscription
    {
        return $tenant->subscriptions()
            ->when($app, function ($q) use ($app) {
                $q->where(function ($subQ) use ($app) {
                    $subQ->where('app_id', $app->id)
                        ->orWhereNull('app_id');
                });
            })
            ->latest('id')
            ->first();
    }

    /**
     * Check build limit for the billing period.
     *
     * @param  array<string, mixed>  $limits
     */
    protected function canBuild(Tenant $tenant, array $limits, Subscription $sub): bool
    {
        $limit = $limits['builds_per_month'] ?? null;
        if ($limit === null) {
            return true; // unlimited
        }

        $periodStart = $this->getPeriodStart($sub);
        $usedBuilds = Build::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', $periodStart)
            ->count();

        return $usedBuilds < (int) $limit;
    }

    /**
     * Check push notification feature and limit for the billing period.
     *
     * @param  array<string, mixed>  $features
     * @param  array<string, mixed>  $limits
     */
    protected function canPush(Tenant $tenant, array $features, array $limits, Subscription $sub): bool
    {
        // First check feature flag
        if (! ($features['push_notifications'] ?? true)) {
            return false;
        }

        $limit = $limits['push_per_month'] ?? null;
        if ($limit === null) {
            return true;
        }

        $periodStart = $this->getPeriodStart($sub);
        $usedPush = UsageEvent::where('tenant_id', $tenant->id)
            ->where('type', 'push')
            ->where('created_at', '>=', $periodStart)
            ->sum('qty');

        return $usedPush < (int) $limit;
    }

    /**
     * Check max apps limit.
     *
     * @param  array<string, mixed>  $limits
     */
    protected function canCreateApp(Tenant $tenant, array $limits): bool
    {
        $limit = $limits['max_apps'] ?? null;
        if ($limit === null) {
            return true;
        }

        $appCount = App::where('tenant_id', $tenant->id)->count();

        return $appCount < (int) $limit;
    }

    /**
     * Trial entitlement evaluation.
     */
    protected function checkTrialEntitlement(string $feature, Tenant $tenant): bool
    {
        return match ($feature) {
            'build', 'builds', 'builds_per_month' => Build::where('tenant_id', $tenant->id)->count() < 1,
            'apps', 'max_apps', 'create_app' => App::where('tenant_id', $tenant->id)->count() < 1,
            'push', 'push_notifications', 'push_per_month' => false,
            'whatsapp' => false,
            default => false,
        };
    }

    /**
     * Compute current period start timestamp.
     */
    protected function getPeriodStart(Subscription $sub): Carbon
    {
        if ($sub->current_period_end) {
            return $sub->current_period_end->copy()->subDays(30);
        }

        return now()->startOfMonth();
    }
}
