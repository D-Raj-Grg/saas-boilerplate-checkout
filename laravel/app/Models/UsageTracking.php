<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageTracking extends Model
{
    protected $table = 'usage_tracking';

    protected $fillable = [
        'organization_id',
        'workspace_id',
        'feature',
        'current_usage',
        'period_type',
        'period_starts_at',
        'period_ends_at',
    ];

    protected $casts = [
        'current_usage' => 'integer',
        'period_starts_at' => 'datetime',
        'period_ends_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the usage tracking.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the workspace that owns the usage tracking.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Check if this tracking period is active.
     */
    public function isActive(): bool
    {
        if ($this->period_type === 'lifetime') {
            return true;
        }

        return $this->period_ends_at === null || $this->period_ends_at->isFuture();
    }

    /**
     * Scope to get only active tracking records.
     *
     * @param  Builder<UsageTracking>  $query
     * @return Builder<UsageTracking>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('period_type', 'lifetime')
                ->orWhere(function ($subQ) {
                    $subQ->where('period_ends_at', '>', now())
                        ->orWhereNull('period_ends_at');
                });
        });
    }

    /**
     * Scope to get tracking for a specific feature.
     *
     * @param  Builder<UsageTracking>  $query
     * @return Builder<UsageTracking>
     */
    public function scopeForFeature(Builder $query, string $feature): Builder
    {
        return $query->where('feature', $feature);
    }

    /**
     * Scope to get tracking for a specific workspace.
     *
     * @param  Builder<UsageTracking>  $query
     * @return Builder<UsageTracking>
     */
    public function scopeForWorkspace(Builder $query, int $workspaceId): Builder
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope to get organization-level tracking (not workspace-specific).
     *
     * @param  Builder<UsageTracking>  $query
     * @return Builder<UsageTracking>
     */
    public function scopeOrganizationLevel(Builder $query): Builder
    {
        return $query->whereNull('workspace_id');
    }

    /**
     * Increment usage safely.
     */
    public function incrementUsage(int $amount = 1): void
    {
        $this->increment('current_usage', $amount);
    }

    /**
     * Decrement usage safely.
     */
    public function decrementUsage(int $amount = 1): void
    {
        if ($this->current_usage >= $amount) {
            $this->decrement('current_usage', $amount);
        }
    }
}
