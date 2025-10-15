<?php

namespace App\Jobs;

use App\Mail\PasswordResetMail;
use App\Models\UserVerification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetEmail implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public UserVerification $verification,
        public string $frontendUrl
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ensure verification is still valid
        if ($this->verification->expires_at < now() || $this->verification->verified_at) {
            return;
        }

        // Build the reset URL for the frontend
        $resetUrl = rtrim($this->frontendUrl, '/')."/reset-password?token={$this->verification->token}";

        // Send the email
        Mail::to($this->verification->identifier)
            ->send(new PasswordResetMail($this->verification, $resetUrl));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Log the failure or handle it as needed
        logger()->error('Failed to send password reset email', [
            'verification_id' => $this->verification->id,
            'email' => $this->verification->identifier,
            'error' => $exception->getMessage(),
        ]);
    }
}
