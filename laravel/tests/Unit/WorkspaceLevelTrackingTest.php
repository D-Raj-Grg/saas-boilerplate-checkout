<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create(['owner_id' => $this->user->id]);

    // Create workspaces
    $this->workspace1 = Workspace::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Workspace 1',
    ]);
    $this->workspace2 = Workspace::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Workspace 2',
    ]);

    // Create plan
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
        'started_at' => now()->subDays(5),
    ]);

    $this->plan->limits()->create([
        'feature' => 'unique_visitors',
        'value' => '10000',
        'type' => 'limit',
        'tracking_scope' => 'organization',
    ]);
});

test('unique visitors tracked per workspace but limit checked at organization level', function () {
    // Consume 100 visitors in workspace 1
    $this->organization->consumeFeature('unique_visitors', 100, $this->workspace1);

    // Consume 200 visitors in workspace 2
    $this->organization->consumeFeature('unique_visitors', 200, $this->workspace2);

    // Check workspace-specific usage
    $workspace1Usage = $this->organization->getCurrentUsage('unique_visitors', $this->workspace1);
    $workspace2Usage = $this->organization->getCurrentUsage('unique_visitors', $this->workspace2);

    expect($workspace1Usage)->toBe(100);
    expect($workspace2Usage)->toBe(200);

    // Check organization-level total (aggregates all workspaces)
    $totalUsage = $this->organization->getCurrentUsage('unique_visitors');
    expect($totalUsage)->toBe(300); // 100 + 200

    // Verify separate tracking records exist
    $trackingRecords = $this->organization->usageTracking()
        ->where('feature', 'unique_visitors')
        ->get();

    expect($trackingRecords)->toHaveCount(2); // One per workspace
});

test('organization limit applies across all workspaces', function () {
    // Set limit to 250
    $this->plan->limits()->where('feature', 'unique_visitors')->update(['value' => '250']);

    // Consume 200 in workspace 1
    $this->organization->consumeFeature('unique_visitors', 200, $this->workspace1);

    // Try to consume 100 in workspace 2 (would exceed 250 total)
    $canConsume = $this->organization->canUse('unique_visitors', 100);

    expect($canConsume)->toBeFalse(); // Organization total would be 300, exceeds 250

    // But consuming 50 should work
    $canConsume50 = $this->organization->canUse('unique_visitors', 50);
    expect($canConsume50)->toBeTrue(); // 200 + 50 = 250 (exactly at limit)
});

test('workspace-level analytics available via usage tracking', function () {
    // Simulate different workspace usage patterns
    $this->organization->consumeFeature('unique_visitors', 500, $this->workspace1);
    $this->organization->consumeFeature('unique_visitors', 1500, $this->workspace2);

    // Get workspace-specific records
    $ws1Record = $this->organization->usageTracking()
        ->where('feature', 'unique_visitors')
        ->where('workspace_id', $this->workspace1->id)
        ->first();

    $ws2Record = $this->organization->usageTracking()
        ->where('feature', 'unique_visitors')
        ->where('workspace_id', $this->workspace2->id)
        ->first();

    expect($ws1Record->current_usage)->toBe(500);
    expect($ws2Record->current_usage)->toBe(1500);

    // Verify both share same yearly period (organization anchor)
    expect($ws1Record->period_starts_at->format('Y-m-d'))->toBe($ws2Record->period_starts_at->format('Y-m-d'));
    expect($ws1Record->period_ends_at->format('Y-m-d'))->toBe($ws2Record->period_ends_at->format('Y-m-d'));
});
