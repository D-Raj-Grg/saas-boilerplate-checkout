<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;

beforeEach(function (): void {
    $this->organizationService = app(OrganizationService::class);
    $this->user = User::factory()->create();
});

test('organization creation adds user to organization_users table', function (): void {
    // Create an organization
    $organization = $this->organizationService->create($this->user, [
        'name' => 'Test Organization',
        'slug' => 'test-org',
        'description' => 'Test organization',
    ]);

    // Check if user is in organization_users table
    $orgUser = $organization->organizationUsers()->where('user_id', $this->user->id)->first();
    expect($orgUser)->not()->toBeNull();
    expect($orgUser->role)->toBe(OrganizationRole::OWNER);
    expect($orgUser->user_id)->toBe($this->user->id);
    expect($orgUser->organization_id)->toBe($organization->id);
});

test('user can access organization after creation', function (): void {
    // Create an organization
    $organization = $this->organizationService->create($this->user, [
        'name' => 'Test Organization',
        'slug' => 'test-org',
        'description' => 'Test organization',
    ]);

    // User should be able to access the organization
    expect($this->user->isOrganizationOwner($organization))->toBeTrue();
    expect($this->user->isOrganizationAdmin($organization))->toBeTrue();

    // User should have access to the organization
    $accessibleOrgs = $this->user->accessibleOrganizations()->get();
    expect($accessibleOrgs)->toHaveCount(1);
    expect($accessibleOrgs->first()->id)->toBe($organization->id);
});

test('user has correct permissions after organization creation', function (): void {
    // Create an organization
    $organization = $this->organizationService->create($this->user, [
        'name' => 'Test Organization',
        'slug' => 'test-org',
        'description' => 'Test organization',
    ]);

    // Check organization permissions
    $permissions = $this->user->getOrganizationPermissions($organization);

    expect($permissions['can_create_workspaces'])->toBeTrue();
    expect($permissions['can_edit_any_workspace'])->toBeTrue();
    expect($permissions['can_delete_any_workspace'])->toBeTrue();
    expect($permissions['can_manage_organization'])->toBeTrue();
    expect($permissions['can_manage_billing'])->toBeTrue();
    expect($permissions['can_transfer_ownership'])->toBeTrue();
    expect($permissions['can_view_all_workspaces'])->toBeTrue();
});
