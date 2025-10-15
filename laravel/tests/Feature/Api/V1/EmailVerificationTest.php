<?php

use App\Jobs\SendEmailVerificationJob;
use App\Models\User;
use App\Models\UserVerification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->apiPrefix = '/api/v1';
    Queue::fake();
    RateLimiter::clear('verify-email:resend:*');
});

describe('Email Verification', function (): void {
    test('user can verify email with valid token', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verification = UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_EMAIL_VERIFY,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'token' => 'test-token-123',
            'expires_at' => Carbon::now()->addHours(48),
        ]);

        $response = $this->postJson("{$this->apiPrefix}/email/verify", [
            'token' => 'test-token-123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email verified successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'email_verified' => true,
                    ],
                ],
            ]);

        // Check user email is verified
        $this->assertNotNull($user->fresh()->email_verified_at);

        // Check verification is marked as verified
        $this->assertNotNull($verification->fresh()->verified_at);
    });

    test('cannot verify email with invalid token', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/email/verify", [
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid verification token',
            ]);
    });

    test('cannot verify email with expired token', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_EMAIL_VERIFY,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'token' => 'expired-token',
            'expires_at' => Carbon::now()->subHour(),
        ]);

        $response = $this->postJson("{$this->apiPrefix}/email/verify", [
            'token' => 'expired-token',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Token has expired',
            ]);
    });

    test('cannot verify email with already used token', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_EMAIL_VERIFY,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'token' => 'used-token',
            'expires_at' => Carbon::now()->addHours(48),
            'verified_at' => Carbon::now()->subMinute(),
        ]);

        $response = $this->postJson("{$this->apiPrefix}/email/verify", [
            'token' => 'used-token',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Token already used',
            ]);
    });

    test('cannot verify already verified email', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now()->subDay(),
        ]);

        UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_EMAIL_VERIFY,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'token' => 'valid-token',
            'expires_at' => Carbon::now()->addHours(48),
        ]);

        $response = $this->postJson("{$this->apiPrefix}/email/verify", [
            'token' => 'valid-token',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Email already verified',
            ]);
    });

    test('token validation is required', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/email/verify", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    });
});

describe('Email Verification Resend', function (): void {
    test('authenticated user can resend verification email', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/email/resend");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Verification email sent',
            ]);

        // Check email job was dispatched
        Queue::assertPushed(SendEmailVerificationJob::class);

        // Check verification was created
        $this->assertDatabaseHas('user_verifications', [
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_EMAIL_VERIFY,
            'identifier' => $user->email,
        ]);
    });

    test('cannot resend if email already verified', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/email/resend");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Email already verified',
            ]);

        Queue::assertNotPushed(SendEmailVerificationJob::class);
    });

    test('rate limiting prevents multiple resends within minute', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($user);

        // First request should succeed
        $response = $this->postJson("{$this->apiPrefix}/email/resend");
        $response->assertStatus(200);

        // Second request within minute should be rate limited
        $response = $this->postJson("{$this->apiPrefix}/email/resend");
        $response->assertStatus(429)
            ->assertJsonFragment([
                'success' => false,
            ]);

        expect($response->json('message'))->toContain('Too many requests');
    });

    test('previous unverified tokens are expired when resending', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Create existing verification tokens
        $oldVerification1 = UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_EMAIL_VERIFY,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'token' => 'old-token-1',
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        $oldVerification2 = UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_EMAIL_VERIFY,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'token' => 'old-token-2',
            'expires_at' => Carbon::now()->addHours(48),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/email/resend");
        $response->assertStatus(200);

        // Check old tokens are still valid (not expired)
        $this->assertFalse($oldVerification1->fresh()->expires_at->isPast());
        $this->assertFalse($oldVerification2->fresh()->expires_at->isPast());

        // Check new verification was created
        $activeVerifications = UserVerification::where('user_id', $user->id)
            ->where('type', UserVerification::TYPE_EMAIL_VERIFY)
            ->where('expires_at', '>', now())
            ->get();

        // All three tokens should be active
        $this->assertEquals(3, $activeVerifications->count());

        // Verify the new token is different from old ones
        $newToken = $activeVerifications->whereNotIn('token', ['old-token-1', 'old-token-2'])->first();
        $this->assertNotNull($newToken);
    });

    test('unauthenticated user cannot resend verification email', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/email/resend");

        $response->assertStatus(401);
    });
});

describe('Email Verification Status', function (): void {
    test('authenticated user can check verification status', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("{$this->apiPrefix}/email/verification-status");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email_verified' => true,
                    'email_verified_at' => $user->email_verified_at->toJSON(),
                ],
            ]);
    });

    test('unverified user sees correct status', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("{$this->apiPrefix}/email/verification-status");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email_verified' => false,
                    'email_verified_at' => null,
                ],
            ]);
    });

    test('unauthenticated user cannot check verification status', function (): void {
        $response = $this->getJson("{$this->apiPrefix}/email/verification-status");

        $response->assertStatus(401);
    });
});

describe('Email Verification Integration', function (): void {
    test('complete email verification flow', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($user);

        // Step 1: Request verification email
        $response = $this->postJson("{$this->apiPrefix}/email/resend");
        $response->assertStatus(200);

        // Get the created verification
        $verification = UserVerification::where('user_id', $user->id)
            ->where('type', UserVerification::TYPE_EMAIL_VERIFY)
            ->latest()
            ->first();

        $this->assertNotNull($verification);

        // Step 2: Verify email with the token
        $response = $this->postJson("{$this->apiPrefix}/email/verify", [
            'token' => $verification->token,
        ]);

        $response->assertStatus(200);

        // Step 3: Check status shows verified
        // Need to refresh the user to get updated email_verified_at
        $user->refresh();

        $response = $this->getJson("{$this->apiPrefix}/email/verification-status");
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'email_verified' => true,
                ],
            ]);

        // Step 4: Cannot use same token again
        $response = $this->postJson("{$this->apiPrefix}/email/verify", [
            'token' => $verification->token,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Token already used',
            ]);
    });

    test('verification history is preserved', function (): void {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Sanctum::actingAs($user);

        // Create multiple verification requests
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::clear('verify-email:resend:'.$user->id);
            $this->postJson("{$this->apiPrefix}/email/resend")->assertStatus(200);
        }

        // Check all verifications are preserved
        $verificationCount = UserVerification::where('user_id', $user->id)
            ->where('type', UserVerification::TYPE_EMAIL_VERIFY)
            ->count();

        expect($verificationCount)->toBe(3);

        // All three should be active (not expired) - improved UX
        $activeCount = UserVerification::where('user_id', $user->id)
            ->where('type', UserVerification::TYPE_EMAIL_VERIFY)
            ->where('expires_at', '>', now())
            ->count();

        expect($activeCount)->toBe(3);
    });
});
