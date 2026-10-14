<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PushDeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $app_id
 * @property int|null $app_user_id
 * @property string $fcm_token
 * @property string $platform
 */
class PushDevice extends Model
{
    /** @use HasFactory<PushDeviceFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'app_id',
        'app_user_id',
        'fcm_token',
        'platform',
    ];

    /**
     * @return BelongsTo<AppUser, $this>
     */
    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class);
    }
}
