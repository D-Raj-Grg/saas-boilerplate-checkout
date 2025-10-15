<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plans
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    public function test_user_session_endpoints_require_authentication(): void
    {
        $response = $this->postJson('/api/v1/user/current-organization/test-uuid');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/user/current-workspace/test-uuid');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/user/clear-session');
        $response->assertStatus(401);
    }

    public function test_me_endpoint_sets_default_context(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        Sanctum::actingAs($user);

        // The /me endpoint should set default context
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.current_organization_uuid', $organization->uuid)
            ->assertJsonPath('data.user.current_workspace_uuid', $workspace->uuid);

        // Verify default context was set
        $user->refresh();
        $this->assertEquals($organization->id, $user->current_organization_id);
        $this->assertEquals($workspace->id, $user->current_workspace_id);
    }

    public function test_user_can_switch_organization(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        // Create two organizations
        $org1 = Organization::factory()->create([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $workspace1 = Workspace::factory()->create([
            'organization_id' => $org1->id,
        ]);
        $workspace1->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        $org2 = Organization::factory()->create([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $workspace2 = Workspace::factory()->create([
            'organization_id' => $org2->id,
        ]);
        $workspace2->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        // Set initial context
        $user->current_organization_id = $org1->id;
        $user->current_workspace_id = $workspace1->id;
        $user->save();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/user/current-organization/{$org2->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Current organization updated successfully',
                'data' => [
                    'organization' => [
                        'uuid' => $org2->uuid,
                        'name' => $org2->name,
                    ],
                    'workspace' => [
                        'uuid' => $workspace2->uuid,
                    ],
                ],
            ]);

        // Verify context was updated
        $user->refresh();
        $this->assertEquals($org2->id, $user->current_organization_id);
        $this->assertEquals($workspace2->id, $user->current_workspace_id);
    }

    public function test_user_cannot_switch_to_organization_without_access(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create([
            'owner_id' => $otherUser->id,
            'plan_id' => $plan->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/user/current-organization/{$organization->uuid}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Access forbidden',
            ]);
    }

    public function test_user_can_switch_workspace(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        // Update user role to MEMBER so workspace role is respected (not overridden by org admin status)
        $organization->updateUserRole($user, OrganizationRole::MEMBER);

        $workspace1 = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $workspace1->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        $workspace2 = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $workspace2->users()->attach($user->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);

        // Set initial context
        $user->current_organization_id = $organization->id;
        $user->current_workspace_id = $workspace1->id;
        $user->save();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/user/current-workspace/{$workspace2->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Current workspace updated successfully',
                'data' => [
                    'workspace' => [
                        'uuid' => $workspace2->uuid,
                        'name' => $workspace2->name,
                        'role' => WorkspaceRole::VIEWER->value,
                    ],
                ],
            ]);

        // Verify context was updated
        $user->refresh();
        $this->assertEquals($workspace2->id, $user->current_workspace_id);
    }

    public function test_user_cannot_switch_to_workspace_without_access(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create([
            'owner_id' => $otherUser->id,
            'plan_id' => $plan->id,
        ]);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Access forbidden',
            ]);
    }

    public function test_switching_workspace_updates_organization_context(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        // Create two organizations with workspaces
        $org1 = Organization::factory()->create([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $workspace1 = Workspace::factory()->create([
            'organization_id' => $org1->id,
        ]);
        $workspace1->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        $org2 = Organization::factory()->create([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $workspace2 = Workspace::factory()->create([
            'organization_id' => $org2->id,
        ]);
        $workspace2->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        // Set initial context to org1
        $user->current_organization_id = $org1->id;
        $user->current_workspace_id = $workspace1->id;
        $user->save();

        Sanctum::actingAs($user);

        // Switch to workspace in org2
        $response = $this->postJson("/api/v1/user/current-workspace/{$workspace2->uuid}");

        $response->assertStatus(200);

        // Verify both workspace and organization were updated
        $user->refresh();
        $this->assertEquals($workspace2->id, $user->current_workspace_id);
        $this->assertEquals($org2->id, $user->current_organization_id);
    }

    public function test_user_can_clear_context(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        // Set context
        $user->current_organization_id = $organization->id;
        $user->current_workspace_id = $workspace->id;
        $user->save();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/user/clear-session');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User session cleared successfully',
            ]);

        // Verify context was cleared
        $user->refresh();
        $this->assertNull($user->current_organization_id);
        $this->assertNull($user->current_workspace_id);
    }

    public function test_default_context_is_set_for_new_user(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create([
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $workspace->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        Sanctum::actingAs($user);

        // The /me endpoint should set default context if not set
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200);

        // Verify default context was set
        $user->refresh();
        $this->assertEquals($organization->id, $user->current_organization_id);
        $this->assertEquals($workspace->id, $user->current_workspace_id);
    }

    public function test_invalid_organization_uuid_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/user/current-organization/12345678-1234-1234-1234-123456789012');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Organization not found',
            ]);
    }

    public function test_invalid_workspace_uuid_returns_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/user/current-workspace/12345678-1234-1234-1234-123456789012');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Workspace not found',
            ]);
    }

    public function test_switching_to_org_as_member_sets_accessible_workspace(): void
    {
        $user = User::factory()->create();
        $orgOwner = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create([
            'owner_id' => $orgOwner->id,
            'plan_id' => $plan->id,
        ]);

        // Add user as organization member so they have access to the organization
        $organization->addUser($user, OrganizationRole::MEMBER);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        // User is a member of the workspace
        $workspace->users()->attach($user->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/user/current-organization/{$organization->uuid}");

        $response->assertStatus(200);

        // Verify workspace was set correctly
        $user->refresh();
        $this->assertEquals($organization->id, $user->current_organization_id);
        $this->assertEquals($workspace->id, $user->current_workspace_id);
    }

    public function test_organization_owner_can_switch_to_workspace_created_by_admin(): void
    {
        $owner = User::factory()->create(['name' => 'Owner']);
        $admin = User::factory()->create(['name' => 'Admin']);
        $plan = Plan::first();

        // Create organization owned by owner
        $organization = Organization::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Test Organization',
            'plan_id' => $plan->id,
        ]);

        // Add admin to organization as admin
        $organization->addUser($admin, OrganizationRole::ADMIN);

        // Admin creates a workspace (not directly adding owner to workspace)
        $workspace = Workspace::factory()->create([
            'name' => 'Admin Created Workspace',
            'organization_id' => $organization->id,
        ]);

        // Set owner's current organization context
        $owner->update([
            'current_organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($owner);

        // Owner should be able to switch to the workspace created by admin
        $response = $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Current workspace updated successfully',
                'data' => [
                    'workspace' => [
                        'uuid' => $workspace->uuid,
                        'name' => $workspace->name,
                    ],
                ],
            ]);

        // Verify context was updated
        $owner->refresh();
        $this->assertEquals($workspace->id, $owner->current_workspace_id);
    }

    public function test_organization_admin_can_switch_to_workspace_created_by_owner(): void
    {
        $owner = User::factory()->create(['name' => 'Owner']);
        $admin = User::factory()->create(['name' => 'Admin']);
        $plan = Plan::first();

        // Create organization owned by owner
        $organization = Organization::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Test Organization',
            'plan_id' => $plan->id,
        ]);

        // Add admin to organization as admin
        $organization->addUser($admin, OrganizationRole::ADMIN);

        // Owner creates a workspace (not directly adding admin to workspace)
        $workspace = Workspace::factory()->create([
            'name' => 'Owner Created Workspace',
            'organization_id' => $organization->id,
        ]);

        // Set admin's current organization context
        $admin->update([
            'current_organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($admin);

        // Admin should be able to switch to the workspace created by owner
        $response = $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Current workspace updated successfully',
                'data' => [
                    'workspace' => [
                        'uuid' => $workspace->uuid,
                        'name' => $workspace->name,
                    ],
                ],
            ]);

        // Verify context was updated
        $admin->refresh();
        $this->assertEquals($workspace->id, $admin->current_workspace_id);
    }
}
