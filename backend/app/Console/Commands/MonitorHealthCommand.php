<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\ShopifyWebhookLog;
use App\Notifications\SystemAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class MonitorHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sys:monitor-health {--failed-threshold=5} {--backlog-threshold=10} {--delay-threshold=600}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor application queues, failed jobs, and Shopify webhook processing delay.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $failedThreshold = (int) $this->option('failed-threshold');
        $backlogThreshold = (int) $this->option('backlog-threshold');
        $delayThreshold = (int) $this->option('delay-threshold'); // in seconds

        $this->info("Starting health checks...");

        // 1. Check failed jobs count
        $failedJobsCount = 0;
        try {
            $failedJobsCount = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            $this->error("Failed to query failed_jobs table: " . $e->getMessage());
        }

        // 2. Check queue backlog size of configured queues
        $queues = [
            'inventory-locks',
            'webhooks',
            'shopify-sync',
            'imports',
            'notifications',
            'default'
        ];

        $queueBacklog = [];
        $totalBacklog = 0;
        foreach ($queues as $q) {
            try {
                $size = Queue::size($q);
                $queueBacklog[$q] = $size;
                $totalBacklog += $size;
            } catch (\Throwable $e) {
                $this->error("Failed to check size for queue '{$q}': " . $e->getMessage());
            }
        }

        // 3. Check webhook processing delay
        $webhookDelay = 0;
        try {
            $oldestPending = ShopifyWebhookLog::whereIn('status', ['pending', 'processing'])->oldest()->first();
            if ($oldestPending) {
                $webhookDelay = now()->diffInSeconds($oldestPending->created_at);
            }
        } catch (\Throwable $e) {
            $this->error("Failed to query shopify_webhook_logs: " . $e->getMessage());
        }

        $this->info("Health check complete.");
        $this->line("Failed jobs: {$failedJobsCount} (threshold: {$failedThreshold})");
        $this->line("Total Queue backlog: {$totalBacklog} (threshold: {$backlogThreshold})");
        $this->line("Webhook processing delay: {$webhookDelay}s (threshold: {$delayThreshold}s)");

        $superAdmins = User::where('role', 'super_admin')->get();

        // Check if failed jobs count exceeded
        if ($failedJobsCount > $failedThreshold) {
            $title = "System Alert - Failed jobs detected";
            $message = "The number of failed jobs is currently {$failedJobsCount}, which exceeds the threshold of {$failedThreshold}. Please check the failed jobs queue.";
            $this->warn($title);
            $this->sendAlertToSuperAdmins($superAdmins, $title, $message);
        }

        // Check if queue backlog exceeded
        if ($totalBacklog > $backlogThreshold) {
            $title = "System Alert - Queue backlog detected";
            
            $breakdown = [];
            foreach ($queueBacklog as $q => $size) {
                if ($size > 0) {
                    $breakdown[] = "{$q}: {$size}";
                }
            }
            $breakdownStr = implode(', ', $breakdown);
            $message = "The total queue backlog is currently {$totalBacklog}, which exceeds the threshold of {$backlogThreshold}. Breakdown: {$breakdownStr}.";
            
            $this->warn($title);
            $this->sendAlertToSuperAdmins($superAdmins, $title, $message);
        }

        // Check if webhook processing delay exceeded
        if ($webhookDelay > $delayThreshold) {
            $title = "System Alert - Webhook processing delay";
            $message = "Shopify webhook processing delay is currently {$webhookDelay} seconds, which exceeds the threshold of {$delayThreshold} seconds. The oldest pending or processing webhook log is delayed.";
            $this->warn($title);
            $this->sendAlertToSuperAdmins($superAdmins, $title, $message);
        }

        // 4. Check repeatedly failing webhooks
        $retryWebhooksCount = ShopifyWebhookLog::where('retry_count', '>=', 3)->where('status', 'failed')->count();
        if ($retryWebhooksCount > 0) {
            $title = "System Alert - Repeatedly failing webhooks";
            $message = "There are {$retryWebhooksCount} Shopify webhooks that have failed processing 3 or more times.";
            $this->warn($title);
            $this->sendAlertToSuperAdmins($superAdmins, $title, $message);
        }

        // 5. Check pending orders delay (remains pending > 15 mins)
        $pendingOrdersCount = \App\Models\Order::whereIn('status', ['pending', 'syncing', 'pending_sync'])
            ->where('created_at', '<=', now()->subMinutes(15))
            ->count();
        if ($pendingOrdersCount > 0) {
            $title = "System Alert - Orders pending sync";
            $message = "There are {$pendingOrdersCount} orders that have remained in pending status for more than 15 minutes.";
            $this->warn($title);
            $this->sendAlertToSuperAdmins($superAdmins, $title, $message);
        }

        // 6. Cache driver health check
        try {
            \Illuminate\Support\Facades\Cache::put('health_check', true, 10);
            if (!\Illuminate\Support\Facades\Cache::has('health_check')) {
                throw new \Exception("Cache store did not persist test key.");
            }
            \Illuminate\Support\Facades\Cache::forget('health_check');
        } catch (\Throwable $e) {
            $title = "System Alert - Cache Connection Failure";
            $message = "Cache storage is failing. Error: " . $e->getMessage();
            $this->error($title);
            $this->sendAlertToSuperAdmins($superAdmins, $title, $message);
        }

        // 7. Shopify API Failure check
        $failedSyncs = \App\Models\SyncJobHistory::where('status', 'failed')
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->count();
        if ($failedSyncs > 0) {
            $title = "System Alert - Shopify Sync Job Failures";
            $message = "There were {$failedSyncs} sync jobs that failed in the last 15 minutes. This could indicate Shopify API rate limits or connection issues.";
            $this->warn($title);
            $this->sendAlertToSuperAdmins($superAdmins, $title, $message);
        }

        return Command::SUCCESS;
    }

    /**
     * Send SystemAlertNotification to Super Admins with deduplication.
     */
    protected function sendAlertToSuperAdmins($superAdmins, string $title, string $message)
    {
        $notification = new SystemAlertNotification($title, $message);

        foreach ($superAdmins as $admin) {
            // Deduplicate: Don't send same alert within 5 minutes
            $isDuplicate = DB::table('notifications')
                ->where('notifiable_id', $admin->id)
                ->where('type', get_class($notification))
                ->where('created_at', '>=', now()->subMinutes(5))
                ->get()
                ->contains(function ($n) use ($title) {
                    $data = json_decode($n->data, true);
                    return isset($data['title']) && $data['title'] === $title;
                });

            if (!$isDuplicate) {
                Log::warning("Dispatching health alert to Admin {$admin->name}: {$title} - {$message}");
                $admin->notify($notification);
            }
        }
    }
}
