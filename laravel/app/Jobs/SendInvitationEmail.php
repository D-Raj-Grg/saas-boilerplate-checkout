<?php

namespace App\Jobs;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvitationEmail implements ShouldQueue
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
        public Invitation $invitation,
        public string $frontendUrl
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ensure invitation is still valid and pending
        if ($this->invitation->status !== 'pending' || $this->invitation->expires_at < now()) {
            return;
        }

        // Build the accept URL for the frontend
        $acceptUrl = rtrim($this->frontendUrl, '/')."/invitations/{$this->invitation->token}";

        // Load relationships for organization invitations
        $this->invitation->load(['organization', 'inviter']);

        // Send the email
        Mail::to($this->invitation->email)
            ->send(new InvitationMail($this->invitation, $acceptUrl));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Log the failure or handle it as needed
        logger()->error('Failed to send invitation email', [
            'invitation_id' => $this->invitation->id,
            'email' => $this->invitation->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
