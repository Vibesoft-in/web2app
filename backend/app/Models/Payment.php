<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $razorpay_payment_id
 * @property float $amount
 * @property string $currency
 * @property string $status
 * @property array<string, mixed>|null $raw
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'razorpay_payment_id',
        'amount',
        'currency',
        'status',
        'raw',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'raw' => 'array',
        ];
    }
}
