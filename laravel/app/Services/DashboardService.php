<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;

class DashboardService
{
    /**
     * Get dashboard statistics for a workspace.
     *
     * @return array{connections_count: int, members_count: int, workspace_name: string}
     */
    public function getWorkspaceStats(?Workspace $workspace): array
    {
        if (! $workspace) {
            return $this->getEmptyWorkspaceStats();
        }

        return [
            'connections_count' => $workspace->connections()->count(),
            'members_count' => $workspace->users()->count(),
            'workspace_name' => $workspace->name,
        ];
    }

    /**
     * Get organization statistics.
     *
     * @return array{workspaces_count: int, members_count: int, organization_name: string, current_plan: string|null}
     */
    public function getOrganizationStats(User $user): array
    {
        $organization = $user->currentOrganization;

        if (! $organization) {
            return $this->getEmptyOrganizationStats();
        }

        $currentPlan = $organization->getCurrentPlan();

        return [
            'workspaces_count' => $organization->workspaces()->count(),
            'members_count' => $organization->users()->count(),
            'organization_name' => $organization->name,
            'current_plan' => $currentPlan?->name,
        ];
    }

    /**
     * Get empty workspace stats structure.
     *
     * @return array{connections_count: int, members_count: int, workspace_name: string}
     */
    private function getEmptyWorkspaceStats(): array
    {
        return [
            'connections_count' => 0,
            'members_count' => 0,
            'workspace_name' => 'No workspace selected',
        ];
    }

    /**
     * Get empty organization stats structure.
     *
     * @return array{workspaces_count: int, members_count: int, organization_name: string, current_plan: string|null}
     */
    private function getEmptyOrganizationStats(): array
    {
        return [
            'workspaces_count' => 0,
            'members_count' => 0,
            'organization_name' => 'No organization selected',
            'current_plan' => null,
        ];
    }

    /**
     * Get dashboard data for API response.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user, ?Workspace $workspace): array
    {
        return [
            'title' => 'Dashboard',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'uuid' => $user->uuid,
            ],
            'organization_stats' => $this->getOrganizationStats($user),
            'workspace_stats' => $this->getWorkspaceStats($workspace),
            'links' => [
                'api_health' => url('/api/v1/health'),
            ],
        ];
    }
}
