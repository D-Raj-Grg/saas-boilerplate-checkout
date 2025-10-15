<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->freePlan = Plan::whereSlug('free')->first() ?? Plan::factory()->create(['slug' => 'free', 'name' => 'Free Plan']);
    $this->paidPlan = Plan::whereSlug('pro')->first() ?? Plan::factory()->create(['slug' => 'pro', 'name' => 'Pro Plan']);
});

test('user can create first free organization', function () {
    actingAs($this->user);

    $response = postJson('/api/v1/organization', [
        'name' => 'My First Organization',
    ]);

    $response->assertStatus(201);
    expect($this->user->ownedOrganizations()->count())->toBe(1);
});

test('user cannot create second free organization', function () {
    actingAs($this->user);

    // Create first free organization
    $firstOrg = Organization::factory()->create(['owner_id' => $this->user->id]);
    $firstOrg->attachPlan($this->freePlan, ['status' => 'active']);

    // Try to create second free organization
    $response = postJson('/api/v1/organization', [
        'name' => 'My Second Organization',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['plan_id']);
    $response->assertJson([
        'success' => false,
        'errors' => [
            'plan_id' => [
                'You already have a free organization. Please upgrade your existing organization or choose a paid plan.',
            ],
        ],
    ]);
});

test('user can create second organization with paid plan', function () {
    actingAs($this->user);

    // Create first free organization
    $firstOrg = Organization::factory()->create(['owner_id' => $this->user->id]);
    $firstOrg->attachPlan($this->freePlan, ['status' => 'active']);

    // Try to create second organization with paid plan
    $response = postJson('/api/v1/organization', [
        'name' => 'My Second Organization',
        'plan_id' => $this->paidPlan->id,
    ]);

    $response->assertStatus(201);
    expect($this->user->ownedOrganizations()->count())->toBe(2);
});

test('user can create organization if first free org is cancelled', function () {
    actingAs($this->user);

    // Create first free organization but cancel it
    $firstOrg = Organization::factory()->create(['owner_id' => $this->user->id]);
    $firstOrg->attachPlan($this->freePlan, ['status' => 'cancelled']);

    // Should be able to create another free organization
    $response = postJson('/api/v1/organization', [
        'name' => 'My Second Organization',
    ]);

    $response->assertStatus(201);
    expect($this->user->ownedOrganizations()->count())->toBe(2);
});

test('validation error when trying to create free org with existing free org', function () {
    actingAs($this->user);

    // Create first free organization
    $firstOrg = Organization::factory()->create(['owner_id' => $this->user->id]);
    $firstOrg->attachPlan($this->freePlan, ['status' => 'active']);

    // Try to create second free organization explicitly specifying free plan
    $response = postJson('/api/v1/organization', [
        'name' => 'My Second Organization',
        'plan_id' => $this->freePlan->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['plan_id']);
});
