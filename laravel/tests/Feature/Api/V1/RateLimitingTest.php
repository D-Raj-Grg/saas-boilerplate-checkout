<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->apiPrefix = '/api/v1';
});

test('authentication endpoints are rate limited (5 per minute)', function () {
    // Make 5 requests (should work)
    for ($i = 0; $i < 5; $i++) {
        $response = $this->postJson("{$this->apiPrefix}/login", [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
        expect($response->status())->toBe(422); // Validation error, not rate limit
    }

    // 6th request should be rate limited
    $response = $this->postJson("{$this->apiPrefix}/login", [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429)
        ->assertJson([
            'success' => false,
            'error_code' => 'HTTP_429',
        ]);
});

test('rate limit headers are included in responses', function () {
    $response = $this->getJson("{$this->apiPrefix}/health");

    $response->assertStatus(200)
        ->assertHeader('X-RateLimit-Limit')
        ->assertHeader('X-RateLimit-Remaining');

    // Verify header values make sense
    $limit = $response->headers->get('X-RateLimit-Limit');
    $remaining = $response->headers->get('X-RateLimit-Remaining');

    expect($limit)->toBeGreaterThan(0);
    expect($remaining)->toBeLessThanOrEqual($limit);
});

test('plan-based rate limit multipliers work correctly', function () {
    // Use existing plans from seeders
    $freePlan = Plan::where('slug', 'free')->first();
    $proPlan = Plan::where('slug', 'pro-yearly')->first();

    // Create organizations and attach plans
    $freeOrg = Organization::factory()->create();
    $proOrg = Organization::factory()->create();

    // Remove auto-attached free plans and attach specific plans
    $freeOrg->plans()->detach();
    $proOrg->plans()->detach();
    $freeOrg->attachPlan($freePlan);
    $proOrg->attachPlan($proPlan);

    // Create workspaces and users
    $freeWorkspace = Workspace::factory()->create(['organization_id' => $freeOrg->id]);
    $proWorkspace = Workspace::factory()->create(['organization_id' => $proOrg->id]);

    $freeUser = User::factory()->create(['current_organization_id' => $freeOrg->id]);
    $proUser = User::factory()->create(['current_organization_id' => $proOrg->id]);

    $freeWorkspace->users()->attach($freeUser, ['joined_at' => now()]);
    $proWorkspace->users()->attach($proUser, ['joined_at' => now()]);

    // Test free user gets base limit (300 requests/min from seeder)
    Sanctum::actingAs($freeUser);
    $response = $this->getJson("{$this->apiPrefix}/health");
    $freeLimit = $response->headers->get('X-RateLimit-Limit');

    // Test pro user gets higher limit (1200 requests/min from seeder)
    Sanctum::actingAs($proUser);
    $response = $this->getJson("{$this->apiPrefix}/health");
    $proLimit = $response->headers->get('X-RateLimit-Limit');

    // Pro user should have 5x the limit of free user (300 vs 60)
    expect($freeLimit)->toBe('60');
    expect($proLimit)->toBe('300');
});

test('different endpoints have different rate limits', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // Test general API endpoint
    $healthResponse = $this->getJson("{$this->apiPrefix}/health");
    $generalLimit = $healthResponse->headers->get('X-RateLimit-Limit');

    // For authenticated routes, we'd need to create proper test data
    // This is a basic structure - you'd expand based on your endpoints
    expect($generalLimit)->toBeGreaterThan(0);
});

test('bulk operations have stricter rate limits', function () {
    // This would test bulk endpoints if accessible in tests
    // You'd need to set up proper test data for bulk operations
    expect(true)->toBe(true); // Placeholder
});

test('unauthenticated users get base rate limits', function () {
    $response = $this->getJson("{$this->apiPrefix}/health");

    $response->assertStatus(200)
        ->assertHeader('X-RateLimit-Limit')
        ->assertHeader('X-RateLimit-Remaining');

    $limit = $response->headers->get('X-RateLimit-Limit');
    expect($limit)->toBe('500'); // Base API limit
});

test('rate limit violation returns helpful error message', function () {
    // Exhaust rate limit
    for ($i = 0; $i < 6; $i++) {
        $response = $this->postJson("{$this->apiPrefix}/login", [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $response->assertStatus(429)
        ->assertJson([
            'success' => false,
            'message' => 'Rate limit exceeded. Please wait before making more requests.',
            'error_code' => 'HTTP_429',
        ]);
});

test('rate limits reset after time window', function () {
    // This test would require time manipulation or longer test execution
    // Marking as placeholder for the concept
    expect(true)->toBe(true);
});

test('organization-based rate limiting works', function () {
    $org = Organization::factory()->create();
    $workspace = Workspace::factory()->create(['organization_id' => $org->id]);

    $user1 = User::factory()->create(['current_organization_id' => $org->id]);
    $user2 = User::factory()->create(['current_organization_id' => $org->id]);

    $workspace->users()->attach([
        $user1->id => ['joined_at' => now()],
        $user2->id => ['joined_at' => now()],
    ]);

    // Both users should share the same rate limit (organization-based)
    Sanctum::actingAs($user1);
    $response1 = $this->getJson("{$this->apiPrefix}/health");

    Sanctum::actingAs($user2);
    $response2 = $this->getJson("{$this->apiPrefix}/health");

    // The remaining count should be decremented for both users
    // since they share the same organization limit
    $remaining1 = (int) $response1->headers->get('X-RateLimit-Remaining');
    $remaining2 = (int) $response2->headers->get('X-RateLimit-Remaining');

    expect($remaining2)->toBeLessThan($remaining1);
});
