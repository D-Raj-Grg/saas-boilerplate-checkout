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

class MeEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PlanSeeder::class);
    }

    public function test_me_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated',
            ]);
    }

    public function test_me_endpoint_returns_user_profile_with_all_data(): void
    {
        // Create a user with organizations and workspaces
        $user = User::factory()->create([
            'name' => 'John Doe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        // Create a plan
        $plan = Plan::first(); // Use seeded plan

        // Create an organization owned by the user
        $organization = Organization::factory()->create([
            'name' => 'My Organization',
            'owner_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        // Add user to organization as owner
        // User automatically added as OWNER by factory

        // Create a workspace in the organization
        $workspace = Workspace::factory()->create([
            'name' => 'My Workspace',
            'organization_id' => $organization->id,
        ]);

        // Add user to workspace as manager (equivalent to old 'owner' role)
        $workspace->users()->attach($user->id, [
            'role' => WorkspaceRole::MANAGER->value,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'name',
                        'first_name',
                        'last_name',
                        'email',
                        'email_verified_at',
                        'current_organization_uuid',
                        'current_workspace_uuid',
                    ],
                    'organizations' => [
                        '*' => [
                            'uuid',
                            'name',
                            'slug',
                            'is_owner',
                            'current_plan' => [
                                'name',
                                'slug',
                            ],
                        ],
                    ],
                    'workspaces' => [
                        '*' => [
                            'uuid',
                            'name',
                            'slug',
                            'organization_uuid',
                            'role',
                        ],
                    ],
                    'current_workspace_permissions',
                ],
            ]);

        // Verify user data
        $userData = $response->json('data.user');
        $this->assertEquals('John Doe', $userData['name']);
        $this->assertEquals('john@example.com', $userData['email']);

        // Verify organizations data
        $organizations = $response->json('data.organizations');
        $this->assertCount(1, $organizations);
        $this->assertEquals($organization->uuid, $organizations[0]['uuid']);
        $this->assertTrue($organizations[0]['is_owner']);

        // Verify workspaces data
        $workspaces = $response->json('data.workspaces');
        $this->assertCount(1, $workspaces);
        $this->assertEquals($workspace->uuid, $workspaces[0]['uuid']);
        $this->assertEquals('manager', $workspaces[0]['role']);
    }

    public function test_me_endpoint_returns_correct_permissions_for_different_roles(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['owner_id' => $user->id]);

        // Add user to organization as owner
        // User automatically added as OWNER by factory

        $workspace = Workspace::factory()->create(['organization_id' => $organization->id]);

        $workspace->users()->attach($user->id, [
            'role' => WorkspaceRole::EDITOR->value,
            'joined_at' => now(),
        ]);

        // Set current workspace
        $user->current_workspace_id = $workspace->id;
        $user->current_organization_id = $organization->id;
        $user->save();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200);

        $permissions = $response->json('data.current_workspace_permissions');
        $this->assertIsArray($permissions);
        $this->assertArrayHasKey('can_invite_users', $permissions);
        $this->assertArrayHasKey('can_manage_settings', $permissions);
    }

    public function test_me_endpoint_handles_user_without_organizations(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'organizations' => [],
                    'workspaces' => [],
                    'current_workspace_permissions' => [],
                ],
            ]);
    }

    public function test_me_endpoint_returns_correct_organization_ownership_status(): void
    {
        $user = User::factory()->create();

        // Create owned organization
        $ownedOrg = Organization::factory()->create([
            'owner_id' => $user->id,
        ]);

        // Add user to owned organization as owner
        // User automatically added as OWNER by factory

        // Create organization where user is just a member
        $otherUser = User::factory()->create();
        $accessibleOrg = Organization::factory()->create([
            'owner_id' => $otherUser->id,
        ]);

        // Add other user to their organization as owner
        // Other user automatically added as OWNER by factory

        // Add our user to accessible org as member
        $accessibleOrg->addUser($user, OrganizationRole::MEMBER);

        // Create workspace in accessible org and add user as viewer
        $workspace = Workspace::factory()->create([
            'organization_id' => $accessibleOrg->id,
        ]);
        $workspace->users()->attach($user->id, [
            'role' => WorkspaceRole::VIEWER->value,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200);

        $organizations = $response->json('data.organizations');
        $this->assertCount(2, $organizations);

        // Find the organizations in the response
        $ownedOrgData = collect($organizations)->firstWhere('uuid', $ownedOrg->uuid);
        $accessibleOrgData = collect($organizations)->firstWhere('uuid', $accessibleOrg->uuid);

        $this->assertTrue($ownedOrgData['is_owner']);
        $this->assertFalse($accessibleOrgData['is_owner']);
    }

    public function test_me_endpoint_includes_current_organization_permissions(): void
    {
        // Create organization owner
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'owner_id' => $user->id,
        ]);

        // Add user to organization as owner
        // User automatically added as OWNER by factory

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $user->update([
            'current_organization_id' => $organization->id,
            'current_workspace_id' => $workspace->id,
        ]);

        $workspace->users()->attach($user->id, [
            'role' => WorkspaceRole::MANAGER->value,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'current_organization_permissions' => [
                        'can_create_workspaces',
                        'can_edit_any_workspace',
                        'can_delete_any_workspace',
                        'can_manage_organization',
                        'can_manage_billing',
                        'can_transfer_ownership',
                        'can_view_all_workspaces',
                    ],
                ],
            ])
            ->assertJsonPath('data.current_organization_permissions.can_create_workspaces', true)
            ->assertJsonPath('data.current_organization_permissions.can_edit_any_workspace', true)
            ->assertJsonPath('data.current_organization_permissions.can_delete_any_workspace', true)
            ->assertJsonPath('data.current_organization_permissions.can_manage_organization', true)
            ->assertJsonPath('data.current_organization_permissions.can_manage_billing', true)
            ->assertJsonPath('data.current_organization_permissions.can_transfer_ownership', true)
            ->assertJsonPath('data.current_organization_permissions.can_view_all_workspaces', true);
    }

    public function test_me_endpoint_organization_permissions_false_for_non_owners(): void
    {
        // Create non-owner user
        $user = User::factory()->create();
        $orgOwner = User::factory()->create();
        $organization = Organization::factory()->create([
            'owner_id' => $orgOwner->id,
        ]);

        // Add org owner to organization as owner
        // Org owner automatically added as OWNER by factory

        // Add user to organization as member
        $organization->addUser($user, OrganizationRole::MEMBER);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $user->update([
            'current_organization_id' => $organization->id,
            'current_workspace_id' => $workspace->id,
        ]);

        $workspace->users()->attach($user->id, [
            'role' => WorkspaceRole::VIEWER->value,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.current_organization_permissions.can_create_workspaces', false)
            ->assertJsonPath('data.current_organization_permissions.can_edit_any_workspace', false)
            ->assertJsonPath('data.current_organization_permissions.can_delete_any_workspace', false)
            ->assertJsonPath('data.current_organization_permissions.can_manage_organization', false)
            ->assertJsonPath('data.current_organization_permissions.can_manage_billing', false)
            ->assertJsonPath('data.current_organization_permissions.can_transfer_ownership', false)
            ->assertJsonPath('data.current_organization_permissions.can_view_all_workspaces', false);
    }

    public function test_me_endpoint_empty_organization_permissions_when_no_current_organization(): void
    {
        $user = User::factory()->create([
            'current_organization_id' => null,
            'current_workspace_id' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.current_organization_permissions', []);
    }

    public function test_me_endpoint_workspace_permissions_for_organization_owner(): void
    {
        // Create organization owner
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'owner_id' => $user->id,
        ]);

        // Add user to organization as owner
        // User automatically added as OWNER by factory

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $user->update([
            'current_organization_id' => $organization->id,
            'current_workspace_id' => $workspace->id,
        ]);

        // Organization owner is not a direct member of workspace
        // but should still get permissions via organization ownership

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'current_workspace_permissions' => [
                        'can_invite_users',
                        'can_remove_users',
                        'can_change_user_roles',
                        'can_manage_settings',
                        'can_delete_workspace',
                        'can_update_workspace',
                        'can_view_audit_logs',
                    ],
                ],
            ])
            // Organization owners should have all permissions
            ->assertJsonPath('data.current_workspace_permissions.can_invite_users', true)
            ->assertJsonPath('data.current_workspace_permissions.can_remove_users', true)
            ->assertJsonPath('data.current_workspace_permissions.can_change_user_roles', true)
            ->assertJsonPath('data.current_workspace_permissions.can_manage_settings', true)
            ->assertJsonPath('data.current_workspace_permissions.can_delete_workspace', true)
            ->assertJsonPath('data.current_workspace_permissions.can_update_workspace', true)
            ->assertJsonPath('data.current_workspace_permissions.can_view_audit_logs', true);
    }

    public function test_me_endpoint_workspace_permissions_consistent_structure_for_non_members(): void
    {
        // Create user who doesn't belong to any workspace
        $user = User::factory()->create();
        $orgOwner = User::factory()->create();
        $organization = Organization::factory()->create([
            'owner_id' => $orgOwner->id,
        ]);

        // Add org owner to organization as owner
        // Org owner automatically added as OWNER by factory

        // Add user to organization as member (but not to workspace)
        $organization->addUser($user, OrganizationRole::MEMBER);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $user->update([
            'current_organization_id' => $organization->id,
            'current_workspace_id' => $workspace->id,
        ]);

        // User is not a member of the workspace and not organization owner

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.current_workspace_permissions', []);
    }

    public function test_me_endpoint_organization_permissions_consistent_across_multiple_calls(): void
    {
        // Create organization owner
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'owner_id' => $user->id,
        ]);

        // Add user to organization as owner
        // User automatically added as OWNER by factory

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $user->update([
            'current_organization_id' => $organization->id,
            'current_workspace_id' => $workspace->id,
        ]);

        $workspace->users()->attach($user->id, [
            'role' => WorkspaceRole::MANAGER->value,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        // Make multiple requests to ensure consistency
        $response1 = $this->getJson('/api/v1/me');
        $response2 = $this->getJson('/api/v1/me');

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Permissions should be identical
        $permissions1 = $response1->json('data.current_organization_permissions');
        $permissions2 = $response2->json('data.current_organization_permissions');

        $this->assertEquals($permissions1, $permissions2);
        $this->assertCount(8, $permissions1); // All 8 permissions should be present (including can_create_organization)
    }

    public function test_organization_admin_can_see_all_workspaces_in_organization(): void
    {
        // Create organization owner
        $owner = User::factory()->create();
        $organization = Organization::factory()->create([
            'owner_id' => $owner->id,
        ]);

        // Owner is automatically added as OWNER by factory

        // Create an admin user
        $admin = User::factory()->create();
        $organization->addUser($admin, OrganizationRole::ADMIN);

        // Create multiple workspaces in the organization
        $workspace1 = Workspace::factory()->create([
            'name' => 'Workspace 1',
            'organization_id' => $organization->id,
        ]);

        $workspace2 = Workspace::factory()->create([
            'name' => 'Workspace 2',
            'organization_id' => $organization->id,
        ]);

        // Add some other user to one workspace (admin is NOT a direct member)
        $member = User::factory()->create();
        $organization->addUser($member, OrganizationRole::MEMBER);
        $workspace1->users()->attach($member->id, [
            'role' => WorkspaceRole::VIEWER->value,
            'joined_at' => now(),
        ]);

        // Set admin's current organization
        $admin->update([
            'current_organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200);

        // Admin should see both workspaces even though they're not direct members
        $workspaces = $response->json('data.workspaces');
        $this->assertCount(2, $workspaces);

        $workspaceUuids = collect($workspaces)->pluck('uuid')->toArray();
        $this->assertContains($workspace1->uuid, $workspaceUuids);
        $this->assertContains($workspace2->uuid, $workspaceUuids);

        // Check that admin role is properly assigned for these workspaces
        foreach ($workspaces as $workspaceData) {
            $this->assertEquals('manager', $workspaceData['role']); // Admin access defaults to manager role
            $this->assertEquals($organization->uuid, $workspaceData['organization_uuid']);
        }
    }

    public function test_organization_owner_can_see_all_workspaces_with_manager_role(): void
    {
        // Create organization owner
        $owner = User::factory()->create();
        $organization = Organization::factory()->create([
            'owner_id' => $owner->id,
        ]);

        // Owner is automatically added as OWNER by factory

        // Create multiple workspaces in the organization
        $workspace1 = Workspace::factory()->create([
            'name' => 'Workspace 1',
            'organization_id' => $organization->id,
        ]);

        $workspace2 = Workspace::factory()->create([
            'name' => 'Workspace 2',
            'organization_id' => $organization->id,
        ]);

        // Add some other user to one workspace (owner is NOT a direct member)
        $member = User::factory()->create();
        $organization->addUser($member, OrganizationRole::MEMBER);
        $workspace1->users()->attach($member->id, [
            'role' => WorkspaceRole::VIEWER->value,
            'joined_at' => now(),
        ]);

        // Set owner's current organization
        $owner->update([
            'current_organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200);

        // Owner should see both workspaces even though they're not direct members
        $workspaces = $response->json('data.workspaces');
        $this->assertCount(2, $workspaces);

        $workspaceUuids = collect($workspaces)->pluck('uuid')->toArray();
        $this->assertContains($workspace1->uuid, $workspaceUuids);
        $this->assertContains($workspace2->uuid, $workspaceUuids);

        // Check that owner gets manager role for these workspaces
        foreach ($workspaces as $workspaceData) {
            $this->assertEquals('manager', $workspaceData['role']); // Owner access defaults to manager role
            $this->assertEquals($organization->uuid, $workspaceData['organization_uuid']);
        }
    }

    public function test_organization_admin_gets_workspace_permissions_after_switching(): void
    {
        // Create organization owner
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

        // Owner creates a workspace (admin is NOT a direct member)
        $workspace = Workspace::factory()->create([
            'name' => 'Test Workspace',
            'organization_id' => $organization->id,
        ]);

        // Admin switches to this workspace (this should work now)
        $admin->update([
            'current_organization_id' => $organization->id,
            'current_workspace_id' => $workspace->id,
        ]);

        Sanctum::actingAs($admin);

        // The /me endpoint should return workspace permissions and UUID
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify workspace UUID is not null
        $this->assertEquals($workspace->uuid, $data['user']['current_workspace_uuid'], 'current_workspace_uuid should not be null');

        // Verify workspace permissions are not empty
        $this->assertNotEmpty($data['current_workspace_permissions'], 'current_workspace_permissions should not be empty');

        // Verify admin has appropriate permissions
        $permissions = $data['current_workspace_permissions'];
        $this->assertTrue($permissions['can_manage_settings'], 'Admin should be able to manage settings');
        $this->assertTrue($permissions['can_invite_users'], 'Admin should be able to invite users');
    }

    public function test_organization_admin_gets_default_workspace_context(): void
    {
        // Create organization owner and admin
        $owner = User::factory()->create(['name' => 'Owner']);
        $admin = User::factory()->create(['name' => 'Admin']);
        $plan = Plan::first();

        // Create organization
        $organization = Organization::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Test Organization',
            'plan_id' => $plan->id,
        ]);

        // Add admin to organization
        $organization->addUser($admin, OrganizationRole::ADMIN);

        // Create workspace (admin is NOT a direct member)
        $workspace = Workspace::factory()->create([
            'name' => 'Test Workspace',
            'organization_id' => $organization->id,
        ]);

        // Admin has no current context set
        $admin->update([
            'current_organization_id' => null,
            'current_workspace_id' => null,
        ]);

        Sanctum::actingAs($admin);

        // The /me endpoint should automatically set default context
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify default context was set
        $this->assertEquals($organization->uuid, $data['user']['current_organization_uuid'], 'Should set default organization');
        $this->assertEquals($workspace->uuid, $data['user']['current_workspace_uuid'], 'Should set default workspace for admin');

        // Verify permissions are available
        $this->assertNotEmpty($data['current_workspace_permissions'], 'Should have workspace permissions');
    }
}
