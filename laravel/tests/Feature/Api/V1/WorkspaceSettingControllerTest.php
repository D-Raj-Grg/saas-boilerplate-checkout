<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspaceSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $admin;

    protected User $member;

    protected User $outsider;

    protected Organization $organization;

    protected Workspace $workspace;

    protected Workspace $otherWorkspace;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plans and plan features
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'PlanFeaturesSeeder']);

        // Create users
        $this->owner = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->member = User::factory()->create();
        $this->outsider = User::factory()->create();

        // Create organization using role-based system
        $this->organization = Organization::factory()->create();
        $this->organization->addUser($this->owner, OrganizationRole::OWNER);
        $this->organization->attachPlan(Plan::first());

        $this->workspace = Workspace::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $this->otherWorkspace = Workspace::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        // Add users to organization with appropriate roles
        // Admin user should have OrganizationRole::ADMIN to be able to update workspace settings
        $this->organization->addUser($this->admin, OrganizationRole::ADMIN);
        $this->organization->addUser($this->member, OrganizationRole::MEMBER);

        // Assign workspace roles
        $this->workspace->users()->attach($this->owner->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);
        $this->workspace->users()->attach($this->admin->id, ['role' => WorkspaceRole::EDITOR->value, 'joined_at' => now()]);
        $this->workspace->users()->attach($this->member->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);

        // Set current workspace for users
        $this->owner->update(['current_workspace_id' => $this->workspace->id]);
        $this->admin->update(['current_workspace_id' => $this->workspace->id]);
        $this->member->update(['current_workspace_id' => $this->workspace->id]);
    }

    public function test_workspace_settings_endpoints_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/workspace/settings');
        $response->assertStatus(401);

        $response = $this->putJson('/api/v1/workspace/settings');
        $response->assertStatus(401);
    }

    public function test_workspace_settings_require_current_workspace(): void
    {
        $user = User::factory()->create();
        $user->update(['current_workspace_id' => null]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/workspace/settings');
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No workspace selected',
            ]);
    }

    public function test_member_can_view_workspace_settings(): void
    {
        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/workspace/settings');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'workspace_uuid',
                    'settings',
                ],
            ]);
    }

    public function test_view_workspace_settings_returns_empty_array_when_no_settings_exist(): void
    {
        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/workspace/settings');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'workspace_uuid' => $this->workspace->uuid,
                    'settings' => [],
                ],
            ]);
    }

    public function test_view_workspace_settings_returns_existing_settings(): void
    {
        $settings = [
            'default_traffic_split' => 60,
            'timezone' => 'America/New_York',
        ];

        WorkspaceSetting::factory()->create([
            'workspace_id' => $this->workspace->id,
            'settings' => $settings,
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/workspace/settings');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'workspace_uuid' => $this->workspace->uuid,
                    'settings' => $settings,
                ],
            ]);
    }

    public function test_workspace_settings_use_current_workspace_context(): void
    {
        // Create settings for both workspaces
        $settings1 = ['timezone' => 'UTC'];
        $settings2 = ['timezone' => 'PST'];

        WorkspaceSetting::factory()->create([
            'workspace_id' => $this->workspace->id,
            'settings' => $settings1,
        ]);

        WorkspaceSetting::factory()->create([
            'workspace_id' => $this->otherWorkspace->id,
            'settings' => $settings2,
        ]);

        // Check member gets settings for their current workspace
        Sanctum::actingAs($this->member);
        $response = $this->getJson('/api/v1/workspace/settings');
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'settings' => $settings1,
                ],
            ]);

        // Change current workspace and check again
        // Note: member is already in organization, so no need to add again
        $this->otherWorkspace->users()->attach($this->member->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);
        $this->member->update(['current_workspace_id' => $this->otherWorkspace->id]);

        // Refresh the member to ensure the relationship is updated
        $this->member->refresh();

        $response = $this->getJson('/api/v1/workspace/settings');
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'settings' => $settings2,
                ],
            ]);
    }

    public function test_member_cannot_update_workspace_settings(): void
    {
        Sanctum::actingAs($this->member);

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => ['timezone' => 'UTC'],
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_update_workspace_settings(): void
    {
        Sanctum::actingAs($this->admin);

        $newSettings = [
            'default_traffic_split' => 70,
            'timezone' => 'Europe/London',
        ];

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => $newSettings,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Workspace settings updated successfully',
                'data' => [
                    'workspace_uuid' => $this->workspace->uuid,
                    'settings' => $newSettings,
                ],
            ])
            ->assertJsonMissing([
                'data' => [
                    'id' => null,
                    'workspace_id' => null,
                ],
            ]);

        $this->assertDatabaseHas('workspace_settings', [
            'workspace_id' => $this->workspace->id,
            'settings' => json_encode($newSettings),
        ]);
    }

    public function test_owner_can_update_workspace_settings(): void
    {
        Sanctum::actingAs($this->owner);

        $newSettings = [
            'default_traffic_split' => 80,
            'auto_stop_enabled' => true,
        ];

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => $newSettings,
        ]);

        $response->assertStatus(200);
    }

    public function test_update_workspace_settings_creates_record_if_not_exists(): void
    {
        Sanctum::actingAs($this->admin);

        $this->assertDatabaseMissing('workspace_settings', [
            'workspace_id' => $this->workspace->id,
        ]);

        $settings = [
            'timezone' => 'Asia/Tokyo',
        ];

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => $settings,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('workspace_settings', [
            'workspace_id' => $this->workspace->id,
            'settings' => json_encode($settings),
        ]);
    }

    public function test_update_workspace_settings_updates_existing_record(): void
    {
        $originalSettings = [
            'timezone' => 'UTC',
            'default_traffic_split' => 50,
        ];

        WorkspaceSetting::factory()->create([
            'workspace_id' => $this->workspace->id,
            'settings' => $originalSettings,
        ]);

        Sanctum::actingAs($this->admin);

        $newSettings = [
            'timezone' => 'America/Los_Angeles',
            'default_traffic_split' => 60,
            'new_field' => 'new_value',
        ];

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => $newSettings,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('workspace_settings', [
            'workspace_id' => $this->workspace->id,
            'settings' => json_encode($newSettings),
        ]);

        $this->assertDatabaseCount('workspace_settings', 1);
    }

    public function test_update_workspace_settings_validates_input(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson('/api/v1/workspace/settings', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['settings']);

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => 'not-an-array',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['settings']);
    }

    public function test_update_workspace_settings_requires_current_workspace(): void
    {
        $user = User::factory()->create();
        $user->update(['current_workspace_id' => null]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => ['timezone' => 'UTC'],
        ]);
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No workspace selected',
            ]);
    }

    public function test_workspace_settings_response_includes_plan_limits(): void
    {
        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/workspace/settings');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'workspace_uuid',
                    'settings',
                    'plan_limits' => [
                        'max_data_retention_days',
                    ],
                ],
            ]);
    }

    public function test_workspace_settings_response_includes_plan_limits_with_existing_settings(): void
    {
        $settings = [
            'data_retention_days' => 30,
            'cookie_lifetime' => 30,
        ];

        WorkspaceSetting::factory()->create([
            'workspace_id' => $this->workspace->id,
            'settings' => $settings,
        ]);

        Sanctum::actingAs($this->member);

        $response = $this->getJson('/api/v1/workspace/settings');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'workspace_uuid',
                    'settings',
                    'plan_limits' => [
                        'max_data_retention_days',
                    ],
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_update_workspace_settings_includes_plan_limits_in_response(): void
    {
        Sanctum::actingAs($this->admin);

        $newSettings = [
            'data_retention_days' => 5, // Within the 7 day limit
            'cookie_lifetime' => 30,
        ];

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => $newSettings,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'workspace_uuid',
                    'settings',
                    'plan_limits' => [
                        'max_data_retention_days',
                    ],
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_data_retention_days_validation_within_plan_limit(): void
    {
        Sanctum::actingAs($this->admin);

        // The free plan has a limit of 7 days (from PlanSeeder)
        $validSettings = [
            'data_retention_days' => 5, // Within the 7 day limit
            'cookie_lifetime' => 30,
        ];

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => $validSettings,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'settings' => $validSettings,
                ],
            ]);
    }

    public function test_data_retention_days_validation_exceeds_plan_limit(): void
    {
        Sanctum::actingAs($this->admin);

        // The free plan has a limit of 7 days (from PlanSeeder)
        $invalidSettings = [
            'data_retention_days' => 10, // Exceeds the 7 day plan limit
            'cookie_lifetime' => 30,
        ];

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => $invalidSettings,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'message',
            ]);

        // Verify the error message mentions the plan limit
        $responseData = $response->json();
        $this->assertStringContainsString('plan limit', $responseData['message']);
    }

    public function test_data_retention_days_validation_at_plan_limit_boundary(): void
    {
        Sanctum::actingAs($this->admin);

        // Test setting exactly at the plan limit (7 days for free plan)
        $boundarySettings = [
            'data_retention_days' => 7, // Exactly at the plan limit
            'cookie_lifetime' => 30,
        ];

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => $boundarySettings,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'settings' => $boundarySettings,
                ],
            ]);
    }

    public function test_data_retention_days_validation_with_unlimited_plan(): void
    {
        // Create a plan with unlimited data retention (-1)
        $unlimitedPlan = Plan::factory()->create(['name' => 'Unlimited Plan']);
        $unlimitedPlan->limits()->create([
            'feature' => 'data_retention_days',
            'value' => '-1',
            'type' => 'limit',
            'tracking_scope' => 'organization',
        ]);

        // Attach unlimited plan to organization
        $this->organization->attachPlan($unlimitedPlan);

        Sanctum::actingAs($this->admin);

        $settings = [
            'data_retention_days' => 999, // Should be allowed with unlimited plan
            'cookie_lifetime' => 30,
        ];

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => $settings,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'settings' => $settings,
                ],
            ]);
    }

    public function test_data_retention_days_validation_with_null_plan_limit(): void
    {
        // Create a plan without data retention limit (but has other limits)
        $limitedPlan = Plan::factory()->create(['name' => 'Limited Plan']);
        $limitedPlan->limits()->create([
            'feature' => 'team_members',
            'value' => '10',
            'type' => 'limit',
            'tracking_scope' => 'organization',
        ]);

        // Attach plan without data retention limit
        $this->organization->attachPlan($limitedPlan);

        Sanctum::actingAs($this->admin);

        $settings = [
            'data_retention_days' => 100, // Should be allowed when no limit is set
            'cookie_lifetime' => 30,
        ];

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => $settings,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'settings' => $settings,
                ],
            ]);
    }

    public function test_update_settings_without_data_retention_days_works(): void
    {
        Sanctum::actingAs($this->admin);

        $settings = [
            'cookie_lifetime' => 30,
            'timezone' => 'UTC',
        ];

        $response = $this->putJson('/api/v1/workspace/settings', [
            'settings' => $settings,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'settings' => $settings,
                ],
            ]);
    }
}
