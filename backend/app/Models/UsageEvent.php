<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\UsageEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $app_id
 * @property string $type
 * @property int $qty
 * @property float $cost
 * @property Carbon $created_at
 */
class UsageEvent extends Model
{
    /** @use HasFactory<UsageEventFactory> */
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'app_id',
        'type',
        'qty',
        'cost',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'cost' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\App, $this>
     */
    public function app(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\App::class);
    }
}
