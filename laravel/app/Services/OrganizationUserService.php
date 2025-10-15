<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrganizationUserService
{
    /**
     * Invite a user to an organization with optional workspace assignments.
     *
     * @param  array<int, array{workspace_id: int, role: string, capabilities?: array<string, mixed>}>  $workspaces  Array of ['workspace_id' => x, 'role' => WorkspaceRole, 'capabilities' => []]
     */
    public function inviteToOrganization(
        Organization $organization,
        string $email,
        OrganizationRole $role,
        array $workspaces = [],
        ?User $invitedBy = null
    ): Invitation {
        // Validate that all workspaces belong to the organization
        foreach ($workspaces as $workspaceData) {
            /** @var Workspace $workspace */
            $workspace = Workspace::findOrFail($workspaceData['workspace_id']);
            if ($workspace->organization_id !== $organization->id) {
                throw new \Exception('Workspace does not belong to the organization');
            }
        }

        // Create invitation with organization context
        $invitation = new Invitation;
        $invitation->organization_id = $organization->id;
        $invitation->email = $email;
        $invitation->role = $role->value;
        $invitation->workspace_assignments = $workspaces;
        $invitation->inviter_id = $invitedBy?->id;
        $invitation->save();

        // Send invitation email (implement your email logic here)
        // Mail::to($email)->send(new OrganizationInvitationMail($invitation));

        Log::info('Organization invitation created', [
            'organization_id' => $organization->id,
            'email' => $email,
            'role' => $role->value,
            'workspace_count' => count($workspaces),
        ]);

        return $invitation;
    }

    /**
     * Accept an organization invitation and setup user memberships.
     */
    public function acceptInvitation(string $token, User $user): bool
    {
        $invitation = Invitation::where('token', $token)
            ->where('email', $user->email)
            ->valid()
            ->firstOrFail();

        DB::transaction(function () use ($invitation, $user) {
            // Add user to organization
            /** @var Organization $organization */
            $organization = Organization::findOrFail($invitation->organization_id);
            $orgUser = $organization->addUser(
                $user,
                OrganizationRole::from($invitation->role),
                $invitation->inviter
            );

            // If admin/owner, they get implicit access to all workspaces
            if ($orgUser->hasImplicitWorkspaceAccess()) {
                Log::info('User has implicit workspace access as org admin/owner', [
                    'user_id' => $user->id,
                    'organization_id' => $organization->id,
                ]);
            } else {
                // Add to specific workspaces if member
                $workspaceAssignments = $invitation->workspace_assignments ?? [];
                foreach ($workspaceAssignments as $assignment) {
                    /** @var Workspace|null $workspace */
                    $workspace = Workspace::find($assignment['workspace_id']);
                    if ($workspace && $workspace->organization_id === $organization->id) {
                        $workspace->addUser(
                            $user,
                            WorkspaceRole::from($assignment['role'] ?? WorkspaceRole::VIEWER->value),
                            $invitation->inviter
                        );

                        // Add capabilities if specified
                        if (! empty($assignment['capabilities'])) {
                            $workspaceUser = $workspace->users()
                                ->where('user_id', $user->id)
                                ->first();
                            if ($workspaceUser) {
                                $workspaceUser->pivot->update([
                                    'capabilities' => $assignment['capabilities'],
                                ]);
                            }
                        }
                    }
                }
            }

            // Mark invitation as accepted
            $invitation->markAsAccepted();
        });

        return true;
    }

    /**
     * Remove a user from an organization and all its workspaces.
     */
    public function removeFromOrganization(Organization $organization, User $user): bool
    {
        return DB::transaction(function () use ($organization, $user) {
            // Remove from all workspaces first
            foreach ($organization->workspaces as $workspace) {
                $workspace->removeUser($user);
            }

            // Remove from organization
            $removed = $organization->removeUser($user);

            Log::info('User removed from organization', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
            ]);

            return $removed;
        });
    }

    /**
     * Update a user's role in the organization.
     */
    public function updateRole(
        Organization $organization,
        User $user,
        OrganizationRole $newRole
    ): bool {
        // Prevent removing the last owner
        if ($this->isLastOwner($organization, $user) && $newRole !== OrganizationRole::OWNER) {
            throw new \Exception('Cannot change role of the last owner');
        }

        $updated = $organization->updateUserRole($user, $newRole);

        if ($updated) {
            Log::info('Organization user role updated', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'new_role' => $newRole->value,
            ]);
        }

        return $updated;
    }

    /**
     * Assign a user to workspaces within an organization.
     *
     * @param  array<int, array{workspace_id: int, role?: string, capabilities?: array<string, mixed>}>  $workspaces
     */
    public function assignToWorkspaces(
        Organization $organization,
        User $user,
        array $workspaces
    ): void {
        // Verify user is an organization member
        if (! $organization->hasUser($user)) {
            throw new \Exception('User must be an organization member');
        }

        // If user is admin/owner, they already have implicit access
        if ($organization->isUserAdmin($user)) {
            Log::warning('Attempted to assign workspaces to org admin/owner', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
            ]);

            return;
        }

        DB::transaction(function () use ($organization, $user, $workspaces) {
            foreach ($workspaces as $workspaceData) {
                /** @var Workspace|null $workspace */
                $workspace = Workspace::find($workspaceData['workspace_id']);

                if (! $workspace || $workspace->organization_id !== $organization->id) {
                    continue;
                }

                // Add or update workspace membership
                if ($workspace->users()->where('user_id', $user->id)->exists()) {
                    $workspace->updateUserRole(
                        $user,
                        WorkspaceRole::from($workspaceData['role'] ?? WorkspaceRole::VIEWER->value)
                    );
                } else {
                    $workspace->addUser(
                        $user,
                        WorkspaceRole::from($workspaceData['role'] ?? WorkspaceRole::VIEWER->value)
                    );
                }

                // Update capabilities if provided
                if (! empty($workspaceData['capabilities'])) {
                    $workspaceUser = $workspace->users()
                        ->where('user_id', $user->id)
                        ->first();
                    if ($workspaceUser) {
                        $workspaceUser->pivot->update(['capabilities' => $workspaceData['capabilities']]);
                    }
                }
            }
        });
    }

    /**
     * Get all members of an organization with their roles.
     *
     * @return \Illuminate\Support\Collection<int, array{user: User, role: \App\Enums\OrganizationRole, capabilities: array<string, mixed>|null, joined_at: \Carbon\Carbon|null, invited_by: User|null}>
     */
    public function getOrganizationMembers(Organization $organization): Collection
    {
        return $organization->organizationUsers()
            ->with(['user', 'inviter'])
            ->get()
            ->map(function ($orgUser) {
                return [
                    'user' => $orgUser->user,
                    'role' => $orgUser->role,
                    'capabilities' => $orgUser->capabilities,
                    'joined_at' => $orgUser->joined_at,
                    'invited_by' => $orgUser->inviter,
                ];
            });
    }

    /**
     * Check if a user is the last owner of an organization.
     */
    private function isLastOwner(Organization $organization, User $user): bool
    {
        $ownerCount = $organization->organizationUsers()
            ->where('role', OrganizationRole::OWNER->value)
            ->count();

        return $ownerCount === 1 && $organization->isOwnedBy($user);
    }

    /**
     * Transfer organization ownership to another user.
     */
    public function transferOwnership(
        Organization $organization,
        User $currentOwner,
        User $newOwner
    ): bool {
        if (! $organization->isOwnedBy($currentOwner)) {
            throw new \Exception('Only the current owner can transfer ownership');
        }

        return DB::transaction(function () use ($organization, $currentOwner, $newOwner) {
            // Validate new owner is already an organization member
            if (! $organization->hasUser($newOwner)) {
                throw new \Exception('New owner must be a member of the organization');
            }

            // Update new owner role to OWNER
            $organization->updateUserRole($newOwner, OrganizationRole::OWNER);

            // Demote current owner to admin
            $organization->updateUserRole($currentOwner, OrganizationRole::ADMIN);

            // Update the owner_id field (if it's still being used for legacy purposes)
            $organization->update(['owner_id' => $newOwner->id]);

            Log::info('Organization ownership transferred', [
                'organization_id' => $organization->id,
                'from_user_id' => $currentOwner->id,
                'to_user_id' => $newOwner->id,
            ]);

            return true;
        });
    }
}
