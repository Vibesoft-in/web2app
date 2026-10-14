<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\KeystoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $app_id
 * @property string $path
 * @property string $alias
 * @property array<string, mixed> $passwords
 * @property string|null $sha1
 * @property string|null $sha256
 */
class Keystore extends Model
{
    /** @use HasFactory<KeystoreFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'app_id',
        'path',
        'alias',
        'passwords',
        'sha1',
        'sha256',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'passwords' => 'encrypted:array',
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
