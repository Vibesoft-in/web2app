<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AppFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $ulid
 * @property int $tenant_id
 * @property string $name
 * @property string $platform
 * @property string $package_name
 * @property string $app_key
 * @property string $setup_step
 * @property string $status
 * @property string|null $firebase_project_id
 * @property string|null $firebase_app_id
 */
class App extends Model
{
    /** @use HasFactory<AppFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ulid',
        'tenant_id',
        'name',
        'platform',
        'package_name',
        'app_key',
        'setup_step',
        'status',
        'firebase_project_id',
        'firebase_app_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (App $app): void {
            if (empty($app->ulid)) {
                $app->ulid = (string) Str::ulid();
            }
            if (empty($app->app_key)) {
                $app->app_key = 'wia_'.Str::random(32);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * @return HasOne<Connection, $this>
     */
    public function connection(): HasOne
    {
        return $this->hasOne(Connection::class);
    }

    /**
     * @return HasMany<AppConfig, $this>
     */
    public function configs(): HasMany
    {
        return $this->hasMany(AppConfig::class);
    }

    /**
     * @return HasMany<Build, $this>
     */
    public function builds(): HasMany
    {
        return $this->hasMany(Build::class);
    }

    /**
     * @return HasOne<Keystore, $this>
     */
    public function keystore(): HasOne
    {
        return $this->hasOne(Keystore::class);
    }
}
