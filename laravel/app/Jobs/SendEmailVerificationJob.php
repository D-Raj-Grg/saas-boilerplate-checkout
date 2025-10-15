<?php

namespace App\Jobs;

use App\Mail\EmailVerificationMail;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public UserVerification $verification
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)->send(new EmailVerificationMail($this->user, $this->verification));
    }
}
