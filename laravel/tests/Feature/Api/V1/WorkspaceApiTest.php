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

class WorkspaceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plans and plan features (PHPUnit classes need individual setup)
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'PlanFeaturesSeeder']);
    }

    public function test_workspace_endpoints_require_authentication(): void
    {
        $response = $this->postJson('/api/v1/organization/workspace');
        $response->assertStatus(401);
    }

    public function test_user_can_list_organization_workspaces(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        // Create organizations using role-based system
        $org1 = Organization::factory()->create();
        $org1->addUser($user, OrganizationRole::OWNER);
        $org1->attachPlan($plan);

        $otherUser = User::factory()->create();
        $org2 = Organization::factory()->create();
        $org2->addUser($otherUser, OrganizationRole::OWNER);
        $org2->attachPlan($plan);

        // Create workspaces
        $workspace1 = Workspace::factory()->create([
            'organization_id' => $org1->id,
            'name' => 'Workspace 1',
        ]);

        $workspace2 = Workspace::factory()->create([
            'organization_id' => $org2->id,
            'name' => 'Workspace 2',
        ]);

        $workspace3 = Workspace::factory()->create([
            'organization_id' => $org1->id,
            'name' => 'Workspace 3',
        ]);

        // Add user to workspaces and organizations
        $workspace1->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);
        $org2->addUser($user, OrganizationRole::MEMBER); // Add to org2 for workspace2 access
        $workspace2->users()->attach($user->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);
        $workspace3->users()->attach($user->id, ['role' => WorkspaceRole::EDITOR->value, 'joined_at' => now()]);

        Sanctum::actingAs($user);

        // Set organization context first
        $this->postJson("/api/v1/user/current-organization/{$org1->uuid}");

        $response = $this->getJson('/api/v1/organization/workspaces');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'uuid',
                        'name',
                        'slug',
                        'description',
                        'organization',
                        'settings',
                    ],
                ],
            ]);

        $workspaces = $response->json('data');
        // Should only return workspaces from org1 that user has access to
        $this->assertCount(2, $workspaces);
    }

    public function test_user_can_create_workspace_in_owned_organization(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($user, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        Sanctum::actingAs($user);

        // Set organization context first
        $this->postJson("/api/v1/user/current-organization/{$organization->uuid}");

        $workspaceData = [
            'name' => 'New Workspace',
            'description' => 'Workspace description',
        ];

        $response = $this->postJson('/api/v1/organization/workspace', $workspaceData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Workspace created successfully',
                'data' => [
                    'name' => 'New Workspace',
                    'description' => 'Workspace description',
                ],
            ])
            ->assertJsonStructure([
                'data' => ['slug'],
            ]);

        $this->assertDatabaseHas('workspaces', [
            'name' => 'New Workspace',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_workspace_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($user, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        Sanctum::actingAs($user);

        // Set organization context first
        $this->postJson("/api/v1/user/current-organization/{$organization->uuid}");

        $response = $this->postJson('/api/v1/organization/workspace', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_cannot_create_workspace_in_organization_they_dont_own(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($otherUser, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        Sanctum::actingAs($user);

        // Attempt to set organization context to organization they don't own
        $contextResponse = $this->postJson("/api/v1/user/current-organization/{$organization->uuid}");

        // This should fail with 403 or similar error
        if ($contextResponse->status() !== 200) {
            $contextResponse->assertStatus(403);

            return;
        }

        $workspaceData = [
            'name' => 'New Workspace',
        ];

        $response = $this->postJson('/api/v1/organization/workspace', $workspaceData);

        // Should be forbidden since user doesn't own the organization
        $response->assertStatus(403);
    }

    public function test_user_can_view_workspace_they_have_access_to(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($user, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Test Workspace',
        ]);

        $workspace->users()->attach($user->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);

        Sanctum::actingAs($user);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response = $this->getJson('/api/v1/workspace');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $workspace->uuid,
                    'name' => 'Test Workspace',
                ],
            ]);
    }

    public function test_user_cannot_view_workspace_when_no_current_workspace(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/workspace');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Please select a workspace first. Use the user session endpoints to set your current workspace.',
                'error_code' => 'MISSING_WORKSPACE_CONTEXT',
            ]);
    }

    public function test_workspace_admin_can_update_workspace(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($user, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Old Name',
        ]);

        $workspace->users()->attach($user->id, ['role' => WorkspaceRole::EDITOR->value, 'joined_at' => now()]);

        Sanctum::actingAs($user);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->putJson('/api/v1/workspace', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Workspace updated successfully',
                'data' => [
                    'name' => 'Updated Name',
                    'description' => 'Updated description',
                ],
            ]);

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_workspace_member_cannot_update_workspace(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $otherUser = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addUser($otherUser, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        // Add user to organization as MEMBER so they have organization context
        $organization->addUser($user, OrganizationRole::MEMBER);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach($user->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);

        Sanctum::actingAs($user);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response = $this->putJson('/api/v1/workspace', [
            'name' => 'New Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_workspace_owner_can_delete_workspace_when_multiple_exist(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($user, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        // Create two workspaces
        $workspace1 = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace2 = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace1->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);
        $workspace2->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        Sanctum::actingAs($user);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace1->uuid}");

        $response = $this->deleteJson('/api/v1/workspace');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Workspace deleted successfully. Switched to '.$workspace2->name,
                    'next_workspace' => [
                        'uuid' => $workspace2->uuid,
                        'name' => $workspace2->name,
                    ],
                ],
            ]);

        $this->assertSoftDeleted('workspaces', ['id' => $workspace1->id]);
        $this->assertDatabaseHas('workspaces', ['id' => $workspace2->id, 'deleted_at' => null]);
    }

    public function test_workspace_owner_cannot_delete_last_workspace_in_organization(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($user, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        // Create only one workspace
        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        Sanctum::actingAs($user);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response = $this->deleteJson('/api/v1/workspace');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete the last workspace in an organization. Create another workspace first, or delete the entire organization instead.',
            ]);

        // Workspace should still exist (not deleted)
        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'deleted_at' => null]);
    }

    public function test_can_delete_workspace_when_multiple_workspaces_available(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($user, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        // Create two workspaces so deletion is allowed
        $workspace1 = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace2 = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace1->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);
        $workspace2->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        Sanctum::actingAs($user);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace1->uuid}");

        $response = $this->deleteJson('/api/v1/workspace');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Workspace deleted successfully. Switched to '.$workspace2->name,
                    'next_workspace' => [
                        'uuid' => $workspace2->uuid,
                        'name' => $workspace2->name,
                    ],
                ],
            ]);

        $this->assertSoftDeleted('workspaces', ['id' => $workspace1->id]);
        $this->assertDatabaseHas('workspaces', ['id' => $workspace2->id, 'deleted_at' => null]);
    }

    public function test_workspace_admin_can_add_members(): void
    {
        $admin = User::factory()->create();
        $newMember = User::factory()->create();
        // Use pro plan which allows 10 team members
        $plan = Plan::where('slug', 'pro-yearly')->first() ?? Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($admin, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        // Add new member to organization first so they can be added to workspace
        $organization->addUser($newMember, OrganizationRole::MEMBER);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach($admin->id, ['role' => WorkspaceRole::EDITOR->value, 'joined_at' => now()]);

        Sanctum::actingAs($admin);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response = $this->postJson('/api/v1/workspace/members', [
            'user_id' => $newMember->id,
            'role' => WorkspaceRole::VIEWER->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Member added successfully',
            ]);

        $this->assertDatabaseHas('workspace_users', [
            'workspace_id' => $workspace->id,
            'user_id' => $newMember->id,
            'role' => WorkspaceRole::VIEWER->value,
        ]);
    }

    public function test_workspace_member_cannot_add_members(): void
    {
        $member = User::factory()->create();
        $newMember = User::factory()->create();
        $plan = Plan::first();

        $otherUser = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addUser($otherUser, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        // Add member to organization so they have organization context
        $organization->addUser($member, OrganizationRole::MEMBER);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach($member->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);

        Sanctum::actingAs($member);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response = $this->postJson('/api/v1/workspace/members', [
            'user_id' => $newMember->id,
            'role' => WorkspaceRole::VIEWER->value,
        ]);

        $response->assertStatus(403);
    }

    public function test_workspace_admin_can_remove_members(): void
    {
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($admin, OrganizationRole::OWNER);
        $organization->addUser($member, OrganizationRole::MEMBER);
        $organization->attachPlan($plan);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach($admin->id, ['role' => WorkspaceRole::EDITOR->value, 'joined_at' => now()]);
        $workspace->users()->attach($member->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);

        Sanctum::actingAs($admin);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response = $this->deleteJson("/api/v1/workspace/members/{$member->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Member removed successfully',
            ]);

        $this->assertDatabaseMissing('workspace_users', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_cannot_remove_last_owner_from_workspace(): void
    {
        $owner = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($owner, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach($owner->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        Sanctum::actingAs($owner);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response = $this->deleteJson("/api/v1/workspace/members/{$owner->uuid}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot remove the last owner from workspace',
            ]);
    }

    public function test_workspace_owner_can_change_member_roles(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($owner, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach($owner->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);
        $workspace->users()->attach($member->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);

        Sanctum::actingAs($owner);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response = $this->patchJson("/api/v1/workspace/members/{$member->uuid}/role", [
            'role' => WorkspaceRole::EDITOR->value,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Member role updated successfully',
            ]);

        $this->assertDatabaseHas('workspace_users', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => WorkspaceRole::EDITOR->value,
        ]);
    }

    public function test_workspace_admin_cannot_change_owner_role(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($owner, OrganizationRole::OWNER);
        $organization->addUser($admin, OrganizationRole::MEMBER);
        $organization->attachPlan($plan);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach($owner->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);
        $workspace->users()->attach($admin->id, ['role' => WorkspaceRole::EDITOR->value, 'joined_at' => now()]);

        Sanctum::actingAs($admin);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response = $this->patchJson("/api/v1/workspace/members/{$owner->uuid}/role", [
            'role' => WorkspaceRole::VIEWER->value,
        ]);

        $response->assertStatus(403);
    }

    public function test_workspace_owner_can_transfer_ownership(): void
    {
        $currentOwner = User::factory()->create();
        $newOwner = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($currentOwner, OrganizationRole::OWNER);
        $organization->addUser($newOwner, OrganizationRole::MEMBER);
        $organization->attachPlan($plan);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach($currentOwner->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);
        $workspace->users()->attach($newOwner->id, ['role' => WorkspaceRole::EDITOR->value, 'joined_at' => now()]);

        Sanctum::actingAs($currentOwner);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response = $this->postJson('/api/v1/workspace/transfer-ownership', [
            'new_owner_id' => $newOwner->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Workspace ownership transferred successfully',
            ]);

        // Verify role changes
        $this->assertDatabaseHas('workspace_users', [
            'workspace_id' => $workspace->id,
            'user_id' => $newOwner->id,
            'role' => WorkspaceRole::MANAGER->value,
        ]);

        $this->assertDatabaseHas('workspace_users', [
            'workspace_id' => $workspace->id,
            'user_id' => $currentOwner->id,
            'role' => WorkspaceRole::EDITOR->value,
        ]);
    }

    public function test_workspace_owner_can_duplicate_workspace(): void
    {
        $user = User::factory()->create();
        // Use a plan that allows multiple workspaces
        $plan = Plan::where('slug', 'pro-yearly')->first() ?: Plan::where('slug', 'business-yearly')->first();

        $organization = Organization::factory()->create();
        $organization->addUser($user, OrganizationRole::OWNER);

        if ($plan) {
            $organization->attachPlan($plan);
        }

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Original Workspace',
            'settings' => ['theme' => 'dark'],
        ]);

        $workspace->users()->attach($user->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        Sanctum::actingAs($user);

        // Set workspace context first
        $this->postJson("/api/v1/user/current-workspace/{$workspace->uuid}");

        $response = $this->postJson('/api/v1/workspace/duplicate', [
            'name' => 'Duplicated Workspace',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Workspace duplicated successfully',
                'data' => [
                    'name' => 'Duplicated Workspace',
                ],
            ])
            ->assertJsonStructure([
                'data' => ['slug'],
            ]);

        // Verify duplication
        $this->assertDatabaseHas('workspaces', [
            'name' => 'Duplicated Workspace',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_workspace_operations_require_current_workspace(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Test various operations without setting current workspace
        $response = $this->getJson('/api/v1/workspace');
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Please select a workspace first. Use the user session endpoints to set your current workspace.',
                'error_code' => 'MISSING_WORKSPACE_CONTEXT',
            ]);

        $response = $this->putJson('/api/v1/workspace', ['name' => 'Test']);
        $response->assertStatus(400);

        $response = $this->deleteJson('/api/v1/workspace');
        $response->assertStatus(400);

        $response = $this->getJson('/api/v1/workspace/members');
        $response->assertStatus(400);
    }

    public function test_workspace_admin_can_update_workspace_by_uuid(): void
    {
        $admin = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($admin, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Original Name',
            'description' => 'Original Description',
        ]);

        $workspace->users()->attach($admin->id, ['role' => WorkspaceRole::EDITOR->value, 'joined_at' => now()]);

        Sanctum::actingAs($admin);

        // Set current organization context (required for the route)
        $this->postJson("/api/v1/user/current-organization/{$organization->uuid}");

        $updateData = [
            'name' => 'Updated Workspace Name',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/v1/workspaces/{$workspace->uuid}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Workspace updated successfully',
                'data' => [
                    'name' => 'Updated Workspace Name',
                    'description' => 'Updated description',
                    'uuid' => $workspace->uuid,
                ],
            ]);

        // Verify database was updated
        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Updated Workspace Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_workspace_owner_can_update_workspace_by_uuid(): void
    {
        $owner = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($owner, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Original Name',
        ]);

        $workspace->users()->attach($owner->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/user/current-organization/{$organization->uuid}");

        $response = $this->putJson("/api/v1/workspaces/{$workspace->uuid}", [
            'name' => 'Owner Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Owner Updated Name',
                ],
            ]);
    }

    public function test_workspace_member_cannot_update_workspace_by_uuid(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($owner, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        // Add member to organization so they have organization context
        $organization->addUser($member, OrganizationRole::MEMBER);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach([
            $owner->id => ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()],
            $member->id => ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()],
        ]);

        Sanctum::actingAs($member);
        $this->postJson("/api/v1/user/current-organization/{$organization->uuid}");

        $response = $this->putJson("/api/v1/workspaces/{$workspace->uuid}", [
            'name' => 'Unauthorized Update',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_update_workspace_without_access(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($owner, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        // Add member to organization so they have organization context
        $organization->addUser($member, OrganizationRole::MEMBER);

        $workspace1 = Workspace::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Workspace 1',
        ]);

        $workspace2 = Workspace::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Workspace 2',
        ]);

        // Owner has access to both workspaces
        $workspace1->users()->attach($owner->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);
        $workspace2->users()->attach($owner->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

        // Member only has access to workspace1, not workspace2
        $workspace1->users()->attach($member->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);

        Sanctum::actingAs($member);
        $this->postJson("/api/v1/user/current-organization/{$organization->uuid}");

        // Try to update workspace2 (member doesn't have access)
        $response = $this->putJson("/api/v1/workspaces/{$workspace2->uuid}", [
            'name' => 'Unauthorized Update',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Access forbidden',
                'error_code' => 'HTTP_403',
            ]);
    }

    public function test_update_workspace_by_uuid_validates_input(): void
    {
        $admin = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($admin, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $workspace->users()->attach($admin->id, ['role' => WorkspaceRole::EDITOR->value, 'joined_at' => now()]);

        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/user/current-organization/{$organization->uuid}");

        // Test with invalid data
        $response = $this->putJson("/api/v1/workspaces/{$workspace->uuid}", [
            'name' => '', // Empty name should fail validation
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_update_workspace_by_uuid_requires_authentication(): void
    {
        $workspace = Workspace::factory()->create();

        $response = $this->putJson("/api/v1/workspaces/{$workspace->uuid}", [
            'name' => 'Test Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_workspace_by_uuid_requires_organization_context(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/workspaces/{$workspace->uuid}", [
            'name' => 'Test Name',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Please select an organization first. Use the user session endpoints to set your current organization.',
                'error_code' => 'MISSING_ORGANIZATION_CONTEXT',
            ]);
    }

    public function test_update_workspace_by_uuid_returns_404_for_invalid_uuid(): void
    {
        $user = User::factory()->create();
        $plan = Plan::first();

        $organization = Organization::factory()->create();
        $organization->addUser($user, OrganizationRole::OWNER);
        $organization->attachPlan($plan);

        Sanctum::actingAs($user);
        $this->postJson("/api/v1/user/current-organization/{$organization->uuid}");

        $response = $this->putJson('/api/v1/workspaces/12345678-1234-1234-1234-123456789012', [
            'name' => 'Test Name',
        ]);

        $response->assertStatus(404);
    }

    public function test_workspace_members_includes_organization_admins_and_owners(): void
    {
        // Create users
        $owner = User::factory()->create(['name' => 'Organization Owner']);
        $admin = User::factory()->create(['name' => 'Organization Admin']);
        $directMember = User::factory()->create(['name' => 'Direct Member']);

        $plan = Plan::first();

        // Create organization owned by owner
        $organization = Organization::factory()->create([
            'owner_id' => $owner->id,
            'plan_id' => $plan->id,
        ]);

        // Add admin to organization
        $organization->addUser($admin, OrganizationRole::ADMIN);

        // Create workspace
        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        // Add only direct member to workspace (owner and admin are NOT direct members)
        $workspace->addUser($directMember, WorkspaceRole::EDITOR);

        // Set admin as current user and workspace context
        $admin->update([
            'current_organization_id' => $organization->id,
            'current_workspace_id' => $workspace->id,
        ]);

        Sanctum::actingAs($admin);

        // Get workspace members
        $response = $this->getJson('/api/v1/workspace/members');

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $members = $responseData['current_members'];
        $memberNames = collect($members)->pluck('name')->toArray();

        // Should include organization owner (implicit access)
        $this->assertContains('Organization Owner', $memberNames, 'Organization owner should be included in members list');

        // Should include organization admin (implicit access)
        $this->assertContains('Organization Admin', $memberNames, 'Organization admin should be included in members list');

        // Should include direct member
        $this->assertContains('Direct Member', $memberNames, 'Direct workspace member should be included');

        // Verify we have all 3 members
        $this->assertCount(3, $members, 'Should have 3 total members (owner + admin + direct member)');

        // Verify the new response structure
        $this->assertArrayHasKey('current_members', $responseData);
        $this->assertArrayHasKey('pending_invitations', $responseData);
        $this->assertArrayHasKey('summary', $responseData);
        $this->assertEquals(3, $responseData['summary']['total_current_members']);
        $this->assertEquals(0, $responseData['summary']['total_pending_invitations']);

        // Verify roles are correct
        $ownerData = collect($members)->firstWhere('name', 'Organization Owner');
        $adminData = collect($members)->firstWhere('name', 'Organization Admin');
        $directMemberData = collect($members)->firstWhere('name', 'Direct Member');

        $this->assertEquals('manager', $ownerData['role'], 'Organization owner should have manager role');
        $this->assertEquals('manager', $adminData['role'], 'Organization admin should have manager role');
        $this->assertEquals('editor', $directMemberData['role'], 'Direct member should have editor role');
    }

    public function test_workspace_members_includes_pending_invitations(): void
    {
        // Create users
        $owner = User::factory()->create(['name' => 'Test Owner']);
        $plan = Plan::first();

        // Create organization
        $organization = Organization::factory()->create([
            'owner_id' => $owner->id,
            'plan_id' => $plan->id,
        ]);

        // Create workspace
        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Test Workspace',
        ]);

        // Create another workspace
        $workspace2 = Workspace::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Other Workspace',
        ]);

        // Create pending invitations

        // 1. Admin invitation (should appear - has access to all workspaces)
        $adminInvitation = \App\Models\Invitation::create([
            'organization_id' => $organization->id,
            'email' => 'admin@test.com',
            'role' => 'admin',
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'workspace_assignments' => null,
            'inviter_id' => $owner->id,
        ]);

        // 2. Member invitation with this workspace (should appear)
        $memberInvitation = \App\Models\Invitation::create([
            'organization_id' => $organization->id,
            'email' => 'member@test.com',
            'role' => 'member',
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'workspace_assignments' => [
                ['workspace_id' => $workspace->uuid, 'role' => 'editor'],
            ],
            'inviter_id' => $owner->id,
        ]);

        // 3. Member invitation with different workspace (should NOT appear)
        $otherInvitation = \App\Models\Invitation::create([
            'organization_id' => $organization->id,
            'email' => 'other@test.com',
            'role' => 'member',
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'workspace_assignments' => [
                ['workspace_id' => $workspace2->uuid, 'role' => 'viewer'],
            ],
            'inviter_id' => $owner->id,
        ]);

        // Set owner context
        $owner->update([
            'current_organization_id' => $organization->id,
            'current_workspace_id' => $workspace->id,
        ]);

        Sanctum::actingAs($owner);

        // Get workspace members
        $response = $this->getJson('/api/v1/workspace/members');
        $response->assertStatus(200);

        $responseData = $response->json('data');

        // Verify structure
        $this->assertArrayHasKey('current_members', $responseData);
        $this->assertArrayHasKey('pending_invitations', $responseData);
        $this->assertArrayHasKey('summary', $responseData);

        // Should have 2 pending invitations (admin + member for this workspace)
        $this->assertCount(2, $responseData['pending_invitations'], 'Should have 2 pending invitations');

        $pendingEmails = collect($responseData['pending_invitations'])->pluck('email')->toArray();
        $this->assertContains('admin@test.com', $pendingEmails);
        $this->assertContains('member@test.com', $pendingEmails);
        $this->assertNotContains('other@test.com', $pendingEmails);

        // Verify summary
        $this->assertEquals(2, $responseData['summary']['total_pending_invitations']);
    }
}
