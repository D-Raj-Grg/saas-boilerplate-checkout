<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Cache;

class OrganizationPlan extends Pivot
{
    protected $table = 'organization_plans';

    public $incrementing = true;

    protected $fillable = [
        'organization_id',
        'plan_id',
        'user_id',
        'status',
        'is_revoked',
        'revoked_at',
        'revoked_by',
        'started_at',
        'ends_at',
        'trial_start',
        'trial_end',
        'billing_cycle',
        'quantity',
        'charging_price',
        'charging_currency',
        'purchase_uuid',
        'checkout_uuid',
        'sc_price_uuid',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'is_revoked' => 'boolean',
        'revoked_at' => 'datetime',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_start' => 'datetime',
        'trial_end' => 'datetime',
        'quantity' => 'integer',
        'charging_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the organization that owns the plan association.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the plan associated with the organization.
     *
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the user who revoked this plan association.
     *
     * @return BelongsTo<User, $this>
     */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Check if this plan association is active.
     */
    public function isActive(): bool
    {
        if ($this->is_revoked) {
            return false;
        }

        if ($this->status !== 'active') {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->started_at->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Clear related cache when plan association changes.
     */
    private function clearCache(): void
    {
        $cacheKeys = [
            "org_{$this->organization_id}_plans",
            "org_{$this->organization_id}_current_plan",
            "org_{$this->organization_id}_active_plans",
            "org_{$this->organization_id}_has_active_plan",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        Cache::tags(["org_{$this->organization_id}"])->flush();
    }

    /**
     * Boot method to clear cache on model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            $model->clearCache();
        });

        static::deleted(function ($model) {
            $model->clearCache();
        });
    }
}
