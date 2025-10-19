<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Invitation\CreateOrganizationInvitationRequest;
use App\Http\Resources\PendingInvitationResource;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Invitations
 *
 * APIs for managing organization invitations. Users can be invited to join organizations with specific roles.
 * Invitations expire after 7 days and can be accepted, declined, or resent.
 */
class InvitationController extends BaseApiController
{
    private InvitationService $invitationService;

    public function __construct(
        InvitationService $invitationService
    ) {
        $this->invitationService = $invitationService;
    }

    /**
     * Get invitation details
     *
     * Returns details of a specific invitation including organization and inviter information.
     *
     * @urlParam invitation string required The invitation UUID. Example: uuid-here
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "uuid": "uuid-here",
     *     "email": "user@example.com",
     *     "organization": {"name": "Acme Corp"},
     *     "role": "member",
     *     "status": "pending",
     *     "expires_at": "2024-01-22T10:30:00.000000Z"
     *   }
     * }
     */
    public function show(Invitation $invitation): JsonResponse
    {
        // Check if user can view this invitation
        $this->authorize('view', $invitation);

        return $this->successResponse($invitation->load(['organization', 'inviter']));
    }

    /**
     * Remove the specified invitation (cancel).
     */
    public function destroy(Invitation $invitation): JsonResponse
    {
        // Check if user can cancel this invitation
        $this->authorize('cancel', $invitation);

        if ($invitation->status === 'accepted') {
            return $this->errorResponse('Cannot cancel an already accepted invitation', 400);
        }

        try {
            $this->invitationService->cancelInvitation($invitation);

            return $this->successResponse(null, 'Invitation cancelled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Accept an invitation.
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = $this->invitationService->getInvitationByToken($token);

        if (! $invitation) {
            return $this->notFoundResponse('Invalid invitation');
        }

        if ($invitation->status !== 'pending') {
            return $this->errorResponse('This invitation is no longer valid', 400);
        }

        if ($invitation->expires_at < now()) {
            return $this->errorResponse('This invitation has expired', 400);
        }

        try {
            /** @var \App\Models\User $user */
            $user = $request->user();
            $this->invitationService->acceptInvitation($token, $user);

            return $this->successResponse(null, 'Invitation accepted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Decline an invitation.
     */
    public function decline(Request $request, string $token): JsonResponse
    {
        try {
            $this->invitationService->declineInvitation($token);

            return $this->successResponse(null, 'Invitation declined');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Resend an invitation.
     */
    public function resend(Request $request, Invitation $invitation): JsonResponse
    {
        // Check if user can resend this invitation
        $this->authorize('resend', $invitation);

        try {
            $this->invitationService->resendInvitation($invitation);

            return $this->successResponse(null, 'Invitation resent successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get received invitations for the authenticated user.
     */
    public function received(Request $request): JsonResponse
    {
        $this->authorize('viewReceived', Invitation::class);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $invitations = $this->invitationService->getInvitationsByEmail($user->email);

        return $this->successResponse($invitations);
    }

    /**
     * Preview invitation details (public endpoint).
     * This endpoint doesn't require authentication.
     */
    public function preview(string $token): JsonResponse
    {
        $invitation = $this->invitationService->getInvitationByToken($token);

        if (! $invitation) {
            return $this->notFoundResponse('Invalid invitation link');
        }

        if ($invitation->status !== 'pending') {
            return $this->errorResponse('This invitation is no longer valid', 400);
        }

        if ($invitation->expires_at < now()) {
            return $this->errorResponse('This invitation has expired', 400);
        }

        $userExists = User::where('email', $invitation->email)->exists();

        $organization = $invitation->organization;
        $inviter = $invitation->inviter;

        if (! $organization || ! $inviter) {
            return $this->errorResponse('Invalid invitation data', 404);
        }

        return $this->successResponse([
            'invitation' => [
                'email' => $invitation->email,
                'role' => $invitation->role,
                'message' => $invitation->message,
                'expires_at' => $invitation->expires_at,
                'workspace_assignments' => $invitation->workspace_assignments,
            ],
            'organization' => [
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'inviter' => [
                'name' => $inviter->name,
            ],
            'requires_signup' => ! $userExists,
        ]);
    }

    /**
     * Check invitation status for a given token.
     */
    public function checkStatus(string $token): JsonResponse
    {
        $invitation = $this->invitationService->getInvitationByToken($token);

        if (! $invitation) {
            return $this->errorResponse('Invalid invitation', 404);
        }

        return $this->successResponse([
            'valid' => $invitation->status === 'pending' && $invitation->expires_at > now(),
            'status' => $invitation->status,
            'email' => $invitation->email,
            'expires_at' => $invitation->expires_at,
            'user_exists' => User::where('email', $invitation->email)->exists(),
        ]);
    }

    /**
     * Display a listing of pending invitations for the current organization.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            return $this->errorResponse('No current organization set', 400);
        }

        $this->authorize('inviteUsers', $organization);

        $invitations = $this->invitationService->getPendingOrganizationInvitations($organization);

        return $this->successResponse(PendingInvitationResource::collection($invitations));
    }

    /**
     * Store a newly created organization invitation.
     * This handles the unified invitation system where users can be invited to the organization
     * with optional workspace assignments.
     */
    public function store(CreateOrganizationInvitationRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            return $this->errorResponse('No current organization set', 400);
        }

        $this->authorize('inviteUsers', $organization);

        try {
            // Check if user already exists in the organization
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser && $existingUser->belongsToOrganization($organization)) {
                // User is already in organization
                if ($request->role === 'admin' && ! $existingUser->isOrganizationAdmin($organization)) {
                    // Upgrade to admin if requested
                    $organization->updateUserRole($existingUser, OrganizationRole::from($request->role));
                }

                // Add to any new workspaces if member role with workspace assignments
                if ($request->role === 'member' && $request->has('workspace_assignments')) {
                    foreach ($request->workspace_assignments as $assignment) {
                        $workspace = Workspace::where('uuid', $assignment['workspace_id'])->first();
                        if ($workspace && $workspace->organization_id === $organization->id && ! $existingUser->belongsToWorkspace($workspace)) {
                            $workspace->addUser($existingUser, WorkspaceRole::from($assignment['role']));
                        }
                    }
                }

                return $this->successResponse([
                    'action' => 'updated',
                    'message' => 'User updated and added to new workspaces',
                    'user' => [
                        'email' => $existingUser->email,
                        'new_role' => $request->role,
                    ],
                ]);
            }

            // Check if there's already a pending invitation
            if (! $this->invitationService->canInviteEmailToOrganization($request->email, $organization)) {
                return $this->errorResponse('An invitation for this email already exists', 400);
            }

            /** @var \App\Models\User $inviter */
            $inviter = $request->user();

            $invitation = $this->invitationService->inviteToOrganization(
                $organization,
                $inviter,
                $request->email,
                OrganizationRole::from($request->role),
                $request->workspace_assignments ?? [],
                $request->message ?? null
            );

            return $this->createdResponse($invitation, 'Organization invitation sent successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
