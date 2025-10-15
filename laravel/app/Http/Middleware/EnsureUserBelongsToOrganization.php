<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToOrganization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $parameter = 'organization'): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        // Get organization from route parameter
        $organizationUuid = $request->route($parameter);

        if (! $organizationUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not specified',
                'error_code' => 'ORGANIZATION_NOT_SPECIFIED',
            ], 400);
        }

        // Find organization by UUID
        $organization = Organization::where('uuid', $organizationUuid)->first();

        if (! $organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found',
                'error_code' => 'ORGANIZATION_NOT_FOUND',
            ], 404);
        }

        // Check if user has access to the organization
        $hasAccess = $user->ownsOrganization($organization) ||
                    $user->accessibleOrganizations()->where('organizations.id', $organization->id)->exists();

        if (! $hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this organization',
                'error_code' => 'ORGANIZATION_ACCESS_DENIED',
            ], 403);
        }

        // Add organization to request for controller access
        $request->attributes->set('organization', $organization);

        return $next($request);
    }
}
