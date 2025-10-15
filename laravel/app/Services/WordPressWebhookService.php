<?php

namespace App\Services;

use App\Models\Connection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WordPress Webhook Service - Boilerplate
 *
 * This service provides a foundation for sending webhooks to WordPress sites
 * through authenticated connections. Customize the methods below to fit your
 * application's specific needs.
 *
 * Example use cases:
 * - Send notifications when data changes
 * - Trigger sync operations
 * - Push updates to connected WordPress sites
 * - Handle real-time integrations
 */
class WordPressWebhookService
{
    /**
     * Send a generic webhook to a WordPress site.
     *
     * This is the core method for sending webhooks. Customize the payload
     * structure and webhook URL pattern based on your requirements.
     *
     * @param  Connection  $connection  The WordPress connection to send webhook to
     * @param  array<string, mixed>  $payload  The data to send in the webhook
     * @param  string  $event  The event type (e.g., 'data.updated', 'record.created')
     * @return bool Whether the webhook was sent successfully
     *
     * @throws ConnectionException If the HTTP connection fails
     * @throws RequestException If the HTTP request fails
     */
    public function sendWebhook(Connection $connection, array $payload, string $event = 'generic'): bool
    {
        // Verify connection is active
        if (! $connection->isActive()) {
            Log::warning('Skipping webhook for inactive connection', [
                'connection_id' => $connection->id,
                'status' => $connection->status,
            ]);

            return false;
        }

        // Get access token from connection config
        $accessToken = $connection->config['access_token'] ?? null;

        if (! $accessToken) {
            Log::error('No access token found for connection', [
                'connection_id' => $connection->id,
                'site_url' => $connection->site_url,
            ]);

            return false;
        }

        // Build the webhook URL
        $webhookUrl = $this->buildWebhookUrl($connection->site_url ?? '', $event);

        try {
            // Send the webhook request
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$accessToken,
                    'Content-Type' => 'application/json',
                    'User-Agent' => config('app.name').'-Webhook/1.0',
                ])
                ->post($webhookUrl, array_merge($payload, [
                    'event' => $event,
                    'timestamp' => now()->toIso8601String(),
                ]));

            if ($response->successful()) {
                Log::debug('WordPress webhook successful', [
                    'connection_id' => $connection->id,
                    'event' => $event,
                    'url' => $webhookUrl,
                    'status_code' => $response->status(),
                ]);

                return true;
            }

