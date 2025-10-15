<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own invitations
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invitation $invitation): bool
    {
        // User can view if they are the inviter or if they belong to the organization
        return $invitation->inviter_id === $user->id ||
               ($invitation->organization && $user->belongsToOrganization($invitation->organization));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Specific organization authorization is checked in the service
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invitation $invitation): bool
    {
        // Only the inviter can update invitation details
        return $invitation->inviter_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        // Allow if user is the inviter
        if ($invitation->inviter_id === $user->id) {
            return true;
        }

        // Allow if user is an organization owner or admin
        if ($invitation->organization_id) {
            $organization = $invitation->organization;
            if ($organization && $user->isOrganizationAdmin($organization)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the invitation.
     */
    public function cancel(User $user, Invitation $invitation): bool
    {
        return $this->delete($user, $invitation);
    }

    /**
     * Determine whether the user can resend the invitation.
     */
    public function resend(User $user, Invitation $invitation): bool
    {
        // Allow if user is the inviter
        if ($invitation->inviter_id === $user->id) {
            return true;
        }

        // Allow if user is an organization owner or admin
        if ($invitation->organization_id) {
            $organization = $invitation->organization;
            if ($organization && $user->isOrganizationAdmin($organization)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can accept the invitation.
     */
    public function accept(User $user, Invitation $invitation): bool
    {
        // User can accept if the invitation is sent to their email
        return $invitation->email === $user->email;
    }

    /**
     * Determine whether the user can decline the invitation.
     */
    public function decline(User $user, Invitation $invitation): bool
    {
        // User can decline if the invitation is sent to their email
        return $invitation->email === $user->email;
    }

    /**
     * Determine whether the user can view received invitations.
     */
    public function viewReceived(User $user): bool
    {
        return true; // Users can view invitations sent to their email
    }
}
