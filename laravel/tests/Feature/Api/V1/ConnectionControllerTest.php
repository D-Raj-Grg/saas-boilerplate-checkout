<?php

namespace Tests\Feature\Api\V1;

use App\Enums\WorkspaceRole;
use App\Models\Connection;
use App\Models\ConnectionToken;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConnectionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        // Set encryption key for testing
        config(['app.key' => 'base64:'.base64_encode('12345678901234567890123456789012')]);

        $plan = Plan::factory()->create();

        // Create plan limits for connections
        PlanLimit::create([
            'plan_id' => $plan->id,
            'feature' => 'connections_per_workspace',
            'value' => '10',  // Allow 10 connections per workspace
            'type' => 'limit',
        ]);

        $this->organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);
        $this->user = User::factory()->create([
            'current_organization_id' => $this->organization->id,
        ]);
        $this->workspace = Workspace::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->user->update(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->users()->attach($this->user->id, [
            'joined_at' => now(),
            'role' => WorkspaceRole::MANAGER->value,
        ]);
    }

    public function test_initiate_connection_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/connections/initiate', [
            'redirect_url' => 'https://example.com/wp-admin',
        ]);

        $response->assertStatus(401);
    }

    public function test_initiate_connection_validates_redirect_url(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/connections/initiate', [
            'redirect_url' => 'invalid-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['redirect_url']);
    }

    public function test_initiate_connection_requires_workspace_context(): void
    {
        $this->user->update(['current_workspace_id' => null]);
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/connections/initiate', [
            'redirect_url' => 'https://example.com/wp-admin',
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'No workspace selected']);
    }

    public function test_initiate_connection_creates_token_successfully(): void
    {
        Sanctum::actingAs($this->user);

        $redirectUrl = 'https://example.com/wp-admin';
        $response = $this->postJson('/api/v1/connections/initiate', [
            'redirect_url' => $redirectUrl,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'oauth_token',
                    'redirect_url',
                    'expires_at',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('connection_tokens', [
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'redirect_url' => $redirectUrl,
            'used' => false,
        ]);

        $responseData = $response->json('data');
        $this->assertStringContainsString('oauth_token=', $responseData['redirect_url']);
    }

    public function test_exchange_token_validates_request(): void
    {
        $response = $this->postJson('/api/v1/connections/exchange', [
            'oauth_token' => 'invalid',
            'site_url' => 'invalid-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['oauth_token', 'site_url']);
    }

    public function test_exchange_token_rejects_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/connections/exchange', [
            'oauth_token' => Str::random(64),
            'site_url' => 'https://example.com',
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid or expired token']);
    }

    public function test_exchange_token_rejects_expired_token(): void
    {
        $expiredToken = ConnectionToken::create([
            'token' => Str::random(64),
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'redirect_url' => 'https://example.com',
            'expires_at' => now()->subMinutes(5),
            'used' => false,
        ]);

        $response = $this->postJson('/api/v1/connections/exchange', [
            'oauth_token' => $expiredToken->token,
            'site_url' => 'https://example.com',
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid or expired token']);
    }

    public function test_exchange_token_rejects_used_token(): void
    {
        $usedToken = ConnectionToken::create([
            'token' => Str::random(64),
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'redirect_url' => 'https://example.com',
            'expires_at' => now()->addMinutes(10),
            'used' => true,
        ]);

        $response = $this->postJson('/api/v1/connections/exchange', [
            'oauth_token' => $usedToken->token,
            'site_url' => 'https://example.com',
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Invalid or expired token']);
    }

    public function test_exchange_token_creates_connection_successfully(): void
    {
        $connectionToken = ConnectionToken::create([
            'token' => Str::random(64),
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'redirect_url' => 'https://example.com',
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        $siteUrl = 'https://mywordpress.com';
        $response = $this->postJson('/api/v1/connections/exchange', [
            'oauth_token' => $connectionToken->token,
            'site_url' => $siteUrl,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'connection_id',
                    'access_token',
                ],
                'message',
            ]);

        // Check connection was created
        $this->assertDatabaseHas('connections', [
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'status' => 'active',
        ]);

        // Check token was marked as used
        $this->assertDatabaseHas('connection_tokens', [
            'id' => $connectionToken->id,
            'used' => true,
        ]);

        // Check Sanctum token was created
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'WordPress Connection',
        ]);
    }

    public function test_exchange_token_updates_existing_connection_same_site(): void
    {
        // Create existing connection for same site
        $siteUrl = 'https://mywordpress.com';
        $existingConnection = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => $siteUrl,
            'config' => [
                'site_url' => $siteUrl,
                'access_token' => 'old-token',
            ],
            'status' => 'active',
        ]);

        $connectionToken = ConnectionToken::create([
            'token' => Str::random(64),
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'redirect_url' => 'https://example.com',
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        $response = $this->postJson('/api/v1/connections/exchange', [
            'oauth_token' => $connectionToken->token,
            'site_url' => $siteUrl, // Same site URL
        ]);

        $response->assertStatus(200);

        // Check connection was updated (not created new)
        $this->assertDatabaseCount('connections', 1);

        $existingConnection->refresh();
        $config = $existingConnection->config;
        $this->assertEquals($siteUrl, $config['site_url']);
        $this->assertNotEquals('old-token', $config['access_token']);
    }

    public function test_exchange_token_creates_new_connection_for_different_site(): void
    {
        // Create existing connection for one site
        $existingConnection = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://old-site.com',
            'config' => [
                'site_url' => 'https://old-site.com',
                'access_token' => 'old-token',
            ],
            'status' => 'active',
        ]);

        $connectionToken = ConnectionToken::create([
            'token' => Str::random(64),
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'redirect_url' => 'https://example.com',
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        $newSiteUrl = 'https://new-site.com';
        $response = $this->postJson('/api/v1/connections/exchange', [
            'oauth_token' => $connectionToken->token,
            'site_url' => $newSiteUrl,
        ]);

        $response->assertStatus(200);

        // Check new connection was created (now we have 2)
        $this->assertDatabaseCount('connections', 2);

        // Check new connection exists
        $this->assertDatabaseHas('connections', [
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => $newSiteUrl,
            'status' => 'active',
        ]);

        // Check old connection still exists unchanged
        $existingConnection->refresh();
        $this->assertEquals('https://old-site.com', $existingConnection->site_url);
    }

    public function test_revoke_connection_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/v1/connections/some-uuid');

        $response->assertStatus(401);
    }

    public function test_list_connections_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/connections');

        $response->assertStatus(401);
    }

    public function test_list_connections_requires_workspace(): void
    {
        $this->user->update(['current_workspace_id' => null]);
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/connections');

        $response->assertStatus(403);
    }

    public function test_list_connections_returns_only_current_workspace_connections(): void
    {
        Sanctum::actingAs($this->user);

        // Create connections for current workspace
        $connection1 = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://site1.com',
            'config' => ['site_url' => 'https://site1.com'],
            'status' => 'active',
            'created_at' => now()->subMinutes(5),
        ]);

        $connection2 = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://site2.com',
            'config' => ['site_url' => 'https://site2.com'],
            'status' => 'inactive',
            'created_at' => now(),
        ]);

        // Create connection for different workspace
        $otherWorkspace = Workspace::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $otherWorkspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://other-site.com',
            'config' => ['site_url' => 'https://other-site.com'],
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/connections');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'integration_name',
                        'site_url',
                        'status',
                        'last_sync_at',
                        'created_at',
                    ],
                ],
            ]);

        // Verify we only get current workspace connections
        $responseData = $response->json('data');
        $uuids = array_column($responseData, 'id');
        $this->assertContains($connection1->uuid, $uuids);
        $this->assertContains($connection2->uuid, $uuids);
    }

    public function test_revoke_connection_not_found(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson('/api/v1/connections/'.Str::uuid());

        $response->assertStatus(404);
    }

    public function test_revoke_connection_from_different_user(): void
    {
        // Create connection for this user
        $connection = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://example.com',
            'config' => [
                'site_url' => 'https://example.com',
                'access_token' => 'test-token',
            ],
            'status' => 'active',
        ]);

        // Create and authenticate as different user
        $otherUser = User::factory()->create([
            'current_organization_id' => $this->organization->id,
            'current_workspace_id' => $this->workspace->id,
        ]);
        $this->workspace->users()->attach($otherUser->id, [
            'joined_at' => now(),
            'role' => WorkspaceRole::VIEWER->value,
        ]);
        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/connections/{$connection->uuid}");

        $response->assertStatus(403); // Policy returns 403 Forbidden

        // Verify connection still exists
        $this->assertDatabaseHas('connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_revoke_connection_successfully(): void
    {
        Sanctum::actingAs($this->user);

        // Create a Sanctum token that will be revoked
        $sanctumToken = $this->user->createToken('Test Token')->plainTextToken;

        // Create connection
        $connection = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://example.com',
            'config' => [
                'site_url' => 'https://example.com',
                'access_token' => $sanctumToken,
            ],
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/v1/connections/{$connection->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Connection revoked successfully',
            ]);

        // Verify connection was deleted
        $this->assertDatabaseMissing('connections', [
            'id' => $connection->id,
        ]);

        // Verify token was revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'Test Token',
        ]);
    }

    public function test_revoke_connection_without_access_token(): void
    {
        Sanctum::actingAs($this->user);

        // Create connection without access token
        $connection = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://example.com',
            'config' => [
                'site_url' => 'https://example.com',
                // No access_token
            ],
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/v1/connections/{$connection->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Connection revoked successfully',
            ]);

        // Verify connection was deleted
        $this->assertDatabaseMissing('connections', [
            'id' => $connection->id,
        ]);
    }

    public function test_update_connection_status_requires_authentication(): void
    {
        $response = $this->patchJson('/api/v1/connections/status', [
            'workspace_uuid' => $this->workspace->uuid,
            'plugin_version' => '1.0.0',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_connection_status_validates_request(): void
    {
        Sanctum::actingAs($this->user);

        // Missing required fields
        $response = $this->patchJson('/api/v1/connections/status', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_uuid', 'plugin_version']);

        // Invalid data types
        $response = $this->patchJson('/api/v1/connections/status', [
            'workspace_uuid' => 'invalid-uuid',
            'plugin_version' => 123, // Should be string
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_uuid', 'plugin_version']);
    }

    public function test_update_connection_status_requires_valid_workspace(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->patchJson('/api/v1/connections/status', [
            'workspace_uuid' => Str::uuid(),
            'plugin_version' => '1.0.0',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_uuid']);
    }

    public function test_update_connection_status_requires_matching_token(): void
    {
        // Create connection with a different token
        $otherToken = $this->user->createToken('Other Token')->plainTextToken;
        Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://example.com',
            'config' => [
                'site_url' => 'https://example.com',
                'access_token' => $otherToken,
            ],
            'status' => 'active',
        ]);

        // Authenticate with a different token
        $currentToken = $this->user->createToken('Current Token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$currentToken);

        $response = $this->patchJson('/api/v1/connections/status', [
            'workspace_uuid' => $this->workspace->uuid,
            'plugin_version' => '1.0.0',
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Connection not found for this workspace and token']);
    }

    public function test_update_connection_status_successfully(): void
    {
        // Create a Sanctum token for the connection
        $token = $this->user->createToken('WordPress Connection')->plainTextToken;

        // Create connection with this token
        $connection = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://example.com',
            'config' => [
                'site_url' => 'https://example.com',
                'access_token' => $token,
            ],
            'status' => 'active',
            'plugin_version' => null,
        ]);

        // Authenticate using the same token
        $this->withHeader('Authorization', 'Bearer '.$token);

        $response = $this->patchJson('/api/v1/connections/status', [
            'workspace_uuid' => $this->workspace->uuid,
            'plugin_version' => '1.2.3',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'connection_id',
                    'plugin_version',
                    'last_sync_at',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Connection updated successfully',
                'data' => [
                    'connection_id' => $connection->uuid,
                    'plugin_version' => '1.2.3',
                ],
            ]);

        // Verify database was updated
        $this->assertDatabaseHas('connections', [
            'id' => $connection->id,
            'plugin_version' => '1.2.3',
        ]);

        // Verify last_sync_at was updated
        $connection->refresh();
        $this->assertNotNull($connection->last_sync_at);
        $this->assertTrue($connection->last_sync_at->greaterThan(now()->subMinute()));
    }

    public function test_update_connection_status_updates_only_plugin_fields(): void
    {
        // Create a Sanctum token for the connection
        $token = $this->user->createToken('WordPress Connection')->plainTextToken;

        // Create connection with initial data
        $connection = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://example.com',
            'config' => [
                'site_url' => 'https://example.com',
                'access_token' => $token,
                'some_other_field' => 'value',
            ],
            'status' => 'active',
            'plugin_version' => '1.0.0',
        ]);

        // Authenticate using the token
        $this->withHeader('Authorization', 'Bearer '.$token);

        $response = $this->patchJson('/api/v1/connections/status', [
            'workspace_uuid' => $this->workspace->uuid,
            'plugin_version' => '2.0.0',
        ]);

        $response->assertStatus(200);

        // Verify only plugin fields were updated
        $connection->refresh();
        $this->assertEquals('2.0.0', $connection->plugin_version);

        // Verify other fields remain unchanged
        $this->assertEquals('WordPress', $connection->integration_name);
        $this->assertEquals('https://example.com', $connection->site_url);
        $this->assertEquals('active', $connection->status);
        $this->assertEquals('value', $connection->config['some_other_field']);
    }

    public function test_sync_connection_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/connections/sync', [
            'connection_uuid' => Str::uuid(),
        ]);

        $response->assertStatus(401);
    }

    public function test_sync_connection_validates_request(): void
    {
        Sanctum::actingAs($this->user);

        // Missing connection_uuid
        $response = $this->postJson('/api/v1/connections/sync', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['connection_uuid']);

        // Invalid UUID
        $response = $this->postJson('/api/v1/connections/sync', [
            'connection_uuid' => Str::uuid(), // Non-existent
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['connection_uuid']);
    }

    public function test_sync_connection_requires_authorization(): void
    {
        // Create connection for this workspace
        $connection = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://example.com',
            'config' => ['site_url' => 'https://example.com'],
            'status' => 'active',
        ]);

        // Create another user in different workspace
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $otherUser->update(['current_workspace_id' => $otherWorkspace->id]);
        $otherWorkspace->users()->attach($otherUser->id, [
            'joined_at' => now(),
            'role' => WorkspaceRole::MANAGER->value,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson('/api/v1/connections/sync', [
            'connection_uuid' => $connection->uuid,
        ]);

        $response->assertStatus(403);
    }

    public function test_sync_connection_fails_for_inactive_connection(): void
    {
        Sanctum::actingAs($this->user);

        // Create inactive connection
        $connection = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://example.com',
            'config' => ['site_url' => 'https://example.com'],
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/v1/connections/sync', [
            'connection_uuid' => $connection->uuid,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to sync connection. Connection may be inactive.',
            ]);
    }

    public function test_sync_connection_dispatches_job_successfully(): void
    {
        Queue::fake();
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);
        Sanctum::actingAs($this->user);

        // Create active connection
        $connection = Connection::create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'integration_name' => 'WordPress',
            'site_url' => 'https://example.com',
            'config' => [
                'site_url' => 'https://example.com',
                'access_token' => 'test-token',
            ],
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/connections/sync', [
            'connection_uuid' => $connection->uuid,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'connection_id',
                    'site_url',
                    'status',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Manual sync initiated successfully',
                'data' => [
                    'connection_id' => $connection->uuid,
                    'site_url' => 'https://example.com',
                    'status' => 'Sync job dispatched',
                ],
            ]);

        // Note: The boilerplate WordPressWebhookService sends webhooks synchronously
        // If you want async job dispatch, uncomment and implement job dispatching in manualSync()
        // Queue::assertPushed(\App\Jobs\WordPressWebhookJob::class, function ($job) use ($connection) {
        //     return $job->wpConnection->id === $connection->id
        //         && $job->eventType === 'sync.manual';
        // });
    }
}
