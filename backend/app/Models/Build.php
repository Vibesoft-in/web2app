<?php

namespace App\Models;

use App\Enums\BuildStatus;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\BuildFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $ulid
 * @property int $tenant_id
 * @property int $app_id
 * @property int $config_version
 * @property BuildStatus $status
 * @property string|null $logs_path
 * @property string|null $artifact_path
 * @property int $version_code
 * @property string $version_name
 * @property string|null $error
 */
class Build extends Model
{
    /** @use HasFactory<BuildFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ulid',
        'tenant_id',
        'app_id',
        'config_version',
        'status',
        'logs_path',
        'artifact_path',
        'version_code',
        'version_name',
        'error',
    ];

    protected static function booted(): void
    {
        static::creating(function (Build $build): void {
            if (empty($build->ulid)) {
                $build->ulid = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BuildStatus::class,
            'config_version' => 'integer',
            'version_code' => 'integer',
        ];
    }

    /**
     * Attempt transition to a new build state.
     */
    public function transitionTo(BuildStatus $nextStatus, ?string $error = null): void
    {
        if (! $this->status->canTransitionTo($nextStatus)) {
            throw new InvalidArgumentException(
                "Invalid build state transition from [{$this->status->value}] to [{$nextStatus->value}]."
            );
        }

        $this->status = $nextStatus;
        if ($error !== null) {
            $this->error = $error;
        }
        $this->save();
    }

    /**
     * @return BelongsTo<App, $this>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
