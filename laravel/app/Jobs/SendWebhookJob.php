<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $url,
        /** @var array<string, mixed> */
        public array $payload = [],
        public string $method = 'POST',
        /** @var array<string, string> */
        public array $headers = [],
        ?int $timeout = null,
        ?int $maxTries = null,
        ?int $backoff = null
    ) {
        $this->timeout = $timeout ?? config('webhooks.timeout', 30);
        $this->tries = $maxTries ?? config('webhooks.max_tries', 3);
        $this->backoff = $backoff ?? config('webhooks.backoff', 60);

        // Set queue if specified in config
        $queue = config('webhooks.queue', 'default');
        if ($queue !== 'default') {
            $this->onQueue($queue);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Sending webhook', [
            'url' => $this->url,
            'method' => $this->method,
            'attempt' => $this->attempts(),
        ]);

        try {
            $response = $this->sendWebhook();

            Log::info('Webhook sent successfully', [
                'url' => $this->url,
                'status' => $response->status(),
                'attempt' => $this->attempts(),
            ]);

            // Check if response indicates we should not retry
            if ($response->clientError()) {
                Log::warning('Webhook returned client error, will not retry', [
                    'url' => $this->url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                // Delete the job to prevent retries on 4xx errors
                $this->delete();
            }
        } catch (\Exception $e) {
            Log::error('Webhook failed', [
                'url' => $this->url,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Check if we should retry
            if ($this->attempts() >= $this->tries) {
                Log::error('Webhook permanently failed after max attempts', [
                    'url' => $this->url,
                    'attempts' => $this->attempts(),
                ]);
                $this->fail($e);
            } else {
                // Re-throw to trigger retry
                throw $e;
            }
        }
    }

    /**
     * Send the webhook HTTP request.
     */
    private function sendWebhook(): Response
    {
        $httpClient = Http::timeout($this->timeout)
            ->withHeaders($this->headers)
            ->withUserAgent(config('app.name', 'Laravel').'/Webhook');

        // Add default content type if not specified
        if (! isset($this->headers['Content-Type'])) {
            $httpClient = $httpClient->contentType('application/json');
        }
        // Add same for Accept header
        if (! isset($this->headers['Accept'])) {
            $httpClient = $httpClient->accept('application/json');
        }

        return match (strtoupper($this->method)) {
            'GET' => $httpClient->get($this->url, $this->payload),
            'POST' => $httpClient->post($this->url, $this->payload),
            'PUT' => $httpClient->put($this->url, $this->payload),
            'PATCH' => $httpClient->patch($this->url, $this->payload),
            'DELETE' => $httpClient->delete($this->url, $this->payload),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$this->method}"),
        };
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        // Exponential backoff: 1 min, 5 min, 15 min
        return [60, 300, 900];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook job permanently failed', [
            'url' => $this->url,
            'method' => $this->method,
            'payload' => $this->payload,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // You can add additional failure handling here
        // For example, send notification to admin, store in dead letter queue, etc.
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return ['webhook', parse_url($this->url, PHP_URL_HOST) ?: 'unknown'];
    }

    /**
     * Get the display name for the queued job.
     */
    public function displayName(): string
    {
        return 'Webhook: '.$this->method.' '.$this->url;
    }
}
