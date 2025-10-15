<?php

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Laravel\Sanctum\Sanctum;

test('organization members endpoint returns members with workspace access', function () {
    // Create users and plan
    $owner = User::factory()->create(['name' => 'Organization Owner']);
    $admin = User::factory()->create(['name' => 'Organization Admin']);
    $member = User::factory()->create(['name' => 'Regular Member']);
    $plan = Plan::first();

    // Create organization
    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
        'plan_id' => $plan->id,
    ]);

    // Add admin and member to organization
    $organization->addUser($admin, OrganizationRole::ADMIN);
    $organization->addUser($member, OrganizationRole::MEMBER);

    // Create workspaces
    $workspace1 = Workspace::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Test Workspace 1',
    ]);
    $workspace2 = Workspace::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Test Workspace 2',
    ]);

    // Add member to one workspace directly
    $workspace1->addUser($member, WorkspaceRole::EDITOR);

    // Set admin as current user and organization context
    $admin->update(['current_organization_id' => $organization->id]);
    Sanctum::actingAs($admin);

    // Test the endpoint
    $response = $this->getJson('/api/v1/organization/members');

    $response->assertStatus(200);

    $members = $response->json('data.current_members');

    // Should have 3 members total
    expect($members)->toHaveCount(3);

    // Find each member
    $ownerData = collect($members)->firstWhere('name', 'Organization Owner');
    $adminData = collect($members)->firstWhere('name', 'Organization Admin');
    $memberData = collect($members)->firstWhere('name', 'Regular Member');

    // Verify owner data
    expect($ownerData['organization_role'])->toBe('owner');
    expect($ownerData['is_owner'])->toBeTrue();
    expect($ownerData['workspace_access'])->toHaveCount(2); // Has access to all workspaces

    // Verify admin data
    expect($adminData['organization_role'])->toBe('admin');
    expect($adminData['is_owner'])->toBeFalse();
    expect($adminData['workspace_access'])->toHaveCount(2); // Has access to all workspaces

    // Verify member data
    expect($memberData['organization_role'])->toBe('member');
    expect($memberData['is_owner'])->toBeFalse();
    expect($memberData['workspace_access'])->toHaveCount(1); // Only has direct workspace access
});

test('organization admin can remove members except owner', function () {
    // Create users
    $owner = User::factory()->create(['name' => 'Owner']);
    $admin = User::factory()->create(['name' => 'Admin']);
    $member = User::factory()->create(['name' => 'Member']);
    $plan = Plan::first();

    // Create organization
    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
        'plan_id' => $plan->id,
    ]);

    // Add users to organization
    $organization->addUser($admin, OrganizationRole::ADMIN);
    $organization->addUser($member, OrganizationRole::MEMBER);

    // Set admin as current user
    $admin->update(['current_organization_id' => $organization->id]);
    Sanctum::actingAs($admin);

    // Test removing member - should succeed
    $response = $this->deleteJson("/api/v1/organization/members/{$member->uuid}");
    $response->assertStatus(200);
    expect($response->json('data.message'))->toBe('Member removed successfully');

    // Verify member is removed
    expect($organization->fresh()->users()->where('user_id', $member->id)->exists())->toBeFalse();

    // Test removing owner - should fail
    $response = $this->deleteJson("/api/v1/organization/members/{$owner->uuid}");
    $response->assertStatus(400);
    expect($response->json('message'))->toBe('Cannot remove the organization owner');

    // Test removing yourself - should fail
    $response = $this->deleteJson("/api/v1/organization/members/{$admin->uuid}");
    $response->assertStatus(400);
    expect($response->json('message'))->toBe('Cannot remove yourself from the organization');
});

