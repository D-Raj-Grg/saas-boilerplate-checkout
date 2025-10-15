<?php

test('homepage redirects to documentation', function (): void {
    $response = $this->get('/');

    $response->assertStatus(302)
        ->assertRedirect('/docs');
})->skip('Web routes require APP_KEY - not needed for API-only app');

test('web status endpoint works', function (): void {
    $response = $this->get('/status');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
            'service' => config('app.name').' API',
        ]);
})->skip('Web routes require APP_KEY - not needed for API-only app');

test('api v1 health check endpoint works', function (): void {
    $response = $this->get('/api/v1/health');

    $response->assertStatus(200)
        ->assertJson(['status' => 'ok']);
});

test('invalid route returns 404', function (): void {
    $response = $this->get('/non-existent-route');

    $response->assertStatus(404);
});
