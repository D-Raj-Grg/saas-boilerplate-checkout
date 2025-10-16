<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $organization_id
 * @property int $user_id
 * @property int|null $organization_plan_id
 * @property string $gateway
 * @property float $amount
 * @property string $currency
 * @property string $status
 * @property string|null $gateway_transaction_id
 * @property array|null $gateway_response
 * @property array|null $metadata
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Payment extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'organization_id',
        'user_id',
        'organization_plan_id',
        'gateway',
        'amount',
        'currency',
        'status',
        'gateway_transaction_id',
        'gateway_response',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'id',
        'organization_id',
        'user_id',
        'organization_plan_id',
    ];

    /**
     * Get the organization that owns the payment.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who initiated the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization plan this payment is for.
     */
    public function organizationPlan(): BelongsTo
    {
        return $this->belongsTo(OrganizationPlan::class);
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment was refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
}