            // Log unsuccessful responses
            Log::warning('WordPress webhook returned error status', [
                'connection_id' => $connection->id,
                'event' => $event,
                'url' => $webhookUrl,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return false;

        } catch (ConnectionException $e) {
            Log::error('WordPress webhook connection failed', [
                'connection_id' => $connection->id,
                'event' => $event,
                'url' => $webhookUrl,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (RequestException $e) {
            Log::error('WordPress webhook request failed', [
                'connection_id' => $connection->id,
                'event' => $event,
                'url' => $webhookUrl,
                'error' => $e->getMessage(),
                'response_status' => $e->response->status(),
                'response_body' => $e->response->body(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('WordPress webhook unexpected error', [
                'connection_id' => $connection->id,
                'event' => $event,
                'url' => $webhookUrl,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Manually trigger a sync operation to a WordPress connection.
     *
     * BOILERPLATE: Customize this method based on your sync requirements.
     * You can dispatch a job, send a webhook, or perform any sync logic.
     *
     * @param  Connection  $connection  The WordPress connection to sync
     * @return bool Whether the sync was initiated successfully
     */
    public function manualSync(Connection $connection): bool
    {
        if (! $connection->isActive()) {
            Log::warning('Cannot sync to inactive connection', [
                'connection_id' => $connection->id,
                'status' => $connection->status,
            ]);

            return false;
        }

        // CUSTOMIZE: Implement your sync logic here
        // Examples:
        // - Send a webhook notification
        // - Dispatch a sync job to a queue
        // - Trigger a data export
        // - Update remote data

        $payload = [
            'action' => 'manual_sync',
            'connection_id' => $connection->uuid,
        ];

        try {
            $this->sendWebhook($connection, $payload, 'sync.manual');

            Log::info('Manual sync initiated', [
                'connection_id' => $connection->id,
                'site_url' => $connection->site_url,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Manual sync failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get all active WordPress connections for a workspace.
     *
     * BOILERPLATE: Helper method to retrieve connections.
     * Modify the filters based on your connection setup.
     *
     * @param  int  $workspaceId  The workspace ID
     * @return \Illuminate\Database\Eloquent\Collection<int, Connection>
     */
    public function getActiveConnections(int $workspaceId)
    {
        return Connection::where('workspace_id', $workspaceId)
            ->where('integration_name', 'WordPress')
            ->where('status', 'active')
            ->get();
    }

    /**
     * Build the WordPress webhook URL.
     *
     * BOILERPLATE: Customize this method to match your WordPress plugin's
     * webhook endpoint structure.
     *
     * @param  string  $siteUrl  The base WordPress site URL
     * @param  string  $event  The event type (optional, for event-specific endpoints)
     * @return string The complete webhook URL
     */
    protected function buildWebhookUrl(string $siteUrl, string $event = ''): string
    {
        // Default implementation - customize as needed
        // Examples:
        // - rtrim($siteUrl, '/').'/wp-json/your-plugin/v1/webhook'
        // - rtrim($siteUrl, '/').'/webhook/'.urlencode($event)
        // - rtrim($siteUrl, '/').'/api/sync'

        return rtrim($siteUrl, '/').'/wp-json/your-plugin/v1/webhook';
    }

    // =============================================================================
    // EXAMPLE METHODS - Uncomment and customize based on your application needs
    // =============================================================================

    /**
     * Send a notification when a record is created.
     *
     * Example: Notify WordPress when a new item is created in your app.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  array<string, mixed>  $recordData  The record data to send
     * @return void
     */
    // public function notifyRecordCreated(int $workspaceId, array $recordData): void
    // {
    //     $connections = $this->getActiveConnections($workspaceId);
    //
    //     foreach ($connections as $connection) {
    //         $payload = [
    //             'action' => 'record_created',
    //             'data' => $recordData,
    //         ];
    //
    //         try {
    //             $this->sendWebhook($connection, $payload, 'record.created');
    //         } catch (\Exception $e) {
    //             Log::error('Failed to notify WordPress of record creation', [
    //                 'connection_id' => $connection->id,
    //                 'error' => $e->getMessage(),
    //             ]);
    //         }
    //     }
    // }

    /**
     * Send a notification when a record is updated.
     *
     * Example: Notify WordPress when data is updated in your app.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  array<string, mixed>  $recordData  The updated record data
     * @return void
     */
    // public function notifyRecordUpdated(int $workspaceId, array $recordData): void
    // {
    //     $connections = $this->getActiveConnections($workspaceId);
    //
    //     foreach ($connections as $connection) {
    //         $payload = [
    //             'action' => 'record_updated',
    //             'data' => $recordData,
    //         ];
    //
    //         try {
    //             $this->sendWebhook($connection, $payload, 'record.updated');
    //         } catch (\Exception $e) {
    //             Log::error('Failed to notify WordPress of record update', [
    //                 'connection_id' => $connection->id,
    //                 'error' => $e->getMessage(),
    //             ]);
    //         }
    //     }
    // }

    /**
     * Batch notify multiple connections.
     *
     * Example: Send the same webhook to multiple connections efficiently.
     *
     * @param  int  $workspaceId  The workspace ID
     * @param  array<string, mixed>  $payload  The payload to send
     * @param  string  $event  The event type
     * @return array<string, mixed> Results with success/failure counts
     */
    // public function batchNotify(int $workspaceId, array $payload, string $event): array
    // {
    //     $connections = $this->getActiveConnections($workspaceId);
    //     $results = ['success' => 0, 'failed' => 0, 'errors' => []];
    //
    //     foreach ($connections as $connection) {
    //         try {
    //             if ($this->sendWebhook($connection, $payload, $event)) {
    //                 $results['success']++;
    //             } else {
    //                 $results['failed']++;
    //             }
    //         } catch (\Exception $e) {
    //             $results['failed']++;
    //             $results['errors'][] = [
    //                 'connection_id' => $connection->id,
    //                 'error' => $e->getMessage(),
    //             ];
    //         }
    //     }
    //
    //     return $results;
    // }
}
