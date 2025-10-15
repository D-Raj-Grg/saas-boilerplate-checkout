<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'priority',
        'max_price',
        'billing_cycle',
        'is_active',
        'group',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'priority' => 'integer',
        'max_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'id',
    ];

    /**
     * Get the plan limits.
     *
     * @return HasMany<PlanLimit, $this>
     */
    public function limits(): HasMany
    {
        return $this->hasMany(PlanLimit::class);
    }

    /**
     * Check if this is a free plan.
     * Only the 'free' plan is considered a true free plan.
     * Early-bird and other zero-price plans are promotional, not free tier.
     */
    public function isFree(): bool
    {
        return $this->slug === 'free';
    }

    /**
     * Get the plan limit record for a feature.
     */
    public function getPlanLimitRecord(string $feature): ?PlanLimit
    {
        return $this->limits()->where('feature', $feature)->first();
    }

    /**
     * Convert a raw limit value to validated integer.
     *
     * @param  string  $value  Raw limit value from database
     * @param  string  $type  Feature type (limit, boolean, etc.)
     * @param  string  $feature  Feature name for logging
     * @param  array<string, mixed>  $context  Additional logging context
     */
    public static function parseLimit(string $value, string $type, string $feature, array $context = []): ?int
    {
        // Only limit-type features should have numeric limits
        if ($type !== 'limit') {
            return null;
        }

        // Handle unlimited (-1)
        if ($value === '-1') {
            return -1;
        }

        // Validate numeric value
        if (! is_numeric($value)) {
            // Log error for invalid data
            logger()->error('Invalid non-numeric value in plan_limits', array_merge([
                'feature' => $feature,
                'value' => $value,
            ], $context));

            return null;
        }

        $limit = (int) $value;

        // Ensure non-negative values (except -1 for unlimited)
        return $limit >= 0 ? $limit : null;
    }

    /**
     * Get the limit for a specific resource.
     * Only works with limit-type features. Returns null for boolean features.
     */
    public function getLimit(string $resource): ?int
    {
        $planLimit = $this->getPlanLimitRecord($resource);

        if (! $planLimit) {
            return null;
        }

        return self::parseLimit(
            $planLimit->value,
            $planLimit->type,
            $resource,
            ['plan_id' => $this->id]
        );
    }

    /**
     * Check if the plan allows unlimited usage for a resource.
     */
    public function hasUnlimitedUsage(string $resource): bool
    {
        return $this->getLimit($resource) === -1;
    }

    /**
     * Check if usage is within limits.
     */
    public function isWithinLimit(string $resource, int $currentUsage): bool
    {
        $limit = $this->getLimit($resource);

        if ($limit === null) {
            return true; // No limit defined
        }

        if ($limit === -1) {
            return true; // Unlimited
        }

        return $currentUsage < $limit;
    }

    /**
     * Get the percentage of limit used.
     */
    public function getLimitUsagePercentage(string $resource, int $currentUsage): float
    {
        $limit = $this->getLimit($resource);

        if ($limit === null || $limit === -1) {
            return 0.0; // No limit or unlimited
        }

        if ($limit === 0) {
            return 100.0;
        }

        return min(($currentUsage / $limit) * 100, 100.0);
    }

    /**
     * Scope to get only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the plan price formatted.
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        return '$'.number_format($this->price, 2);
    }
}
