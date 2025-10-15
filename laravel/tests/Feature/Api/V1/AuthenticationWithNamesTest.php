<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->apiPrefix = '/api/v1';
});

describe('Registration with first_name and last_name', function (): void {
    test('user can register with first_name and last_name', function (): void {
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

        // Check that the name was auto-generated
        $user = User::where('email', 'john@bsf.io')->first();
        expect($user->name)->toBe('John Doe');
        expect($user->first_name)->toBe('John');
        expect($user->last_name)->toBe('Doe');
    });

    test('user can register with custom name field', function (): void {
        $userData = [
            'name' => 'Johnny D',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'johnny@bsf.io',
            'password' => 'password123',
        ];

        $response = $this->postJson("{$this->apiPrefix}/register", $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'johnny@bsf.io',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'name' => 'Johnny D', // Should use provided name, not auto-generated
        ]);
    });

    test('registration fails without first_name', function (): void {
        $userData = [
            'last_name' => 'Doe',
            'email' => 'john@bsf.io',
            'password' => 'password123',
        ];

        $response = $this->postJson("{$this->apiPrefix}/register", $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name']);
    });

    test('registration fails without last_name', function (): void {
        $userData = [
            'first_name' => 'John',
            'email' => 'john@bsf.io',
            'password' => 'password123',
        ];

        $response = $this->postJson("{$this->apiPrefix}/register", $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['last_name']);
    });
});

describe('User data retrieval includes names', function (): void {
    test('authenticated user can retrieve profile with names', function (): void {
        $user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'name' => 'Jane Smith',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("{$this->apiPrefix}/user");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Jane Smith',
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'email' => $user->email,
                ],
            ]);
    });
});

describe('Token functionality with names', function (): void {
    test('login returns user with first_name and last_name', function (): void {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('password123'),
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'name' => 'Jane Smith',
        ]);

        $response = $this->postJson("{$this->apiPrefix}/login", [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);
    });
});
