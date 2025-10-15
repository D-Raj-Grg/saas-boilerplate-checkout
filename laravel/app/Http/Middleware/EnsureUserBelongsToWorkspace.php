<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToWorkspace
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $parameter = 'workspace'): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        // Get workspace from route parameter
        $workspaceUuid = $request->route($parameter);

        if (! $workspaceUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not specified',
                'error_code' => 'WORKSPACE_NOT_SPECIFIED',
            ], 400);
        }

        // Find workspace by UUID
        $workspace = Workspace::where('uuid', $workspaceUuid)->first();

        if (! $workspace) {
            return response()->json([
                'success' => false,
                'message' => 'Workspace not found',
                'error_code' => 'WORKSPACE_NOT_FOUND',
            ], 404);
        }

        // Check if user belongs to the workspace
        if (! $user->belongsToWorkspace($workspace)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this workspace',
                'error_code' => 'WORKSPACE_ACCESS_DENIED',
            ], 403);
        }

        // Add workspace to request for controller access
        $request->attributes->set('workspace', $workspace);

        return $next($request);
    }
}
