<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

test('user can update first name and last name', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'name' => 'John Doe',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/user', [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'name',
                    'first_name',
                    'last_name',
                    'email',
                    'email_verified_at',
                ],
                'password_changed',
            ],
            'message',
        ]);

    $user->refresh();
    expect($user->first_name)->toBe('Jane');
    expect($user->last_name)->toBe('Smith');
    expect($user->name)->toBe('Jane Smith');
    expect($response->json('data.password_changed'))->toBeFalse();
});

test('user can update only first name', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'name' => 'John Doe',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/user', [
        'first_name' => 'Jane',
    ]);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->first_name)->toBe('Jane');
    expect($user->last_name)->toBe('Doe'); // Should remain unchanged
    expect($user->name)->toBe('Jane Doe');
});

test('user can update only last name', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'name' => 'John Doe',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/user', [
        'last_name' => 'Smith',
    ]);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->first_name)->toBe('John'); // Should remain unchanged
    expect($user->last_name)->toBe('Smith');
    expect($user->name)->toBe('John Smith');
});

test('user can change password with correct old password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    // Create additional tokens
    $regularToken = $user->createToken('Regular Token');
    $wordpressToken = $user->createToken('WordPress Connection');

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/user', [
        'old_password' => 'oldpassword123',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(200);

    $user->refresh();
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    expect(Hash::check('oldpassword123', $user->password))->toBeFalse();
    expect($response->json('data.password_changed'))->toBeTrue();

    // Verify token deletion behavior
    $remainingTokens = $user->tokens()->get();
    expect($remainingTokens)->toHaveCount(1); // Only WordPress token remains (Sanctum::actingAs doesn't create a DB token)

    // Check that WordPress token is preserved
    $wordPressTokenExists = $user->tokens()
        ->where('name', 'WordPress Connection')
        ->exists();
    expect($wordPressTokenExists)->toBeTrue();

    // Check that regular token was deleted
    $regularTokenExists = $user->tokens()
        ->where('name', 'Regular Token')
        ->exists();
    expect($regularTokenExists)->toBeFalse();
});

test('user cannot change password with incorrect old password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/user', [
        'old_password' => 'wrongpassword',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['old_password']);

    $user->refresh();
    expect(Hash::check('oldpassword123', $user->password))->toBeTrue();
});

test('user can update name and password together', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/user', [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'old_password' => 'oldpassword123',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->first_name)->toBe('Jane');
    expect($user->last_name)->toBe('Smith');
    expect($user->name)->toBe('Jane Smith');
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    expect($response->json('data.password_changed'))->toBeTrue();
});

test('password change requires confirmation', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/user', [
        'old_password' => 'oldpassword123',
        'new_password' => 'newpassword123',
        // Missing confirmation
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['new_password']);
});

test('password must be at least 8 characters', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/user', [
        'old_password' => 'oldpassword123',
        'new_password' => '1234567', // Only 7 characters
        'new_password_confirmation' => '1234567',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['new_password']);
});

test('unauthenticated user cannot update profile', function () {
    $response = $this->putJson('/api/v1/user', [
        'first_name' => 'Jane',
    ]);

    $response->assertStatus(401);
});

test('updating with empty data returns success without changes', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/user', []);

    $response->assertStatus(200);

    $user->refresh();
    expect($user->first_name)->toBe('John'); // Unchanged
    expect($user->last_name)->toBe('Doe'); // Unchanged
});

test('wordpress connection tokens are preserved when password is changed', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    // Create multiple tokens
    $mobileToken = $user->createToken('Mobile App');
    $webToken = $user->createToken('Web Browser');
    $wordpressToken1 = $user->createToken('WordPress Connection');
    $wordpressToken2 = $user->createToken('WordPress Connection'); // Multiple WP tokens

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/user', [
        'old_password' => 'oldpassword123',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(200);

    // Check token counts
    $remainingTokens = $user->tokens()->get();
    expect($remainingTokens)->toHaveCount(2); // Only 2 WordPress tokens remain (Sanctum::actingAs doesn't create a DB token)

    // Verify WordPress tokens are preserved
    $wordPressTokenCount = $user->tokens()
        ->where('name', 'WordPress Connection')
        ->count();
    expect($wordPressTokenCount)->toBe(2);

    // Verify other tokens were deleted
    $nonWordPressTokenCount = $user->tokens()
        ->where('name', '!=', 'WordPress Connection')
        ->whereNull('last_used_at') // Exclude current token (which has last_used_at)
        ->count();
    expect($nonWordPressTokenCount)->toBe(0);
});
