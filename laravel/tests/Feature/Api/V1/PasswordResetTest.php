<?php

use App\Jobs\SendPasswordResetEmail;
use App\Models\User;
use App\Models\UserVerification;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->apiPrefix = '/api/v1';
    Queue::fake();
});

describe('Password Reset Request', function (): void {
    test('user can request password reset code', function (): void {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson("{$this->apiPrefix}/password/forgot", [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'If the email exists, a reset code has been sent.',
                'data' => [
                    'expires_in_minutes' => 15,
                ],
            ]);

        // Check queue job was dispatched
        Queue::assertPushed(SendPasswordResetEmail::class);

        // Check verification was created
        $this->assertDatabaseHas('user_verifications', [
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_PASSWORD_RESET,
            'identifier' => 'john@example.com',
        ]);
    });

    test('non-existent email still returns success to prevent enumeration', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/password/forgot", [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'If the email exists, a reset code has been sent.',
            ]);

        // Check no queue job was dispatched
        Queue::assertNothingPushed();

        // Check no verification was created
        $this->assertDatabaseMissing('user_verifications', [
            'identifier' => 'nonexistent@example.com',
        ]);
    });

    test('request fails with invalid email format', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/password/forgot", [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('rate limiting prevents too many requests', function (): void {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        // Make 3 requests (the limit)
        for ($i = 0; $i < 3; $i++) {
            $this->postJson("{$this->apiPrefix}/password/forgot", [
                'email' => 'john@example.com',
            ])->assertStatus(200);
        }

        // 4th request should be rate limited
        $response = $this->postJson("{$this->apiPrefix}/password/forgot", [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(429); // Too Many Requests
    });
});

// Removed Code Verification tests as password reset uses token-based verification

