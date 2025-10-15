<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\ConnectionToken;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class ConnectionService
{
    public function initiateConnection(User $user, int $workspaceId, string $redirectUrl): ConnectionToken
    {
        return ConnectionToken::generate($user->id, $workspaceId, $redirectUrl);
    }

    /**
     * @return array{connection: Connection, access_token: string}
     */
    public function exchangeToken(string $tempToken, string $siteUrl): array
    {
        $connectionToken = ConnectionToken::findValidToken($tempToken);

        if (! $connectionToken) {
            throw new \Exception('Invalid or expired token');
        }

        $user = $connectionToken->user;
        $workspace = $connectionToken->workspace;

        if (! $user) {
            throw new \Exception('User not found');
        }

        if (! $workspace) {
            throw new \Exception('Workspace not found');
        }

        $sanctumToken = $user->createToken('WordPress Connection', ['*'])->plainTextToken;

        $connection = Connection::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'integration_name' => 'WordPress',
                'site_url' => $siteUrl,
            ],
            [
                'config' => [
                    'site_url' => $siteUrl,
                    'access_token' => $sanctumToken,
                    'established_at' => now()->toISOString(),
                ],
                'status' => 'active',
            ]
        );

        $connectionToken->markAsUsed();

        return [
            'connection' => $connection,
            'access_token' => $sanctumToken,
        ];
    }

    public function getActiveConnection(int $userId, int $workspaceId): ?Connection
    {
        return Connection::where('user_id', $userId)
            ->where('workspace_id', $workspaceId)
            ->where('integration_name', 'wordpress')
            ->where('status', 'active')
            ->first();
    }

    public function disconnectConnection(int $connectionId, int $userId): bool
    {
        $connection = Connection::where('id', $connectionId)
            ->where('user_id', $userId)
            ->first();

        if (! $connection) {
            return false;
        }

        $connection->update(['status' => 'inactive']);

        return true;
    }

    public function revokeAccessToken(string $accessToken): void
    {
        $token = PersonalAccessToken::findToken($accessToken);

        if ($token) {
            $token->delete();
        }
    }
}
