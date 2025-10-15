<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspaceEditorInvitationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Workspace $workspace1;

    private Workspace $workspace2;

    private User $admin;

    private User $editor;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plan data
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'PlanFeaturesSeeder']);

        // Create organization
        $this->organization = Organization::factory()->create();

        // Attach a plan with sufficient team_members limit
        $plan = Plan::where('slug', 'pro-yearly')->first();
        $this->organization->attachPlan($plan);

        // Create workspaces
        $this->workspace1 = Workspace::factory()->create(['organization_id' => $this->organization->id]);
        $this->workspace2 = Workspace::factory()->create(['organization_id' => $this->organization->id]);

        // Create users
        $this->admin = User::factory()->create([
            'current_organization_id' => $this->organization->id,
            'current_workspace_id' => $this->workspace1->id,
        ]);
        $this->editor = User::factory()->create([
            'current_organization_id' => $this->organization->id,
            'current_workspace_id' => $this->workspace1->id,
        ]);
        $this->viewer = User::factory()->create([
            'current_organization_id' => $this->organization->id,
            'current_workspace_id' => $this->workspace1->id,
        ]);

        // Assign organization roles
        $this->organization->addUser($this->admin, OrganizationRole::ADMIN);
        $this->organization->addUser($this->editor, OrganizationRole::MEMBER);
        $this->organization->addUser($this->viewer, OrganizationRole::MEMBER);

        // Assign workspace roles
        $this->workspace1->addUser($this->admin, WorkspaceRole::MANAGER);
        $this->workspace1->addUser($this->editor, WorkspaceRole::EDITOR);
        $this->workspace1->addUser($this->viewer, WorkspaceRole::VIEWER);
    }

    public function test_workspace_editor_can_invite_to_their_current_workspace(): void
    {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'newuser@example.com',
            'role' => 'member',
            'workspace_assignments' => [
                [
                    'workspace_id' => $this->workspace1->uuid,
                    'role' => WorkspaceRole::VIEWER->value,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'email',
                    'role',
                    'workspace_assignments',
                ],
            ]);

        $this->assertDatabaseHas('invitations', [
            'email' => 'newuser@example.com',
            'organization_id' => $this->organization->id,
            'role' => 'member',
            'status' => 'pending',
        ]);
    }

    public function test_workspace_editor_cannot_invite_with_admin_role(): void
    {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'newuser@example.com',
            'role' => 'admin',
            'workspace_assignments' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_workspace_editor_cannot_invite_to_different_workspace(): void
    {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'newuser@example.com',
            'role' => 'member',
            'workspace_assignments' => [
                [
                    'workspace_id' => $this->workspace2->uuid,
                    'role' => WorkspaceRole::VIEWER->value,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_assignments.0.workspace_id']);
    }

    public function test_workspace_editor_cannot_assign_manager_role(): void
    {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'newuser@example.com',
            'role' => 'member',
            'workspace_assignments' => [
                [
                    'workspace_id' => $this->workspace1->uuid,
                    'role' => WorkspaceRole::MANAGER->value,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_assignments.0.role']);
    }

    public function test_workspace_editor_can_assign_editor_role(): void
    {
        Sanctum::actingAs($this->editor);

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'newuser@example.com',
            'role' => 'member',
            'workspace_assignments' => [
                [
                    'workspace_id' => $this->workspace1->uuid,
                    'role' => WorkspaceRole::EDITOR->value,
                ],
            ],
        ]);

        $response->assertStatus(201);
    }

    public function test_workspace_viewer_cannot_invite_users(): void
    {
        Sanctum::actingAs($this->viewer);

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'newuser@example.com',
            'role' => 'member',
            'workspace_assignments' => [
                [
                    'workspace_id' => $this->workspace1->uuid,
                    'role' => WorkspaceRole::VIEWER->value,
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_org_admin_can_still_invite_to_any_workspace(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'newuser@example.com',
            'role' => 'member',
            'workspace_assignments' => [
                [
                    'workspace_id' => $this->workspace2->uuid,
                    'role' => WorkspaceRole::VIEWER->value,
                ],
            ],
        ]);

        $response->assertStatus(201);
    }

    public function test_org_admin_can_invite_with_admin_role(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'newuser@example.com',
            'role' => 'admin',
        ]);

        $response->assertStatus(201);
    }

    public function test_workspace_manager_can_invite_to_their_workspace(): void
    {
        $manager = User::factory()->create([
            'current_organization_id' => $this->organization->id,
            'current_workspace_id' => $this->workspace1->id,
        ]);
        $this->organization->addUser($manager, OrganizationRole::MEMBER);
        $this->workspace1->addUser($manager, WorkspaceRole::MANAGER);

        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'newuser@example.com',
            'role' => 'member',
            'workspace_assignments' => [
                [
                    'workspace_id' => $this->workspace1->uuid,
                    'role' => WorkspaceRole::VIEWER->value,
                ],
            ],
        ]);

        $response->assertStatus(201);
    }
}