describe('Password Reset', function (): void {
    test('password can be reset with valid token', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $verification = UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_PASSWORD_RESET,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'code' => null,
            'token' => 'valid-reset-token-72-characters-long-for-security-1234567890abcdef',
            'expires_at' => now()->addMinutes(60),
            'verified_at' => now(), // Token already verified
        ]);

        $response = $this->postJson("{$this->apiPrefix}/password/reset", [
            'email' => $user->email,
            'token' => 'valid-reset-token-72-characters-long-for-security-1234567890abcdef',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password has been reset successfully',
            ]);

        // Check password was updated
        $user->refresh();
        expect(Hash::check('new-password123', $user->password))->toBeTrue();

        // Check verification was marked as expired
        $verification->refresh();
        expect($verification->expires_at->isPast())->toBeTrue();

        // Check all tokens were revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    });

    test('password reset fails with invalid token', function (): void {
        $user = User::factory()->create();

        $response = $this->postJson("{$this->apiPrefix}/password/reset", [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    });

    test('password reset fails if token not verified', function (): void {
        $user = User::factory()->create();

        UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_PASSWORD_RESET,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'code' => null,
            'token' => 'unverified-token-72-characters-long-for-security-1234567890abcdef',
            'expires_at' => now()->addMinutes(60),
            'verified_at' => null, // Not verified yet
        ]);

        $response = $this->postJson("{$this->apiPrefix}/password/reset", [
            'email' => $user->email,
            'token' => 'unverified-token-72-characters-long-for-security-1234567890abcdef',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    });

    test('password reset fails if verification expired after 30 minutes', function (): void {
        $user = User::factory()->create();

        UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_PASSWORD_RESET,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'code' => null,
            'token' => 'expired-token-72-characters-long-for-security-purposes-1234567890ab',
            'expires_at' => now()->addMinutes(60),
            'verified_at' => now()->subMinutes(31), // Verified 31 minutes ago
        ]);

        $response = $this->postJson("{$this->apiPrefix}/password/reset", [
            'email' => $user->email,
            'token' => 'expired-token-72-characters-long-for-security-purposes-1234567890ab',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    });

    test('password reset fails with weak password', function (): void {
        $user = User::factory()->create();

        $verification = UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_PASSWORD_RESET,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'code' => null,
            'token' => 'valid-token-72-characters-long-for-security-purposes-1234567890abcd',
            'expires_at' => now()->addMinutes(60),
            'verified_at' => now(),
        ]);

        $response = $this->postJson("{$this->apiPrefix}/password/reset", [
            'email' => $user->email,
            'token' => 'valid-token-72-characters-long-for-security-purposes-1234567890abcd',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });
});

// Removed Combined Flow tests as the endpoint doesn't exist

describe('Service Functionality', function (): void {
    test('verification service generates secure tokens for password reset', function (): void {
        $service = new VerificationService;
        $user = User::factory()->create();

        $verification = $service->createVerification(
            $user,
            UserVerification::TYPE_PASSWORD_RESET,
            $user->email
        );

        expect($verification->code)->toBeNull();
        expect(strlen($verification->token))->toBe(64);
    });

    test('old verifications are invalidated when new one created', function (): void {
        $service = new VerificationService;
        $user = User::factory()->create();

        // Create first verification
        $first = $service->createVerification(
            $user,
            UserVerification::TYPE_PASSWORD_RESET,
            $user->email
        );

        // Create second verification
        $second = $service->createVerification(
            $user,
            UserVerification::TYPE_PASSWORD_RESET,
            $user->email
        );

        // First should be expired
        $first->refresh();
        expect($first->isExpired())->toBeTrue();

        // Second should be valid
        expect($second->isValid())->toBeTrue();
    });

    test('user cannot request more than 3 verifications per hour', function (): void {
        $service = new VerificationService;
        $user = User::factory()->create();

        // Create 3 verifications
        for ($i = 0; $i < 3; $i++) {
            UserVerification::create([
                'user_id' => $user->id,
                'type' => UserVerification::TYPE_PASSWORD_RESET,
                'channel' => UserVerification::CHANNEL_EMAIL,
                'identifier' => $user->email,
                'code' => null,
                'token' => "token-$i-72-characters-long-for-security-purposes-123456789abcdef",
                'expires_at' => now()->addMinutes(60),
                'created_at' => now()->subMinutes($i),
            ]);
        }

        expect($service->canRequestVerificationByIdentifier($user->email, UserVerification::TYPE_PASSWORD_RESET))->toBeFalse();
    });
});

describe('Login after password reset', function (): void {
    test('user can login with new password after reset', function (): void {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $verification = UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_PASSWORD_RESET,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'code' => null,
            'token' => 'valid-token-72-characters-long-for-security-purposes-123456789abcd',
            'expires_at' => now()->addMinutes(60),
            'verified_at' => now(),
        ]);

        // Reset password
        $this->postJson("{$this->apiPrefix}/password/reset", [
            'email' => $user->email,
            'token' => 'valid-token-72-characters-long-for-security-purposes-123456789abcd',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        // Try to login with new password
        $response = $this->postJson("{$this->apiPrefix}/login", [
            'email' => 'john@example.com',
            'password' => 'new-password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['access_token', 'token_type'],
            ]);
    });

    test('user cannot login with old password after reset', function (): void {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $verification = UserVerification::create([
            'user_id' => $user->id,
            'type' => UserVerification::TYPE_PASSWORD_RESET,
            'channel' => UserVerification::CHANNEL_EMAIL,
            'identifier' => $user->email,
            'code' => null,
            'token' => 'valid-token-72-characters-long-for-security-purposes-123456789efgh',
            'expires_at' => now()->addMinutes(60),
            'verified_at' => now(),
        ]);

        // Reset password
        $this->postJson("{$this->apiPrefix}/password/reset", [
            'email' => $user->email,
            'token' => 'valid-token-72-characters-long-for-security-purposes-123456789efgh',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        // Try to login with old password
        $response = $this->postJson("{$this->apiPrefix}/login", [
            'email' => 'john@example.com',
            'password' => 'old-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
});
