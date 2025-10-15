<?php

use App\Enums\OrganizationRole;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
});

test('invitations received endpoint returns pending organization invitations', function (): void {
    // Create users
    $inviter = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee2@example.com']);

    // Create organization
    $plan = Plan::where('slug', 'free')->first();
    $organization = Organization::factory()->create([
        'owner_id' => $inviter->id,
        'plan_id' => $plan->id,
    ]);

    // Create a pending organization invitation for the invitee
    $invitation = Invitation::factory()->create([
        'email' => 'invitee2@example.com',
        'organization_id' => $organization->id,
        'inviter_id' => $inviter->id,
        'role' => OrganizationRole::MEMBER->value,
        'status' => 'pending',
    ]);

    // Authenticate as the invitee
    Sanctum::actingAs($invitee);

    // Call the received invitations endpoint
    $response = $this->getJson('/api/v1/invitations/received');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'email',
                    'role',
                    'status',
                    'message',
                    'token',
                    'expires_at',
                    'organization' => [
                        'uuid',
                        'name',
                    ],
                    'inviter' => [
                        'name',
                        'email',
                    ],
                ],
            ],
        ]);
});
