<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use App\Traits\HasPricingPlan;
use App\Traits\HasSlug;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property int $owner_id
 * @property int|null $plan_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Organization extends Model
{
    use HasFactory;
    use HasPricingPlan;
    use HasSlug;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'owner_id',
        'settings',
        'currency',
        'market',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    protected $hidden = [
        'id',
        'owner_id',
    ];

    /**
     * The relationships that should always be loaded.
     */
    protected $with = [];

    /**
     * Get the owner of the organization.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all plans associated with the organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Plan, $this, OrganizationPlan>
     */
    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'organization_plans')
            ->using(OrganizationPlan::class)
            ->withPivot([
                'status', 'is_revoked', 'revoked_at', 'revoked_by',
                'started_at', 'ends_at', 'trial_start', 'trial_end',
                'billing_cycle', 'quantity', 'metadata', 'notes',
            ])
            ->withTimestamps()
            ->orderByDesc(\DB::raw('plans.priority'));
    }

    /**
     * Get the organization plans (pivot records).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<OrganizationPlan, $this>
     */
    public function organizationPlans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrganizationPlan::class);
    }

    /**
     * Get only active plans associated with the organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Plan, $this, OrganizationPlan>
     */
    public function activePlans(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->plans()
            ->wherePivot('is_revoked', false)
            ->wherePivot('status', 'active')
            ->where(function ($query) {
                $query->whereNull('organization_plans.ends_at')
                    ->orWhere('organization_plans.ends_at', '>', now());
            })
            ->where('organization_plans.started_at', '<=', now());
    }

    /**
     * Get the current plan for the organization.
     * Always returns the highest-priority active plan.
     *
     * Logic:
     * 1. Returns the highest-priority active plan (highest priority number wins)
     * 2. Returns null if no active plans exist
     *
     * Note: The returned Plan instance includes pivot data (OrganizationPlan) accessible via $plan->pivot
     * when loaded through the relationship. The pivot property may be null if not loaded through relationship.
     */
    public function getCurrentPlan(): ?Plan
    {
        return \Cache::remember("org_{$this->id}_current_plan", 300, function () {
            return $this->activePlans()->first();
        });
    }

    /**
     * Check if organization has any active plans.
     * Highly cached for performance on high-traffic endpoints.
     * Cache is cleared when plans are attached/updated.
     */
    public function hasActivePlan(): bool
    {
        return \Cache::remember("org_{$this->id}_has_active_plan", 600, function () {
            return $this->activePlans()->exists();
        });
    }

    /**
     * Attach a plan to this organization.
     *
     * This is the SINGLE SOURCE OF TRUTH for plan attachment.
     * Handles all scenarios: manual, webhook, free plan assignment.
     *
     * @param  Plan|string  $plan  The plan to attach
     * @param  array<string, mixed>  $attributes  Plan attributes (optional)
     * @return OrganizationPlan|null Returns the plan or null if skipped
     */
    public function attachPlan(Plan|string $plan, array $attributes = []): ?OrganizationPlan
    {
        // Resolve plan from slug if needed
        if (is_string($plan)) {
            $planSlug = $plan;
            $plan = Plan::where('slug', $planSlug)->first();
            if (! $plan) {
                \Log::error('Plan not found', ['slug' => $planSlug]);

                return null;
            }
        }

        // Business Rule: Skip duplicate free plan
        if ($plan->isFree() && $this->hasFreePlan()) {
            \Log::info('Skipped duplicate free plan', ['org_id' => $this->id]);

            return null;
        }

        // Business Rule: No free plan if org has paid plan
        if ($plan->isFree() && $this->hasNonFreePlan()) {
            \Log::info('Skipped free plan - org has paid plan', ['org_id' => $this->id]);

            return null;
        }

        // Auto-cancel free plan when attaching paid plan
        if (! $plan->isFree()) {
            $freePlan = $this->organizationPlans()
                ->whereHas('plan', fn ($q) => $q->where('slug', 'free'))
                ->where('is_revoked', false)
                ->first();

            if ($freePlan) {
                $freePlan->update([
                    'status' => 'cancelled',
                    'ends_at' => now(),
                    'notes' => 'Replaced by paid plan',
                ]);
            }

            // Set organization currency and market from first paid plan
            if ($this->currency === 'NPR' && $this->market === 'nepal') {
                $this->update([
                    'currency' => $plan->currency,
                    'market' => $plan->market,
                ]);
            }
        }

        // Create the plan
        $organizationPlan = $this->organizationPlans()->create(array_merge([
            'plan_id' => $plan->id,
            'user_id' => $this->owner_id,
            'status' => 'active',
            'is_revoked' => false,
            'started_at' => now(),
            'quantity' => 1,
            'billing_cycle' => 'monthly',
        ], $attributes));

        $this->clearPlanCache();

        \Log::info('Plan attached', [
            'org_id' => $this->id,
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
        ]);

        return $organizationPlan;
    }

    /**
     * Check if organization has an active free plan.
     * Only checks for the actual 'free' plan, not promotional zero-price plans.
     */
    private function hasFreePlan(): bool
    {
        return $this->activePlans()
            ->where('plans.slug', 'free')
            ->exists();
    }

    /**
     * Check if organization has any active non-free plan.
     */
    private function hasNonFreePlan(): bool
    {
        return $this->activePlans()
            ->where('plans.slug', '!=', 'free')
            ->exists();
    }

    /**
     * Clear plan-related cache for this organization.
     * Clears all plan, limit, and feature-related cache entries.
     */
    protected function clearPlanCache(): void
    {
        $cacheKeys = [
            "org_{$this->id}_plans",
            "org_{$this->id}_current_plan",
            "org_{$this->id}_active_plans",
            "org_{$this->id}_has_active_plan",
            "org_{$this->id}_yearly_anchor", // Clear yearly tracking anchor when plans change
        ];

        foreach ($cacheKeys as $key) {
            \Cache::forget($key);
        }

        // Clear feature-specific caches (these have dynamic keys)
        \Cache::tags(["org_{$this->id}"])->flush();
    }

    /**
     * Get complete trial information in a single call (optimized).
     * Use this when you need multiple trial fields to avoid redundant queries.
     *
     * @return array{is_active: bool, is_expired: bool, days_remaining: int|null, ends_at: \Carbon\Carbon|null}
     */
    public function getTrialInfo(): array
    {
        $currentPlan = $this->getCurrentPlan();

        // Default response when no plan or no pivot
        if (! $currentPlan) {
            return [
                'is_active' => false,
                'is_expired' => false,
                'days_remaining' => null,
                'ends_at' => null,
            ];
        }

        /** @var Plan&object{pivot: OrganizationPlan|null} $currentPlan */
        if (! $currentPlan->pivot) {
            return [
                'is_active' => false,
                'is_expired' => false,
                'days_remaining' => null,
                'ends_at' => null,
            ];
        }

        /** @var OrganizationPlan $pivot */
        $pivot = $currentPlan->pivot;

        // No trial dates set
        if (! $pivot->trial_start || ! $pivot->trial_end) {
            return [
                'is_active' => false,
                'is_expired' => false,
                'days_remaining' => null,
                'ends_at' => null,
            ];
        }

        $isActive = $pivot->trial_start->isPast() && $pivot->trial_end->isFuture();
        $isExpired = $pivot->trial_end->isPast();
        $daysRemaining = (int) now()->diffInDays($pivot->trial_end, false);

        return [
            'is_active' => $isActive,
            'is_expired' => $isExpired,
            'days_remaining' => $daysRemaining,
            'ends_at' => $pivot->trial_end,
        ];
    }

    /**
     * Check if organization is currently in trial period.
     */
    public function isInTrial(): bool
    {
        return $this->getTrialInfo()['is_active'];
    }

    /**
     * Check if trial has expired.
     */
    public function isTrialExpired(): bool
    {
        return $this->getTrialInfo()['is_expired'];
    }

    /**
     * Get days remaining in trial (can be negative if expired).
     *
     * @return int|null Returns null if not in trial, number of days otherwise
     */
    public function getTrialDaysRemaining(): ?int
    {
        return $this->getTrialInfo()['days_remaining'];
    }

    /**
     * Get trial end date.
     */
    public function getTrialEndDate(): ?\Carbon\Carbon
    {
        return $this->getTrialInfo()['ends_at'];
    }

    /**
     * Get the organization users (members).
     *
     * @return HasMany<OrganizationUser, $this>
     */
    public function organizationUsers(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    /**
     * Get the users that belong to the organization.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_users')
            ->withPivot(['role', 'capabilities', 'joined_at', 'invited_by'])
            ->withTimestamps();
    }

    /**
     * Get the workspaces for the organization.
     *
     * @return HasMany<Workspace, $this>
     */
    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    /**
     * Get the invitations for the organization.
     *
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Get the first workspace for the organization.
     */
    public function defaultWorkspace()
    {
        return $this->workspaces()->first();
    }

    /**
     * Check if the user owns this organization.
     */
    public function isOwnedBy(User $user): bool
    {
        $orgUser = $this->organizationUsers()
            ->where('user_id', $user->id)
            ->first();

        return $orgUser && $orgUser->role === OrganizationRole::OWNER;
    }

    /**
     * Check if user has access to this organization.
     */
    public function hasUser(User $user): bool
    {
        return $this->organizationUsers()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Get user's role in this organization.
     */
    public function getUserRole(User $user): ?OrganizationRole
    {
        $orgUser = $this->organizationUsers()
            ->where('user_id', $user->id)
            ->first();

        return $orgUser?->role;
    }

    /**
     * Check if user is an admin of this organization.
     */
    public function isUserAdmin(User $user): bool
    {
        $role = $this->getUserRole($user);

        return $role && in_array($role, [OrganizationRole::OWNER, OrganizationRole::ADMIN]);
    }

    /**
     * Add a user to the organization.
     */
    public function addUser(User $user, OrganizationRole $role = OrganizationRole::MEMBER, ?User $invitedBy = null): OrganizationUser
    {
        return $this->organizationUsers()->create([
            'user_id' => $user->id,
            'role' => $role->value,
            'invited_by' => $invitedBy?->id,
            'joined_at' => now(),
        ]);
    }

    /**
     * Remove a user from the organization.
     */
    public function removeUser(User $user): bool
    {
        // Remove from all workspaces first
        foreach ($this->workspaces as $workspace) {
            $workspace->removeUser($user);
        }

        // Remove from organization
        return $this->organizationUsers()
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    /**
     * Update user's role in the organization.
     */
    public function updateUserRole(User $user, OrganizationRole $role): bool
    {
        return $this->organizationUsers()
            ->where('user_id', $user->id)
            ->update(['role' => $role->value]) > 0;
    }

    /**
     * Get organization admins.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<User, $this>
     */
    public function admins(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->users()->wherePivot('role', OrganizationRole::ADMIN->value);
    }

    /**
     * Get organization members.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<User, $this>
     */
    public function members(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->users()->wherePivot('role', OrganizationRole::MEMBER->value);
    }

    /**
     * Get organization owners.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<User, $this>
     */
    public function owners(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->users()->wherePivot('role', OrganizationRole::OWNER->value);
    }

    /**
     * Get the actual rate limit for this organization based on plan.
     * Returns the absolute rate limit, not a multiplier.
     */
    public function getRateLimit(string $limitType = 'api'): int
    {
        $currentPlan = $this->getCurrentPlan();
        if (! $currentPlan) {
            return config("rate-limiting.limits.{$limitType}.attempts", 60);
        }

        // Get the actual rate limit from the plan
        $rateLimit = $currentPlan->getLimit('api_rate_limit');
        if ($rateLimit !== null) {
            return $rateLimit === -1 ? PHP_INT_MAX : $rateLimit;
        }

        // Fallback to config base limit
        return config("rate-limiting.limits.{$limitType}.attempts", 60);
    }

    /**
     * Get the rate limit multiplier for this organization based on plan.
     *
     * @deprecated Use getRateLimit() instead for absolute values
     */
    public function getRateLimitMultiplier(): float
    {
        $currentPlan = $this->getCurrentPlan();
        if (! $currentPlan) {
            return 1; // Default multiplier if no plan
        }

        // Get the actual rate limit from the plan
        $rateLimit = $currentPlan->getLimit('api_rate_limit');
        if ($rateLimit !== null && $rateLimit > 0) {
            // Convert absolute rate limit to multiplier based on base limit
            $baseLimit = config('rate-limiting.limits.api.attempts', 300);

            return (float) ($rateLimit / $baseLimit);
        }

        // Fallback to config-based multiplier
        return config("rate-limiting.plan_multipliers.{$currentPlan->slug}", 1);
    }

    /**
     * Get the feature overrides for this organization.
     *
     * @return HasMany<OrganizationFeatureOverride, $this>
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(OrganizationFeatureOverride::class);
    }

    /**
     * Get the usage tracking records for this organization.
     *
     * @return HasMany<UsageTracking, $this>
     */
    public function usageTracking(): HasMany
    {
        return $this->hasMany(UsageTracking::class);
    }

    /**
     * Boot method to automatically create free plan association for new organizations.
     */
    protected static function boot()
    {
        parent::boot();

        // Don't auto-attach free plan in created event
        // Let the OrganizationService handle plan attachment
        // This prevents race conditions with plan assignment
    }
}
