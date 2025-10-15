<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Workspace\UpdateWorkspaceSettingRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceSettingController extends BaseApiController
{
    /**
     * Display the current workspace settings.
     */
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        /** @var \App\Models\Workspace|null $workspace */
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        try {
            $this->authorize('view', $workspace);

            $settings = $workspace->setting;
            /** @var \App\Models\Organization|null $organization */
            $organization = $workspace->organization;

            // Get plan limits for data retention
            $maxDataRetentionDays = $organization?->getLimit('data_retention_days');

            $responseData = [
                'workspace_uuid' => $workspace->uuid,
                'settings' => $settings->settings ?? [],
                'plan_limits' => [
                    'max_data_retention_days' => $maxDataRetentionDays,
                ],
            ];

            if ($settings) {
                $responseData['created_at'] = $settings->created_at;
                $responseData['updated_at'] = $settings->updated_at;
            }

            return $this->successResponse($responseData);
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Not authorized to perform this action');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }

    /**
     * Update the current workspace settings.
     */
    public function update(UpdateWorkspaceSettingRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        /** @var \App\Models\Workspace|null $workspace */
        $workspace = $user->currentWorkspace;

        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        try {
            $this->authorize('update', $workspace);

            /** @var \App\Models\Organization|null $organization */
            $organization = $workspace->organization;
            $maxDataRetentionDays = $organization?->getLimit('data_retention_days');

            // Validate data_retention_days against plan limits
            if (isset($request->settings['data_retention_days'])) {
                $requestedDays = (int) $request->settings['data_retention_days'];

                if ($maxDataRetentionDays !== null && $maxDataRetentionDays !== -1 && $requestedDays > $maxDataRetentionDays) {
                    return $this->errorResponse(
                        "Data retention days must be less than or equal to {$maxDataRetentionDays} days (plan limit)",
                        422
                    );
                }
            }

            $settings = $workspace->setting()->updateOrCreate(
                ['workspace_id' => $workspace->id],
                ['settings' => $request->settings]
            );

            return $this->successResponse([
                'workspace_uuid' => $workspace->uuid,
                'settings' => $settings->settings,
                'plan_limits' => [
                    'max_data_retention_days' => $maxDataRetentionDays,
                ],
                'created_at' => $settings->created_at,
                'updated_at' => $settings->updated_at,
            ], 'Workspace settings updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Let Laravel handle validation errors (returns 422)
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Not authorized to perform this action');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }
}
