<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);

    // Create a plan with yearly tracking
    $this->plan = Plan::factory()->create([
        'slug' => 'test-yearly',
        'name' => 'Test Yearly Plan',
    ]);

    // Ensure unique_visitors feature exists
    PlanFeature::updateOrCreate(
        ['feature' => 'unique_visitors'],
        [
            'name' => 'Unique Visitors',
            'description' => 'Number of unique visitors per year',
            'type' => 'limit',
            'period' => 'yearly',
            'category' => 'tracking',
            'tracking_scope' => 'organization',
            'is_active' => true,
        ]
    );

    // Attach plan with limit
    $this->organization->attachPlan($this->plan, [
        'status' => 'active',
        'started_at' => now()->subDays(5), // Plan started 5 days ago
    ]);

    $this->plan->limits()->create([
        'feature' => 'unique_visitors',
        'value' => '10000',
        'type' => 'limit',
        'tracking_scope' => 'organization',
    ]);
});

test('yearly tracking uses consistent anchor date for multiple consumptions', function () {
    // Consume feature multiple times on different "days"
    $this->organization->consumeFeature('unique_visitors', 100);

    // Simulate next day
    $this->travel(1)->days();
    $this->organization->consumeFeature('unique_visitors', 50);

    // Simulate another day
    $this->travel(1)->days();
    $this->organization->consumeFeature('unique_visitors', 75);

    // Should only have ONE tracking record, not multiple
    $trackingRecords = $this->organization->usageTracking()
        ->where('feature', 'unique_visitors')
        ->where('period_type', 'yearly')
        ->get();

    expect($trackingRecords)->toHaveCount(1);
    expect($trackingRecords->first()->current_usage)->toBe(225); // 100 + 50 + 75
});

test('yearly tracking period starts from plan start date', function () {
    $this->organization->consumeFeature('unique_visitors', 100);

    $tracking = $this->organization->usageTracking()
        ->where('feature', 'unique_visitors')
        ->where('period_type', 'yearly')
        ->first();

    expect($tracking)->not->toBeNull();

    // Period should start at 00:00:00 on the day plan started (normalized)
    $planStartDate = $this->organization->organizationPlans()
        ->where('status', 'active')
        ->first()
        ->started_at
        ->startOfDay(); // Normalized to 00:00:00

    expect($tracking->period_starts_at->format('Y-m-d H:i'))->toBe($planStartDate->format('Y-m-d H:i'));

    // Period should end 1 year after plan start (also at 00:00:00)
    expect($tracking->period_ends_at->format('Y-m-d H:i'))->toBe($planStartDate->copy()->addYear()->format('Y-m-d H:i'));
});

test('expired yearly tracking creates new record for next period', function () {
    // Create an expired tracking record
    $expiredTracking = $this->organization->usageTracking()->create([
        'feature' => 'unique_visitors',
        'workspace_id' => null,
        'period_type' => 'yearly',
        'period_starts_at' => now()->subYear()->subDay(),
        'period_ends_at' => now()->subDay(), // Expired yesterday
        'current_usage' => 5000,
    ]);

    // Consume feature now (after expiry)
    $this->organization->consumeFeature('unique_visitors', 100);

    // Should create a NEW tracking record, not reuse expired one
    $activeTracking = $this->organization->usageTracking()
        ->where('feature', 'unique_visitors')
        ->where('period_type', 'yearly')
        ->where('period_ends_at', '>', now())
        ->get();

    expect($activeTracking)->toHaveCount(1);
    expect($activeTracking->first()->id)->not->toBe($expiredTracking->id);
    expect($activeTracking->first()->current_usage)->toBe(100); // Fresh start
});

test('multiple organizations have independent yearly tracking', function () {
    $org2 = Organization::factory()->create(['owner_id' => $this->user->id]);
    $org2->attachPlan($this->plan, [
        'status' => 'active',
        'started_at' => now()->subDays(10), // Different start date
    ]);

    // Consume on both orgs
    $this->organization->consumeFeature('unique_visitors', 100);
    $org2->consumeFeature('unique_visitors', 200);

    // Check org 1
    $tracking1 = $this->organization->usageTracking()
        ->where('feature', 'unique_visitors')
        ->where('period_type', 'yearly')
        ->first();

    // Check org 2
    $tracking2 = $org2->usageTracking()
        ->where('feature', 'unique_visitors')
        ->where('period_type', 'yearly')
        ->first();

    expect($tracking1->current_usage)->toBe(100);
    expect($tracking2->current_usage)->toBe(200);

    // Different anchor dates
    expect($tracking1->period_starts_at->format('Y-m-d'))->not->toBe($tracking2->period_starts_at->format('Y-m-d'));
});
