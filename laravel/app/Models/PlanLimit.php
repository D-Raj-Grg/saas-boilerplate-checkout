<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanLimit extends Model
{
    protected $fillable = [
        'plan_id',
        'feature',
        'value',
        'type',
    ];

    /**
     * Get the plan that owns the limit.
     *
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the feature configuration.
     */
    public function featureConfig(): ?PlanFeature
    {
        return PlanFeature::where('feature', $this->feature)->first();
    }

    /**
     * Get the numeric value of the limit.
     */
    public function getNumericValue(): int
    {
        return (int) $this->value;
    }

    /**
     * Check if this is an unlimited feature.
     */
    public function isUnlimited(): bool
    {
        return $this->value === '-1';
    }

    /**
     * Check if this feature is enabled.
     */
    public function isEnabled(): bool
    {
        $config = $this->featureConfig();

        if (! $config) {
            return false;
        }

        // For boolean features
        if ($config->type === 'boolean') {
            return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
        }

        // For limit features, any positive value or -1 means enabled
        return $this->getNumericValue() > 0 || $this->isUnlimited();
    }
}
