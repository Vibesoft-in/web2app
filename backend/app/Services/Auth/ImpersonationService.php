<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ImpersonationService
{
    public const SESSION_KEY = 'impersonator_id';

    /**
     * Start impersonating a target user and record audit log.
     */
    public function start(User $impersonator, User $target): void
    {
        session()->put(self::SESSION_KEY, $impersonator->id);

        AuditLog::create([
            'tenant_id' => $target->tenant_id,
            'actor_id' => $impersonator->id,
            'action' => 'impersonation_start',
            'target' => "User #{$target->id} ({$target->email})",
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta' => [
                'impersonator_email' => $impersonator->email,
                'target_tenant_id' => $target->tenant_id,
            ],
            'created_at' => now(),
        ]);

        Auth::login($target);
    }

    /**
     * Stop impersonating and restore original user session.
     */
    public function leave(): ?User
    {
        $impersonatorId = session()->get(self::SESSION_KEY);
        if (! $impersonatorId) {
            return null;
        }

        /** @var User|null $impersonator */
        $impersonator = User::withoutGlobalScopes()->find($impersonatorId);
        $currentUser = Auth::user();

        AuditLog::create([
            'tenant_id' => $currentUser?->tenant_id ?? $impersonator?->tenant_id,
            'actor_id' => $impersonator?->id,
            'action' => 'impersonation_leave',
            'target' => $impersonator ? "Returned to {$impersonator->email}" : 'Impersonation ended',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta' => [
                'ended_by_user_id' => $currentUser?->id,
            ],
            'created_at' => now(),
        ]);

        session()->forget(self::SESSION_KEY);

        if ($impersonator) {
            Auth::login($impersonator);
        }

        return $impersonator;
    }

    /**
     * Determine if current session is an active impersonation.
     */
    public function isImpersonating(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    /**
     * Get the original administrator performing the impersonation.
     */
    public function getImpersonator(): ?User
    {
        $id = session()->get(self::SESSION_KEY);

        return $id ? User::withoutGlobalScopes()->find($id) : null;
    }
}
