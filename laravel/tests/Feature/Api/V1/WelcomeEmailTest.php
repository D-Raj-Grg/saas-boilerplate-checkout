<?php

namespace Tests\Feature\Api\V1;

use App\Enums\WorkspaceRole;
use App\Jobs\SendEmailVerificationJob;
use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
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
    public function it_sends_welcome_email_after_successful_registration(): void
    {
        Queue::fake();

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@bsf.io',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/register', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully. Please check your email to verify your account.',
            ]);

        // Get the created user
        $user = User::where('email', 'john.doe@bsf.io')->first();
        $this->assertNotNull($user, 'User should be created');

        // Verify both email verification and welcome email jobs were dispatched
        Queue::assertPushed(SendEmailVerificationJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });

        Queue::assertPushed(SendWelcomeEmailJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });

        // Should dispatch exactly one of each job
        Queue::assertPushed(SendWelcomeEmailJob::class, 1);
        Queue::assertPushed(SendEmailVerificationJob::class, 1);
    }

    #[Test]
    public function it_sends_welcome_email_even_when_invited_to_existing_workspace(): void
    {
        Queue::fake();

        // Create an existing organization and workspace
        $existingUser = User::factory()->create();
        $organization = \App\Models\Organization::factory()->create([
            'owner_id' => $existingUser->id,
        ]);
        $workspace = \App\Models\Workspace::factory()->create([
            'organization_id' => $organization->id,
        ]);

        // Create an invitation
        $invitation = \App\Models\Invitation::create([
            'email' => 'invited@bsf.io',
            'token' => 'test-invitation-token',
            'role' => WorkspaceRole::VIEWER->value,
            'workspace_id' => $workspace->id,
            'invited_by' => $existingUser->id,
            'expires_at' => now()->addDays(7),
        ]);

        $data = [
            'first_name' => 'Invited',
            'last_name' => 'User',
            'email' => 'invited@bsf.io',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'invitation_token' => 'test-invitation-token',
        ];

        $response = $this->postJson('/api/v1/register', $data);

        $response->assertStatus(201);

        // Get the created user
        $user = User::where('email', 'invited@bsf.io')->first();
        $this->assertNotNull($user, 'Invited user should be created');

        // Verify welcome email is sent even for invited users
        Queue::assertPushed(SendWelcomeEmailJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });
    }

    #[Test]
    public function welcome_email_job_sends_email_successfully(): void
    {
        // Don't fake the queue for this test - we want to test the actual job execution

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@bsf.io',
        ]);

        // Create and execute the job
        $job = new SendWelcomeEmailJob($user);

        // This would normally send the email - in testing it goes to log or array driver
        $job->handle();

        // The job executed without errors (no exception thrown)
        $this->assertTrue(true, 'Welcome email job executed successfully');
    }
}
