<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $actor_id
 * @property string $action
 * @property string|null $target
 * @property string|null $ip
 * @property string|null $user_agent
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 */
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'actor_id',
        'action',
        'target',
        'ip',
        'user_agent',
        'meta',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