test('organization admin can change member roles', function () {
    // Create users
    $owner = User::factory()->create(['name' => 'Owner']);
    $admin = User::factory()->create(['name' => 'Admin']);
    $member = User::factory()->create(['name' => 'Member']);
    $plan = Plan::first();

    // Create organization
    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
        'plan_id' => $plan->id,
    ]);

    // Add users to organization
    $organization->addUser($admin, OrganizationRole::ADMIN);
    $organization->addUser($member, OrganizationRole::MEMBER);

    // Set admin as current user
    $admin->update(['current_organization_id' => $organization->id]);
    Sanctum::actingAs($admin);

    // Test promoting member to admin
    $response = $this->patchJson("/api/v1/organization/members/{$member->uuid}/role", [
        'role' => 'admin',
    ]);
    $response->assertStatus(200);
    expect($response->json('data.new_role'))->toBe('admin');

    // Verify role is changed
    $memberRole = $organization->fresh()->users()->where('user_id', $member->id)->first()->pivot->role;
    expect($memberRole)->toBe('admin');

    // Test changing owner's role - should fail
    $response = $this->patchJson("/api/v1/organization/members/{$owner->uuid}/role", [
        'role' => 'member',
    ]);
    $response->assertStatus(400);
    expect($response->json('message'))->toBe('Cannot change the organization owner\'s role');

    // Test changing own role - should fail
    $response = $this->patchJson("/api/v1/organization/members/{$admin->uuid}/role", [
        'role' => 'member',
    ]);
    $response->assertStatus(400);
    expect($response->json('message'))->toBe('Cannot change your own role');

    // Test promoting to owner - should fail
    $response = $this->patchJson("/api/v1/organization/members/{$member->uuid}/role", [
        'role' => 'owner',
    ]);
    $response->assertStatus(400);
    expect($response->json('message'))->toBe('Cannot promote to owner. Use transfer ownership instead');
});

test('member workspace access updates when promoted to admin', function () {
    // Create users and plan
    $owner = User::factory()->create(['name' => 'Owner']);
    $member = User::factory()->create(['name' => 'Member']);
    $plan = Plan::first();

    // Create organization
    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
        'plan_id' => $plan->id,
    ]);

    // Add member to organization
    $organization->addUser($member, OrganizationRole::MEMBER);

    // Create multiple workspaces
    $workspace1 = Workspace::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Workspace 1',
    ]);
    $workspace2 = Workspace::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Workspace 2',
    ]);

    // Member initially only has access to workspace1
    $workspace1->addUser($member, WorkspaceRole::EDITOR);

    // Set owner as current user
    $owner->update(['current_organization_id' => $organization->id]);
    Sanctum::actingAs($owner);

    // Check initial workspace access - should only have 1 workspace
    $response = $this->getJson('/api/v1/organization/members');
    $members = $response->json('data.current_members');
    $memberData = collect($members)->firstWhere('name', 'Member');
    expect($memberData['workspace_access'])->toHaveCount(1);
    expect($memberData['organization_role'])->toBe('member');

    // Promote member to admin
    $response = $this->patchJson("/api/v1/organization/members/{$member->uuid}/role", [
        'role' => 'admin',
    ]);
    $response->assertStatus(200);

    // Check workspace access after promotion - should now have access to all workspaces
    $response = $this->getJson('/api/v1/organization/members');
    $members = $response->json('data.current_members');
    $memberData = collect($members)->firstWhere('name', 'Member');
    expect($memberData['workspace_access'])->toHaveCount(2);
    expect($memberData['organization_role'])->toBe('admin');

    // Verify both workspaces are accessible with organization_admin access type
    $workspaceNames = array_column($memberData['workspace_access'], 'name');
    expect($workspaceNames)->toContain('Workspace 1');
    expect($workspaceNames)->toContain('Workspace 2');

    $accessTypes = array_column($memberData['workspace_access'], 'access_type');
    expect($accessTypes)->toContain('organization_admin');
});

