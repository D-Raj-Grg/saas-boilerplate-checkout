<?php

namespace Tests\Unit\Traits;

use App\Models\Organization;
use App\Models\OrganizationFeatureOverride;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanLimit;
use App\Models\UsageTracking;
use App\Models\Workspace;
use App\Models\WorkspaceFeatureLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HasPricingPlanTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Plan $plan;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plan data
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'PlanFeaturesSeeder']);

        // Use existing free plan from seeder (already has all limits configured)
        $this->plan = Plan::where('slug', 'free')->first();

        // Create test organization
        $this->organization = Organization::factory()->create([
            'plan_id' => $this->plan->id,
        ]);

        // Create test workspace
        $this->workspace = Workspace::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
    }

    #[Test]
    public function it_can_check_if_organization_has_feature()
    {
        $this->assertFalse($this->organization->hasFeature('priority_support'));
        $this->assertTrue($this->organization->hasFeature('workspaces'));
        $this->assertFalse($this->organization->hasFeature('non_existent_feature'));
    }

    #[Test]
    public function it_returns_correct_limit_for_feature()
    {
        $this->assertEquals(1, $this->organization->getLimit('workspaces'));
        $this->assertEquals(1000, $this->organization->getLimit('unique_visitors'));
        $this->assertNull($this->organization->getLimit('non_existent_feature'));
    }

    #[Test]
    public function it_can_check_if_organization_can_use_feature()
    {
        // Should be able to use within limits (free plan has 5 team members, 1 already used)
        $this->assertTrue($this->organization->canUse('team_members', 3));
        $this->assertTrue($this->organization->canUse('team_members', 4));

        // Should not be able to use beyond limits
        $this->assertFalse($this->organization->canUse('team_members', 5));
    }

    #[Test]
    public function it_handles_unlimited_features_correctly()
    {
        // Create unlimited feature
        PlanLimit::create([
            'plan_id' => $this->plan->id,
            'feature' => 'unlimited_feature',
            'value' => '-1',
            'type' => 'limit',
        ]);

        PlanFeature::create([
            'feature' => 'unlimited_feature',
            'name' => 'Unlimited Feature',
            'type' => 'limit',
            'period' => 'lifetime',
            'category' => 'test',
        ]);

        $this->assertTrue($this->organization->canUse('unlimited_feature', 999999));
        $this->assertEquals(-1, $this->organization->getLimit('unlimited_feature'));
    }

    #[Test]
    public function it_can_consume_and_unconsume_features()
    {
        // Workspace creation already consumed 1 team member
        $this->assertEquals(1, $this->organization->getCurrentUsage('team_members'));

        // Consume 2 more members (free plan has 5, so 4 remaining)
        $result = $this->organization->consumeFeature('team_members', 2);
        $this->assertTrue($result);
        $this->assertEquals(3, $this->organization->getCurrentUsage('team_members'));

        // Unconsume 1 member
        $this->organization->unconsumeFeature('team_members', 1);
        $this->assertEquals(2, $this->organization->getCurrentUsage('team_members'));

        // Remaining should be correct (5 total - 2 used = 3 remaining)
        $this->assertEquals(3, $this->organization->getRemainingUsage('team_members'));
    }

    #[Test]
    public function it_prevents_consumption_beyond_limits()
    {
        // Already have 1 member used, try to consume 5 more (would exceed limit of 5)
        $result = $this->organization->consumeFeature('team_members', 5);
        $this->assertFalse($result);
        $this->assertEquals(1, $this->organization->getCurrentUsage('team_members'));

        // Consume exactly up to limit (4 more to reach 5 total)
        $result = $this->organization->consumeFeature('team_members', 4);
        $this->assertTrue($result);
        $this->assertEquals(5, $this->organization->getCurrentUsage('team_members'));

        // Try to consume one more (should fail - at limit)
        $result = $this->organization->consumeFeature('team_members', 1);
        $this->assertFalse($result);
        $this->assertEquals(5, $this->organization->getCurrentUsage('team_members'));
    }

    #[Test]
    public function it_respects_organization_overrides()
    {
        // Create override that increases limit
        OrganizationFeatureOverride::create([
            'organization_id' => $this->organization->id,
            'feature' => 'workspaces',
            'value' => '20',
            'reason' => 'Test override',
        ]);

        $this->assertEquals(20, $this->organization->getLimit('workspaces'));
        $this->assertTrue($this->organization->canUse('workspaces', 15));
        $this->assertFalse($this->organization->canUse('workspaces', 25));
    }

    #[Test]
    public function it_ignores_expired_overrides()
    {
        // Create expired override
        OrganizationFeatureOverride::create([
            'organization_id' => $this->organization->id,
            'feature' => 'workspaces',
            'value' => '200',
            'reason' => 'Expired override',
            'expires_at' => now()->subDay(),
        ]);

        // Should use plan limit, not override (free plan has 1 workspace)
        $this->assertEquals(1, $this->organization->getLimit('workspaces'));
    }

    #[Test]
    public function it_handles_workspace_specific_limits()
    {
        // Create workspace limit
        WorkspaceFeatureLimit::create([
            'workspace_id' => $this->workspace->id,
            'organization_id' => $this->organization->id,
            'feature' => 'connections_per_workspace',
            'allocated' => 5,
        ]);

        // Should respect workspace allocation
        $this->assertTrue($this->organization->canUse('connections_per_workspace', 3, $this->workspace));
        $this->assertTrue($this->organization->canUse('connections_per_workspace', 5, $this->workspace));
        $this->assertFalse($this->organization->canUse('connections_per_workspace', 6, $this->workspace));
    }

    #[Test]
    public function it_tracks_workspace_specific_usage()
    {
        // Consume at workspace level (free plan has 1 connection per workspace)
        $result = $this->organization->consumeFeature('connections_per_workspace', 1, $this->workspace);
        $this->assertTrue($result);

        // Check workspace usage
        $this->assertEquals(1, $this->organization->getCurrentUsage('connections_per_workspace', $this->workspace));

        // Check organization-wide usage
        $this->assertEquals(1, $this->organization->getCurrentUsage('connections_per_workspace'));
    }

    #[Test]
    public function it_calculates_usage_percentage_correctly()
    {
        // Already at 1/5 = 20%, consume 4 more to reach 100%
        $this->organization->consumeFeature('team_members', 4);

        $percentage = $this->organization->getUsagePercentage('team_members');
        $this->assertEquals(100.0, $percentage);

        // Unconsume 2 to test partial usage (3/5 = 60%)
        $this->organization->unconsumeFeature('team_members', 2);
        $percentage = $this->organization->getUsagePercentage('team_members');
        $this->assertEquals(60.0, round($percentage, 2));
    }

    #[Test]
    public function it_handles_yearly_period_features()
    {
        // Create usage in current year
        UsageTracking::create([
            'organization_id' => $this->organization->id,
            'feature' => 'unique_visitors',
            'current_usage' => 100,
            'period_type' => 'yearly',
            'period_starts_at' => now()->startOfYear(),
            'period_ends_at' => now()->endOfYear(),
        ]);

        $this->assertEquals(100, $this->organization->getCurrentUsage('unique_visitors'));
        $this->assertTrue($this->organization->canUse('unique_visitors', 800));
        $this->assertFalse($this->organization->canUse('unique_visitors', 1000));
    }

    #[Test]
    public function it_ignores_expired_yearly_usage()
    {
        // Create usage in previous year
        UsageTracking::create([
            'organization_id' => $this->organization->id,
            'feature' => 'unique_visitors',
            'current_usage' => 400,
            'period_type' => 'yearly',
            'period_starts_at' => now()->subYear()->startOfYear(),
            'period_ends_at' => now()->subYear()->endOfYear(),
        ]);

        // Should not count expired usage
        $this->assertEquals(0, $this->organization->getCurrentUsage('unique_visitors'));
        $this->assertTrue($this->organization->canUse('unique_visitors', 1000));
    }

    #[Test]
    public function it_provides_usage_summary()
    {
        // Already have 1 used, consume 4 more to reach limit
        $this->organization->consumeFeature('team_members', 4);

        $summary = $this->organization->getUsageSummary();

        $this->assertArrayHasKey('team_members', $summary);
        $this->assertEquals(5, $summary['team_members']['current']);
        $this->assertEquals(5, $summary['team_members']['limit']);
        $this->assertEquals(0, $summary['team_members']['remaining']);
        $this->assertEquals(100.0, $summary['team_members']['percentage']);
        $this->assertTrue($summary['team_members']['has_feature']);
    }

    #[Test]
    public function it_prevents_unconsuming_below_zero()
    {
        // Start with 1 already used, consume 1 more
        $this->organization->consumeFeature('team_members', 1);
        $this->assertEquals(2, $this->organization->getCurrentUsage('team_members'));

        // Try to unconsume more than total consumed (5 > 2)
        $this->organization->unconsumeFeature('team_members', 5);

        // Should not go below zero
        $usage = $this->organization->getCurrentUsage('team_members');
        $this->assertGreaterThanOrEqual(0, $usage);
    }

    #[Test]
    public function it_handles_boolean_features_correctly()
    {
        // Boolean feature should work regardless of canUse parameters (free plan doesn't have priority_support)
        $this->assertFalse($this->organization->hasFeature('priority_support'));
        $this->assertFalse($this->organization->canUse('priority_support', 1));

        // Use pro plan which has priority_support
        $planWithFeature = Plan::where('slug', 'pro-yearly')->first();
        $orgWithFeature = Organization::factory()->create();
        $orgWithFeature->attachPlan($planWithFeature);

        $this->assertTrue($orgWithFeature->hasFeature('priority_support'));
    }
}
