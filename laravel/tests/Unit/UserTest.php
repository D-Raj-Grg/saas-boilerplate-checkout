<?php

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->workspace = Workspace::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->user = User::factory()->create([
        'current_organization_id' => $this->organization->id,
        'current_workspace_id' => $this->workspace->id,
    ]);
});

test('get workspace permissions returns all permissions for organization owner', function (): void {
    $this->organization->addUser($this->user, OrganizationRole::OWNER);

    $permissions = $this->user->getWorkspacePermissions($this->workspace);

    $expectedPermissions = [
        'can_invite_users' => true,
        'can_remove_users' => true,
        'can_change_user_roles' => true,
        'can_manage_settings' => true,
        'can_delete_workspace' => true,
        'can_update_workspace' => true,
        'can_view_audit_logs' => true,
    ];

    expect($permissions)->toBe($expectedPermissions);
});

test('get workspace permissions returns role based permissions for workspace members', function (): void {
    $this->workspace->users()->attach($this->user->id, [
        'role' => WorkspaceRole::MANAGER->value,
        'joined_at' => now(),
    ]);

    $permissions = $this->user->getWorkspacePermissions($this->workspace);

    expect($permissions['can_invite_users'])->toBeTrue();
    expect($permissions['can_remove_users'])->toBeTrue();
    expect($permissions['can_manage_settings'])->toBeTrue();
    expect($permissions['can_update_workspace'])->toBeTrue();
    expect($permissions['can_view_audit_logs'])->toBeTrue();

    expect($permissions['can_delete_workspace'])->toBeFalse();
    expect($permissions['can_change_user_roles'])->toBeFalse();
});

test('get workspace permissions returns member permissions', function (): void {
    $this->workspace->users()->attach($this->user->id, [
        'role' => WorkspaceRole::EDITOR->value,
        'joined_at' => now(),
    ]);

    $permissions = $this->user->getWorkspacePermissions($this->workspace);

    // Editors have some basic permissions
    expect($permissions['can_invite_users'])->toBeFalse();
    expect($permissions['can_remove_users'])->toBeFalse();
    expect($permissions['can_change_user_roles'])->toBeFalse();
    expect($permissions['can_manage_settings'])->toBeFalse();
    expect($permissions['can_delete_workspace'])->toBeFalse();
    expect($permissions['can_update_workspace'])->toBeFalse();
    expect($permissions['can_view_audit_logs'])->toBeFalse();
});

test('get workspace permissions returns false permissions for non members', function (): void {
    $permissions = $this->user->getWorkspacePermissions($this->workspace);

    $expectedPermissions = [
        'can_invite_users' => false,
        'can_remove_users' => false,
        'can_change_user_roles' => false,
        'can_manage_settings' => false,
        'can_delete_workspace' => false,
        'can_update_workspace' => false,
        'can_view_audit_logs' => false,
    ];

    expect($permissions)->toBe($expectedPermissions);
});

test('get organization permissions returns all permissions for organization owner', function (): void {
    $this->organization->addUser($this->user, OrganizationRole::OWNER);

    $permissions = $this->user->getOrganizationPermissions($this->organization);

    $expectedPermissions = [
        'can_create_workspaces' => true,
        'can_edit_any_workspace' => true,
        'can_delete_any_workspace' => true,
        'can_manage_organization' => true,
        'can_manage_billing' => true,
        'can_transfer_ownership' => true,
        'can_view_all_workspaces' => true,
        'can_create_organization' => true,
    ];

    expect($permissions)->toBe($expectedPermissions);
});

test('get organization permissions returns false permissions for non owners', function (): void {
    $otherOwner = User::factory()->create();
    $this->organization->update(['owner_id' => $otherOwner->id]);

    $permissions = $this->user->getOrganizationPermissions($this->organization);

    $expectedPermissions = [
        'can_create_workspaces' => false,
        'can_edit_any_workspace' => false,
        'can_delete_any_workspace' => false,
        'can_manage_organization' => false,
        'can_manage_billing' => false,
        'can_transfer_ownership' => false,
        'can_view_all_workspaces' => false,
        'can_create_organization' => true,
    ];

    expect($permissions)->toBe($expectedPermissions);
});

