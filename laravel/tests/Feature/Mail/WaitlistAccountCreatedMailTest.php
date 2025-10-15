<?php

use App\Mail\WaitlistAccountCreatedMail;
use App\Models\Waitlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('waitlist account created email can be rendered', function (): void {
    $waitlist = Waitlist::factory()->create([
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $password = 'temp123password';

    $mail = new WaitlistAccountCreatedMail($waitlist, $password);

    expect($mail->envelope()->subject)->toBe('Your '.config('app.name').' Account is Ready!');
    expect($mail->waitlist->email)->toBe('test@example.com');
    expect($mail->password)->toBe($password);
});

test('waitlist account created email can be sent directly', function (): void {
    Mail::fake();

    $waitlist = Waitlist::factory()->create();
    $password = 'temp123password';

    Mail::to($waitlist->email)->send(new WaitlistAccountCreatedMail($waitlist, $password));

    Mail::assertSent(WaitlistAccountCreatedMail::class, function ($mail) use ($waitlist, $password) {
        return $mail->waitlist->id === $waitlist->id && $mail->password === $password;
    });
});
