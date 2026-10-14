<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $app_id
 * @property string $platform
 * @property string $site_url
 * @property array<string, mixed>|null $credentials
 * @property string $status
 * @property Carbon|null $last_verified_at
 * @property string|null $last_error_code
 */
class Connection extends Model
{
    /** @use HasFactory<ConnectionFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'app_id',
        'platform',
        'site_url',
        'credentials',
        'status',
        'last_verified_at',
        'last_error_code',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'last_verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<App, $this>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