test('can manage any workspace in organization method', function (): void {
    $this->organization->addUser($this->user, OrganizationRole::OWNER);

    $permissions = $this->user->getOrganizationPermissions($this->organization);
    expect($permissions['can_edit_any_workspace'])->toBeTrue();

    $nonOwner = User::factory()->create();
    $nonOwnerPermissions = $nonOwner->getOrganizationPermissions($this->organization);
    expect($nonOwnerPermissions['can_edit_any_workspace'])->toBeFalse();
});

test('can create workspaces in organization method', function (): void {
    $this->organization->addUser($this->user, OrganizationRole::OWNER);

    $permissions = $this->user->getOrganizationPermissions($this->organization);
    expect($permissions['can_create_workspaces'])->toBeTrue();

    $nonOwner = User::factory()->create();
    $nonOwnerPermissions = $nonOwner->getOrganizationPermissions($this->organization);
    expect($nonOwnerPermissions['can_create_workspaces'])->toBeFalse();
});

test('get organization owner permissions method', function (): void {
    $this->organization->addUser($this->user, OrganizationRole::OWNER);

    $permissions = $this->user->getOrganizationPermissions($this->organization);

    $expectedPermissions = [
        'can_create_workspaces' => true,
        'can_edit_any_workspace' => true,
        'can_delete_any_workspace' => true,
        'can_manage_organization' => true,
        'can_manage_billing' => true,
        'can_transfer_ownership' => true,
        'can_view_all_workspaces' => true,
        'can_create_organization' => true,
    ];

    expect($permissions)->toBe($expectedPermissions);
});

test('workspace permissions consistent structure', function (): void {
    $permissions1 = $this->user->getWorkspacePermissions($this->workspace);

    $this->organization->addUser($this->user, OrganizationRole::MEMBER);
    $this->workspace->users()->attach($this->user->id, [
        'role' => WorkspaceRole::EDITOR->value,
        'joined_at' => now(),
    ]);
    $permissions2 = $this->user->getWorkspacePermissions($this->workspace);

    // Remove member role and add as owner
    $this->organization->removeUser($this->user);
    $this->organization->addUser($this->user, OrganizationRole::OWNER);
    $permissions3 = $this->user->getWorkspacePermissions($this->workspace);

    expect(array_keys($permissions1))->toBe(array_keys($permissions2));
    expect(array_keys($permissions2))->toBe(array_keys($permissions3));

    expect($permissions1)->toHaveCount(7);
    expect($permissions2)->toHaveCount(7);
    expect($permissions3)->toHaveCount(7);
});

test('organization permissions consistent structure', function (): void {
    $permissions1 = $this->user->getOrganizationPermissions($this->organization);

    $this->organization->addUser($this->user, OrganizationRole::OWNER);
    $permissions2 = $this->user->getOrganizationPermissions($this->organization);

    expect(array_keys($permissions1))->toBe(array_keys($permissions2));

    expect($permissions1)->toHaveCount(8);
    expect($permissions2)->toHaveCount(8);
});

test('organization owner overrides workspace membership requirement', function (): void {
    $this->organization->addUser($this->user, OrganizationRole::OWNER);

    $permissions = $this->user->getWorkspacePermissions($this->workspace);

    expect($permissions['can_invite_users'])->toBeTrue();
    expect($permissions['can_remove_users'])->toBeTrue();
    expect($permissions['can_change_user_roles'])->toBeTrue();
    expect($permissions['can_manage_settings'])->toBeTrue();
    expect($permissions['can_delete_workspace'])->toBeTrue();
    expect($permissions['can_update_workspace'])->toBeTrue();
    expect($permissions['can_view_audit_logs'])->toBeTrue();
});
