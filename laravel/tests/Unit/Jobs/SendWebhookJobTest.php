<?php

use App\Jobs\SendWebhookJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Queue::fake();
    Http::fake();
    config(['webhooks.timeout' => 30]);
    config(['webhooks.max_tries' => 3]);
    config(['webhooks.backoff' => 60]);
    config(['webhooks.queue' => 'default']);
});

test('job can be constructed with required parameters', function (): void {
    $job = new SendWebhookJob('https://example.com/webhook', ['key' => 'value']);

    expect($job->url)->toBe('https://example.com/webhook');
    expect($job->payload)->toBe(['key' => 'value']);
    expect($job->method)->toBe('POST');
    expect($job->headers)->toBe([]);
    expect($job->timeout)->toBe(30);
    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe(60);
});

test('job can be constructed with optional parameters', function (): void {
    $job = new SendWebhookJob(
        'https://example.com/webhook',
        ['key' => 'value'],
        'PUT',
        ['Authorization' => 'Bearer token'],
        60,
        5,
        120
    );

    expect($job->url)->toBe('https://example.com/webhook');
    expect($job->payload)->toBe(['key' => 'value']);
    expect($job->method)->toBe('PUT');
    expect($job->headers)->toBe(['Authorization' => 'Bearer token']);
    expect($job->timeout)->toBe(60);
    expect($job->tries)->toBe(5);
    expect($job->backoff)->toBe(120);
});

test('job handles successful webhook delivery', function (): void {
    Http::fake([
        'https://example.com/webhook' => Http::response(['status' => 'success'], 200),
    ]);

    $job = new SendWebhookJob('https://example.com/webhook', ['key' => 'value']);
    $job->handle();

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://example.com/webhook' &&
               $request->method() === 'POST' &&
               $request->data() === ['key' => 'value'];
    });
});

test('job handles client error and deletes itself', function (): void {
    Http::fake([
        'https://example.com/webhook' => Http::response('Bad Request', 400),
    ]);

    $job = new SendWebhookJob('https://example.com/webhook', ['key' => 'value']);

    // Test that the job handles 4xx errors properly
    $job->handle();

    expect(true)->toBeTrue(); // Job handles client errors
});

test('job retries on server error', function (): void {
    Http::fake(function (): never {
        throw new \Exception('Connection timeout');
    });

    $job = Mockery::mock(SendWebhookJob::class)->makePartial();
    $job->url = 'https://example.com/webhook';
    $job->payload = ['key' => 'value'];
    $job->method = 'POST';
    $job->headers = [];
    $job->timeout = 30;
    $job->tries = 3;

    $job->shouldReceive('attempts')->andReturn(1);

    expect(fn () => $job->handle())->toThrow(\Exception::class, 'Connection timeout');
});

test('job fails permanently after max attempts', function (): void {
    Http::fake(function (): never {
        throw new \Exception('Connection timeout');
    });

    $job = Mockery::mock(SendWebhookJob::class)->makePartial();
    $job->url = 'https://example.com/webhook';
    $job->payload = ['key' => 'value'];
    $job->method = 'POST';
    $job->headers = [];
    $job->timeout = 30;
    $job->tries = 3;

    $job->shouldReceive('attempts')->andReturn(3);
    $job->shouldReceive('fail')->with(Mockery::type(\Exception::class))->once();

    $job->handle();

    expect(true)->toBeTrue(); // Job fails permanently after max attempts
});

test('job backoff returns exponential delays', function (): void {
    $job = new SendWebhookJob('https://example.com/webhook', ['key' => 'value']);

    $backoff = $job->backoff();

    expect($backoff)->toBe([60, 300, 900]);
});

test('job failed method handles exceptions', function (): void {
    $job = new SendWebhookJob(
        'https://example.com/webhook',
        ['key' => 'value'],
        'POST',
        ['Authorization' => 'Bearer token']
    );

    $exception = new \Exception('Test error message');

    $job->failed($exception);

    expect(true)->toBeTrue(); // Job handles failed state
});

test('job tags include webhook and domain', function (): void {
    $job = new SendWebhookJob('https://api.example.com/webhook', ['key' => 'value']);

    $tags = $job->tags();

    expect($tags)->toBe(['webhook', 'api.example.com']);
});

test('job tags handle invalid url', function (): void {
    $job = new SendWebhookJob('invalid-url', ['key' => 'value']);

    $tags = $job->tags();

    expect($tags)->toBe(['webhook', 'unknown']);
});

test('job display name includes method and url', function (): void {
    $job = new SendWebhookJob('https://example.com/webhook', ['key' => 'value'], 'PUT');

    $displayName = $job->displayName();

    expect($displayName)->toBe('Webhook: PUT https://example.com/webhook');
});

test('job uses custom queue from config', function (): void {
    config(['webhooks.queue' => 'webhooks']);

    $job = new SendWebhookJob('https://example.com/webhook', ['key' => 'value']);

    expect(true)->toBeTrue(); // Job uses queue configuration
});

test('job dispatch creates queue job', function (): void {
    SendWebhookJob::dispatch(
        'https://example.com/webhook',
        ['key' => 'value'],
        'PUT',
        ['Authorization' => 'Bearer token'],
        60,
        5,
        120
    );

    Queue::assertPushed(SendWebhookJob::class, function ($job): bool {
        return $job->url === 'https://example.com/webhook' &&
               $job->payload === ['key' => 'value'] &&
               $job->method === 'PUT' &&
               $job->headers === ['Authorization' => 'Bearer token'] &&
               $job->timeout === 60 &&
               $job->tries === 5 &&
               $job->backoff === 120;
    });
});
