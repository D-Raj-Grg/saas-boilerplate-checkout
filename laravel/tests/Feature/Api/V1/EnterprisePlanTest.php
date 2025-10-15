<?php

use App\Enums\OrganizationRole;
use App\Enums\WorkspaceRole;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use App\Services\InvitationService;
use App\Services\OrganizationService;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->businessPlan = Plan::where('slug', 'business-yearly')->first();
    $this->freePlan = Plan::where('slug', 'free')->first();
});

test('business plan has unlimited workspace limits', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create([
        'owner_id' => $user->id,
    ]);

    // Attach business plan
    $organization->attachPlan($this->businessPlan);

    $organizationService = app(OrganizationService::class);

    expect($organizationService->hasReachedWorkspaceLimit($organization))->toBeFalse();
});

test('can invite unlimited users to business plan organization', function (): void {
    $owner = User::factory()->create();
    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
    ]);

    // Attach business plan
    $organization->attachPlan($this->businessPlan);

    $workspace = Workspace::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $workspace->users()->attach($owner->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

    Sanctum::actingAs($owner);

    $invitationService = app(InvitationService::class);

    for ($i = 1; $i <= 5; $i++) {
        $invitation = $invitationService->inviteToOrganization(
            $workspace->organization,
            $owner,
            "user{$i}@example.com",
            OrganizationRole::MEMBER,
            [['workspace_id' => $workspace->uuid, 'role' => WorkspaceRole::VIEWER->value]]
        );

        expect($invitation)->toBeInstanceOf(\App\Models\Invitation::class);
        expect($invitation->email)->toBe("user{$i}@example.com");
    }
});

test('free plan hits member limit', function (): void {
    $owner = User::factory()->create();
    $organization = Organization::factory()->create([
        'owner_id' => $owner->id,
    ]);

    // Remove auto-attached free plan and attach it manually for test consistency
    $freePlan = Plan::where('slug', 'free')->first();
    $organization->plans()->detach($freePlan->id);
    $organization->attachPlan($this->freePlan);

    $workspace = Workspace::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $workspace->users()->attach($owner->id, ['role' => WorkspaceRole::MANAGER->value, 'joined_at' => now()]);

    // Add 49 users to reach the free plan limit of 50 (owner + 49 = 50)
    for ($i = 1; $i <= 49; $i++) {
        $user = User::factory()->create();
        $organization->addUser($user, OrganizationRole::MEMBER);
        $workspace->users()->attach($user->id, ['role' => WorkspaceRole::VIEWER->value, 'joined_at' => now()]);
    }

    Sanctum::actingAs($owner);

    $invitationService = app(InvitationService::class);

    expect(function () use ($invitationService, $workspace, $owner, $organization): void {
        $invitationService->inviteToOrganization(
            $organization,
            $owner,
            'user51@example.com',
            OrganizationRole::MEMBER,
            [['workspace_id' => $workspace->uuid, 'role' => WorkspaceRole::VIEWER->value]]
        );
    })->toThrow(\Exception::class, 'Organization has reached member limit');
});
