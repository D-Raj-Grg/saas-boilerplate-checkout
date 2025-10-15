<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanFeature extends Model
{
    protected $fillable = [
        'feature',
        'name',
        'description',
        'type',
        'period',
        'category',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Scope to get only active features.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<PlanFeature>  $query
     * @return \Illuminate\Database\Eloquent\Builder<PlanFeature>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get features by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<PlanFeature>  $query
     * @return \Illuminate\Database\Eloquent\Builder<PlanFeature>
     */
    public function scopeCategory(\Illuminate\Database\Eloquent\Builder $query, string $category): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Check if feature is a limit type.
     */
    public function isLimit(): bool
    {
        return $this->type === 'limit';
    }

    /**
     * Check if feature is a boolean type.
     */
    public function isBoolean(): bool
    {
        return $this->type === 'boolean';
    }

    /**
     * Check if feature has a period-based limit.
     */
    public function isPeriodBased(): bool
    {
        return $this->period !== 'lifetime';
    }
}
