<?php

namespace App\Jobs;

use App\Models\ShopifyWebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessShopifyWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $logId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [5, 15, 30, 60, 120]; // Exponential backoff for rate limits
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $logId)
    {
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $log = ShopifyWebhookLog::findOrFail($this->logId);

        $log->update(['status' => 'processing']);

        try {
            // Instantiate the webhook controller to reuse all internal logic
            $controller = new \App\Http\Controllers\ShopifyWebhookController();
            $controller->processWebhookPayload($log->topic, $log->payload, $log->shop_domain);

            // Record in idempotency table after successful processing
            if ($log->webhook_id) {
                \App\Models\ShopifyWebhookIdempotency::create([
                    'webhook_id' => $log->webhook_id,
                    'topic' => $log->topic,
                ]);
            }

            $log->update([
                'status' => 'processed',
                'error_message' => null
            ]);
        } catch (\Throwable $e) {
            Log::channel('shopify')->error("Webhook processing failed on log ID {$this->logId}: " . $e->getMessage());

            $log->increment('retry_count');

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage() . "\n" . $e->getTraceAsString()
            ]);

            // Re-throw the exception to trigger retry mechanisms
            throw $e;
        }
    }
}
