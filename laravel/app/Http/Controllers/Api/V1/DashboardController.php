<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\DashboardService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends BaseApiController
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Display the dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            /** @var \App\Models\Workspace|null $workspace */
            $workspace = $this->getCurrentWorkspace();

            if ($workspace) {
                $this->authorize('view', $workspace);
            }

            $cacheKey = $workspace ? "dashboard:{$workspace->uuid}:{$user->uuid}" : "dashboard:no_workspace:{$user->uuid}";
            $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user, $workspace) {
                return $this->dashboardService->getDashboardData($user, $workspace);
            });

            return $this->successResponse($data, 'Dashboard data retrieved successfully');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('Not authorized to perform this action');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('An error occurred while processing your request');
        }
    }
}
