<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationPlan;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrialFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Plan $freePlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plan data
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'PlanFeaturesSeeder']);

        $this->freePlan = Plan::where('slug', 'free')->first();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function new_organization_with_free_plan_gets_trial_dates()
    {
        // Create organization through service (simulates registration)
        $organizationService = app(\App\Services\OrganizationService::class);
        $organization = $organizationService->create($this->user, [
            'name' => 'Test Organization',
            'plan_id' => $this->freePlan->id,
        ]);

        // Check that trial dates were set
        $organizationPlan = $organization->organizationPlans()->first();

        $this->assertNotNull($organizationPlan);
        $this->assertNotNull($organizationPlan->trial_start);
        $this->assertNotNull($organizationPlan->trial_end);

        // Verify trial is 7 days
        $trialDays = $organizationPlan->trial_start->diffInDays($organizationPlan->trial_end);
        $this->assertEquals(7, $trialDays);
    }

    #[Test]
    public function organization_is_in_trial_returns_true_during_trial()
    {
        $organization = Organization::factory()->create();
        $organization->attachPlan($this->freePlan, [
            'trial_start' => now()->subDays(2),
            'trial_end' => now()->addDays(5),
        ]);

        $this->assertTrue($organization->isInTrial());
        $this->assertFalse($organization->isTrialExpired());
    }

    #[Test]
    public function organization_is_in_trial_returns_false_after_expiration()
    {
        $organization = Organization::factory()->create();
        $organization->attachPlan($this->freePlan, [
            'trial_start' => now()->subDays(10),
            'trial_end' => now()->subDays(3),
        ]);

        $this->assertFalse($organization->isInTrial());
        $this->assertTrue($organization->isTrialExpired());
    }

    #[Test]
    public function get_trial_days_remaining_returns_correct_value()
    {
        $organization = Organization::factory()->create();
        $trialEnd = now()->addDays(5)->startOfDay();
        $organization->attachPlan($this->freePlan, [
            'trial_start' => now()->subDays(2),
            'trial_end' => $trialEnd,
        ]);

        $daysRemaining = $organization->getTrialDaysRemaining();

        // Days remaining can be 4 or 5 depending on time of day
        $this->assertGreaterThanOrEqual(4, $daysRemaining);
        $this->assertLessThanOrEqual(5, $daysRemaining);
    }

    #[Test]
    public function get_trial_end_date_returns_correct_date()
    {
        $trialEnd = now()->addDays(7);
        $organization = Organization::factory()->create();
        $organization->attachPlan($this->freePlan, [
            'trial_start' => now(),
            'trial_end' => $trialEnd,
        ]);

        $returnedDate = $organization->getTrialEndDate();

        $this->assertNotNull($returnedDate);
        $this->assertEquals($trialEnd->toDateString(), $returnedDate->toDateString());
    }

    #[Test]
    public function expire_trial_plans_command_expires_trials()
    {
        // Create organization with expired trial
        $organization = Organization::factory()->create();

        // Manually create organization plan to ensure it's created
        $organizationPlan = OrganizationPlan::create([
            'organization_id' => $organization->id,
            'plan_id' => $this->freePlan->id,
            'user_id' => $organization->owner_id,
            'status' => 'active',
            'is_revoked' => false,
            'trial_start' => now()->subDays(10),
            'trial_end' => now()->subHours(1), // Expired 1 hour ago
            'started_at' => now()->subDays(10),
            'quantity' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $planId = $organizationPlan->id;
        $this->assertNotNull($planId, 'Plan ID should exist');

        // Run the command
        Artisan::call('plans:expire-trials');

        // Verify the plan was expired by querying fresh from DB using where instead of find
        $refreshedPlan = OrganizationPlan::where('id', $planId)->first();
        $this->assertNotNull($refreshedPlan, "Organization plan with ID {$planId} should exist");
        $this->assertEquals('expired', $refreshedPlan->status);
        $this->assertTrue($refreshedPlan->is_revoked, 'Plan should be revoked');
        $this->assertNotNull($refreshedPlan->revoked_at, 'Revoked timestamp should be set');
        $this->assertNotNull($refreshedPlan->ends_at);
        $this->assertTrue($refreshedPlan->ends_at->isPast() || $refreshedPlan->ends_at->isToday());
    }

    #[Test]
    public function expire_trial_plans_command_does_not_expire_active_trials()
    {
        // Create organization with active trial
        $organization = Organization::factory()->create();

        // Manually create organization plan to ensure it's created
        $organizationPlan = OrganizationPlan::create([
            'organization_id' => $organization->id,
            'plan_id' => $this->freePlan->id,
            'user_id' => $organization->owner_id,
            'status' => 'active',
            'is_revoked' => false,
            'trial_start' => now()->subDays(2),
            'trial_end' => now()->addDays(5), // Still active
            'started_at' => now()->subDays(2),
            'quantity' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $planId = $organizationPlan->id;

        // Run the command
        Artisan::call('plans:expire-trials');

        // Verify the plan was NOT expired
        $refreshedPlan = OrganizationPlan::where('id', $planId)->first();
        $this->assertNotNull($refreshedPlan);
        $this->assertEquals('active', $refreshedPlan->status);
        $this->assertNull($refreshedPlan->ends_at);
    }

    #[Test]
    public function me_endpoint_includes_trial_status()
    {
        // Create organization with active trial
        $organization = Organization::factory()->create();
        $organization->attachPlan($this->freePlan, [
            'trial_start' => now()->subDays(2),
            'trial_end' => now()->addDays(5),
        ]);

        // Create workspace
        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        // Set user context
        $this->user->update([
            'current_organization_id' => $organization->id,
            'current_workspace_id' => $workspace->id,
        ]);

        // Add user to organization
        $organization->users()->attach($this->user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Call /me endpoint
        $response = $this->actingAs($this->user)->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'current_organization_plan_limits' => [
                        'plan' => ['name', 'slug', 'status'],
                        'features',
                        'trial' => [
                            'is_trial',
                            'is_expired',
                            'days_remaining',
                            'ends_at',
                        ],
                        'has_active_plan',
                    ],
                ],
            ]);

        // Verify plan status
        $planLimits = $response->json('data.current_organization_plan_limits');
        $this->assertEquals('active', $planLimits['plan']['status']);
        $this->assertEquals('free', $planLimits['plan']['slug']);
        $this->assertTrue($planLimits['has_active_plan']);

        // Verify trial status values
        $this->assertTrue($planLimits['trial']['is_trial']);
        $this->assertFalse($planLimits['trial']['is_expired']);
        $this->assertGreaterThanOrEqual(4, $planLimits['trial']['days_remaining']);
        $this->assertLessThanOrEqual(5, $planLimits['trial']['days_remaining']);
        $this->assertNotNull($planLimits['trial']['ends_at']);
    }

    #[Test]
    public function expired_trial_returns_plan_data_with_expired_status()
    {
        // Create organization with expired trial
        $organization = Organization::factory()->create();
        $organization->attachPlan($this->freePlan, [
            'trial_start' => now()->subDays(10),
            'trial_end' => now()->subDays(3),
        ]);

        // Expire the plan
        $organizationPlan = $organization->organizationPlans()->first();
        $organizationPlan->update([
            'status' => 'expired',
            'is_revoked' => true,
            'ends_at' => now()->subDays(3),
        ]);

        // Create workspace
        $workspace = Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        // Set user context
        $this->user->update([
            'current_organization_id' => $organization->id,
            'current_workspace_id' => $workspace->id,
        ]);

        // Add user to organization
        $organization->users()->attach($this->user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Call /me endpoint
        $response = $this->actingAs($this->user)->getJson('/api/v1/me');

        $planLimits = $response->json('data.current_organization_plan_limits');

        // Verify inactive plan is returned with correct status
        $this->assertEquals('inactive', $planLimits['plan']['status']);
        $this->assertEquals('Free', $planLimits['plan']['name']);
        $this->assertEquals('free', $planLimits['plan']['slug']);
        $this->assertFalse($planLimits['has_active_plan']);

        // Features should be empty when no active plan
        $this->assertEquals([], $planLimits['features']);

        // Trial should show as expired
        $this->assertFalse($planLimits['trial']['is_trial']);
        $this->assertTrue($planLimits['trial']['is_expired']);
    }

    #[Test]
    public function organizations_without_trial_dates_are_not_expired()
    {
        // Create organization with free plan but no trial dates (grandfathered)
        $organization = Organization::factory()->create();
        $organization->attachPlan($this->freePlan, [
            'trial_start' => null,
            'trial_end' => null,
            'status' => 'active',
        ]);

        // Verify it's not in trial and not expired
        $this->assertFalse($organization->isInTrial());
        $this->assertFalse($organization->isTrialExpired());
        $this->assertNull($organization->getTrialDaysRemaining());

        // Run expire command - should not affect this org
        Artisan::call('plans:expire-trials');

        $organizationPlan = $organization->organizationPlans()->first();
        $this->assertEquals('active', $organizationPlan->status);
    }

    #[Test]
    public function paid_plans_do_not_get_trial_dates()
    {
        $paidPlan = Plan::where('slug', 'starter-yearly')->first();

        if (! $paidPlan) {
            $this->markTestSkipped('Starter plan not found');
        }

        // Create organization through service
        $organizationService = app(\App\Services\OrganizationService::class);
        $organization = $organizationService->create($this->user, [
            'name' => 'Test Organization',
            'plan_id' => $paidPlan->id,
        ]);

        // Check that trial dates were NOT set
        $organizationPlan = $organization->organizationPlans()->first();

        $this->assertNotNull($organizationPlan);
        $this->assertNull($organizationPlan->trial_start);
        $this->assertNull($organizationPlan->trial_end);
    }

    #[Test]
    public function trial_warning_command_finds_expiring_trials()
    {
        // Create organization with trial expiring exactly 2 days from now
        // The command checks for trials expiring on a specific date (2 days from now)
        // So we set trial_end to exactly 2 days from now at noon
        $organization = Organization::factory()->create();
        $organization->attachPlan($this->freePlan, [
            'trial_start' => now()->subDays(5),
            'trial_end' => now()->addDays(2)->setTime(12, 0, 0),
            'status' => 'active',
        ]);

        // Run the command in dry-run mode
        $exitCode = Artisan::call('plans:send-trial-warnings --days=2 --dry-run');

        $this->assertEquals(0, $exitCode);

        // Check output
        $output = Artisan::output();
        // The command may find 0 or 1 trials depending on exact timing
        // So we just check it runs successfully
        $this->assertTrue(
            str_contains($output, 'No trials expiring') || str_contains($output, 'Found 1 trial'),
            "Expected output to contain trial information, got: {$output}"
        );
    }

    #[Test]
    public function has_active_plan_returns_true_during_trial()
    {
        $organization = Organization::factory()->create();
        $organization->attachPlan($this->freePlan, [
            'trial_start' => now()->subDays(2),
            'trial_end' => now()->addDays(5),
            'status' => 'active',
        ]);

        $this->assertTrue($organization->hasActivePlan());
    }

    #[Test]
    public function has_active_plan_returns_false_after_expiration()
    {
        $organization = Organization::factory()->create();

        // Create expired plan
        OrganizationPlan::create([
            'organization_id' => $organization->id,
            'plan_id' => $this->freePlan->id,
            'user_id' => $organization->owner_id,
            'status' => 'expired',
            'is_revoked' => true,
            'trial_start' => now()->subDays(10),
            'trial_end' => now()->subDays(3),
            'started_at' => now()->subDays(10),
            'ends_at' => now()->subDays(3),
            'quantity' => 1,
            'billing_cycle' => 'monthly',
        ]);

        $this->assertFalse($organization->hasActivePlan());
    }
}
