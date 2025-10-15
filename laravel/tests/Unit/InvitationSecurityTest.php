<?php

namespace Tests\Unit;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private InvitationService $invitationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invitationService = app(InvitationService::class);
    }

    public function test_organization_member_cannot_invite_admin()
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $organization->addUser($owner, OrganizationRole::OWNER);
        $organization->addUser($member, OrganizationRole::MEMBER);

        // Mock canUse to return true to bypass member limit check
        $organization = \Mockery::mock($organization)->makePartial();
        $organization->shouldReceive('canUse')->with('team_members', 1)->andReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Members cannot invite users with admin or owner privileges');

        $this->invitationService->inviteToOrganization(
            $organization,
            $member, // Member trying to invite
            'newadmin@example.com',
            OrganizationRole::ADMIN, // Trying to invite as admin
            []
        );
    }

    public function test_organization_member_cannot_invite_owner()
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $organization->addUser($owner, OrganizationRole::OWNER);
        $organization->addUser($member, OrganizationRole::MEMBER);

        // Mock canUse to return true to bypass member limit check
        $organization = \Mockery::mock($organization)->makePartial();
        $organization->shouldReceive('canUse')->with('team_members', 1)->andReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Members cannot invite users with admin or owner privileges');

        $this->invitationService->inviteToOrganization(
            $organization,
            $member, // Member trying to invite
            'newowner@example.com',
            OrganizationRole::OWNER, // Trying to invite as owner
            []
        );
    }

    public function test_organization_member_can_invite_other_member()
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspace = $organization->workspaces()->create([
            'name' => 'Test Workspace',
            'description' => 'Test workspace',
        ]);

        $organization->addUser($owner, OrganizationRole::OWNER);
        $organization->addUser($member, OrganizationRole::MEMBER);

        // Mock canUse to return true to bypass member limit check
        $organization = \Mockery::mock($organization)->makePartial();
        $organization->shouldReceive('canUse')->with('team_members', 1)->andReturn(true);

        // This should work without throwing an exception
        $invitation = $this->invitationService->inviteToOrganization(
            $organization,
            $member, // Member inviting
            'newmember@example.com',
            OrganizationRole::MEMBER, // Inviting as member
            [['workspace_id' => $workspace->uuid, 'role' => 'viewer']]
        );

        $this->assertNotNull($invitation);
        $this->assertEquals('member', $invitation->role);
        $this->assertEquals('newmember@example.com', $invitation->email);
    }

    public function test_organization_admin_can_invite_admin()
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create();
        $admin = User::factory()->create();

        $organization->addUser($owner, OrganizationRole::OWNER);
        $organization->addUser($admin, OrganizationRole::ADMIN);

        // Mock canUse to return true to bypass member limit check
        $organization = \Mockery::mock($organization)->makePartial();
        $organization->shouldReceive('canUse')->with('team_members', 1)->andReturn(true);

        // Admins should be able to invite other admins
        $invitation = $this->invitationService->inviteToOrganization(
            $organization,
            $admin, // Admin inviting
            'newadmin@example.com',
            OrganizationRole::ADMIN, // Inviting as admin
            []
        );

        $this->assertNotNull($invitation);
        $this->assertEquals('admin', $invitation->role);
        $this->assertEquals('newadmin@example.com', $invitation->email);
    }

    public function test_organization_owner_can_invite_owner()
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create();

        $organization->addUser($owner, OrganizationRole::OWNER);

        // Mock canUse to return true to bypass member limit check
        $organization = \Mockery::mock($organization)->makePartial();
        $organization->shouldReceive('canUse')->with('team_members', 1)->andReturn(true);

        // Owners should be able to invite other owners
        $invitation = $this->invitationService->inviteToOrganization(
            $organization,
            $owner, // Owner inviting
            'newowner@example.com',
            OrganizationRole::OWNER, // Inviting as owner
            []
        );

        $this->assertNotNull($invitation);
        $this->assertEquals('owner', $invitation->role);
        $this->assertEquals('newowner@example.com', $invitation->email);
    }
}
