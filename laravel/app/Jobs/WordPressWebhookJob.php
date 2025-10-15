<?php

namespace App\Jobs;

use App\Models\Connection;
use App\Services\WordPressWebhookService;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * WordPress Webhook Job - Boilerplate
 *
 * Handles asynchronous webhook delivery to WordPress sites with automatic retry logic.
 * This job provides robust error handling and exponential backoff for failed deliveries.
 *
 * Features:
 * - 4 delivery attempts (1 initial + 3 retries)
 * - Exponential backoff: 5min, 1hr, 24hr
 * - Automatic connection error marking on permanent failure
 * - Queue tagging for monitoring and debugging
 *
 * Customize the retry strategy, backoff timings, and failure handling based on your needs.
 */
class WordPressWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4; // 1 initial attempt + 3 retries

    /** @var array<int> */
    public array $backoff = [300, 3600, 86400]; // 5min, 1hr, 24hr

    public int $timeout = 30; // 30 second HTTP timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Connection $wpConnection,
        /** @var array<string, mixed> */
        public array $payload,
        public string $eventType
    ) {
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(WordPressWebhookService $webhookService): void
    {
        Log::info('Processing WordPress webhook', [
            'connection_id' => $this->wpConnection->id,
            'site_url' => $this->wpConnection->site_url,
            'event_type' => $this->eventType,
            'attempt' => $this->attempts(),
        ]);

        try {
            $success = $webhookService->sendWebhook($this->wpConnection, $this->payload, $this->eventType);

            if (! $success) {
                throw new \Exception('Webhook delivery failed');
            }

            Log::info('WordPress webhook delivered successfully', [
                'connection_id' => $this->wpConnection->id,
                'site_url' => $this->wpConnection->site_url,
                'event_type' => $this->eventType,
            ]);

            // Update connection last sync time on successful delivery
            $this->wpConnection->updateLastSync();

        } catch (Throwable $exception) {
            Log::warning('WordPress webhook delivery failed', [
                'connection_id' => $this->wpConnection->id,
                'site_url' => $this->wpConnection->site_url,
                'event_type' => $this->eventType,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            throw $exception; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): DateTime
    {
        return now()->addHours(25)->toDateTime(); // Slightly longer than 24hr final retry
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('WordPress webhook failed permanently', [
            'connection_id' => $this->wpConnection->id,
            'site_url' => $this->wpConnection->site_url,
            'event_type' => $this->eventType,
            'total_attempts' => $this->attempts(),
            'exception' => $exception->getMessage(),
            'payload_event' => $this->payload['event'] ?? 'unknown',
        ]);

        // Mark connection as error state if it was active
        if ($this->wpConnection->isActive()) {
            $this->wpConnection->markAsError();

            Log::warning('Connection marked as error due to webhook failures', [
                'connection_id' => $this->wpConnection->id,
                'site_url' => $this->wpConnection->site_url,
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'wordpress-webhook',
            'connection:'.$this->wpConnection->id,
            'event:'.$this->eventType,
        ];
    }
}
