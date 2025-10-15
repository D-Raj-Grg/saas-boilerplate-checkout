<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetCurrentPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plan data
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'PlanFeaturesSeeder']);
    }

    #[Test]
    public function returns_highest_priority_plan_regardless_of_order()
    {
        $organization = Organization::factory()->create();

        // Get different priority plans
        $starter = Plan::where('slug', 'starter-yearly')->first(); // Priority 999
        $pro = Plan::where('slug', 'pro-yearly')->first(); // Priority 1000 (higher)

        // Remove auto-attached free plan first
        $free = Plan::where('slug', 'free')->first();
        if ($free) {
            $organization->plans()->detach($free->id);
        }

        // Attach lower priority plan first, then higher priority plan
        $organization->attachPlan($starter);
        $organization->attachPlan($pro);

        $currentPlan = $organization->getCurrentPlan();

        // Should always return the highest priority plan (pro-yearly)
        $this->assertEquals('pro-yearly', $currentPlan->slug);
        $this->assertTrue($pro->priority > $starter->priority);
    }

    #[Test]
    public function returns_highest_priority_plan_among_multiple_plans()
    {
        $organization = Organization::factory()->create();

        // Get different priority plans
        $free = Plan::where('slug', 'free')->first(); // Priority 997
        $starter = Plan::where('slug', 'starter-yearly')->first(); // Priority 999
        $business = Plan::where('slug', 'business-yearly')->first(); // Priority 1001 (highest)

        // Remove the auto-attached free plan first
        $organization->plans()->detach($free->id);

        // Attach multiple plans in random order
        $organization->attachPlan($starter);
        $organization->attachPlan($free);
        $organization->attachPlan($business);

        $currentPlan = $organization->getCurrentPlan();

        // Should return the highest priority plan
        $this->assertEquals('business-yearly', $currentPlan->slug);
        $this->assertTrue($business->priority > $starter->priority);
        $this->assertTrue($business->priority > $free->priority);
    }

    #[Test]
    public function returns_null_when_no_active_plans()
    {
        $organization = Organization::factory()->create();

        // Remove auto-attached free plan
        $free = Plan::where('slug', 'free')->first();
        if ($free) {
            $organization->plans()->detach($free->id);
        }

        $currentPlan = $organization->getCurrentPlan();

        $this->assertNull($currentPlan);
    }

    #[Test]
    public function respects_active_status_filter()
    {
        $organization = Organization::factory()->create();

        // Remove auto-attached free plan first
        $free = Plan::where('slug', 'free')->first();
        if ($free) {
            $organization->plans()->detach($free->id);
        }

        // Get plans with different priorities
        $business = Plan::where('slug', 'business-yearly')->first(); // Priority 1001 (highest)
        $starter = Plan::where('slug', 'starter-yearly')->first(); // Priority 999 (lower)

        // Attach business plan as cancelled (inactive)
        $organization->attachPlan($business, ['status' => 'cancelled']);
        // Attach starter as active (lower priority but active)
        $organization->attachPlan($starter, ['status' => 'active']);

        $currentPlan = $organization->getCurrentPlan();

        // Should return the active plan even if it has lower priority
        $this->assertEquals('starter-yearly', $currentPlan->slug);
    }

    #[Test]
    public function respects_revoked_status_filter()
    {
        $organization = Organization::factory()->create();

        // Remove auto-attached free plan first
        $free = Plan::where('slug', 'free')->first();
        if ($free) {
            $organization->plans()->detach($free->id);
        }

        // Get plans with different priorities
        $business = Plan::where('slug', 'business-yearly')->first(); // Priority 1001 (highest)
        $starter = Plan::where('slug', 'starter-yearly')->first(); // Priority 999 (lower)

        // Attach business plan as revoked
        $organization->attachPlan($business, ['is_revoked' => true]);
        // Attach starter as active and not revoked
        $organization->attachPlan($starter, ['is_revoked' => false]);

        $currentPlan = $organization->getCurrentPlan();

        // Should return the non-revoked plan even if it has lower priority
        $this->assertEquals('starter-yearly', $currentPlan->slug);
    }

    #[Test]
    public function respects_date_range_filters()
    {
        $organization = Organization::factory()->create();

        // Remove auto-attached free plan first
        $free = Plan::where('slug', 'free')->first();
        if ($free) {
            $organization->plans()->detach($free->id);
        }

        // Get plans with different priorities
        $business = Plan::where('slug', 'business-yearly')->first(); // Priority 1001 (highest)
        $starter = Plan::where('slug', 'starter-yearly')->first(); // Priority 999 (lower)

        // Attach business plan with past end date (expired)
        $organization->attachPlan($business, [
            'started_at' => now()->subDays(30),
            'ends_at' => now()->subDays(5),
        ]);

        // Attach starter with current date range
        $organization->attachPlan($starter, [
            'started_at' => now()->subDays(10),
            'ends_at' => null,
        ]);

        $currentPlan = $organization->getCurrentPlan();

        // Should return the currently valid plan
        $this->assertEquals('starter-yearly', $currentPlan->slug);
    }

    #[Test]
    public function cache_is_properly_invalidated_after_attaching_plans()
    {
        $organization = Organization::factory()->create();

        // Remove auto-attached free plan first
        $free = Plan::where('slug', 'free')->first();
        if ($free) {
            $organization->plans()->detach($free->id);
        }

        // Initially no plans
        $this->assertNull($organization->getCurrentPlan());

        // Attach starter plan
        $starter = Plan::where('slug', 'starter-yearly')->first();
        $organization->attachPlan($starter);

        $currentPlan = $organization->getCurrentPlan();
        $this->assertEquals('starter-yearly', $currentPlan->slug);

        // Attach higher priority plan
        $business = Plan::where('slug', 'business-yearly')->first();
        $organization->attachPlan($business);

        // Cache should be invalidated and return the higher priority plan
        $currentPlan = $organization->getCurrentPlan();
        $this->assertEquals('business-yearly', $currentPlan->slug);
    }

    #[Test]
    public function complex_scenario_with_multiple_plans()
    {
        $organization = Organization::factory()->create();

        // Remove auto-attached free plan first
        $free = Plan::where('slug', 'free')->first();
        if ($free) {
            $organization->plans()->detach($free->id);
        }

        // Create test plans with different priorities
        $lowPriorityPlan = Plan::factory()->create(['priority' => 800, 'slug' => 'low']);
        $midPriorityPlan = Plan::factory()->create(['priority' => 900, 'slug' => 'mid']);
        $highPriorityPlan = Plan::factory()->create(['priority' => 1100, 'slug' => 'high']);
        $premiumPlan = Plan::factory()->create(['priority' => 1200, 'slug' => 'premium']);

        $organization->attachPlan($lowPriorityPlan);
        $organization->attachPlan($midPriorityPlan);
        $organization->attachPlan($highPriorityPlan);
        $organization->attachPlan($premiumPlan);

        $currentPlan = $organization->getCurrentPlan();

        // Should always return the highest priority plan (premium)
        $this->assertEquals('premium', $currentPlan->slug);
        $this->assertEquals(1200, $currentPlan->priority);
    }

    #[Test]
    public function performance_test_with_many_plans()
    {
        $organization = Organization::factory()->create();

        // Remove auto-attached free plan first
        $free = Plan::where('slug', 'free')->first();
        if ($free) {
            $organization->plans()->detach($free->id);
        }

        // Create many plans
        for ($i = 1; $i <= 50; $i++) {
            $plan = Plan::factory()->create([
                'priority' => 1000 + $i,
                'slug' => "plan-{$i}",
            ]);
            $organization->attachPlan($plan);
        }

        $start = microtime(true);
        $currentPlan = $organization->getCurrentPlan();
        $end = microtime(true);

        // Should return the highest priority plan quickly
        $this->assertEquals('plan-50', $currentPlan->slug);
        $this->assertEquals(1050, $currentPlan->priority);
        $this->assertLessThan(0.1, $end - $start, 'getCurrentPlan should execute quickly even with many plans');
    }
}