test('member workspace assignments are updated when changing role with workspace assignments', function () {
    // Create users and plan
    $owner = User::factory()->create(['name' => 'Owner']);
    $member = User::factory()->create(['name' => 'Member']);
    $plan = Plan::first();

    // Create organization
    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
        'plan_id' => $plan->id,
    ]);

    // Add member to organization
    $organization->addUser($member, OrganizationRole::MEMBER);

    // Create multiple workspaces
    $workspace1 = Workspace::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Workspace 1',
    ]);
    $workspace2 = Workspace::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Workspace 2',
    ]);
    $workspace3 = Workspace::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Workspace 3',
    ]);

    // Member initially only has access to workspace1
    $workspace1->addUser($member, WorkspaceRole::EDITOR);

    // Set owner as current user
    $owner->update(['current_organization_id' => $organization->id]);
    Sanctum::actingAs($owner);

    // Check initial workspace access - should only have 1 workspace
    $response = $this->getJson('/api/v1/organization/members');
    $members = $response->json('data.current_members');
    $memberData = collect($members)->firstWhere('name', 'Member');
    expect($memberData['workspace_access'])->toHaveCount(1);
    expect($memberData['organization_role'])->toBe('member');

    // Update member role with new workspace assignments (workspace2 and workspace3)
    $response = $this->patchJson("/api/v1/organization/members/{$member->uuid}/role", [
        'role' => 'member',
        'workspace_assignments' => [
            ['workspace_id' => $workspace2->uuid, 'role' => 'editor'],
            ['workspace_id' => $workspace3->uuid, 'role' => 'viewer'],
        ],
    ]);
    $response->assertStatus(200);
    expect($response->json('data.workspace_assignments_updated'))->toBeTrue();

    // Check workspace access after update - should now have access to workspace2 and workspace3 (not workspace1)
    $response = $this->getJson('/api/v1/organization/members');
    $members = $response->json('data.current_members');
    $memberData = collect($members)->firstWhere('name', 'Member');
    expect($memberData['workspace_access'])->toHaveCount(2);
    expect($memberData['organization_role'])->toBe('member');

    // Verify correct workspaces are accessible
    $workspaceNames = array_column($memberData['workspace_access'], 'name');
    expect($workspaceNames)->toContain('Workspace 2');
    expect($workspaceNames)->toContain('Workspace 3');
    expect($workspaceNames)->not->toContain('Workspace 1'); // Should be removed

    $accessTypes = array_column($memberData['workspace_access'], 'access_type');
    expect($accessTypes)->toContain('direct_member'); // Should be direct member access
});

test('cannot assign member to workspace from different organization', function () {
    // Create two separate organizations
    $owner1 = User::factory()->create(['name' => 'Owner 1']);
    $owner2 = User::factory()->create(['name' => 'Owner 2']);
    $member = User::factory()->create(['name' => 'Member']);
    $plan = Plan::first();

    $organization1 = Organization::factory()->create([
        'owner_id' => $owner1->id,
        'plan_id' => $plan->id,
    ]);

    $organization2 = Organization::factory()->create([
        'owner_id' => $owner2->id,
        'plan_id' => $plan->id,
    ]);

    // Add member to organization1
    $organization1->addUser($member, OrganizationRole::MEMBER);

    // Create workspace in organization2 (different org)
    $workspace2 = Workspace::factory()->create([
        'organization_id' => $organization2->id,
        'name' => 'Other Org Workspace',
    ]);

    // Set owner1 as current user for organization1
    $owner1->update(['current_organization_id' => $organization1->id]);
    Sanctum::actingAs($owner1);

    // Try to assign member to workspace from different organization - should fail
    $response = $this->patchJson("/api/v1/organization/members/{$member->uuid}/role", [
        'role' => 'member',
        'workspace_assignments' => [
            ['workspace_id' => $workspace2->uuid, 'role' => 'editor'], // Different org workspace
        ],
    ]);

    $response->assertStatus(422); // Validation should fail - security working
    expect($response->json('success'))->toBeFalse();
});

