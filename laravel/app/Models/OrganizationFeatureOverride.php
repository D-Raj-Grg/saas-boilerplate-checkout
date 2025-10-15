<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationFeatureOverride extends Model
{
    protected $table = 'organization_feature_overrides';

    protected $fillable = [
        'organization_id',
        'feature',
        'value',
        'reason',
        'approved_by',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the override.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who approved the override.
     *
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if the override is active.
     */
    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /**
     * Scope to get only active overrides.
     *
     * @param  Builder<OrganizationFeatureOverride>  $query
     * @return Builder<OrganizationFeatureOverride>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Get the numeric value of the override.
     */
    public function getNumericValue(): int
    {
        return (int) $this->value;
    }

    /**
     * Check if this is an unlimited override.
     */
    public function isUnlimited(): bool
    {
        return $this->value === '-1';
    }
}
