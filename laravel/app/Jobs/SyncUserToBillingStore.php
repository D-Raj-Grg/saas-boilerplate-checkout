<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncUserToBillingStore implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $url = config('billing-store.url');
        $apiKey = config('billing-store.api_key');
        $endpoint = config('billing-store.sync_endpoint');

        if (! $url || ! $apiKey) {
            Log::warning('Billing store configuration missing. Skipping user sync.', [
                'user_id' => $this->user->id,
            ]);

            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->post($url.$endpoint, [
                'email' => $this->user->email,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
            ]);

            if ($response->successful()) {
                Log::info('User synced to billing store successfully', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                    'response' => $response->json(),
                ]);
            } else {
                Log::error('Failed to sync user to billing store', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while syncing user to billing store', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
