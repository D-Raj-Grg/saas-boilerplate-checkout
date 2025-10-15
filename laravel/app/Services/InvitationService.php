<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Exceptions\InvitationException;
use App\Exceptions\OrganizationException;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitationService
{
    /**
     * Accept an invitation (organization only).
     */
    public function acceptInvitation(string $token, User $user): bool
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invitation) {
            throw InvitationException::expired();
        }

        // Check if invitation email matches user email
        if ($invitation->email !== $user->email) {
            throw InvitationException::emailMismatch();
        }

        // Only organization invitations are supported
        if (! $invitation->organization_id) {
            throw InvitationException::notFound();
        }

        return $this->acceptOrganizationInvitationInternal($invitation, $user);
    }

    /**
     * Decline an invitation.
     */
    public function declineInvitation(string $token): bool
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (! $invitation) {
            throw InvitationException::notFound();
        }

        return $invitation->update([
            'status' => 'declined',
            'declined_at' => now(),
        ]);
    }

    /**
     * Cancel an invitation.
     */
    public function cancelInvitation(Invitation $invitation): bool
    {
        if ($invitation->status !== 'pending') {
            throw InvitationException::cannotCancel();
        }

        return $invitation->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Resend invitation.
     */
    public function resendInvitation(Invitation $invitation): bool
    {
        if ($invitation->status !== 'pending') {
            throw InvitationException::cannotResend();
        }

        // Generate new token and update expiry
        $invitation->update([
            'token' => Str::uuid(),
            'expires_at' => now()->addDays(7),
        ]);

        // Send invitation email
        $this->sendInvitationEmail($invitation);

        return true;
    }

    /**
     * Get invitation by token.
     */
    public function getInvitationByToken(string $token): ?Invitation
    {
        return Invitation::where('token', $token)
            ->with(['organization', 'inviter'])
            ->first();
    }

    /**
     * Get invitations sent by user.
     */
    public function getInvitationsByInviter(User $user): Collection
    {
        return Invitation::where('inviter_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get invitations received by email.
     */
    public function getInvitationsByEmail(string $email): Collection
    {
        return Invitation::where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with(['organization', 'inviter'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Clean up expired invitations.
     */
    public function cleanupExpiredInvitations(): int
    {
        return Invitation::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update([
                'status' => 'expired',
                'expired_at' => now(),
            ]);
    }

    /**
     * Get invitation URL.
     */
    public function getInvitationUrl(Invitation $invitation): string
    {
        return config('app.frontend_url').'/invitations/accept/'.$invitation->token;
    }

    /**
     * Send invitation to join organization with optional workspace assignments.
     *
     * @param  array<array{workspace_id: string, role: string}>  $workspaceAssignments
     */
    public function inviteToOrganization(
        Organization $organization,
        User $inviter,
        string $email,
        OrganizationRole $role,
        array $workspaceAssignments = [],
        ?string $message = null
    ): Invitation {
        // Check if user is already in organization
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $existingUser->belongsToOrganization($organization)) {
            throw InvitationException::alreadyMember();
        }

        // Check if there's already a pending invitation
        $existingInvitation = Invitation::where('email', $email)
            ->where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->first();

        if ($existingInvitation) {
            throw InvitationException::alreadyPending();
        }

        // Check organization member limits
        if (! $organization->canUse('team_members', 1)) {
            throw OrganizationException::memberLimitReached();
        }

        // SECURITY: Defense-in-depth role restriction validation
        // Prevent privilege escalation where members try to invite admins/owners
        $inviterRole = $inviter->getOrganizationRole($organization);
        if ($inviterRole === OrganizationRole::MEMBER) {
            // Members can only invite other members, never admins or owners
            if (in_array($role, [OrganizationRole::ADMIN, OrganizationRole::OWNER])) {
                throw new \InvalidArgumentException('Members cannot invite users with admin or owner privileges');
            }
        }

        // Validate workspace assignments for members
        if ($role === OrganizationRole::MEMBER && empty($workspaceAssignments)) {
            throw new \InvalidArgumentException('Members must be assigned to at least one workspace');
        }

        return DB::transaction(function () use ($organization, $inviter, $email, $role, $workspaceAssignments, $message) {
            // Create invitation with organization context
            $invitation = Invitation::create([
                'organization_id' => $organization->id,
                'inviter_id' => $inviter->id,
                'email' => $email,
                'role' => $role->value,
                'token' => Str::uuid(),
                'message' => $message,
                'expires_at' => now()->addDays(7),
                'status' => 'pending',
                'workspace_assignments' => $workspaceAssignments,
            ]);

            // Send invitation email
            $this->sendInvitationEmail($invitation);

            return $invitation;
        });
    }

    /**
     * Get pending invitations for organization.
     *
     * @return Collection<int, Invitation>
     */
    public function getPendingOrganizationInvitations(Organization $organization): Collection
    {
        return Invitation::where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->with(['inviter'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->makeHidden('id');
    }

    /**
     * Check if email can be invited to organization.
     */
    public function canInviteEmailToOrganization(string $email, Organization $organization): bool
    {
        // Check if user already exists and is in organization
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $existingUser->belongsToOrganization($organization)) {
            return false;
        }

        // Check if there's already a pending invitation
        $existingInvitation = Invitation::where('email', $email)
            ->where('organization_id', $organization->id)
            ->where('status', 'pending')
            ->exists();

        return ! $existingInvitation;
    }

    /**
     * Accept an organization invitation (public method for backward compatibility).
     */
    public function acceptOrganizationInvitation(string $token, User $user): bool
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invitation) {
            throw InvitationException::expired();
        }

        return $this->acceptOrganizationInvitationInternal($invitation, $user);
    }

    /**
     * Accept an organization invitation (internal method to avoid duplicate queries).
     */
    private function acceptOrganizationInvitationInternal(Invitation $invitation, User $user): bool
    {
        // Check if invitation email matches user email
        if ($invitation->email !== $user->email) {
            throw InvitationException::emailMismatch();
        }

        // Check if this is an organization invitation
        if (! $invitation->organization_id) {
            throw new \InvalidArgumentException('Not an organization invitation');
        }

        $organization = Organization::find($invitation->organization_id);
        if (! $organization) {
            throw InvitationException::notFound();
        }

        // Check if user is already in organization
        if ($user->belongsToOrganization($organization)) {
            throw InvitationException::alreadyMember();
        }

        return DB::transaction(function () use ($invitation, $user, $organization) {
            // Add user to organization with the specified role
            $organization->addUser($user, OrganizationRole::from($invitation->role), $invitation->inviter);

            // Add to workspaces if specified
            if ($invitation->workspace_assignments) {
                foreach ($invitation->workspace_assignments as $assignment) {
                    $workspace = Workspace::where('uuid', $assignment['workspace_id'])
                        ->where('organization_id', $organization->id)
                        ->first();

                    if ($workspace && ! $user->belongsToWorkspace($workspace)) {
                        $workspace->addUser($user, WorkspaceRole::from($assignment['role']));
                    }
                }
            }

            // Mark invitation as accepted
            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Send invitation email (organization invitations only).
     */
    private function sendInvitationEmail(Invitation $invitation): void
    {
        // Get frontend URL from config
        $frontendUrl = config('app.frontend_url', config('app.url'));

        // Dispatch email job to queue
        \App\Jobs\SendInvitationEmail::dispatch($invitation, $frontendUrl);

        // Log the invitation for tracking
        \Log::info('Invitation sent', [
            'email' => $invitation->email,
            'organization_id' => $invitation->organization_id,
            'role' => $invitation->role,
            'workspace_assignments' => $invitation->workspace_assignments,
            'token' => $invitation->token,
            'expires_at' => $invitation->expires_at,
        ]);
    }
}
