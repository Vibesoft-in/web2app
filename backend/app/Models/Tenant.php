<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property int|null $owner_user_id
 * @property string $status
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ulid',
        'name',
        'owner_user_id',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (empty($tenant->ulid)) {
                $tenant->ulid = (string) Str::ulid();
            }
        });
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<App, $this>
     */
    public function apps(): HasMany
    {
        return $this->hasMany(App::class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Check if tenant has an active subscription.
     */
    public function hasActiveSubscription(?int $appId = null): bool
    {
        $query = $this->subscriptions()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', now());
            });

        if ($appId !== null) {
            $query->where(function ($q) use ($appId) {
                $q->where('app_id', $appId)
                    ->orWhereNull('app_id');
            });
        }

        return $query->exists();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
