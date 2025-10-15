<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Organization;
use App\Models\Workspace;
use App\Services\UserSessionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSessionController extends BaseApiController
{
    public function __construct(
        private readonly UserSessionService $userSessionService
    ) {}

    /**
     * Set user's current organization.
     */
    public function setCurrentOrganization(Request $request, string $uuid): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Find organization by UUID
            $organization = Organization::where('uuid', $uuid)->first();

            if (! $organization) {
                return $this->notFoundResponse('Organization not found');
            }

            // Check if user has access to this organization
            $this->authorize('switchTo', $organization);

            $responseData = $this->userSessionService->setCurrentOrganization($user, $organization);

            return $this->successResponse($responseData, 'Current organization updated successfully');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Access forbidden');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }

    /**
     * Set user's current workspace.
     */
    public function setCurrentWorkspace(Request $request, string $uuid): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Find workspace by UUID
            $workspace = Workspace::where('uuid', $uuid)->first();

            if (! $workspace) {
                return $this->notFoundResponse('Workspace not found');
            }

            // Check if user has access to this workspace
            $this->authorize('switchTo', $workspace);

            $responseData = $this->userSessionService->setCurrentWorkspace($user, $workspace);

            return $this->successResponse($responseData, 'Current workspace updated successfully');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Access forbidden');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }

    /**
     * Clear current session (useful for testing or resetting).
     */
    public function clearSession(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            // Check if user can clear their own session
            $this->authorize('clearSession', $user);

            $this->userSessionService->clearSession($user);

            return $this->successResponse(null, 'User session cleared successfully');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Access forbidden');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }
}
