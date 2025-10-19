<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WorkspaceRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Workspace\AddWorkspaceMemberRequest;
use App\Http\Requests\Workspace\ChangeWorkspaceMemberRoleRequest;
use App\Http\Requests\Workspace\CreateWorkspaceRequest;
use App\Http\Requests\Workspace\DuplicateWorkspaceRequest;
use App\Http\Requests\Workspace\TransferWorkspaceOwnershipRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Http\Resources\PendingInvitationResource;
use App\Http\Resources\WorkspaceMemberResource;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Services\OrganizationService;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Workspaces
 *
 * APIs for managing workspaces. Workspaces are sub-tenants within an organization.
 * Each workspace can have its own members, settings, and connections.
 */
class WorkspaceController extends BaseApiController
{
    private WorkspaceService $workspaceService;

    private OrganizationService $organizationService;

    public function __construct(WorkspaceService $workspaceService, OrganizationService $organizationService)
    {
        $this->workspaceService = $workspaceService;
        $this->organizationService = $organizationService;
    }

    /**
     * List all workspaces
     *
     * Returns all workspaces in the current organization accessible to the authenticated user.
     * Requires current organization context to be set via POST /api/v1/user/current-organization/{uuid}.
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "uuid": "uuid-here",
     *       "name": "Main Workspace",
     *       "slug": "main-workspace",
     *       "role": "manager"
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Workspace::class);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // When called from /organization/workspaces, filter by current organization
        if ($user->currentOrganization) {
            $workspaces = $this->workspaceService->getOrganizationWorkspaces($user->currentOrganization, $user);
        } else {
            // Fallback to all accessible workspaces if no organization context
            $workspaces = $this->workspaceService->getAccessibleWorkspaces($user);
        }

        return $this->successResponse($workspaces);
    }

    /**
     * Store a newly created workspace.
     */
    public function store(CreateWorkspaceRequest $request): JsonResponse
    {
        $data = $request->validated();

        // SECURITY: Use user's current organization context instead of accepting organization_id from request
        // This prevents IDOR attacks where users could create workspaces in any organization
        /** @var \App\Models\User $user */
        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            return $this->errorResponse('No active organization selected. Please select an organization first.', 400);
        }

        $this->authorize('createWorkspace', $organization);

        // Check workspace limits before creating
        if ($this->organizationService->hasReachedWorkspaceLimit($organization)) {
            return $this->errorResponse(
                'Workspace limit reached for your current plan',
                403
            );
        }

        try {
            /** @var \App\Models\User $user */
            $user = $request->user();
            $workspace = $this->workspaceService->create($organization, $user, $data);

            return $this->createdResponse($workspace, 'Workspace created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Display the current workspace.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        $this->authorize('view', $workspace);

        return $this->successResponse($workspace);
    }

    /**
     * Update the current workspace.
     */
    public function update(UpdateWorkspaceRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        $this->authorize('update', $workspace);

        $updatedWorkspace = $this->workspaceService->update($workspace, $request->validated());

        return $this->successResponse($updatedWorkspace, 'Workspace updated successfully');
    }

    /**
     * Update a workspace by UUID.
     */
    public function updateByUuid(UpdateWorkspaceRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);

        $updatedWorkspace = $this->workspaceService->update($workspace, $request->validated());

        return $this->successResponse($updatedWorkspace, 'Workspace updated successfully');
    }

