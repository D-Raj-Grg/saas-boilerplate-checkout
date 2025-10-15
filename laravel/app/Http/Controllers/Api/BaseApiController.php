<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PlanFeature;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    protected function successResponse(mixed $data, string $message = '', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function createdResponse(mixed $data, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    protected function paginatedResponse(mixed $data, string $message = '', ?string $resourceClass = null): JsonResponse
    {
        $items = $data->items();

        // Apply resource transformation if provided
        if ($resourceClass && class_exists($resourceClass)) {
            $items = $resourceClass::collection($items);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'meta' => [
                'current_page' => $data->currentPage(),
                'from' => $data->firstItem(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'to' => $data->lastItem(),
                'total' => $data->total(),
            ],
        ]);
    }

    protected function validationErrorResponse(mixed $errors): JsonResponse
    {
        return $this->errorResponse('Validation failed', 422, $errors);
    }

    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    protected function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, 500);
    }

    /**
     * SECURITY: Get the current user's organization context
     * This ensures operations are performed within the user's current organization.
     */
    protected function getCurrentOrganization(): ?Organization
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if (! $user || ! $user->current_organization_id) {
            return null;
        }

        return $user->currentOrganization;
    }

    /**
     * SECURITY: Get the current user's workspace context
     * This ensures operations are performed within the user's current workspace.
     */
    protected function getCurrentWorkspace(): ?Workspace
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if (! $user || ! $user->current_workspace_id) {
            return null;
        }

        return $user->currentWorkspace;
    }

    /**
     * SECURITY: Ensure the user has a valid organization context
     * Returns an error response if no organization is selected.
     */
    protected function requireOrganizationContext(): ?JsonResponse
    {
        if (! $this->getCurrentOrganization()) {
            return $this->errorResponse(
                'Please select an organization first. Use the user session endpoints to set your current organization.',
                400
            );
        }

        return null;
    }

    /**
     * SECURITY: Ensure the user has a valid workspace context
     * Returns an error response if no workspace is selected.
     */
    protected function requireWorkspaceContext(): ?JsonResponse
    {
        if (! $this->getCurrentWorkspace()) {
            return $this->errorResponse(
                'Please select a workspace first. Use the user session endpoints to set your current workspace.',
                400
            );
        }

        return null;
    }

    /**
     * Get plan information for error responses (handles expired/cancelled plans gracefully).
     *
     * @return array{name: string, slug: string, status: string}
     */
    protected function getPlanInfo(Organization $organization): array
    {
        $currentPlan = $organization->getCurrentPlan();
        $planStatus = 'active';

        if (! $currentPlan) {
            // Single query to get most recent inactive plan (any status except 'active')
            $inactivePlan = $organization->organizationPlans()
                ->where('status', '!=', 'active')
                ->with('plan')
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($inactivePlan && $inactivePlan->plan) {
                $currentPlan = $inactivePlan->plan;
                $planStatus = 'inactive';
            }
        }

        return [
            'name' => $currentPlan ? $currentPlan->name : 'No Plan',
            'slug' => $currentPlan ? $currentPlan->slug : 'none',
            'status' => $planStatus,
        ];
    }

    /**
     * Require the organization to have an active plan.
     * Returns an error response if plan is expired/inactive, null otherwise.
     *
     * This should be called BEFORE feature/limit checks for critical operations.
     *
     * @param  Organization|null  $organization  Optional organization to check (defaults to current org from auth context)
     * @return JsonResponse|null Error response if no active plan, null if OK
     */
    protected function requireActivePlan(?Organization $organization = null): ?JsonResponse
    {
        // Use provided organization or get from auth context
        $organization = $organization ?? $this->getCurrentOrganization();

        if (! $organization) {
            return $this->errorResponse('No organization context available', 400);
        }

        // Check if organization has an active plan (uses cached method)
        if (! $organization->hasActivePlan()) {
            $planInfo = $this->getPlanInfo($organization);

            return $this->errorResponse(
                "Your {$planInfo['name']} plan is no longer active. Please upgrade to continue using this feature.",
                403,
                [
                    'error_code' => 'plan_inactive',
                    'plan_name' => $planInfo['name'],
                    'upgrade_required' => true,
                    'trial_expired' => $organization->isTrialExpired(),
                ]
            );
        }

        return null;
    }

    /**
     * Check if the organization has reached a plan limit for a feature.
     * Returns an error response if limit is reached, null otherwise.
     *
     * @param  string  $feature  The feature to check
     * @param  int  $amount  The amount of usage required
     * @param  Workspace|null  $workspace  The workspace context (optional)
     * @return JsonResponse|null Error response if limit exceeded, null if OK
     */
    protected function checkPlanLimit(string $feature, int $amount = 1, ?Workspace $workspace = null): ?JsonResponse
    {
        $organization = $this->getCurrentOrganization();

        if (! $organization) {
            return $this->errorResponse('No organization context available', 400);
        }

        // IMPORTANT: Check active plan FIRST before checking limits
        // If plan is expired, canUse() returns false, which looks like a limit issue
        if (! $organization->hasActivePlan()) {
            return $this->requireActivePlan($organization);
        }

        // Now check if organization can use the feature (actual limit check)
        if (! $organization->canUse($feature, $amount, $workspace)) {
            return $this->planLimitExceededResponse($feature, $organization, $workspace);
        }

        return null;
    }

    /**
     * Check if the organization has a specific feature enabled.
     * Returns an error response if feature is not available, null otherwise.
     *
     * @param  string  $feature  The feature to check
     * @return JsonResponse|null Error response if feature not available, null if OK
     */
    protected function checkFeatureAvailable(string $feature): ?JsonResponse
    {
        $organization = $this->getCurrentOrganization();

        if (! $organization) {
            return $this->errorResponse('No organization context available', 400);
        }

        // Check if organization has the feature
        if (! $organization->hasFeature($feature)) {
            $featureConfig = PlanFeature::where('feature', $feature)->first();
            $featureName = $featureConfig->name ?? $feature;
            $planInfo = $this->getPlanInfo($organization);

            return $this->errorResponse(
                "{$featureName} is not available in your current plan",
                403,
                [
                    'feature' => $feature,
                    'feature_name' => $featureName,
                    'current_plan' => $planInfo['name'],
                    'upgrade_required' => true,
                ]
            );
        }

        return null;
    }

    /**
     * Consume a feature after successful operation.
     *
     * @param  string  $feature  The feature to consume
     * @param  int  $amount  The amount to consume
     * @param  Workspace|null  $workspace  The workspace context (optional)
     * @return bool Whether the consumption was successful
     */
    protected function consumeFeature(string $feature, int $amount = 1, ?Workspace $workspace = null): bool
    {
        $organization = $this->getCurrentOrganization();

        if (! $organization) {
            return false;
        }

        return $organization->consumeFeature($feature, $amount, $workspace);
    }

    /**
     * Unconsume a feature after deletion/rollback.
     *
     * @param  string  $feature  The feature to unconsume
     * @param  int  $amount  The amount to unconsume
     * @param  Workspace|null  $workspace  The workspace context (optional)
     */
    protected function unconsumeFeature(string $feature, int $amount = 1, ?Workspace $workspace = null): void
    {
        $organization = $this->getCurrentOrganization();

        if ($organization) {
            $organization->unconsumeFeature($feature, $amount, $workspace);
        }
    }

    /**
     * Generate a standardized plan limit exceeded response.
     *
     * @param  string  $feature  The feature that exceeded limit
     * @param  Organization  $organization  The organization
     * @param  Workspace|null  $workspace  The workspace context
     */
    protected function planLimitExceededResponse(string $feature, Organization $organization, ?Workspace $workspace = null): JsonResponse
    {
        $limit = $organization->getLimit($feature);
        $current = $organization->getCurrentUsage($feature, $workspace);
        $remaining = $organization->getRemainingUsage($feature, $workspace);

        $featureConfig = PlanFeature::where('feature', $feature)->first();
        $featureName = $featureConfig->name ?? $feature;

        $message = "You have reached the limit for {$featureName}.";

        if ($limit !== null && $limit !== -1) {
            $message .= " Current usage: {$current}/{$limit}.";
        }

        return $this->errorResponse(
            $message,
            403,
            [
                'error_code' => 'plan_limit_exceeded',
                'feature' => $feature,
                'feature_name' => $featureName,
                'limit' => $limit,
                'current_usage' => $current,
                'remaining' => $remaining,
                'percentage_used' => $organization->getUsagePercentage($feature, $workspace),
                'plan' => $this->getPlanInfo($organization),
                'upgrade_required' => true,
            ]
        );
    }

    /**
     * Get usage summary for a specific feature.
     *
     * @param  string  $feature  The feature to check
     * @param  Workspace|null  $workspace  The workspace context
     * @return array{limit: int|null, current: int, remaining: int|null, percentage: float, has_feature: bool}
     */
    protected function getFeatureUsage(string $feature, ?Workspace $workspace = null): array
    {
        $organization = $this->getCurrentOrganization();

        if (! $organization) {
            return [
                'limit' => null,
                'current' => 0,
                'remaining' => null,
                'percentage' => 0,
                'has_feature' => false,
            ];
        }

        return [
            'limit' => $organization->getLimit($feature),
            'current' => $organization->getCurrentUsage($feature, $workspace),
            'remaining' => $organization->getRemainingUsage($feature, $workspace),
            'percentage' => $organization->getUsagePercentage($feature, $workspace),
            'has_feature' => $organization->hasFeature($feature),
        ];
    }


    /**
     * Check team member limits for a workspace.
     * Returns an error response if limit is exceeded, null otherwise.
     *
     * @param  Workspace  $workspace  The workspace context
     * @param  int  $newMemberCount  Number of new members being added
     * @return JsonResponse|null Error response if limit exceeded, null if OK
     */
    protected function checkTeamMemberLimits(Workspace $workspace, int $newMemberCount = 1): ?JsonResponse
    {
        $organization = $this->getCurrentOrganization();

        if (! $organization) {
            return $this->errorResponse('No organization context available', 400);
        }

        // IMPORTANT: Check active plan FIRST before checking limits
        if (! $organization->hasActivePlan()) {
            return $this->requireActivePlan($organization);
        }

        $currentMemberCount = $workspace->users()->count();
        $pendingInvitationCount = $workspace->invitations()->where('status', 'pending')->count();
        $totalAfterAddition = $currentMemberCount + $pendingInvitationCount + $newMemberCount;

        if (! $organization->canUse('team_members', $totalAfterAddition)) {
            $limit = $organization->getLimit('team_members');
            $available = max(0, $limit - $currentMemberCount - $pendingInvitationCount);

            return $this->errorResponse(
                "Team member limit reached. Your plan allows {$limit} members. You can invite {$available} more member(s).",
                403,
                [
                    'error_code' => 'team_member_limit_exceeded',
                    'feature' => 'team_members',
                    'feature_name' => 'Team Members',
                    'limit' => $limit,
                    'current_members' => $currentMemberCount,
                    'pending_invitations' => $pendingInvitationCount,
                    'requested' => $newMemberCount,
                    'available_slots' => $available,
                    'plan' => $this->getPlanInfo($organization),
                    'upgrade_required' => true,
                ]
            );
        }

        return null;
    }

    /**
     * Check connection limits for a workspace.
     * Returns an error response if limit is exceeded, null otherwise.
     *
     * @param  Workspace  $workspace  The workspace context
     * @return JsonResponse|null Error response if limit exceeded, null if OK
     */
    protected function checkConnectionLimits(Workspace $workspace): ?JsonResponse
    {
        // Use checkPlanLimit which checks active plan FIRST, then limits
        return $this->checkPlanLimit('connections_per_workspace', 1, $workspace);
    }
}
