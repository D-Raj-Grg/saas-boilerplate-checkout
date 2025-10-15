<?php

namespace App\Jobs;

use App\Services\PlanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSureCartWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public $backoff = [60, 120, 300]; // 1min, 2min, 5min

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $type,
        public array $data,
        public string $webhookId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PlanService $planService): void
    {

        \Log::info('Processing SureCart webhook', [
            'type' => $this->type,
            'webhook_id' => $this->webhookId,
        ]);

        try {
            switch ($this->type) {
                case 'purchase.created':
                    $planService->purchaseCreated($this->data, $this->webhookId);
                    break;
                case 'purchase.revoked':
                    $planService->purchaseRevoked($this->data, $this->webhookId);
                    break;
                case 'purchase.invoked':
                    $planService->purchaseInvoked($this->data, $this->webhookId);
                    break;
                case 'purchase.updated':
                    $planService->purchaseUpdated($this->data, $this->webhookId);
                    break;
                case 'subscription.renewed':
                case 'subscription.completed':
                    $planService->subscriptionRenewed($this->data, $this->webhookId);
                    break;
                default:
                    Log::warning('Unknown webhook type', ['type' => $this->type]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'type' => $this->type,
                'webhook_id' => $this->webhookId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to trigger retry
        }
    }
}
