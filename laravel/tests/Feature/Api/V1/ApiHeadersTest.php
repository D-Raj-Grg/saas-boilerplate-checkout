<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('api endpoints include security headers', function (): void {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertHeader('X-API-Version', 'v1')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-XSS-Protection', '1; mode=block');
});

test('api endpoints return consistent json structure for errors', function (): void {
    $response = $this->getJson('/api/v1/non-existent-endpoint');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'error_code' => 'HTTP_404',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'error_code',
        ]);
});

test('rate limiting is applied to auth endpoints', function (): void {
    // Make multiple requests to exceed rate limit
    for ($i = 0; $i < 6; $i++) {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
    }

    // The 6th request should be rate limited
    $response->assertStatus(429)
        ->assertJson([
            'success' => false,
            'error_code' => 'HTTP_429',
        ]);
});
