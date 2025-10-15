<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can clear their session.
     */
    public function clearSession(User $user, User $targetUser): bool
    {
        // Users can only clear their own session
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can set their current organization.
     * This is redundant as OrganizationPolicy already handles this,
     * but included for completeness.
     */
    public function setCurrentOrganization(User $user, User $targetUser): bool
    {
        // Users can only set their own current organization
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can set their current workspace.
     * This is redundant as WorkspacePolicy already handles this,
     * but included for completeness.
     */
    public function setCurrentWorkspace(User $user, User $targetUser): bool
    {
        // Users can only set their own current workspace
        return $user->id === $targetUser->id;
    }
}
