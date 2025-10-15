<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Workspace;

/**
 * BOILERPLATE: Plan Validation Service
 *
 * This service validates features and resources against plan limits.
 * Customize the validation methods to match your SaaS features and limits.
 */
class PlanValidationService
{
    /**
     * BOILERPLATE: Validate custom feature limits.
     *
     * Example method for validating your custom SaaS features against plan limits.
     * Replace this with your own feature validation logic.
     *
     * @param  array<string, mixed>  $data
     * @return array{message: string, code: string}|null Returns error details if invalid, null if valid
     */
    public function validateCustomFeatureLimits(array $data, Workspace $workspace): ?array
    {
        $organization = $workspace->organization;
        if (! $organization instanceof Organization) {
            return null;
        }

        // EXAMPLE: Check if organization can create one more of your custom feature
        // Replace 'custom_feature' with your actual feature name from plan_limits table
        /*
        if (! $organization->canUse('custom_feature', 1, $workspace)) {
            $limit = $organization->getLimit('custom_feature');

            return [
                'message' => "Custom feature limit reached. Your plan allows {$limit} features.",
                'code' => 'CUSTOM_FEATURE_LIMIT_EXCEEDED',
            ];
        }
        */

        return null;
    }

    /**
     * Validate connection limits for a workspace.
     *
     * @return array{message: string, code: string}|null Returns error details if invalid, null if valid
     */
    public function validateConnectionLimits(Workspace $workspace): ?array
    {
        $organization = $workspace->organization;
        if (! $organization instanceof Organization) {
            return null;
        }

        if (! $organization->canUse('connections_per_workspace', 1, $workspace)) {
            return [
                'message' => 'Connection limit reached for your current plan',
                'code' => 'CONNECTION_LIMIT_EXCEEDED',
            ];
        }

        return null;
    }

    /**
     * Validate team member limits for organization.
     *
     * @param  int  $newMemberCount  Number of new members being added
     * @return array{message: string, code: string}|null Returns error details if invalid, null if valid
     */
    public function validateTeamMemberLimits(Workspace $workspace, int $newMemberCount): ?array
    {
        $organization = $workspace->organization;
        if (! $organization instanceof Organization) {
            return null;
        }

        // team_members is organization-scoped, so check at org level
        $currentUsage = $organization->getCurrentUsage('team_members');

        if (! $organization->canUse('team_members', $newMemberCount)) {
            $limit = $organization->getLimit('team_members');
            $available = max(0, ($limit === -1 ? PHP_INT_MAX : $limit) - $currentUsage);

            return [
                'message' => "Team member limit reached. Your plan allows {$limit} members. You can invite {$available} more member(s).",
                'code' => 'TEAM_MEMBER_LIMIT_EXCEEDED',
            ];
        }

        return null;
    }

    /**
     * Get detailed plan limit information for an organization.
     *
     * @return array<string, mixed>
     */
    public function getPlanLimitsSummary(Organization $organization, ?Workspace $workspace = null): array
    {
        return [
            'connections_per_workspace' => [
                'limit' => $organization->getLimit('connections_per_workspace'),
                'current' => $workspace ? $organization->getCurrentUsage('connections_per_workspace', $workspace) : 0,
                'remaining' => $workspace ? $organization->getRemainingUsage('connections_per_workspace', $workspace) : null,
            ],
            'team_members' => [
                'limit' => $organization->getLimit('team_members'),
                'current' => $organization->getCurrentUsage('team_members'),
                'remaining' => $organization->getRemainingUsage('team_members'),
            ],
            // BOILERPLATE: Add your custom feature limits here
            // Example:
            // 'custom_feature' => [
            //     'limit' => $organization->getLimit('custom_feature'),
            //     'current' => $workspace ? $organization->getCurrentUsage('custom_feature', $workspace) : 0,
            //     'remaining' => $workspace ? $organization->getRemainingUsage('custom_feature', $workspace) : null,
            // ],
        ];
    }
}
