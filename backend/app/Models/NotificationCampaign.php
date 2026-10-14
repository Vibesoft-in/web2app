<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $app_id
 * @property string $title
 * @property string $body
 * @property string|null $deep_link
 * @property array<string, mixed>|null $audience
 * @property Carbon|null $scheduled_at
 * @property string $status
 * @property int $sent_count
 * @property int $failed_count
 */
class NotificationCampaign extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'app_id',
        'title',
        'body',
        'deep_link',
        'audience',
        'scheduled_at',
        'status',
        'sent_count',
        'failed_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => 'array',
            'scheduled_at' => 'datetime',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
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
