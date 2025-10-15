<?php

use App\Mail\WaitlistAccountCreatedMail;
use App\Models\Waitlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('process waitlist command shows no entries when none exist', function (): void {
    $this->artisan('waitlist:process-accounts')
        ->expectsOutput('✅ No waitlist entries to process.')
        ->assertSuccessful();
});

test('process waitlist command shows correct count with dry run', function (): void {
    Waitlist::factory()->count(3)->create(['converted_at' => null]);

    $this->artisan('waitlist:process-accounts --dry-run')
        ->expectsOutput('📋 Found 3 entries to process (DRY RUN)')
        ->expectsOutput('🔍 DRY RUN COMPLETE')
        ->expectsOutput('📊 Would process: 3 entries')
        ->assertSuccessful();

    // Verify no entries were actually converted
    expect(Waitlist::whereNull('converted_at')->count())->toBe(3);
});

test('process waitlist command respects limit option', function (): void {
    Waitlist::factory()->count(5)->create(['converted_at' => null]);

    $this->artisan('waitlist:process-accounts --dry-run --limit=2')
        ->expectsOutput('📋 Found 2 entries to process (DRY RUN)')
        ->expectsOutput('📊 Would process: 2 entries')
        ->assertSuccessful();
});

test('process waitlist command processes entries correctly', function (): void {
    Mail::fake();

    // Create test entries with valid @bsf.io emails for registration
    $waitlist1 = Waitlist::factory()->create([
        'email' => 'test1@bsf.io',
        'converted_at' => null,
    ]);
    $waitlist2 = Waitlist::factory()->create([
        'email' => 'test2@bsf.io',
        'converted_at' => null,
    ]);

    $this->artisan('waitlist:process-accounts --limit=2')
        ->expectsOutput('📋 Found 2 entries to process')
        ->expectsOutput('✨ PROCESSING COMPLETE')
        ->expectsOutput('📊 Success: 2 | Errors: 0')
        ->assertSuccessful();

    // Verify entries were marked as converted
    expect($waitlist1->fresh()->converted_at)->not->toBeNull();
    expect($waitlist2->fresh()->converted_at)->not->toBeNull();

    // Verify status changed from pending to converted
    expect($waitlist1->fresh()->status)->toBe('converted');
    expect($waitlist2->fresh()->status)->toBe('converted');

    // Verify emails were sent
    Mail::assertSent(WaitlistAccountCreatedMail::class, 2);
});

test('process waitlist command skips already converted entries', function (): void {
    // Create one converted and one pending entry
    Waitlist::factory()->create(['converted_at' => now()]);
    Waitlist::factory()->create(['converted_at' => null, 'email' => 'pending@bsf.io']);

    $this->artisan('waitlist:process-accounts')
        ->expectsOutput('📋 Found 1 entries to process')
        ->assertSuccessful();

    expect(Waitlist::whereNull('converted_at')->count())->toBe(0);
    expect(Waitlist::whereNotNull('converted_at')->count())->toBe(2);
});

test('process waitlist command shows help correctly', function (): void {
    $this->artisan('waitlist:process-accounts --help')
        ->assertSuccessful();

    // Just verify the command runs successfully with help flag
    expect(true)->toBeTrue();
});
