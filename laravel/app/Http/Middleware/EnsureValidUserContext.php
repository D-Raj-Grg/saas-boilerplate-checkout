<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidUserContext
{
    /**
     * Handle an incoming request.
     *
     * SECURITY: This middleware ensures that users have a valid organization and workspace context
     * before performing operations. This prevents IDOR attacks and ensures proper authorization.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$requirements): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        // Check if organization context is required and available
        if (in_array('organization', $requirements) && ! $user->current_organization_id) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an organization first. Use the user session endpoints to set your current organization.',
                'error_code' => 'MISSING_ORGANIZATION_CONTEXT',
            ], 400);
        }

        // Check if workspace context is required and available
        if (in_array('workspace', $requirements) && ! $user->current_workspace_id) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a workspace first. Use the user session endpoints to set your current workspace.',
                'error_code' => 'MISSING_WORKSPACE_CONTEXT',
            ], 400);
        }

        // Validate that the user actually has access to their current organization
        if ($user->current_organization_id) {
            $hasOrgAccess = $user->accessibleOrganizations()
                ->where('organizations.id', $user->current_organization_id)
                ->exists();

            if (! $hasOrgAccess) {
                // Clear invalid context
                $user->update([
                    'current_organization_id' => null,
                    'current_workspace_id' => null,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Your current organization context is invalid. Please select a valid organization.',
                    'error_code' => 'INVALID_ORGANIZATION_CONTEXT',
                ], 400);
            }
        }

        // Validate that the user actually has access to their current workspace
        // Only check workspace context if it's required or if we have a workspace_id set
        if (in_array('workspace', $requirements) && $user->current_workspace_id) {
            // Check if user is direct member of the workspace
            $hasWorkspaceAccess = $user->workspaces()
                ->where('workspaces.id', $user->current_workspace_id)
                ->exists();

            // If not a direct member, check if they're org admin/owner
            if (! $hasWorkspaceAccess) {
                $currentWorkspace = \App\Models\Workspace::find($user->current_workspace_id);
                if ($currentWorkspace && $currentWorkspace->organization) {
                    $hasWorkspaceAccess = $user->isOrganizationAdmin($currentWorkspace->organization);
                }
            }

            if (! $hasWorkspaceAccess) {
                // Clear invalid workspace context
                $user->update(['current_workspace_id' => null]);

                return response()->json([
                    'success' => false,
                    'message' => 'Your current workspace context is invalid. Please select a valid workspace.',
                    'error_code' => 'INVALID_WORKSPACE_CONTEXT',
                ], 400);
            }
        }

        return $next($request);
    }
}
