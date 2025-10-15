<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Connection\ExchangeTokenRequest;
use App\Http\Requests\Connection\InitiateConnectionRequest;
use App\Http\Requests\Connection\SyncConnectionRequest;
use App\Http\Requests\Connection\UpdateConnectionRequest;
use App\Jobs\SendEmailVerificationJob;
use App\Models\Connection;
use App\Services\ConnectionService;
use App\Services\VerificationService;
use App\Services\WordPressWebhookService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class ConnectionController extends BaseApiController
{
    public function __construct(
        private ConnectionService $connectionService
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Connection::class);

        $user = request()->user();
        if (! $user) {
            return $this->unauthorizedResponse('Unauthenticated');
        }

        $workspace = $user->currentWorkspace;
        if (! $workspace) {
            return $this->notFoundResponse('No workspace selected');
        }

        // Build query based on user's role
        $query = Connection::where('workspace_id', $user->current_workspace_id);

        // If user is not admin/owner, only show their own connections
        if (! $user->canManageWorkspace($workspace)) {
            $query->where('user_id', $user->id);
        }

        $connections = $query->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            $connections->map(fn (Connection $connection) => [
                'id' => $connection->uuid,
                'integration_name' => $connection->integration_name,
                'site_url' => $connection->site_url,
                'status' => $connection->status,
                'last_sync_at' => $connection->last_sync_at ? $connection->last_sync_at->toISOString() : null,
                'created_at' => $connection->created_at?->toISOString() ?? '',
                'created_by' => $connection->user ? [
                    'name' => $connection->user->name,
                    'email' => $connection->user->email,
                ] : null,
                'permissions' => [
                    'can_view' => $user->can('view', $connection),
                    'can_update' => $user->can('update', $connection),
                    'can_delete' => $user->can('delete', $connection),
                ],
                'plugin_version' => $connection->plugin_version,
            ]),
            'Connections retrieved successfully'
        );
    }

    public function initiate(InitiateConnectionRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return $this->unauthorizedResponse('Unauthenticated');
            }

            // Check email verification before allowing connection initiation
            if (! $user->email_verified_at) {
                // Send email verification email
                $verificationService = app(VerificationService::class);
                $verification = $verificationService->createEmailVerification($user, 48 * 60);
                SendEmailVerificationJob::dispatch($user, $verification);

                return $this->errorResponse(
                    'Your email must be verified before you can create connections. We\'ve sent a verification email to your address. Please check your email and verify your account.',
                    403,
                    [
                        'error_code' => 'EMAIL_VERIFICATION_REQUIRED',
                        'email' => $user->email,
                        'verification_sent' => true,
                    ]
                );
            }

            $workspace = $user->currentWorkspace;
            if (! $workspace) {
                return $this->notFoundResponse('No workspace selected');
            }

            // Check connection limit for the workspace
            if ($response = $this->checkConnectionLimits($workspace)) {
                return $response;
            }

            $connectionToken = $this->connectionService->initiateConnection(
                $user,
                $workspace->id,
                $request->redirect_url
            );

            $redirectUrl = $request->redirect_url;
            $separator = strpos($redirectUrl, '?') !== false ? '&' : '?';
            $redirectUrlWithToken = $redirectUrl.$separator.'oauth_token='.$connectionToken->token;

            return $this->successResponse([
                'oauth_token' => $connectionToken->token,
                'redirect_url' => $redirectUrlWithToken,
                'expires_at' => $connectionToken->expires_at->toISOString(),
            ], 'Connection token generated successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate connection token');
        }
    }

    public function exchange(ExchangeTokenRequest $request): JsonResponse
    {
        try {
            $result = $this->connectionService->exchangeToken(
                $request->oauth_token,
                $request->site_url
            );

            /** @var \App\Models\Workspace $workspace */
            $workspace = $result['connection']->workspace;

            return $this->successResponse([
                'connection_id' => $result['connection']->uuid,
                'access_token' => $result['access_token'],
                'workspace_uuid' => $workspace->uuid,
            ], 'Connection established successfully');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function revoke(Connection $connection): JsonResponse
    {
        try {
            $this->authorize('delete', $connection);

            // Revoke the Sanctum token if it exists
            if ($connection->config && isset($connection->config['access_token'])) {
                $this->connectionService->revokeAccessToken($connection->config['access_token']);
            }

            $connection->delete();

            return $this->successResponse(null, 'Connection revoked successfully');
        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('You are not authorized to delete this connection');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to revoke connection');
        }
    }

    public function updateStatus(UpdateConnectionRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return $this->unauthorizedResponse('Unauthenticated');
            }

            // Get the workspace from the provided workspace_uuid
            $workspace = \App\Models\Workspace::where('uuid', $request->workspace_uuid)->first();
            if (! $workspace) {
                return $this->notFoundResponse('Workspace not found');
            }

            // Find the connection that belongs to this workspace and uses this token
            // The token is stored as plain text in config['access_token']
            $bearerToken = $request->bearerToken();
            $connection = Connection::where('workspace_id', $workspace->id)
                ->get()
                ->first(function ($conn) use ($bearerToken) {
                    return $conn->config
                        && isset($conn->config['access_token'])
                        && $conn->config['access_token'] === $bearerToken;
                });

            if (! $connection) {
                return $this->notFoundResponse('Connection not found for this workspace and token');
            }

            // Update only plugin version
            $connection->update([
                'plugin_version' => $request->plugin_version,
                'last_sync_at' => now(),
            ]);

            return $this->successResponse([
                'connection_id' => $connection->uuid,
                'plugin_version' => $connection->plugin_version,
                'last_sync_at' => $connection->last_sync_at?->toISOString(),
            ], 'Connection updated successfully');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update connection: '.$e->getMessage());
        }
    }

    public function sync(SyncConnectionRequest $request, WordPressWebhookService $webhookService): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return $this->unauthorizedResponse('Unauthenticated');
            }

            // Find the connection by UUID
            $connection = Connection::where('uuid', $request->connection_uuid)->first();
            if (! $connection) {
                return $this->notFoundResponse('Connection not found');
            }

            // Check authorization - user must have access to this connection's workspace
            $this->authorize('view', $connection);

            // Trigger manual sync
            $success = $webhookService->manualSync($connection);

            if (! $success) {
                return $this->errorResponse('Failed to sync connection. Connection may be inactive.', 400);
            }

            return $this->successResponse([
                'connection_id' => $connection->uuid,
                'site_url' => $connection->site_url,
                'status' => 'Sync job dispatched',
            ], 'Manual sync initiated successfully');

        } catch (AuthorizationException $e) {
            return $this->forbiddenResponse('You are not authorized to sync this connection');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to initiate sync: '.$e->getMessage());
        }
    }
}