test('cannot remove member from different organization', function () {
    // Create two separate organizations
    $owner1 = User::factory()->create(['name' => 'Owner 1']);
    $owner2 = User::factory()->create(['name' => 'Owner 2']);
    $member1 = User::factory()->create(['name' => 'Member 1']);
    $member2 = User::factory()->create(['name' => 'Member 2']);
    $plan = Plan::first();

    $organization1 = Organization::factory()->create([
        'owner_id' => $owner1->id,
        'plan_id' => $plan->id,
    ]);

    $organization2 = Organization::factory()->create([
        'owner_id' => $owner2->id,
        'plan_id' => $plan->id,
    ]);

    // Add members to their respective organizations
    $organization1->addUser($member1, OrganizationRole::MEMBER);
    $organization2->addUser($member2, OrganizationRole::MEMBER);

    // Set owner1 as current user for organization1
    $owner1->update(['current_organization_id' => $organization1->id]);
    Sanctum::actingAs($owner1);

    // Try to remove member2 (who belongs to organization2) - should fail
    $response = $this->deleteJson("/api/v1/organization/members/{$member2->uuid}");

    $response->assertStatus(404); // Should return not found (member not in current org)
    expect($response->json('message'))->toBe('User is not a member of this organization');
});

test('cannot change role of member from different organization', function () {
    // Create two separate organizations
    $owner1 = User::factory()->create(['name' => 'Owner 1']);
    $owner2 = User::factory()->create(['name' => 'Owner 2']);
    $member1 = User::factory()->create(['name' => 'Member 1']);
    $member2 = User::factory()->create(['name' => 'Member 2']);
    $plan = Plan::first();

    $organization1 = Organization::factory()->create([
        'owner_id' => $owner1->id,
        'plan_id' => $plan->id,
    ]);

    $organization2 = Organization::factory()->create([
        'owner_id' => $owner2->id,
        'plan_id' => $plan->id,
    ]);

    // Add members to their respective organizations
    $organization1->addUser($member1, OrganizationRole::MEMBER);
    $organization2->addUser($member2, OrganizationRole::MEMBER);

    // Set owner1 as current user for organization1
    $owner1->update(['current_organization_id' => $organization1->id]);
    Sanctum::actingAs($owner1);

    // Try to change role of member2 (who belongs to organization2) - should fail
    $response = $this->patchJson("/api/v1/organization/members/{$member2->uuid}/role", [
        'role' => 'admin',
    ]);

    $response->assertStatus(404); // Should return not found (member not in current org)
    expect($response->json('message'))->toBe('User is not a member of this organization');
});

