<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plans and features to ensure we have proper plan limits
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'PlanFeaturesSeeder']);
    }

    public function test_user_can_create_organization_invitation(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $admin = User::factory()->create();
        $organization->addUser($admin, OrganizationRole::ADMIN);
        $admin->current_organization_id = $organization->id;
        $admin->save();

        Sanctum::actingAs($admin);

        $invitationData = [
            'email' => 'new-member@example.com',
            'role' => 'member',
            'workspace_assignments' => [
                [
                    'workspace_id' => $workspace->uuid,
                    'role' => WorkspaceRole::VIEWER->value,
                ],
            ],
            'message' => 'Welcome to our team!',
        ];

        $response = $this->postJson('/api/v1/invitations', $invitationData);

        if ($response->status() !== 201) {
            dump($response->json());
        }

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uuid',
                    'email',
                    'role',
                    'message',
                    'workspace_assignments',
                    'status',
                    'expires_at',
                ],
            ]);

        $this->assertDatabaseHas('invitations', [
            'email' => 'new-member@example.com',
            'organization_id' => $organization->id,
            'role' => 'member',
            'status' => 'pending',
        ]);
    }

    public function test_user_can_list_organization_invitations(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $admin = User::factory()->create();
        $organization->addUser($admin, OrganizationRole::ADMIN);
        $admin->current_organization_id = $organization->id;
        $admin->save();

        // Create test invitations
        Invitation::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'inviter_id' => $admin->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/invitations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'uuid',
                        'name',
                        'first_name',
                        'last_name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                        'role',
                        'joined_at',
                        'is_org_admin_access',
                        'organization_role',
                        'invitation_status',
                        'invitation_uuid',
                        'expires_at',
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_can_accept_organization_invitation(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $invitedUser = User::factory()->create([
            'email' => 'invited@example.com',
        ]);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'invited@example.com',
            'role' => OrganizationRole::MEMBER->value,
            'status' => 'pending',
            'token' => '550e8400-e29b-41d4-a716-446655440001',
            'expires_at' => now()->addDays(7),
            'workspace_assignments' => [
                [
                    'workspace_id' => $workspace->uuid,
                    'role' => WorkspaceRole::VIEWER->value,
                ],
            ],
        ]);

        Sanctum::actingAs($invitedUser);

        $response = $this->postJson('/api/v1/invitations/550e8400-e29b-41d4-a716-446655440001/accept');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invitation accepted successfully',
            ]);

        // Verify user was added to organization
        $this->assertTrue($invitedUser->belongsToOrganization($organization));

        // Verify user was added to workspace
        $this->assertTrue($invitedUser->belongsToWorkspace($workspace));

        // Verify invitation was marked as accepted
        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);
    }

    public function test_invitation_preview_shows_organization_details(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $inviter = User::factory()->create(['name' => 'John Doe']);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->id,
            'inviter_id' => $inviter->id,
            'email' => 'invited@example.com',
            'role' => OrganizationRole::MEMBER->value,
            'message' => 'Join our team!',
            'status' => 'pending',
            'token' => '550e8400-e29b-41d4-a716-446655440000',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->getJson('/api/v1/invitations/550e8400-e29b-41d4-a716-446655440000/preview');

        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'invitation' => [
                        'email' => 'invited@example.com',
                        'role' => 'member',
                        'message' => 'Join our team!',
                    ],
                    'organization' => [
                        'name' => 'Acme Corp',
                        'slug' => 'acme-corp',
                    ],
                    'inviter' => [
                        'name' => 'John Doe',
                    ],
                    'requires_signup' => true,
                ],
            ]);
    }

    public function test_organization_invitation_requires_authentication_for_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/invitations');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'test@example.com',
            'role' => 'member',
        ]);
        $response->assertStatus(401);
    }

    public function test_organization_invitation_validates_required_fields(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $admin = User::factory()->create();
        $organization->addUser($admin, OrganizationRole::ADMIN);
        $admin->current_organization_id = $organization->id;
        $admin->save();

        Sanctum::actingAs($admin);

        // Test missing email
        $response = $this->postJson('/api/v1/invitations', [
            'role' => 'member',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test missing role
        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'test@example.com',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);

        // Test member role without workspace assignments
        $response = $this->postJson('/api/v1/invitations', [
            'email' => 'test@example.com',
            'role' => 'member',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workspace_assignments']);
    }

    public function test_user_can_cancel_pending_invitation(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $admin = User::factory()->create();
        $organization->addUser($admin, OrganizationRole::ADMIN);
        $admin->current_organization_id = $organization->id;
        $admin->save();

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->id,
            'inviter_id' => $admin->id,
            'email' => 'test@example.com',
            'role' => OrganizationRole::ADMIN->value,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/invitations/{$invitation->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invitation cancelled successfully',
            ]);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cannot_cancel_accepted_invitation(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $admin = User::factory()->create();
        $organization->addUser($admin, OrganizationRole::ADMIN);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->id,
            'inviter_id' => $admin->id,
            'email' => 'test@example.com',
            'role' => OrganizationRole::ADMIN->value,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/invitations/{$invitation->uuid}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot cancel an already accepted invitation',
            ]);
    }

    public function test_user_can_decline_invitation(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $invitedUser = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'test@example.com',
            'role' => OrganizationRole::MEMBER->value,
            'status' => 'pending',
            'token' => '550e8400-e29b-41d4-a716-446655440002',
            'expires_at' => now()->addDays(7),
        ]);

        Sanctum::actingAs($invitedUser);

        $response = $this->postJson('/api/v1/invitations/550e8400-e29b-41d4-a716-446655440002/decline');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invitation declined',
            ]);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'status' => 'declined',
        ]);
    }

    public function test_admin_can_resend_invitation(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $admin = User::factory()->create();
        $organization->addUser($admin, OrganizationRole::ADMIN);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->id,
            'inviter_id' => $admin->id,
            'email' => 'test@example.com',
            'role' => OrganizationRole::MEMBER->value,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/invitations/{$invitation->uuid}/resend");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invitation resent successfully',
            ]);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'test@example.com',
            'role' => OrganizationRole::MEMBER->value,
            'status' => 'pending',
            'token' => '550e8400-e29b-41d4-a716-446655440003',
            'expires_at' => now()->subDays(1), // Expired
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/invitations/550e8400-e29b-41d4-a716-446655440003/accept');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'This invitation has expired',
            ]);
    }

    public function test_cannot_invite_existing_organization_member(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $admin = User::factory()->create();
        $existingMember = User::factory()->create(['email' => 'existing@example.com']);

        $organization->addUser($admin, OrganizationRole::ADMIN);
        $organization->addUser($existingMember, OrganizationRole::MEMBER);

        $admin->current_organization_id = $organization->id;
        $admin->save();

        Sanctum::actingAs($admin);

        $invitationData = [
            'email' => 'existing@example.com',
            'role' => 'member',
            'workspace_assignments' => [
                [
                    'workspace_id' => $workspace->uuid,
                    'role' => WorkspaceRole::VIEWER->value,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/invitations', $invitationData);

        // Should update existing member instead of creating invitation
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'action',
                    'message',
                    'user' => [
                        'email',
                        'new_role',
                    ],
                ],
            ]);
    }

    public function test_invitation_check_status_endpoint(): void
    {
        $plan = Plan::where('slug', 'business-yearly')->first();
        $organization = Organization::factory()->create([
            'plan_id' => $plan->id,
        ]);

        $invitation = Invitation::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'test@example.com',
            'role' => OrganizationRole::MEMBER->value,
            'status' => 'pending',
            'token' => '550e8400-e29b-41d4-a716-446655440004',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->getJson('/api/v1/invitations/550e8400-e29b-41d4-a716-446655440004/check-status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'valid' => true,
                    'status' => 'pending',
                    'email' => 'test@example.com',
                    'user_exists' => false,
                ],
            ]);
    }
}
