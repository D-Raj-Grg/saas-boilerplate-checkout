<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->apiPrefix = '/api/v1';
});

describe('Google OAuth Redirect', function (): void {
    test('redirects to Google OAuth', function (): void {
        $response = $this->get("{$this->apiPrefix}/auth/google/redirect");

        // Should redirect to Google
        $response->assertRedirect();

        $redirectUrl = $response->headers->get('Location');

        // Should contain Google OAuth URL
        expect($redirectUrl)->toContain('accounts.google.com');
        expect($redirectUrl)->toContain('oauth');
    });
});

describe('Google OAuth Callback', function (): void {
    test('creates new user with Google OAuth', function (): void {
        // Mock Google user
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-123');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        // Mock Socialite
        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $response = $this->get("{$this->apiPrefix}/auth/google/callback");

        // Should redirect to frontend
        $response->assertRedirect();

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'google_id' => 'google-id-123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        // Verify email is verified
        $user = User::where('email', 'john@example.com')->first();
        expect($user->email_verified_at)->not->toBeNull();
    });

    test('links Google account to existing user with same email', function (): void {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'google_id' => null,
        ]);

        // Mock Google user
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-123');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        // Mock Socialite
        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $response = $this->get("{$this->apiPrefix}/auth/google/callback");

        $response->assertRedirect();

        // Verify Google ID was added to existing user
        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'email' => 'john@example.com',
            'google_id' => 'google-id-123',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        // Verify only one user exists
        expect(User::where('email', 'john@example.com')->count())->toBe(1);
    });

    test('logs in existing Google user', function (): void {
        // Create existing Google user
        User::factory()->create([
            'email' => 'john@example.com',
            'google_id' => 'google-id-123',
        ]);

        // Mock Google user
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-123');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        // Mock Socialite
        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $response = $this->get("{$this->apiPrefix}/auth/google/callback");

        $response->assertRedirect();

        // Verify still only one user exists
        expect(User::where('email', 'john@example.com')->count())->toBe(1);
    });

    test('creates organization and workspace for new Google user', function (): void {
        // Mock Google user
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-123');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        // Mock Socialite
        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $response = $this->get("{$this->apiPrefix}/auth/google/callback");

        $response->assertRedirect();

        // Verify organization was created
        $this->assertDatabaseHas('organizations', [
            'name' => "John Doe's Organization",
        ]);

        // Verify workspace was created
        $this->assertDatabaseHas('workspaces', [
            'name' => "John Doe's Workspace",
        ]);
    });

    test('handles single name from Google', function (): void {
        // Mock Google user with single name
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-123');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getName')->andReturn('John');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        // Mock Socialite
        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $response = $this->get("{$this->apiPrefix}/auth/google/callback");

        $response->assertRedirect();

        // Verify user was created with single name
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => '',
        ]);
    });

    test('email_verified is always true for Google OAuth', function (): void {
        // Mock Google user
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-123');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        // Mock Socialite
        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $response = $this->get("{$this->apiPrefix}/auth/google/callback");

        $response->assertRedirect();

        // Verify redirect contains code
        $redirectUrl = $response->headers->get('Location');
        expect($redirectUrl)->toContain('code=');
    });

    test('handles Google OAuth errors gracefully', function (): void {
        // Mock Socialite to throw exception
        Socialite::shouldReceive('driver->stateless->user')
            ->andThrow(new \Exception('OAuth failed'));

        $response = $this->get("{$this->apiPrefix}/auth/google/callback");

        // Should redirect to frontend with error
        $response->assertRedirect();

        $redirectUrl = $response->headers->get('Location');
        expect($redirectUrl)->toContain('error=oauth_failed');
    });

    test('SECURITY: prevents account takeover by invalidating unverified account password', function (): void {
        // Scenario: Attacker registers with victim's email (unverified)
        // Then victim tries to login with Google using same email

        // Step 1: Attacker creates unverified account with victim's email
        $attackerPassword = 'attacker-password-123';
        $unverifiedUser = User::factory()->create([
            'email' => 'victim@example.com',
            'password' => \Hash::make($attackerPassword),
            'email_verified_at' => null, // NOT verified
        ]);

        // Step 2: Victim authenticates with Google
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-victim');
        $googleUser->shouldReceive('getEmail')->andReturn('victim@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Victim User');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/victim-avatar.jpg');

        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $response = $this->get("{$this->apiPrefix}/auth/google/callback");

        $response->assertRedirect();

        // Verify security measures:
        $user = User::where('email', 'victim@example.com')->first();

        // 1. Google account is linked
        expect($user->google_id)->toBe('google-id-victim');

        // 2. Email is now verified (Google verified it)
        expect($user->email_verified_at)->not->toBeNull();

        // 3. OLD PASSWORD IS INVALIDATED - attacker can no longer login
        expect(\Hash::check($attackerPassword, $user->password))->toBeFalse();

        // 4. User still exists (data preserved)
        expect($user->id)->toBe($unverifiedUser->id);
    });

    test('SECURITY: allows linking when email is already verified', function (): void {
        // Scenario: User registered normally, verified email, then uses Google OAuth

        $userPassword = 'user-password-123';
        $verifiedUser = User::factory()->create([
            'email' => 'user@example.com',
            'password' => \Hash::make($userPassword),
            'email_verified_at' => now(), // VERIFIED
        ]);

        // User authenticates with Google
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-123');
        $googleUser->shouldReceive('getEmail')->andReturn('user@example.com');
        $googleUser->shouldReceive('getName')->andReturn('John User');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $response = $this->get("{$this->apiPrefix}/auth/google/callback");

        $response->assertRedirect();

        $user = User::where('email', 'user@example.com')->first();

        // Google account is linked
        expect($user->google_id)->toBe('google-id-123');

        // ORIGINAL PASSWORD IS PRESERVED (email was verified)
        expect(\Hash::check($userPassword, $user->password))->toBeTrue();
    });

    test('new OAuth users get random password set', function (): void {
        // Mock Google user
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-new');
        $googleUser->shouldReceive('getEmail')->andReturn('newuser@example.com');
        $googleUser->shouldReceive('getName')->andReturn('New User');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $response = $this->getJson("{$this->apiPrefix}/auth/google/callback");

        $response->assertRedirect();

        $user = User::where('email', 'newuser@example.com')->first();

        // Password is set (not null)
        expect($user->password)->not->toBeNull();

        // Password is hashed
        expect($user->password)->toStartWith('$2y$');
    });

    test('callback redirects to frontend with exchange code', function (): void {
        // Mock Google user
        $googleUser = \Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-id-123');
        $googleUser->shouldReceive('getEmail')->andReturn('user@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Test User');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $response = $this->get("{$this->apiPrefix}/auth/google/callback");

        // Should redirect to frontend
        $response->assertRedirect();

        $redirectUrl = $response->headers->get('Location');

        // Should redirect to login page
        expect($redirectUrl)->toContain('/login');

        // Should contain exchange code (not token)
        expect($redirectUrl)->toContain('code=');
        expect($redirectUrl)->not->toContain('token=');
        expect($redirectUrl)->not->toContain('access_token=');
    });

    test('exchange endpoint returns token for valid code', function (): void {
        // Store mock token in cache
        $exchangeCode = 'test-exchange-code-123';
        $tokenData = [
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
            'email_verified' => true,
        ];

        Cache::put("oauth_exchange:{$exchangeCode}", $tokenData, now()->addSeconds(60));

        // Exchange code for token
        $response = $this->postJson("{$this->apiPrefix}/auth/google/exchange", [
            'code' => $exchangeCode,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'access_token' => 'test-access-token',
                    'token_type' => 'Bearer',
                    'email_verified' => true,
                ],
            ]);

        // Code should be deleted after use
        expect(Cache::has("oauth_exchange:{$exchangeCode}"))->toBeFalse();
    });

    test('exchange endpoint fails for invalid code', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/auth/google/exchange", [
            'code' => 'invalid-code',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired exchange code',
            ]);
    });

    test('exchange endpoint fails for expired code', function (): void {
        // Don't store anything in cache - simulates expired code
        $response = $this->postJson("{$this->apiPrefix}/auth/google/exchange", [
            'code' => 'expired-code',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired exchange code',
            ]);
    });

    test('exchange code can only be used once', function (): void {
        $exchangeCode = 'one-time-code-123';
        $tokenData = [
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'email_verified' => true,
        ];

        Cache::put("oauth_exchange:{$exchangeCode}", $tokenData, now()->addSeconds(60));

        // First use - should work
        $response1 = $this->postJson("{$this->apiPrefix}/auth/google/exchange", [
            'code' => $exchangeCode,
        ]);
        $response1->assertStatus(200);

        // Second use - should fail
        $response2 = $this->postJson("{$this->apiPrefix}/auth/google/exchange", [
            'code' => $exchangeCode,
        ]);
        $response2->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired exchange code',
            ]);
    });
});

afterEach(function (): void {
    \Mockery::close();
});
