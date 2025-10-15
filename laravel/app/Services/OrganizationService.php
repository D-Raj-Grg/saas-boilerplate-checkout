<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Exceptions\OrganizationException;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    /**
     * Create a new organization with default workspace.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Organization
    {
        return DB::transaction(function () use ($user, $data) {
            $organization = Organization::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null, // Model will auto-generate if not provided
                'description' => $data['description'] ?? null,
                'owner_id' => $user->id,
                'settings' => $data['settings'] ?? [],
            ]);

            // Attach plan: use provided plan_id, or fall back to free plan
            $planAttached = false;

            if (isset($data['plan_id'])) {
                $plan = Plan::find($data['plan_id']);
                if ($plan instanceof Plan) {
                    // If attaching free plan, add trial dates
                    $attributes = [];
                    if ($plan->slug === 'free') {
                        $trialDays = config('constants.free_plan_trial_days', 7);
                        $attributes['trial_start'] = now();
                        $attributes['trial_end'] = now()->addDays($trialDays);
                    }
                    $planAttached = $organization->attachPlan($plan, $attributes);
                }
            }

            // If no plan was attached (either not provided or failed), attach free plan as fallback
            if (! $planAttached && $organization->plans()->count() === 0) {
                $trialDays = config('constants.free_plan_trial_days', 7);
                $organization->attachPlan('free', [
                    'trial_start' => now(),
                    'trial_end' => now()->addDays($trialDays),
                ]);
            }

            // Add the creator as OWNER to the organization_users table
            $organization->addUser($user, OrganizationRole::OWNER);

            return $organization;
        });
    }

    /**
     * Update an organization.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organization $organization, array $data): Organization
    {
        $organization->update([
            'name' => $data['name'] ?? $organization->name,
            'slug' => $data['slug'] ?? $organization->slug,
            'description' => $data['description'] ?? $organization->description,
            'settings' => array_merge($organization->settings, $data['settings'] ?? []),
        ]);

        return $organization->fresh();
    }

    /**
     * Delete an organization and all its workspaces.
     */
    public function delete(Organization $organization): bool
    {
        return DB::transaction(function () use ($organization) {
            // Delete all workspaces and their user relationships
            foreach ($organization->workspaces as $workspace) {
                $workspace->users()->detach();
                $workspace->delete();
            }

            // Delete the organization
            return $organization->delete();
        });
    }

    /**
     * Get organizations accessible by user.
     */
    public function getAccessibleOrganizations(User $user): Collection
    {
        return $user->accessibleOrganizations()
            ->with(['activePlans', 'workspaces'])
            ->get();
    }

    /**
     * Get organization by UUID.
     */
    public function findByUuid(string $uuid): ?Organization
    {
        return Organization::where('uuid', $uuid)
            ->with(['owner', 'activePlans', 'workspaces'])
            ->first();
    }

    /**
     * Check if user can access organization.
     */
    public function canUserAccess(User $user, Organization $organization): bool
    {
        return $user->ownsOrganization($organization) ||
               $user->accessibleOrganizations()->where('organizations.id', $organization->id)->exists();
    }

    /**
     * Get organization limits based on plan.
     */
    public function getLimits(Organization $organization): array
    {
        $currentPlan = $organization->getCurrentPlan();

        if (! $currentPlan) {
            return [
                'workspaces' => 1,
                'members' => 1,
                'features' => [],
            ];
        }

        return [
            'workspaces' => $organization->getLimit('workspaces') ?? 1,
            'members' => $organization->getLimit('team_members') ?? 1,
            'features' => [],
        ];
    }

    /**
     * Check if organization has reached workspace limit.
     */
    public function hasReachedWorkspaceLimit(Organization $organization): bool
    {
        $limits = $this->getLimits($organization);

        // -1 means unlimited
        if ($limits['workspaces'] === -1) {
            return false;
        }

        return $organization->workspaces()->count() >= $limits['workspaces'];
    }

    /**
     * Transfer organization ownership.
     */
    public function transferOwnership(Organization $organization, User $newOwner): bool
    {
        // Get current owner
        $currentOwner = $organization->owner;
        if (! $currentOwner) {
            throw OrganizationException::noCurrentOwner();
        }

        // Delegate to OrganizationUserService for proper membership handling
        $organizationUserService = app(\App\Services\OrganizationUserService::class);

        return $organizationUserService->transferOwnership($organization, $currentOwner, $newOwner);
    }

    /**
     * Update organization plan by cancelling current plans and adding new one.
     */
    public function updatePlan(Organization $organization, Plan $plan): bool
    {
        DB::transaction(function () use ($organization, $plan) {
            // Cancel all current active plans
            $activePlans = $organization->plans()
                ->wherePivot('status', 'active')
                ->wherePivot('is_revoked', false)
                ->get();

            foreach ($activePlans as $activePlan) {
                $organization->plans()->updateExistingPivot($activePlan->id, [
                    'status' => 'cancelled',
                    'ends_at' => now(),
                ]);
            }

            // Add new plan
            $organization->attachPlan($plan, [
                'status' => 'active',
            ]);
        });

        return true;
    }

    /**
     * Get comprehensive organization statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(Organization $organization): array
    {
        $workspaces = $organization->workspaces()->with([
            'users',
            'connections',
        ])->get();

        // Calculate workspace counts and organization totals
        $workspaceData = $workspaces->map(function ($workspace) {
            $totalMembers = $workspace->users->count();
            $totalConnections = $workspace->connections->count();

            return [
                'uuid' => $workspace->uuid,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'description' => $workspace->description,
                'total_members' => $totalMembers,
                'total_connections' => $totalConnections,
                'created_at' => $workspace->created_at,
                'updated_at' => $workspace->updated_at,
            ];
        });

        // Calculate organization totals
        $totalMembers = $workspaceData->sum('total_members');
        $totalConnections = $workspaceData->sum('total_connections');

        return [
            'organization' => [
                'uuid' => $organization->uuid,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'description' => $organization->description,
                'total_workspaces' => $workspaces->count(),
                'total_members' => $totalMembers,
                'total_connections' => $totalConnections,
                'created_at' => $organization->created_at,
                'updated_at' => $organization->updated_at,
            ],
            'workspaces' => $workspaceData,
            'plan' => $organization->getCurrentPlan() ? [
                'uuid' => $organization->getCurrentPlan()->uuid,
                'name' => $organization->getCurrentPlan()->name,
                'slug' => $organization->getCurrentPlan()->slug,
                'price' => $organization->getCurrentPlan()->price,
                'formatted_price' => $organization->getCurrentPlan()->formatted_price,
            ] : null,
            'all_plans' => $organization->activePlans->map(function ($plan) {
                /** @var \App\Models\Plan $plan */
                return [
                    'uuid' => $plan->uuid,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price' => $plan->price,
                ];
            })->values(),
        ];
    }

    /**
     * Get organization members with their workspace access information.
     *
     * @return \Illuminate\Support\Collection<int, array{user: \App\Models\User, organization_role: mixed, joined_at: mixed, is_owner: bool, workspace_access: array<mixed>}>
     */
    public function getMembers(Organization $organization): \Illuminate\Support\Collection
    {
        return $organization->users()
            ->withPivot(['role', 'joined_at', 'invited_by'])
            ->get()
            ->map(function ($user) use ($organization) {
                /** @phpstan-ignore-next-line Laravel pivot property */
                $pivot = $user->pivot;
                $orgRole = $pivot->role;
                $isOrgAdmin = in_array($orgRole, [OrganizationRole::OWNER->value, OrganizationRole::ADMIN->value]);

                // Get workspace access information
                $workspaceAccess = [];

                if ($isOrgAdmin) {
                    // For org admins/owners, they have access to ALL workspaces
                    $workspaceAccess = $organization->workspaces->map(function ($workspace) {
                        return [
                            'uuid' => $workspace->uuid,
                            'name' => $workspace->name,
                            'role' => 'MANAGER', // Org admins/owners get manager role
                            'access_type' => 'organization_admin',
                        ];
                    })->toArray();
                } else {
                    // For regular members, only show direct workspace memberships
                    $workspaceAccess = $user->workspaces()
                        ->where('organization_id', $organization->id)
                        ->withPivot('role')
                        ->get()
                        ->map(function ($workspace) {
                            /** @phpstan-ignore-next-line Laravel pivot property */
                            $workspacePivot = $workspace->pivot;

                            return [
                                'uuid' => $workspace->uuid,
                                'name' => $workspace->name,
                                'role' => $workspacePivot->role,
                                'access_type' => 'direct_member',
                            ];
                        })->toArray();
                }

                // Return array instead of setting dynamic properties
                return [
                    'user' => $user,
                    'organization_role' => $orgRole,
                    'joined_at' => $pivot->joined_at,
                    'is_owner' => $orgRole === OrganizationRole::OWNER->value,
                    'workspace_access' => $workspaceAccess,
                ];
            })
            ->sortBy(function ($memberData) {
                // Sort by role hierarchy: Owner first, then Admin, then Member
                $roleOrder = [
                    OrganizationRole::OWNER->value => 1,
                    OrganizationRole::ADMIN->value => 2,
                    OrganizationRole::MEMBER->value => 3,
                ];

                return $roleOrder[$memberData['organization_role']] ?? 99;
            })
            ->values();
    }

    /**
     * Remove a member from the organization.
     */
    public function removeMember(Organization $organization, User $user, User $actingUser): void
    {
        // Can't remove the owner
        if ($organization->owner_id === $user->id) {
            throw OrganizationException::cannotRemoveOwner();
        }

        // Can't remove yourself
        if ($user->id === $actingUser->id) {
            throw OrganizationException::cannotRemoveYourself();
        }

        // Check if user is actually a member
        if (! $organization->users()->where('user_id', $user->id)->exists()) {
            throw OrganizationException::userNotMember();
        }

        // Remove from organization
        $organization->users()->detach($user->id);

        // Also remove from all workspaces in this organization
        $organization->workspaces->each(function ($workspace) use ($user) {
            $workspace->users()->detach($user->id);
        });

        // Clear user's current organization/workspace if it was this one
        if ($user->current_organization_id === $organization->id) {
            $user->update([
                'current_organization_id' => null,
                'current_workspace_id' => null,
            ]);
        }
    }

    /**
     * Change a member's role in the organization and update workspace assignments.
     *
     * @param  array<array{workspace_id: string, role: string}>  $workspaceAssignments
     */
    public function changeMemberRole(Organization $organization, User $user, OrganizationRole $newRole, User $actingUser, array $workspaceAssignments = []): void
    {
        // Can't change owner's role
        if ($organization->owner_id === $user->id) {
            throw OrganizationException::cannotChangeOwnerRole();
        }

        // Can't change your own role
        if ($user->id === $actingUser->id) {
            throw OrganizationException::cannotChangeOwnRole();
        }

        // Check if user is actually a member
        $currentMembership = $organization->users()->where('user_id', $user->id)->first();
        if (! $currentMembership) {
            throw OrganizationException::userNotMember();
        }

        // Can't promote to owner (use transfer ownership instead)
        if ($newRole === OrganizationRole::OWNER) {
            throw OrganizationException::cannotPromoteToOwner();
        }

        // Update the role
        $organization->users()->updateExistingPivot($user->id, [
            'role' => $newRole->value,
        ]);

        // Handle workspace assignments for MEMBER role
        if ($newRole === OrganizationRole::MEMBER && ! empty($workspaceAssignments)) {
            DB::transaction(function () use ($organization, $user, $workspaceAssignments) {
                // Remove user from all workspaces first, then add to specified ones
                $organization->workspaces->each(function ($workspace) use ($user) {
                    $workspace->users()->detach($user->id);
                });

                // Add user to specified workspaces with their roles
                foreach ($workspaceAssignments as $assignment) {
                    $workspace = $organization->workspaces()
                        ->where('uuid', $assignment['workspace_id'])
                        ->first();

                    // Validate workspace exists and role is valid
                    if (! $workspace) {
                        continue; // Skip non-existent workspaces
                    }

                    $workspaceRole = \App\Enums\WorkspaceRole::tryFrom($assignment['role']);
                    if (! $workspaceRole) {
                        continue; // Skip invalid roles
                    }

                    $workspace->addUser($user, $workspaceRole);
                }
            });
        }
        // For ADMIN/OWNER roles, they get automatic access to all workspaces (handled in getMembers)
    }

    /**
     * Get pending invitations for the organization.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Invitation>
     */
    public function getPendingInvitations(Organization $organization): \Illuminate\Support\Collection
    {
        return $organization->invitations()
            ->with(['inviter'])
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get organization members with pending invitations.
     *
     * @return array{current_members: \Illuminate\Support\Collection<int, array{user: \App\Models\User, organization_role: mixed, joined_at: mixed, is_owner: bool, workspace_access: array<mixed>}>, pending_invitations: \Illuminate\Support\Collection<int, \App\Models\Invitation>}
     */
    public function getMembersWithInvitations(Organization $organization): array
    {
        return [
            'current_members' => $this->getMembers($organization),
            'pending_invitations' => $this->getPendingInvitations($organization),
        ];
    }
}
