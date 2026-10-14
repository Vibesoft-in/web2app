<?php

namespace App\Support;

use App\Models\Tenant;

class TenantContext
{
    protected static ?Tenant $currentTenant = null;

    protected static ?int $currentTenantId = null;

    protected static bool $resolving = false;

    public static function set(Tenant $tenant): void
    {
        static::$currentTenant = $tenant;
        static::$currentTenantId = $tenant->id;
    }

    public static function setId(int $id): void
    {
        static::$currentTenantId = $id;
        static::$currentTenant = null;
    }

    public static function get(): ?Tenant
    {
        if (static::$currentTenant === null && static::$currentTenantId !== null) {
            static::$currentTenant = Tenant::find(static::$currentTenantId);
        }

        if (static::$currentTenant === null && ! static::$resolving) {
            static::$resolving = true;
            try {
                if (auth()->check()) {
                    $user = auth()->user();
                    if ($user && isset($user->tenant_id)) {
                        static::$currentTenantId = (int) $user->tenant_id;
                        static::$currentTenant = Tenant::find(static::$currentTenantId);
                    }
                }
            } finally {
                static::$resolving = false;
            }
        }

        return static::$currentTenant;
    }

    public static function getId(): ?int
    {
        if (static::$currentTenantId !== null) {
            return static::$currentTenantId;
        }

        if (static::$resolving) {
            return null;
        }

        static::$resolving = true;
        try {
            if (auth()->check()) {
                $user = auth()->user();
                if ($user && isset($user->tenant_id)) {
                    static::$currentTenantId = (int) $user->tenant_id;

                    return static::$currentTenantId;
                }
            }
        } finally {
            static::$resolving = false;
        }

        return null;
    }

    public static function forget(): void
    {
        static::$currentTenant = null;
        static::$currentTenantId = null;
        static::$resolving = false;
    }
}
