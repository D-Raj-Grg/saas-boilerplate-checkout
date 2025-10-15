<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Exceptions\OrganizationException;
use App\Exceptions\WorkspaceException;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkspaceService
{
    private OrganizationService $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    /**
     * Create a new workspace.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, User $creator, array $data): Workspace
    {
        return DB::transaction(function () use ($organization, $creator, $data) {
            $workspace = Workspace::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'organization_id' => $organization->id,
                'uuid' => Str::uuid(),
                'settings' => $data['settings'] ?? [],
            ]);

            // Add creator as manager of the workspace
            $workspace->users()->attach($creator->id, [
                'role' => WorkspaceRole::MANAGER->value,
                'joined_at' => now(),
            ]);

            // Consume team member feature for the workspace creator
            $organization->consumeFeature('team_members', 1, $workspace);

            return $workspace;
        });
    }

    /**
     * Update a workspace.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Workspace $workspace, array $data): Workspace
    {
        $workspace->update([
            'name' => $data['name'] ?? $workspace->name,
            'slug' => $data['slug'] ?? $workspace->slug,
            'description' => $data['description'] ?? $workspace->description,
            'settings' => array_merge($workspace->settings, $data['settings'] ?? []),
        ]);

        return $workspace->fresh();
    }

    /**
     * Delete a workspace.
     */
    public function delete(Workspace $workspace): bool
    {
        return DB::transaction(function () use ($workspace) {
            /** @var Organization $organization */
            $organization = $workspace->organization;

            // Get all users who have this workspace as their current context
            $affectedUsers = User::where('current_workspace_id', $workspace->id)->get();

            // For each affected user, try to assign another workspace from the same organization
            foreach ($affectedUsers as $user) {
                $this->reassignUserToNextWorkspace($user, $organization, $workspace->id);
            }

            // Remove all user relationships
            $workspace->users()->detach();

            // Delete workspace
            return $workspace->delete();
        });
    }

    /**
     * Reassign user to the next best available workspace in the organization.
     */
    private function reassignUserToNextWorkspace(User $user, Organization $organization, int $excludeWorkspaceId): ?Workspace
    {
        // Get user's other workspaces in the same organization (excluding the one being deleted)
        $availableWorkspaces = $user->workspaces()
            ->where('organization_id', $organization->id)
            ->where('workspaces.id', '!=', $excludeWorkspaceId)
            ->withPivot('role')
            ->get();

        if ($availableWorkspaces->isEmpty()) {
            // No other workspaces available, clear workspace context
            $user->update(['current_workspace_id' => null]);

            return null;
        }

        // Smart selection: prioritize by role (owner > admin > member)
        $nextWorkspace = $availableWorkspaces->sortByDesc(function ($workspace) {
            // Access pivot data through getRelation method
            $role = $workspace->getRelation('pivot')->role ?? WorkspaceRole::EDITOR->value;

            return match ($role) {
                WorkspaceRole::MANAGER->value => 3,
                WorkspaceRole::EDITOR->value => 2,
                WorkspaceRole::VIEWER->value => 1,
                default => 0
            };
        })->first();

        // Update user's current workspace context
        if ($nextWorkspace) {
            $user->update(['current_workspace_id' => $nextWorkspace->id]);
        }

        return $nextWorkspace;
    }

    /**
     * Get workspaces accessible by user.
     */
    public function getAccessibleWorkspaces(User $user): Collection
    {
        return $user->workspaces()
            ->with(['organization', 'users'])
            ->withPivot('role', 'joined_at')
            ->get();
    }

    /**
     * Get workspaces in an organization that the user has access to.
     * Uses User::currentOrganizationWorkspaces() as the single source of truth.
     *
     * @return Collection<int, Workspace>
     */
    public function getOrganizationWorkspaces(Organization $organization, User $user): Collection
    {
        // Use the single source of truth from User model
        return $user->currentOrganizationWorkspaces();
    }

    /**
     * Get workspace by UUID.
     */
    public function findByUuid(string $uuid): ?Workspace
    {
        return Workspace::where('uuid', $uuid)
            ->with(['organization', 'users'])
            ->first();
    }

    /**
     * Add user to workspace.
     */
    public function addUser(Workspace $workspace, User $user, WorkspaceRole $role): bool
    {
        // Check if organization has reached member limit (only if service is available)
        if ($this->hasReachedMemberLimit($workspace)) {
            throw OrganizationException::memberLimitReached();
        }

        // Check if user is already in workspace
        if ($user->belongsToWorkspace($workspace)) {
            throw WorkspaceException::memberNotFound();
        }

        return DB::transaction(function () use ($workspace, $user, $role) {
            // Ensure user is also a member of the parent organization
            /** @var \App\Models\Organization $organization */
            $organization = $workspace->organization;
            if (! $organization->hasUser($user)) {
                $organization->addUser($user, OrganizationRole::MEMBER);
            }

            $workspace->users()->attach($user->id, [
                'role' => $role->value,
                'joined_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Remove user from workspace.
     */
    public function removeUser(Workspace $workspace, User $user): bool
    {
        // Check if user is the only owner
        if ($user->hasRoleInWorkspace($workspace, WorkspaceRole::MANAGER)) {
            $ownerCount = $workspace->users()->wherePivot('role', WorkspaceRole::MANAGER->value)->count();
            if ($ownerCount <= 1) {
                throw WorkspaceException::cannotRemoveLastOwner();
            }
        }

        return DB::transaction(function () use ($workspace, $user) {
            return $workspace->users()->detach($user->id) > 0;
        });
    }

    /**
     * Change user role in workspace.
     */
    public function changeUserRole(Workspace $workspace, User $user, WorkspaceRole $newRole): bool
    {
        if (! $user->belongsToWorkspace($workspace)) {
            throw WorkspaceException::memberNotFound();
        }

        // If changing from owner role, ensure there's another owner
        $currentRole = $user->getRoleInWorkspace($workspace);
        if ($currentRole === WorkspaceRole::MANAGER && $newRole !== WorkspaceRole::MANAGER) {
            $ownerCount = $workspace->users()->wherePivot('role', WorkspaceRole::MANAGER->value)->count();
            if ($ownerCount <= 1) {
                throw WorkspaceException::cannotRemoveLastOwner();
            }
        }

        return DB::transaction(function () use ($workspace, $user, $newRole) {
            $workspace->users()->updateExistingPivot($user->id, [
                'role' => $newRole->value,
            ]);

            return true;
        });
    }

    /**
     * Get workspace members with their roles.
     */
    public function getMembers(Workspace $workspace): Collection
    {
        // Get direct workspace members
        $directMembers = $workspace->users()->withPivot(['role', 'joined_at', 'invited_by'])->get();

        // Get org admins/owners who aren't direct members
        /** @var \App\Models\Organization $organization */
        $organization = $workspace->organization;
        $orgAdminsOwners = $organization->users()
            ->whereIn('organization_users.role', [OrganizationRole::OWNER->value, OrganizationRole::ADMIN->value])
            ->whereNotIn('users.id', $directMembers->pluck('id'))
            ->withPivot(['role', 'joined_at', 'invited_by'])
            ->get();

        // Combine and set properties
        /** @phpstan-ignore-next-line */
        return $directMembers->concat($orgAdminsOwners)->map(function ($user) use ($workspace) {
            /** @phpstan-ignore-next-line */
            $isOrgAdmin = $user->isOrganizationAdmin($workspace->organization);

            /** @phpstan-ignore-next-line */
            $user->effective_role = $isOrgAdmin ? WorkspaceRole::MANAGER->value : $user->pivot->role;
            /** @phpstan-ignore-next-line */
            $user->is_org_admin_access = $isOrgAdmin;
            /** @phpstan-ignore-next-line */
            $user->joined_at = $user->pivot->joined_at;
            /** @phpstan-ignore-next-line */
            $user->invited_by = $user->pivot->invited_by;

            // Add organization role
            /** @phpstan-ignore-next-line */
            $user->organization_role = $user->getOrganizationRole($workspace->organization)?->value;

            return $user;
        })->sortBy('joined_at')->values();
    }

    /**
     * Get pending invitations for the workspace.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Invitation>
     */
    public function getPendingInvitationsForWorkspace(Workspace $workspace): \Illuminate\Support\Collection
    {
        // Get all pending invitations for the organization
        $allPendingInvitations = \App\Models\Invitation::where('organization_id', $workspace->organization_id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with(['inviter'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter for invitations that grant access to this specific workspace
        return $allPendingInvitations->filter(function ($invitation) use ($workspace) {
            // Admin/Owner invitations have access to all workspaces
            if (in_array($invitation->role, ['admin', 'owner']) && empty($invitation->workspace_assignments)) {
                return true;
            }

            // Member invitations only if this workspace is in their assignments
            if ($invitation->role === 'member' && $invitation->workspace_assignments) {
                foreach ($invitation->workspace_assignments as $assignment) {
                    if (isset($assignment['workspace_id']) && $assignment['workspace_id'] === $workspace->uuid) {
                        return true;
                    }
                }
            }

            return false;
        });
    }

    /**
     * Get workspace members with pending invitations.
     *
     * @return array{current_members: \Illuminate\Database\Eloquent\Collection<int, \App\Models\User>, pending_invitations: \Illuminate\Support\Collection<int, \App\Models\Invitation>}
     */
    public function getMembersWithInvitations(Workspace $workspace): array
    {
        return [
            'current_members' => $this->getMembers($workspace),
            'pending_invitations' => $this->getPendingInvitationsForWorkspace($workspace),
        ];
    }

    /**
     * Transfer workspace ownership.
     */
    public function transferOwnership(Workspace $workspace, User $currentOwner, User $newOwner): bool
    {
        if (! $currentOwner->hasRoleInWorkspace($workspace, WorkspaceRole::MANAGER)) {
            throw WorkspaceException::transferRequiresAccess();
        }

        if (! $newOwner->belongsToWorkspace($workspace)) {
            throw WorkspaceException::transferRequiresAccess();
        }

        return DB::transaction(function () use ($workspace, $currentOwner, $newOwner) {
            // Demote current owner to editor
            $workspace->users()->updateExistingPivot($currentOwner->id, [
                'role' => WorkspaceRole::EDITOR->value,
            ]);

            // Promote new owner to manager (workspace owner role)
            $workspace->users()->updateExistingPivot($newOwner->id, [
                'role' => WorkspaceRole::MANAGER->value,
            ]);

            return true;
        });
    }

    /**
     * Get workspace usage metrics.
     */
    public function getUsageMetrics(Workspace $workspace): array
    {
        $currentMembers = $this->getMembers($workspace)->count();

        // Get organization limits if service is available
        if ($this->organizationService) {
            $orgLimits = $this->organizationService->getLimits($workspace->organization);

            return [
                'members' => [
                    'current' => $currentMembers,
                    'organization_limit' => $orgLimits['members'],
                ],
                'features_available' => $orgLimits['features'],
            ];
        }

        // Return basic metrics if organization service not available
        return [
            'members' => [
                'current' => $currentMembers,
                'organization_limit' => null,
            ],
            'features_available' => [],
        ];
    }

    /**
     * Duplicate a workspace.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function duplicate(Workspace $workspace, User $user, array $data): Workspace
    {
        return DB::transaction(function () use ($workspace, $user, $data) {
            $newWorkspace = $this->create(
                $workspace->organization,
                $user,
                [
                    'name' => $data['name'],
                    'slug' => $data['slug'] ?? null, // Will be auto-generated by create method
                    'description' => $data['description'] ?? $workspace->description,
                    'settings' => $workspace->settings,
                ]
            );

            // Copy members if requested
            if ($data['copy_members'] ?? false) {
                $members = $this->getMembers($workspace);
                foreach ($members as $member) {
                    if ($member->id !== $user->id) { // Skip the creator as they're already added
                        /** @phpstan-ignore-next-line */
                        $effectiveRole = $member->effective_role;
                        $this->addUser(
                            $newWorkspace,
                            $member,
                            WorkspaceRole::from($effectiveRole)
                        );
                    }
                }
            }

            return $newWorkspace;
        });
    }

    /**
     * Check if workspace has reached member limit.
     */
    public function hasReachedMemberLimit(Workspace $workspace): bool
    {
        /** @var Organization $organization */
        $organization = $workspace->organization;
        $limits = $this->organizationService->getLimits($organization);

        // -1 means unlimited
        if ($limits['members'] === -1) {
            return false;
        }

        // Count current workspace members
        return $workspace->users()->count() >= $limits['members'];
    }
}
