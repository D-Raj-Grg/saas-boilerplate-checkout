<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRestrictionsValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test organization role hierarchy and permissions.
     */
    public function test_organization_role_permissions_are_properly_enforced(): void
    {
        $organization = Organization::factory()->create();

        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();

        // Assign roles
        $organization->addUser($owner, OrganizationRole::OWNER);
        $organization->addUser($admin, OrganizationRole::ADMIN);
        $organization->addUser($member, OrganizationRole::MEMBER);

        // OWNER permissions
        $this->assertTrue($owner->isOrganizationOwner($organization), 'Owner should be recognized as owner');
        $this->assertTrue($owner->isOrganizationAdmin($organization), 'Owner should have admin privileges');
        $this->assertTrue($owner->can('update', $organization), 'Owner should be able to update organization');
        $this->assertTrue($owner->can('delete', $organization), 'Owner should be able to delete organization');
        $this->assertTrue($owner->can('manageBilling', $organization), 'Owner should be able to manage billing');
        $this->assertTrue($owner->can('transferOwnership', $organization), 'Owner should be able to transfer ownership');
        $this->assertTrue($owner->can('inviteUsers', $organization), 'Owner should be able to invite users');
        $this->assertTrue($owner->can('createWorkspace', $organization), 'Owner should be able to create workspaces');

        // ADMIN permissions
        $this->assertFalse($admin->isOrganizationOwner($organization), 'Admin should not be recognized as owner');
        $this->assertTrue($admin->isOrganizationAdmin($organization), 'Admin should have admin privileges');
        $this->assertTrue($admin->can('update', $organization), 'Admin should be able to update organization');
        $this->assertFalse($admin->can('delete', $organization), 'Admin should NOT be able to delete organization');
        $this->assertFalse($admin->can('manageBilling', $organization), 'Admin should NOT be able to manage billing');
        $this->assertFalse($admin->can('transferOwnership', $organization), 'Admin should NOT be able to transfer ownership');
        $this->assertTrue($admin->can('inviteUsers', $organization), 'Admin should be able to invite users');
        $this->assertTrue($admin->can('createWorkspace', $organization), 'Admin should be able to create workspaces');

        // MEMBER permissions (without workspace editor role)
        $this->assertFalse($member->isOrganizationOwner($organization), 'Member should not be recognized as owner');
        $this->assertFalse($member->isOrganizationAdmin($organization), 'Member should not have admin privileges');
        $this->assertTrue($member->can('view', $organization), 'Member should be able to view organization');
        $this->assertFalse($member->can('update', $organization), 'Member should NOT be able to update organization');
        $this->assertFalse($member->can('delete', $organization), 'Member should NOT be able to delete organization');
        $this->assertFalse($member->can('manageBilling', $organization), 'Member should NOT be able to manage billing');
        $this->assertFalse($member->can('inviteUsers', $organization), 'Member without workspace editor role should NOT be able to invite users');
        $this->assertFalse($member->can('createWorkspace', $organization), 'Member should NOT be able to create workspaces');

        // OUTSIDER permissions
        $this->assertFalse($outsider->isOrganizationOwner($organization), 'Outsider should not be recognized as owner');
        $this->assertFalse($outsider->isOrganizationAdmin($organization), 'Outsider should not have admin privileges');
        $this->assertFalse($outsider->can('view', $organization), 'Outsider should NOT be able to view organization');
        $this->assertFalse($outsider->can('update', $organization), 'Outsider should NOT be able to update organization');
    }

    /**
     * Test workspace role hierarchy and permissions.
     */
    public function test_workspace_role_permissions_are_properly_enforced(): void
    {
        $organization = Organization::factory()->create();
        $workspace = Workspace::factory()->create(['organization_id' => $organization->id]);

        $orgOwner = User::factory()->create();
        $orgAdmin = User::factory()->create();
        $manager = User::factory()->create();
        $editor = User::factory()->create();
        $viewer = User::factory()->create();
        $member = User::factory()->create();

        // Set up organization roles
        $organization->addUser($orgOwner, OrganizationRole::OWNER);
        $organization->addUser($orgAdmin, OrganizationRole::ADMIN);
        $organization->addUser($manager, OrganizationRole::MEMBER);
        $organization->addUser($editor, OrganizationRole::MEMBER);
        $organization->addUser($viewer, OrganizationRole::MEMBER);
        $organization->addUser($member, OrganizationRole::MEMBER);

        // Set up workspace roles
        $workspace->addUser($manager, WorkspaceRole::MANAGER);
        $workspace->addUser($editor, WorkspaceRole::EDITOR);
        $workspace->addUser($viewer, WorkspaceRole::VIEWER);
        // member has no workspace role

        // ORGANIZATION OWNER - implicit manager access
        $this->assertTrue($orgOwner->belongsToWorkspace($workspace), 'Org owner should have implicit workspace access');
        $this->assertEquals(WorkspaceRole::MANAGER, $orgOwner->getRoleInWorkspace($workspace), 'Org owner should have manager role');
        $this->assertTrue($orgOwner->can('update', $workspace), 'Org owner should be able to update workspace');
        $this->assertTrue($orgOwner->can('delete', $workspace), 'Org owner should be able to delete workspace');
        $this->assertTrue($orgOwner->can('inviteUsers', $workspace), 'Org owner should be able to invite users');

        // ORGANIZATION ADMIN - implicit manager access
        $this->assertTrue($orgAdmin->belongsToWorkspace($workspace), 'Org admin should have implicit workspace access');
        $this->assertEquals(WorkspaceRole::MANAGER, $orgAdmin->getRoleInWorkspace($workspace), 'Org admin should have manager role');
        $this->assertTrue($orgAdmin->can('update', $workspace), 'Org admin should be able to update workspace');
        $this->assertTrue($orgAdmin->can('delete', $workspace), 'Org admin should be able to delete workspace');

        // WORKSPACE MANAGER
        $manager->update(['current_workspace_id' => $workspace->id]);
        $this->assertTrue($manager->belongsToWorkspace($workspace), 'Manager should belong to workspace');
        $this->assertEquals(WorkspaceRole::MANAGER, $manager->getRoleInWorkspace($workspace), 'Manager should have manager role');
        $this->assertTrue($manager->canManageWorkspace($workspace), 'Manager should be able to manage workspace');
        $this->assertTrue($manager->canInviteToWorkspace($workspace), 'Manager should be able to invite users');
        $this->assertTrue($manager->can('inviteUsers', $organization), 'Manager should be able to invite users to organization');

        // WORKSPACE EDITOR - can now invite users to organization (but only to their workspace)
        $editor->update(['current_workspace_id' => $workspace->id]);
        $this->assertTrue($editor->belongsToWorkspace($workspace), 'Editor should belong to workspace');
        $this->assertEquals(WorkspaceRole::EDITOR, $editor->getRoleInWorkspace($workspace), 'Editor should have editor role');
        $this->assertFalse($editor->canManageWorkspace($workspace), 'Editor should NOT be able to manage workspace');
        $this->assertFalse($editor->canInviteToWorkspace($workspace), 'Editor should NOT be able to invite users via workspace policy');
        $this->assertTrue($editor->can('inviteUsers', $organization), 'Editor should be able to invite users to organization (but restricted to their workspace)');

        // WORKSPACE VIEWER
        $this->assertTrue($viewer->belongsToWorkspace($workspace), 'Viewer should belong to workspace');
        $this->assertEquals(WorkspaceRole::VIEWER, $viewer->getRoleInWorkspace($workspace), 'Viewer should have viewer role');
        $this->assertFalse($viewer->canManageWorkspace($workspace), 'Viewer should NOT be able to manage workspace');
        $this->assertFalse($viewer->canInviteToWorkspace($workspace), 'Viewer should NOT be able to invite users');
        $this->assertTrue($viewer->can('view', $workspace), 'Viewer should be able to view workspace');

        // ORGANIZATION MEMBER without workspace role
        $this->assertFalse($member->belongsToWorkspace($workspace), 'Org member without workspace role should NOT belong to workspace');
        $this->assertNull($member->getRoleInWorkspace($workspace), 'Org member without workspace role should have no role');
        $this->assertFalse($member->can('view', $workspace), 'Org member without workspace role should NOT view workspace');
    }

    /**
     * Test role-based capabilities from enums.
     */
    public function test_role_enum_capabilities_are_correct(): void
    {
        // Organization Role Capabilities
        $this->assertTrue(OrganizationRole::OWNER->canManageOrganization());
        $this->assertTrue(OrganizationRole::OWNER->canInviteUsers());
        $this->assertTrue(OrganizationRole::OWNER->canManageBilling());
        $this->assertTrue(OrganizationRole::OWNER->canTransferOwnership());
        $this->assertTrue(OrganizationRole::OWNER->hasImplicitWorkspaceAccess());

        $this->assertTrue(OrganizationRole::ADMIN->canManageOrganization());
        $this->assertTrue(OrganizationRole::ADMIN->canInviteUsers());
        $this->assertFalse(OrganizationRole::ADMIN->canManageBilling());
        $this->assertFalse(OrganizationRole::ADMIN->canTransferOwnership());
        $this->assertTrue(OrganizationRole::ADMIN->hasImplicitWorkspaceAccess());

        $this->assertFalse(OrganizationRole::MEMBER->canManageOrganization());
        $this->assertFalse(OrganizationRole::MEMBER->canInviteUsers());
        $this->assertFalse(OrganizationRole::MEMBER->canManageBilling());
        $this->assertFalse(OrganizationRole::MEMBER->canTransferOwnership());
        $this->assertFalse(OrganizationRole::MEMBER->hasImplicitWorkspaceAccess());

        // Workspace Role Capabilities
        $this->assertTrue(WorkspaceRole::MANAGER->canManageWorkspace());
        $this->assertTrue(WorkspaceRole::MANAGER->canInviteUsers());
        $this->assertTrue(WorkspaceRole::MANAGER->canEditContent());
        $this->assertTrue(WorkspaceRole::MANAGER->canViewContent());

        $this->assertFalse(WorkspaceRole::EDITOR->canManageWorkspace());
        $this->assertFalse(WorkspaceRole::EDITOR->canInviteUsers());
        $this->assertTrue(WorkspaceRole::EDITOR->canEditContent());
        $this->assertTrue(WorkspaceRole::EDITOR->canViewContent());

        $this->assertFalse(WorkspaceRole::VIEWER->canManageWorkspace());
        $this->assertFalse(WorkspaceRole::VIEWER->canInviteUsers());
        $this->assertFalse(WorkspaceRole::VIEWER->canEditContent());
        $this->assertTrue(WorkspaceRole::VIEWER->canViewContent());
    }
}
