<?php

namespace App\Jobs;

use App\Mail\TrialExpirationWarningMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTrialExpirationWarningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public Organization $organization,
        public int $daysRemaining
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new TrialExpirationWarningMail($this->user, $this->organization, $this->daysRemaining)
        );
    }
}
