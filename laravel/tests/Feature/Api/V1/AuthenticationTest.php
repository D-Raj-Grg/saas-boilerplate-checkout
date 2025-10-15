<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->apiPrefix = '/api/v1';
});

describe('Registration', function (): void {
    test('user can register with valid data', function (): void {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@bsf.io',
            'password' => 'password123',
        ];

        $response = $this->postJson("{$this->apiPrefix}/register", $userData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully. Please check your email to verify your account.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@bsf.io',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'name' => 'John Doe',
        ]);
    });

    test('registration fails with missing data', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/register", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password']);
    });

    test('registration fails with invalid email', function (): void {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $response = $this->postJson("{$this->apiPrefix}/register", $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('registration fails with duplicate email', function (): void {
        User::factory()->create(['email' => 'john@bsf.io']);

        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@bsf.io',
            'password' => 'password123',
        ];

        $response = $this->postJson("{$this->apiPrefix}/register", $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('registration fails with disposable email', function (): void {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@mailinator.com', // Known disposable email service
            'password' => 'password123',
        ];

        $response = $this->postJson("{$this->apiPrefix}/register", $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJson([
                'errors' => [
                    'email' => ['Please use a valid, non-temporary email address to continue.'],
                ],
            ]);
    });

    test('registration fails with short password', function (): void {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@bsf.io',
            'password' => 'short',
        ];

        $response = $this->postJson("{$this->apiPrefix}/register", $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    test('registration succeeds with non-bsf.io email', function (): void {
        $userData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson("{$this->apiPrefix}/register", $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'name' => 'Jane Smith',
        ]);
    });
});

describe('Login', function (): void {
    test('user can login with valid credentials', function (): void {
        $user = User::factory()->create([
            'email' => 'john@bsf.io',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson("{$this->apiPrefix}/login", [
            'email' => 'john@bsf.io',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                ],
            ]);
    });

    test('login fails with invalid credentials', function (): void {
        User::factory()->create([
            'email' => 'john@bsf.io',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson("{$this->apiPrefix}/login", [
            'email' => 'john@bsf.io',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('login fails with non-existent email', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/login", [
            'email' => 'nonexistent@bsf.io',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('login fails with missing credentials', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/login", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    });
});

describe('Logout', function (): void {
    test('authenticated user can logout', function (): void {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("{$this->apiPrefix}/logout");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);

        // Verify token is revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
        ]);
    });

    test('unauthenticated user cannot logout', function (): void {
        $response = $this->postJson("{$this->apiPrefix}/logout");

        $response->assertStatus(401);
    });
});

describe('Get User', function (): void {
    test('authenticated user can retrieve their profile', function (): void {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("{$this->apiPrefix}/user");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    });

    test('unauthenticated user cannot retrieve profile', function (): void {
        $response = $this->getJson("{$this->apiPrefix}/user");

        $response->assertStatus(401);
    });
});

describe('Token functionality', function (): void {
    test('token allows access to protected routes', function (): void {
        $user = User::factory()->create([
            'email' => 'john@bsf.io',
            'password' => bcrypt('password123'),
        ]);

        // Login to get token
        $loginResponse = $this->postJson("{$this->apiPrefix}/login", [
            'email' => 'john@bsf.io',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.access_token');

        // Use token to access protected route
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson("{$this->apiPrefix}/user");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'email' => 'john@bsf.io',
                ],
            ]);
    });

    test('invalid token is rejected', function (): void {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson("{$this->apiPrefix}/user");

        $response->assertStatus(401);
    });

    test('multiple tokens can be created for same user', function (): void {
        $user = User::factory()->create([
            'email' => 'john@bsf.io',
            'password' => bcrypt('password123'),
        ]);

        // Create first token
        $response1 = $this->postJson("{$this->apiPrefix}/login", [
            'email' => 'john@bsf.io',
            'password' => 'password123',
        ]);

        $token1 = $response1->json('data.access_token');

        // Create second token
        $response2 = $this->postJson("{$this->apiPrefix}/login", [
            'email' => 'john@bsf.io',
            'password' => 'password123',
        ]);

        $token2 = $response2->json('data.access_token');

        expect($token1)->not->toBe($token2);

        // Both tokens should work
        $this->withHeaders(['Authorization' => 'Bearer '.$token1])
            ->getJson("{$this->apiPrefix}/user")
            ->assertStatus(200);

        $this->withHeaders(['Authorization' => 'Bearer '.$token2])
            ->getJson("{$this->apiPrefix}/user")
            ->assertStatus(200);
    });
});