    /**
     * Remove the current workspace.
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        $this->authorize('delete', $workspace);

        // Check if workspace can be deleted
        if (! $workspace->canBeDeleted()) {
            return $this->errorResponse(
                'Cannot delete the last workspace in an organization. Create another workspace first, or delete the entire organization instead.',
                400
            );
        }

        try {
            // Delete workspace (this automatically handles context reassignment for all affected users)
            $this->workspaceService->delete($workspace);

            // Check what happened to the current user's context after deletion
            $user->refresh(); // Reload user to get updated current_workspace_id
            $response = ['message' => 'Workspace deleted successfully'];

            if ($user->current_workspace_id) {
                // User was automatically assigned to another workspace
                $nextWorkspace = Workspace::find($user->current_workspace_id);
                if ($nextWorkspace) {
                    $response['next_workspace'] = $nextWorkspace;
                    $response['message'] = 'Workspace deleted successfully. Switched to '.$nextWorkspace->name;
                } else {
                    $response['next_workspace'] = null;
                    $response['message'] = 'Workspace deleted successfully. Context reassignment failed.';
                }
            } else {
                // No other workspaces available, user's workspace context was cleared
                $response['next_workspace'] = null;
                $response['message'] = 'Workspace deleted successfully. No other workspaces available in this organization.';
            }

            return $this->successResponse($response);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get current workspace members with pending invitations.
     */
    public function members(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        $this->authorize('viewMembers', $workspace);

        $data = $this->workspaceService->getMembersWithInvitations($workspace);

        return $this->successResponse([
            'current_members' => WorkspaceMemberResource::collection($data['current_members']),
            'pending_invitations' => PendingInvitationResource::collection($data['pending_invitations']),
            'summary' => [
                'total_current_members' => $data['current_members']->count(),
                'total_pending_invitations' => $data['pending_invitations']->count(),
                'total_members_including_pending' => $data['current_members']->count() + $data['pending_invitations']->count(),
            ],
        ]);
    }

    /**
     * Add member to current workspace.
     */
    public function addMember(AddWorkspaceMemberRequest $request): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = $request->user();
        $workspace = $currentUser->currentWorkspace;

        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        $this->authorize('addMembers', $workspace);

        $user = User::findOrFail($request->user_id);
        $role = WorkspaceRole::from($request->role);

        try {
            $this->workspaceService->addUser($workspace, $user, $role);

            return $this->successResponse(null, 'Member added successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Remove member from current workspace.
     */
    public function removeMember(Request $request, User $user): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = $request->user();
        $workspace = $currentUser->currentWorkspace;

        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        $this->authorize('removeMembers', $workspace);

        // SECURITY: Verify the user belongs to the current workspace
        if (! $workspace->users()->where('user_id', $user->id)->exists()) {
            return $this->notFoundResponse('User is not a member of this workspace');
        }

        // Check if trying to remove the last owner
        if ($user->hasRoleInWorkspace($workspace, WorkspaceRole::MANAGER)) {
            $ownerCount = $workspace->users()->wherePivot('role', WorkspaceRole::MANAGER->value)->count();
            if ($ownerCount <= 1) {
                return $this->errorResponse('Cannot remove the last owner from workspace', 400);
            }
        }

        try {
            $this->workspaceService->removeUser($workspace, $user);

            return $this->successResponse(null, 'Member removed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Change member role in current workspace.
     */
    public function changeRole(ChangeWorkspaceMemberRoleRequest $request, User $user): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = $request->user();
        $workspace = $currentUser->currentWorkspace;

        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        $this->authorize('changeRoles', $workspace);

        // SECURITY: Verify the user belongs to the current workspace
        if (! $workspace->users()->where('user_id', $user->id)->exists()) {
            return $this->notFoundResponse('User is not a member of this workspace');
        }

        $newRole = WorkspaceRole::from($request->role);

        try {
            $this->workspaceService->changeUserRole($workspace, $user, $newRole);

            return $this->successResponse(null, 'Member role updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Transfer current workspace ownership.
     */
    public function transferOwnership(TransferWorkspaceOwnershipRequest $request): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = $request->user();
        $workspace = $currentUser->currentWorkspace;

        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        $this->authorize('transferOwnership', $workspace);

        /** @var \App\Models\User $newOwner */
        $newOwner = User::findOrFail($request->new_owner_id);

        try {
            $this->workspaceService->transferOwnership($workspace, $currentUser, $newOwner);

            return $this->successResponse(null, 'Workspace ownership transferred successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Duplicate current workspace.
     */
    public function duplicate(DuplicateWorkspaceRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        $this->authorize('duplicate', $workspace);

        // Check workspace limits before duplicating
        /** @var \App\Models\Organization $organization */
        $organization = $workspace->organization;
        if ($this->organizationService->hasReachedWorkspaceLimit($organization)) {
            return $this->errorResponse(
                'Workspace limit reached for your current plan',
                403
            );
        }

        try {
            $newWorkspace = $this->workspaceService->duplicate($workspace, $user, $request->all());

            return $this->createdResponse($newWorkspace, 'Workspace duplicated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
