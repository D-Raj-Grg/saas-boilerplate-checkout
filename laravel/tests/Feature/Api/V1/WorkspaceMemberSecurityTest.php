<?php

use App\Enums\WorkspaceRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Laravel\Sanctum\Sanctum;

test('cannot remove member from different workspace', function () {
    // Create two separate organizations with workspaces
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

    $workspace1 = Workspace::factory()->create([
        'organization_id' => $organization1->id,
        'name' => 'Workspace 1',
    ]);

    $workspace2 = Workspace::factory()->create([
        'organization_id' => $organization2->id,
        'name' => 'Workspace 2',
    ]);

    // Add members to their respective workspaces
    $workspace1->addUser($member1, WorkspaceRole::EDITOR);
    $workspace2->addUser($member2, WorkspaceRole::EDITOR);

    // Set owner1 with workspace1 as current
    $owner1->update([
        'current_organization_id' => $organization1->id,
        'current_workspace_id' => $workspace1->id,
    ]);
    Sanctum::actingAs($owner1);

    // Try to remove member2 (who belongs to workspace2) - should fail
    $response = $this->deleteJson("/api/v1/workspace/members/{$member2->uuid}");

    $response->assertStatus(404); // Should return not found (member not in current workspace)
    expect($response->json('message'))->toBe('User is not a member of this workspace');

    // Verify member2 is still in workspace2
    expect($workspace2->users()->where('user_id', $member2->id)->exists())->toBeTrue();
});

test('cannot change role of member from different workspace', function () {
    // Create two separate organizations with workspaces
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

    $workspace1 = Workspace::factory()->create([
        'organization_id' => $organization1->id,
        'name' => 'Workspace 1',
    ]);

    $workspace2 = Workspace::factory()->create([
        'organization_id' => $organization2->id,
        'name' => 'Workspace 2',
    ]);

    // Add members to their respective workspaces
    $workspace1->addUser($member1, WorkspaceRole::EDITOR);
    $workspace2->addUser($member2, WorkspaceRole::EDITOR);

    // Set owner1 with workspace1 as current
    $owner1->update([
        'current_organization_id' => $organization1->id,
        'current_workspace_id' => $workspace1->id,
    ]);
    Sanctum::actingAs($owner1);

    // Try to change role of member2 (who belongs to workspace2) - should fail
    $response = $this->patchJson("/api/v1/workspace/members/{$member2->uuid}/role", [
        'role' => 'manager',
    ]);

    $response->assertStatus(404); // Should return not found (member not in current workspace)
    expect($response->json('message'))->toBe('User is not a member of this workspace');

    // Verify member2's role is unchanged in workspace2
    $member2Role = $workspace2->users()->where('user_id', $member2->id)->first()?->pivot?->role;
    expect($member2Role)->toBe('editor');
});

test('can remove and change roles of members in same workspace', function () {
    // Create organization with workspace
    $owner = User::factory()->create(['name' => 'Owner']);
    $member = User::factory()->create(['name' => 'Member']);
    $plan = Plan::first();

    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
        'plan_id' => $plan->id,
    ]);

    $workspace = Workspace::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Test Workspace',
    ]);

    // Add member to workspace
    $workspace->addUser($member, WorkspaceRole::EDITOR);

    // Set owner with workspace as current
    $owner->update([
        'current_organization_id' => $organization->id,
        'current_workspace_id' => $workspace->id,
    ]);
    Sanctum::actingAs($owner);

    // Should be able to change role of member in same workspace
    $response = $this->patchJson("/api/v1/workspace/members/{$member->uuid}/role", [
        'role' => 'viewer',
    ]);

    $response->assertStatus(200);

    // Verify role was changed
    $memberRole = $workspace->users()->where('user_id', $member->id)->first()?->pivot?->role;
    expect($memberRole)->toBe('viewer');

    // Should be able to remove member from same workspace
    $response = $this->deleteJson("/api/v1/workspace/members/{$member->uuid}");

    $response->assertStatus(200);
    expect($response->json('message'))->toBe('Member removed successfully');

    // Verify member was removed
    expect($workspace->users()->where('user_id', $member->id)->exists())->toBeFalse();
});
