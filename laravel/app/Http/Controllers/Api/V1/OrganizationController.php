<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Organization\ChangeMemberRoleRequest;
use App\Http\Requests\Organization\CreateOrganizationRequest;
use App\Http\Requests\Organization\TransferOrganizationOwnershipRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationMemberResource;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\PendingInvitationResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Organizations
 *
 * APIs for managing organizations. Organizations are the top-level tenant in the multi-tenancy hierarchy.
 * Each organization can have multiple workspaces, members, and a subscription plan.
 */
class OrganizationController extends BaseApiController
{
    private OrganizationService $organizationService;

    private WorkspaceService $workspaceService;

    public function __construct(OrganizationService $organizationService, WorkspaceService $workspaceService)
    {
        $this->organizationService = $organizationService;
        $this->workspaceService = $workspaceService;
    }

    /**
     * List all organizations
     *
     * Returns all organizations accessible to the authenticated user.
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "uuid": "uuid-here",
     *       "name": "My Organization",
     *       "slug": "my-organization",
     *       "is_owner": true
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Organization::class);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $organizations = $this->organizationService->getAccessibleOrganizations($user);

        return $this->successResponse(OrganizationResource::collection($organizations));
    }

    /**
     * Create a new organization
     *
     * Creates a new organization with a default workspace. The authenticated user becomes the organization owner.
     *
     * @bodyParam name string required The organization name. Example: Acme Corporation
     * @bodyParam workspace_name string optional The name for the default workspace. Defaults to organization name. Example: Main Workspace
     *
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "uuid": "uuid-here",
     *     "name": "Acme Corporation",
     *     "slug": "acme-corporation",
     *     "is_owner": true
     *   },
     *   "message": "Organization created successfully"
     * }
     */
    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $this->authorize('create', Organization::class);

        try {
            $organization = DB::transaction(function () use ($request) {
                /** @var \App\Models\User $user */
                $user = $request->user();
                $organization = $this->organizationService->create($user, $request->validated());
                $workspaceName = empty($request->input('workspace_name')) ? $organization->name : $request->input('workspace_name');
                $this->workspaceService->create($organization, $user, [
                    'name' => $workspaceName,
                ]);

                return $organization;
            });

            return $this->createdResponse($organization, 'Organization created successfully');
        } catch (\Exception $e) {
            \Log::info($e->getMessage());

            return $this->errorResponse('Failed to create organization');
        }
    }

    /**
     * Display the current organization.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            return $this->notFoundResponse('No organization selected');
        }

        $this->authorize('view', $organization);

        return $this->successResponse($organization);
    }

    /**
     * Update the current organization.
     */
    public function update(UpdateOrganizationRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            return $this->notFoundResponse('No organization selected');
        }

        $this->authorize('update', $organization);

        $updatedOrganization = $this->organizationService->update($organization, $request->validated());

        return $this->successResponse($updatedOrganization, 'Organization updated successfully');
    }

    /**
     * Remove the current organization.
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            return $this->notFoundResponse('No organization selected');
        }

        $this->authorize('delete', $organization);

        $this->organizationService->delete($organization);

        return $this->successResponse(null, 'Organization deleted successfully');
    }

    /**
     * Transfer current organization ownership.
     */
    public function transferOwnership(TransferOrganizationOwnershipRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            return $this->notFoundResponse('No organization selected');
        }

        $this->authorize('transferOwnership', $organization);

        /** @var \App\Models\User $newOwner */
        $newOwner = \App\Models\User::findOrFail($request->new_owner_id);

        try {
            $this->organizationService->transferOwnership($organization, $newOwner);

            return $this->successResponse(null, 'Organization ownership transferred successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get organization statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            return $this->notFoundResponse('No organization selected');
        }

        $this->authorize('view', $organization);

        $stats = $this->organizationService->getStats($organization);

        return $this->successResponse($stats);
    }

    /**
     * Get organization members with their workspace access and pending invitations.
     */
    public function members(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            return $this->notFoundResponse('No organization selected');
        }

        $this->authorize('viewAny', $organization);

        $data = $this->organizationService->getMembersWithInvitations($organization);

        return $this->successResponse([
            'current_members' => OrganizationMemberResource::collection($data['current_members']),
            'pending_invitations' => PendingInvitationResource::collection($data['pending_invitations']),
            'summary' => [
                'total_current_members' => $data['current_members']->count(),
                'total_pending_invitations' => $data['pending_invitations']->count(),
                'total_members_including_pending' => $data['current_members']->count() + $data['pending_invitations']->count(),
            ],
        ]);
    }

    /**
     * Remove a member from the organization.
     */
    public function removeMember(Request $request, User $user): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = $request->user();
        $organization = $currentUser->currentOrganization;

        if (! $organization) {
            return $this->notFoundResponse('No organization selected');
        }

        $this->authorize('removeUsers', $organization);

        // SECURITY: Verify the user belongs to the current organization
        if (! $organization->users()->where('user_id', $user->id)->exists()) {
            return $this->notFoundResponse('User is not a member of this organization');
        }

        try {
            $this->organizationService->removeMember($organization, $user, $currentUser);

            return $this->successResponse([
                'message' => 'Member removed successfully',
                'removed_user_uuid' => $user->uuid,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Change a member's role in the organization.
     */
    public function changeMemberRole(ChangeMemberRoleRequest $request, User $user): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = $request->user();
        $organization = $currentUser->currentOrganization;

        if (! $organization) {
            return $this->notFoundResponse('No organization selected');
        }

        $this->authorize('removeUsers', $organization); // Same permission as removal

        // SECURITY: Verify the user belongs to the current organization
        if (! $organization->users()->where('user_id', $user->id)->exists()) {
            return $this->notFoundResponse('User is not a member of this organization');
        }

        try {
            $validatedData = $request->validated();
            $newRole = \App\Enums\OrganizationRole::from($validatedData['role']);
            $workspaceAssignments = $validatedData['workspace_assignments'] ?? [];

            $this->organizationService->changeMemberRole($organization, $user, $newRole, $currentUser, $workspaceAssignments);

            return $this->successResponse([
                'message' => 'Member role updated successfully',
                'user_uuid' => $user->uuid,
                'new_role' => $newRole->value,
                'workspace_assignments_updated' => ! empty($workspaceAssignments),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
