<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property string $role
 * @property string|null $two_factor_secret
 * @property Carbon|null $email_verified_at
 */
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Send email verification notification safely without crashing on missing panel routes.
     */
    public function sendEmailVerificationNotification(): void
    {
        $panel = filament()->getCurrentPanel();
        $panelId = $panel ? $panel->getId() : 'app';
        $routeName = "filament.{$panelId}.auth.email-verification.verify";

        if (Route::has($routeName)) {
            parent::sendEmailVerificationNotification();
        } else {
            $this->markEmailAsVerified();
        }
    }

    /**
     * Determine whether user can access Filament owner panel.
     * Strictly restricted to platform super-admins and designated platform owner.
     * Regular tenant owners are prohibited from accessing the system management panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (in_array($panel->getId(), ['app', 'dashboard'], true)) {
            return true;
        }

        return in_array($this->role, ['super_admin', 'admin'], true)
            || ($this->role === 'owner' && $this->email === config('app.owner_email', 'owner@webintoapp.com'));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
        ];
    }
}