test('organization members endpoint returns members with pending invitations', function () {
    // Create users and plan
    $owner = User::factory()->create(['name' => 'Owner']);
    $admin = User::factory()->create(['name' => 'Admin']);
    $member = User::factory()->create(['name' => 'Member']);
    $plan = Plan::first();

    // Create organization
    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
        'plan_id' => $plan->id,
    ]);

    // Add users to organization
    $organization->addUser($admin, OrganizationRole::ADMIN);
    $organization->addUser($member, OrganizationRole::MEMBER);

    // Create workspaces
    $workspace1 = Workspace::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Workspace 1',
    ]);
    $workspace2 = Workspace::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Workspace 2',
    ]);

    // Add member to workspace1
    $workspace1->addUser($member, WorkspaceRole::EDITOR);

    // Create pending invitations
    $pendingAdminInvite = \App\Models\Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'pending-admin@example.com',
        'role' => 'admin',
        'status' => 'pending',
        'inviter_id' => $owner->id,
        'expires_at' => now()->addDays(7),
        'workspace_assignments' => null, // Admins get access to all workspaces
    ]);

    $pendingMemberInvite = \App\Models\Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'pending-member@example.com',
        'role' => 'member',
        'status' => 'pending',
        'inviter_id' => $admin->id,
        'expires_at' => now()->addDays(7),
        'workspace_assignments' => [
            ['workspace_id' => $workspace2->uuid, 'role' => 'viewer'],
        ],
    ]);

    // Set owner as current user
    $owner->update(['current_organization_id' => $organization->id]);
    Sanctum::actingAs($owner);

    // Test the endpoint
    $response = $this->getJson('/api/v1/organization/members');
    $response->assertStatus(200);

    $data = $response->json('data');

    // Should have current members and pending invitations
    expect($data)->toHaveKeys(['current_members', 'pending_invitations', 'summary']);

    // Check current members count (owner + admin + member = 3)
    expect($data['current_members'])->toHaveCount(3);

    // Check pending invitations count (2)
    expect($data['pending_invitations'])->toHaveCount(2);

    // Check summary
    expect($data['summary']['total_current_members'])->toBe(3);
    expect($data['summary']['total_pending_invitations'])->toBe(2);
    expect($data['summary']['total_members_including_pending'])->toBe(5);

    // Verify pending admin invitation structure
    $pendingAdmin = collect($data['pending_invitations'])->firstWhere('email', 'pending-admin@example.com');
    expect($pendingAdmin['organization_role'])->toBe('admin');
    expect($pendingAdmin['invitation_status'])->toBe('pending');
    expect($pendingAdmin['invited_by']['name'])->toBe('Owner');
    expect($pendingAdmin['will_have_access_to_all_workspaces'])->toBeTrue();
    expect($pendingAdmin['workspace_assignments'])->toBeEmpty();

    // Verify pending member invitation structure
    $pendingMember = collect($data['pending_invitations'])->firstWhere('email', 'pending-member@example.com');
    expect($pendingMember['organization_role'])->toBe('member');
    expect($pendingMember['invitation_status'])->toBe('pending');
    expect($pendingMember['invited_by']['name'])->toBe('Admin');
    expect($pendingMember['will_have_access_to_all_workspaces'])->toBeFalse();
    expect($pendingMember['workspace_assignments'])->toHaveCount(1);
    expect($pendingMember['workspace_assignments'][0]['workspace_name'])->toBe('Workspace 2');
    expect($pendingMember['workspace_assignments'][0]['role'])->toBe('viewer');
});

test('expired invitations are not included in pending invitations list', function () {
    // Create organization
    $owner = User::factory()->create();
    $plan = Plan::first();
    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
        'plan_id' => $plan->id,
    ]);

    // Create expired invitation
    \App\Models\Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'expired@example.com',
        'role' => 'member',
        'status' => 'pending',
        'inviter_id' => $owner->id,
        'expires_at' => now()->subDays(1), // Expired
    ]);

    // Create valid invitation
    \App\Models\Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'valid@example.com',
        'role' => 'member',
        'status' => 'pending',
        'inviter_id' => $owner->id,
        'expires_at' => now()->addDays(7), // Valid
    ]);

    $owner->update(['current_organization_id' => $organization->id]);
    Sanctum::actingAs($owner);

    $response = $this->getJson('/api/v1/organization/members');
    $response->assertStatus(200);

    $data = $response->json('data');

    // Should only include the non-expired invitation
    expect($data['pending_invitations'])->toHaveCount(1);
    expect($data['pending_invitations'][0]['email'])->toBe('valid@example.com');
});

test('accepted invitations are not included in pending invitations list', function () {
    // Create organization
    $owner = User::factory()->create();
    $plan = Plan::first();
    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
        'plan_id' => $plan->id,
    ]);

    // Create accepted invitation
    \App\Models\Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'accepted@example.com',
        'role' => 'member',
        'status' => 'accepted',
        'inviter_id' => $owner->id,
        'expires_at' => now()->addDays(7),
        'accepted_at' => now(),
    ]);

    // Create pending invitation
    \App\Models\Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'pending@example.com',
        'role' => 'member',
        'status' => 'pending',
        'inviter_id' => $owner->id,
        'expires_at' => now()->addDays(7),
    ]);

    $owner->update(['current_organization_id' => $organization->id]);
    Sanctum::actingAs($owner);

    $response = $this->getJson('/api/v1/organization/members');
    $response->assertStatus(200);

    $data = $response->json('data');

    // Should only include the pending invitation
    expect($data['pending_invitations'])->toHaveCount(1);
    expect($data['pending_invitations'][0]['email'])->toBe('pending@example.com');
});
