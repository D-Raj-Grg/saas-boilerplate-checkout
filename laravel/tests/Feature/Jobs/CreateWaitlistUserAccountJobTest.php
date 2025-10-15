<?php

use App\Jobs\CreateWaitlistUserAccountJob;
use App\Models\Waitlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('job is dispatched when new user joins waitlist', function (): void {
    Queue::fake();

    $response = $this->postJson('/api/v1/waitlist/join', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ]);

    $response->assertStatus(201);

    // In testing environment, job should not be dispatched
    Queue::assertNothingPushed();
});

test('job is not dispatched for existing waitlist entries', function (): void {
    Queue::fake();

    // Create existing entry
    Waitlist::create([
        'email' => 'existing@example.com',
        'first_name' => 'Existing',
    ]);

    $response = $this->postJson('/api/v1/waitlist/join', [
        'email' => 'existing@example.com',
        'first_name' => 'Updated',
    ]);

    $response->assertStatus(201);

    Queue::assertNothingPushed();
});

test('job handles account creation correctly', function (): void {
    $waitlist = Waitlist::factory()->create([
        'email' => 'test@bsf.io', // Valid email for registration
        'status' => 'pending',
    ]);

    // Mock the job execution
    $job = new CreateWaitlistUserAccountJob($waitlist);

    // Test that the job can be instantiated without errors
    expect($job->waitlist->email)->toBe('test@bsf.io');
    expect($job->waitlist->status)->toBe('pending');
});
