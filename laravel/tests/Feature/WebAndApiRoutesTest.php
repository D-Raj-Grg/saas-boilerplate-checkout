<?php

test('web routes work with regular HTTP responses', function (): void {
    $response = $this->get('/');
    $response->assertStatus(302);

    $response = $this->get('/status');
    $response->assertStatus(200)
        ->assertJson(['status' => 'ok']);
})->skip('Web routes require APP_KEY - not needed for API-only app');

test('api routes work with JSON responses', function (): void {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertJson(['status' => 'ok'])
        ->assertHeader('X-API-Version', 'v1');
});

test('web 404 errors are handled differently than API 404 errors', function (): void {
    // Web 404 - Laravel default handling
    $webResponse = $this->get('/non-existent-web-route');
    $webResponse->assertStatus(404);

    // API 404 - Custom JSON handling
    $apiResponse = $this->getJson('/api/v1/non-existent-api-route');
    $apiResponse->assertStatus(404)
        ->assertJson([
            'success' => false,
            'error_code' => 'HTTP_404',
        ]);
});

test('web dashboard works', function (): void {
    $response = $this->get('/dashboard');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'title',
            'stats',
            'links',
        ]);
})->skip('Dashboard moved to API routes - /api/v1/dashboard');

test('web and api routes can coexist', function (): void {
    // Test web route
    $webResponse = $this->get('/docs');
    $webResponse->assertStatus(200);

    // Test API route
    $apiResponse = $this->getJson('/api/v1/health');
    $apiResponse->assertStatus(200);

    // Both should work without conflicts
    expect($webResponse->getStatusCode())->toBe(200);
    expect($apiResponse->getStatusCode())->toBe(200);
})->skip('Web routes require APP_KEY - not needed for API-only app');
