<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AppUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $app_id
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $store_customer_id
 * @property string|null $name
 * @property Carbon|null $last_login_at
 */
class AppUser extends Model
{
    /** @use HasFactory<AppUserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'app_id',
        'phone',
        'email',
        'store_customer_id',
        'name',
        'last_login_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<App, $this>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * @return HasMany<PushDevice, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(PushDevice::class);
    }
}
