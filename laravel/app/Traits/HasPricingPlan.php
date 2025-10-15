<?php

namespace App\Traits;

use App\Models\OrganizationFeatureOverride;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Workspace;
use App\Models\WorkspaceFeatureLimit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait HasPricingPlan
{
    /**
     * Check if organization has a specific feature.
     * Returns true if ANY active plan has the feature.
     */
    public function hasFeature(string $feature): bool
    {
        return Cache::remember("org_{$this->id}_has_{$feature}", 60, function () use ($feature) {
            // Check for organization override first
            $override = $this->overrides()
                ->where('feature', $feature)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($override) {
                $featureConfig = PlanFeature::where('feature', $feature)->first();
                if ($featureConfig) {
                    if ($featureConfig->type === 'boolean') {
                        return filter_var($override->value, FILTER_VALIDATE_BOOLEAN);
                    }

                    // For limit features, having any positive limit means having the feature
                    return (int) $override->value > 0 || (int) $override->value === -1;
                }
            }

            // Check all active plans for this feature
            $activePlans = $this->activePlans;

            foreach ($activePlans as $plan) {
                $planLimits = $plan->limits()->get();
                $limit = $planLimits->firstWhere('feature', $feature);

                if ($limit) {
                    // For boolean features
                    if ($limit->type === 'boolean') {
                        if (filter_var($limit->value, FILTER_VALIDATE_BOOLEAN)) {
                            return true;
                        }
                    } else {
                        // For limit features, having any positive limit means having the feature
                        if ((int) $limit->value > 0 || (int) $limit->value === -1) {
                            return true;
                        }
                    }
                }
            }

            return false;
        });
    }

    /**
     * Get the aggregated limit for a specific feature across all active plans.
     *
     * Aggregation rules:
     * - For unlimited (-1) values: if ANY plan has unlimited, return unlimited
     * - For additive features (events, api_calls): sum all limits
     * - For maximum features (projects, team_members): take highest limit
     * - Returns null for boolean features or if no limits found
     */
    public function getLimit(string $feature): ?int
    {
        return Cache::remember("org_{$this->id}_limit_{$feature}", 60, function () use ($feature) {
            // Check for organization override first
            $override = $this->overrides()
                ->where('feature', $feature)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($override) {
                $featureConfig = PlanFeature::where('feature', $feature)->first();
                if ($featureConfig) {
                    return Plan::parseLimit(
                        $override->value,
                        $featureConfig->type,
                        $feature,
                        ['organization_id' => $this->id]
                    );
                }
            }

            // Get limits from all active plans
            $activePlans = $this->activePlans;
            $limits = [];

            foreach ($activePlans as $plan) {
                $planLimits = $plan->limits()->get();

                foreach ($planLimits as $limit) {
                    if ($limit->feature === $feature) {
                        $parsedLimit = Plan::parseLimit(
                            $limit->value,
                            $limit->type,
                            $feature,
                            ['organization_id' => $this->id]
                        );
                        if ($parsedLimit !== null) {
                            $limits[] = $parsedLimit;
                        }
                    }
                }
            }

            if (empty($limits)) {
                return null;
            }

            // If any plan has unlimited (-1), return unlimited
            if (in_array(-1, $limits)) {
                return -1;
            }

            // Apply aggregation rules based on feature type
            return $this->aggregateLimits($feature, $limits);
        });
    }

    /**
     * Aggregate limits based on feature type.
     * Based on PlanFeatureSeeder as source of truth.
     *
     * @param  array<int>  $limits
     */
    private function aggregateLimits(string $feature, array $limits): int
    {
        // Features that should be summed (additive) - monthly tracking features
        $additiveFeatures = [
            'unique_visitors',        // monthly organization tracking
        ];

        // Features that should use maximum value - all other limit features
        $maximumFeatures = [
            // Organization & workspace limits
            'team_members',                   // lifetime organization
            'workspaces',                     // lifetime organization
            'connections_per_workspace',      // lifetime workspace

            // API & performance (organization scoped)
            'api_rate_limit',                 // lifetime organization
            'data_retention_days',            // lifetime organization
        ];

        if (in_array($feature, $additiveFeatures)) {
            // Sum all limits for monthly tracking features
            return array_sum($limits);
        } elseif (in_array($feature, $maximumFeatures)) {
            // Take maximum limit for capacity features
            return empty($limits) ? 0 : max($limits);
        } else {
            // Default behavior: take maximum (for any unlisted features)
            return empty($limits) ? 0 : max($limits);
        }
    }

    /**
     * Get feature limit record with type information from active plans.
     * Returns the first limit found across active plans.
     *
     * @return array{value: string, type: string}|null
     */
    private function getFeatureLimit(string $feature): ?array
    {
        // Check for active organization override first
        /** @var OrganizationFeatureOverride|null $override */
        $override = $this->overrides()
            ->where('feature', $feature)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($override) {
            // For overrides, we need to get the type from plan_features
            // since overrides table doesn't have type column
            $featureConfig = PlanFeature::where('feature', $feature)->first();

            return $featureConfig ? [
                'value' => $override->value,
                'type' => $featureConfig->type,
            ] : null;
        }

        // Get from active plans with type information
        $activePlans = $this->activePlans;

        foreach ($activePlans as $plan) {
            $planLimits = $plan->limits()->get();
            $limit = $planLimits->firstWhere('feature', $feature);

            if ($limit) {
                return [
                    'value' => $limit->value,
                    'type' => $limit->type,
                ];
            }
        }

        return null;
    }

    /**
     * Check if organization can use a feature (considering current usage).
     */
    public function canUse(string $feature, int $required = 1, ?Workspace $workspace = null): bool
    {
        $limitRecord = $this->getFeatureLimit($feature);

        if (! $limitRecord) {
            return false;
        }

        $type = $limitRecord['type'];

        // For boolean features, use hasFeature() directly
        if ($type === 'boolean') {
            return $this->hasFeature($feature);
        }

        // For limit features, parse the limit from the record we already have
        $limit = Plan::parseLimit($limitRecord['value'], $limitRecord['type'], $feature);

        // No limit defined or unlimited
        if ($limit === null || $limit === -1) {
            return true;
        }

        // Check workspace-specific allocation if workspace provided
        if ($workspace) {
            $workspaceLimit = WorkspaceFeatureLimit::where('workspace_id', $workspace->id)
                ->where('feature', $feature)
                ->first();

            if ($workspaceLimit) {
                // Check against workspace's allocated limit
                $currentUsage = $this->getCurrentUsage($feature, $workspace);

                return ($currentUsage + $required) <= $workspaceLimit->allocated;
            }
        }

        // Check against organization limit
        $currentUsage = $this->getCurrentUsage($feature, $workspace);

        return ($currentUsage + $required) <= $limit;
    }

    /**
     * Get current usage for a feature.
     */
    public function getCurrentUsage(string $feature, ?Workspace $workspace = null): int
    {
        $cacheKey = $workspace
            ? "org_{$this->id}_workspace_{$workspace->id}_usage_{$feature}"
            : "org_{$this->id}_usage_{$feature}";

        return Cache::remember($cacheKey, 30, function () use ($feature, $workspace) {
            // Special handling for team_members - count actual organization members + pending invitations + manual tracking
            // team_members is organization-scoped, so always count at org level regardless of workspace param
            if ($feature === 'team_members') {
                $organizationMembers = $this->users()->count();
                $pendingOrganizationInvitations = \App\Models\Invitation::where('organization_id', $this->id)
                    ->where('status', 'pending')
                    ->count();

                // Also include any manual consumption tracking (useful for testing or manual adjustments)
                $manualTracking = (int) $this->usageTracking()
                    ->where('feature', 'team_members')
                    ->where('period_type', 'lifetime')
                    ->sum('current_usage');

                return $organizationMembers + $pendingOrganizationInvitations + $manualTracking;
            }

            $featureConfig = PlanFeature::where('feature', $feature)->first();
            if (! $featureConfig) {
                return 0;
            }

            $query = $this->usageTracking()->where('feature', $feature);

            // Filter by workspace if provided
            if ($workspace) {
                $query->where('workspace_id', $workspace->id);
            }

            // Filter by period
            if ($featureConfig->period !== 'lifetime') {
                $query->where('period_ends_at', '>', now());
            }

            return (int) $query->sum('current_usage');
        });
    }

    /**
     * Consume feature usage.
     */
    public function consumeFeature(string $feature, int $amount = 1, ?Workspace $workspace = null): bool
    {
        // Check if can consume
        if (! $this->canUse($feature, $amount, $workspace)) {
            return false;
        }

        $featureConfig = PlanFeature::where('feature', $feature)->first();
        if (! $featureConfig) {
            return false;
        }

        DB::beginTransaction();
        try {
            // For non-lifetime periods, find existing active tracking record
            // instead of trying to match exact end dates
            if ($featureConfig->period !== 'lifetime') {
                $tracking = $this->usageTracking()
                    ->where('feature', $feature)
                    ->where('workspace_id', $workspace?->id)
                    ->where('period_type', $featureConfig->period)
                    ->where('period_ends_at', '>', now())
                    ->first();

                if (! $tracking) {
                    // Create new tracking record
                    $tracking = $this->usageTracking()->create([
                        'feature' => $feature,
                        'workspace_id' => $workspace?->id,
                        'period_type' => $featureConfig->period,
                        'period_starts_at' => $this->getPeriodStart($featureConfig->period),
                        'period_ends_at' => $this->getPeriodEnd($featureConfig->period),
                        'current_usage' => 0,
                    ]);
                }
            } else {
                // For lifetime, use firstOrCreate as before
                $tracking = $this->usageTracking()->firstOrCreate(
                    [
                        'feature' => $feature,
                        'workspace_id' => $workspace?->id,
                        'period_type' => 'lifetime',
                    ],
                    [
                        'current_usage' => 0,
                        'period_starts_at' => null,
                        'period_ends_at' => null,
                    ]
                );
            }

            $tracking->increment('current_usage', $amount);

            // Clear cache
            $this->clearUsageCache($feature, $workspace);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            return false;
        }
    }

    /**
     * Unconsume feature usage (decrement).
     */
    public function unconsumeFeature(string $feature, int $amount = 1, ?Workspace $workspace = null): void
    {
        $featureConfig = PlanFeature::where('feature', $feature)->first();
        if (! $featureConfig) {
            return;
        }

        $query = $this->usageTracking()
            ->where('feature', $feature)
            ->where('workspace_id', $workspace?->id);

        if ($featureConfig->period !== 'lifetime') {
            $query->where('period_ends_at', '>', now());
        }

        $tracking = $query->first();
        if ($tracking && $tracking->current_usage >= $amount) {
            $tracking->decrement('current_usage', $amount);
            $this->clearUsageCache($feature, $workspace);
        }
    }

    /**
     * Get remaining usage for a feature.
     */
    public function getRemainingUsage(string $feature, ?Workspace $workspace = null): ?int
    {
        $limit = $this->getLimit($feature);

        if ($limit === null || $limit === -1) {
            return null; // Unlimited
        }

        $currentUsage = $this->getCurrentUsage($feature, $workspace);

        return max(0, $limit - $currentUsage);
    }

    /**
     * Get usage percentage for a feature.
     */
    public function getUsagePercentage(string $feature, ?Workspace $workspace = null): float
    {
        $limit = $this->getLimit($feature);

        if ($limit === null || $limit === -1 || $limit === 0) {
            return 0.0;
        }

        $currentUsage = $this->getCurrentUsage($feature, $workspace);

        return min(100, ($currentUsage / $limit) * 100);
    }

    /**
     * Clear usage cache for a feature.
     */
    private function clearUsageCache(string $feature, ?Workspace $workspace = null): void
    {
        Cache::forget("org_{$this->id}_usage_{$feature}");
        Cache::forget("org_{$this->id}_has_{$feature}");
        Cache::forget("org_{$this->id}_limit_{$feature}");

        if ($workspace) {
            Cache::forget("org_{$this->id}_workspace_{$workspace->id}_usage_{$feature}");
        }
    }

    /**
     * Get the earliest active plan start date for yearly billing anchor.
     * This is used to ensure yearly tracking periods are consistent.
     * Cached for 1 hour to avoid repeated DB queries.
     */
    private function getYearlyAnchorDate(): \Illuminate\Support\Carbon
    {
        return Cache::remember("org_{$this->id}_yearly_anchor", 3600, function () {
            // Get the earliest started_at from active plans
            $earliestStart = $this->organizationPlans()
                ->where('status', 'active')
                ->where('is_revoked', false)
                ->orderBy('started_at', 'asc')
                ->value('started_at');

            // If no active plan found, use current date as fallback (start of day)
            return $earliestStart
                ? \Illuminate\Support\Carbon::parse($earliestStart)->startOfDay()
                : now()->startOfDay();
        });
    }

    /**
     * Get period start timestamp.
     */
    private function getPeriodStart(string $period): ?\Illuminate\Support\Carbon
    {
        return match ($period) {
            'daily' => now()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            'yearly' => $this->getYearlyAnchorDate(),
            'lifetime' => null,
            default => null,
        };
    }

    /**
     * Get period end timestamp.
     */
    private function getPeriodEnd(string $period): ?\Illuminate\Support\Carbon
    {
        return match ($period) {
            'daily' => now()->endOfDay(),
            'weekly' => now()->endOfWeek(),
            'monthly' => now()->endOfMonth(),
            'yearly' => $this->getYearlyAnchorDate()->copy()->addYear(),
            'lifetime' => null,
            default => null,
        };
    }

    /**
     * Get all feature usage summary across all active plans.
     *
     * @return array<string, array{name: string, type: string, tracking_scope: string, limit: int|null, current: int, remaining: int|null, percentage: float, has_feature: bool}>
     */
    public function getUsageSummary(?Workspace $workspace = null): array
    {
        $activePlans = $this->activePlans;

        if ($activePlans->isEmpty()) {
            return [];
        }

        // Get all unique features from all active plans
        $allFeatures = [];
        foreach ($activePlans as $plan) {
            $planLimits = $plan->limits()->get();
            foreach ($planLimits as $planLimit) {
                $allFeatures[$planLimit->feature] = $planLimit;
            }
        }

        $summary = [];

        foreach ($allFeatures as $feature => $planLimit) {
            // Get feature details from plan_features for display information
            $featureConfig = PlanFeature::where('feature', $feature)->first();
            if (! $featureConfig || ! $featureConfig->is_active) {
                continue;
            }

            // Determine the appropriate workspace context based on tracking scope from plan_limits
            $contextWorkspace = $planLimit->tracking_scope === 'workspace' ? $workspace : null;

            $limit = $this->getLimit($feature);
            $current = $this->getCurrentUsage($feature, $contextWorkspace);

            $summary[$feature] = [
                'name' => $featureConfig->name,
                'type' => $planLimit->type,
                'tracking_scope' => $planLimit->tracking_scope,
                'limit' => $limit,
                'current' => $current,
                'remaining' => $limit === -1 ? null : max(0, $limit - $current),
                'percentage' => $this->getUsagePercentage($feature, $contextWorkspace),
                'has_feature' => $this->hasFeature($feature),
            ];
        }

        return $summary;
    }
}
