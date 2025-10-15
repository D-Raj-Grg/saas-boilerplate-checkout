<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceFeatureLimit extends Model
{
    protected $table = 'workspace_feature_limits';

    protected $fillable = [
        'workspace_id',
        'organization_id',
        'feature',
        'allocated',
    ];

    protected $casts = [
        'allocated' => 'integer',
    ];

    /**
     * Get the workspace that owns the limit.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the organization that owns the limit.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Check if this is an unlimited allocation.
     */
    public function isUnlimited(): bool
    {
        return $this->allocated === -1;
    }

    /**
     * Get remaining allocation for this workspace.
     */
    public function getRemaining(): ?int
    {
        if ($this->isUnlimited()) {
            return null;
        }

        $used = UsageTracking::where('workspace_id', $this->workspace_id)
            ->where('feature', $this->feature)
            ->active()
            ->sum('current_usage');

        return (int) max(0, $this->allocated - $used);
    }

    /**
     * Get usage percentage for this allocation.
     */
    public function getUsagePercentage(): float
    {
        if ($this->isUnlimited() || $this->allocated === 0) {
            return 0.0;
        }

        $used = UsageTracking::where('workspace_id', $this->workspace_id)
            ->where('feature', $this->feature)
            ->active()
            ->sum('current_usage');

        return min(100, ($used / $this->allocated) * 100);
    }
}
