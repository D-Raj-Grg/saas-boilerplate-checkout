<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\SendWelcomeEmailJob;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plans for tests
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);

        // Create config for default plan
        config(['constants.default_org_plan_slug' => 'early-bird-lifetime']);
    }

    #[Test]
    public function it_assigns_early_bird_plan_during_registration_when_configured(): void
    {
        Queue::fake();

        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@bsf.io',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/register', $data);

        $response->assertStatus(201);

        // Check that user was created
        $this->assertDatabaseHas('users', [
            'email' => 'test@bsf.io',
        ]);

        // Get the created organization
        $user = \App\Models\User::where('email', 'test@bsf.io')->first();
        $organization = $user->ownedOrganizations()->first();

        $this->assertNotNull($organization, 'Organization should be created');

        // Check that early-bird plan is attached, NOT free plan
        $activePlans = $organization->activePlans()->get();

        $this->assertEquals(1, $activePlans->count(), 'Should have exactly one active plan');
        $this->assertEquals('early-bird-lifetime', $activePlans->first()->slug, 'Should have early-bird plan, not free plan');

        // Verify free plan is NOT attached
        $this->assertFalse(
            $activePlans->contains('slug', 'free'),
            'Free plan should NOT be attached when early-bird is configured'
        );

        // Verify welcome email job was dispatched
        Queue::assertPushed(SendWelcomeEmailJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });
    }

    #[Test]
    public function it_falls_back_to_free_plan_when_config_is_missing(): void
    {
        // Remove the config to test fallback
        config(['constants.default_org_plan_slug' => null]);

        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'fallback@bsf.io',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/register', $data);

        $response->assertStatus(201);

        // Get the created organization
        $user = \App\Models\User::where('email', 'fallback@bsf.io')->first();
        $organization = $user->ownedOrganizations()->first();

        $this->assertNotNull($organization, 'Organization should be created');

        // Check that free plan is attached as fallback
        $activePlans = $organization->activePlans()->get();

        $this->assertEquals(1, $activePlans->count(), 'Should have exactly one active plan');
        $this->assertEquals('free', $activePlans->first()->slug, 'Should have free plan as fallback');
    }

    #[Test]
    public function it_prevents_free_plan_attachment_when_non_free_plan_exists(): void
    {
        // Create a user with organization
        $user = \App\Models\User::factory()->create();
        $organization = \App\Models\Organization::factory()->create([
            'owner_id' => $user->id,
        ]);

        // Attach a non-free plan (early-bird)
        $earlyBirdPlan = Plan::where('slug', 'early-bird-lifetime')->first();
        $attached = $organization->attachPlan($earlyBirdPlan);
        $this->assertNotNull($attached, 'Early-bird plan should be attached');

        // Try to attach free plan
        $freePlan = Plan::where('slug', 'free')->first();
        $attached = $organization->attachPlan($freePlan);

        $this->assertNull($attached, 'Free plan should NOT be attached when non-free plan exists');

        // Verify only early-bird plan is active
        $activePlans = $organization->activePlans()->get();
        $this->assertEquals(1, $activePlans->count(), 'Should have exactly one active plan');
        $this->assertEquals('early-bird-lifetime', $activePlans->first()->slug, 'Should only have early-bird plan');
    }

    #[Test]
    public function it_allows_multiple_non_free_plans(): void
    {
        // Create a user with organization
        $user = \App\Models\User::factory()->create();
        $organization = \App\Models\Organization::factory()->create([
            'owner_id' => $user->id,
        ]);

        // Attach early-bird plan
        $earlyBirdPlan = Plan::where('slug', 'early-bird-lifetime')->first();
        $attached = $organization->attachPlan($earlyBirdPlan);
        $this->assertNotNull($attached, 'Early-bird plan should be attached');

        // Attach pro plan (topup scenario)
        $proPlan = Plan::where('slug', 'pro-yearly')->first();
        $attached = $organization->attachPlan($proPlan);
        $this->assertNotNull($attached, 'Pro plan should be attached as well');

        // Verify both plans are active
        $activePlans = $organization->activePlans()->get();
        $this->assertEquals(2, $activePlans->count(), 'Should have two active plans');

        $planSlugs = $activePlans->pluck('slug')->toArray();
        $this->assertContains('early-bird-lifetime', $planSlugs);
        $this->assertContains('pro-yearly', $planSlugs);
    }
}
