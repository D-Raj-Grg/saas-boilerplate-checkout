<?php

namespace Tests\Feature\Api\V1;

use App\Enums\WorkspaceRole;
use App\Jobs\SendEmailVerificationJob;
use App\Models\ConnectionToken;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConnectionEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plans and features for tests
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
        $this->artisan('db:seed', ['--class' => 'PlanFeaturesSeeder']);

        // Create organization with a plan
        $freePlan = Plan::where('slug', 'free')->first();
        $this->organization = Organization::factory()->withPlan($freePlan)->create();

        $this->workspace = Workspace::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $this->user = User::factory()->create([
            'current_organization_id' => $this->organization->id,
            'current_workspace_id' => $this->workspace->id,
            'email_verified_at' => null, // Ensure email is not verified
        ]);

        $this->workspace->addUser($this->user, WorkspaceRole::EDITOR->value);

        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function it_blocks_unverified_user_from_initiating_connection_and_sends_verification_email(): void
    {
        Queue::fake();

        $data = [
            'redirect_url' => 'https://example.com/callback',
        ];

        $response = $this->postJson('/api/v1/connections/initiate', $data);

        // Should fail with email verification error
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Your email must be verified before you can create connections. We\'ve sent a verification email to your address. Please check your email and verify your account.',
                'errors' => [
                    'error_code' => 'EMAIL_VERIFICATION_REQUIRED',
                    'email' => $this->user->email,
                    'verification_sent' => true,
                ],
            ]);

        // Verify that the email verification job was dispatched
        Queue::assertPushed(SendEmailVerificationJob::class, function ($job) {
            return $job->user->id === $this->user->id;
        });

        // Verify no connection token was created
        $this->assertDatabaseMissing('connection_tokens', [
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function it_allows_verified_user_to_initiate_connection(): void
    {
        Queue::fake();

        // Verify the user's email
        $this->user->email_verified_at = now();
        $this->user->save();

        $data = [
            'redirect_url' => 'https://example.com/callback',
        ];

        $response = $this->postJson('/api/v1/connections/initiate', $data);

        // Should succeed
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'oauth_token',
                    'redirect_url',
                    'expires_at',
                ],
            ]);

        // Verify that no email verification job was dispatched
        Queue::assertNotPushed(SendEmailVerificationJob::class);

        // Verify connection token was created
        $this->assertDatabaseHas('connection_tokens', [
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
        ]);
    }

    #[Test]
    public function it_allows_exchange_with_valid_token_from_verified_user(): void
    {
        Queue::fake();

        // Create a connection token (this would normally only exist if user was verified during initiate)
        // But for testing purposes, we're creating it directly to test the exchange flow
        $connectionToken = ConnectionToken::generate($this->user->id, $this->workspace->id, 'https://example.com/callback');

        // Now verify the user's email (simulating they got verified after getting the token)
        $this->user->email_verified_at = now();
        $this->user->save();

        $data = [
            'oauth_token' => $connectionToken->token,
            'site_url' => 'https://example.com',
        ];

        $response = $this->postJson('/api/v1/connections/exchange', $data);

        // Should succeed since the token is valid and user is verified
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'connection_id',
                    'access_token',
                ],
            ]);

        // Verify that no email verification job was dispatched
        Queue::assertNotPushed(SendEmailVerificationJob::class);

        // Verify connection was created
        $this->assertDatabaseHas('connections', [
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'site_url' => 'https://example.com',
            'status' => 'active',
        ]);
    }
}
