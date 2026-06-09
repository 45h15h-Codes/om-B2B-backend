<?php

namespace App\Jobs;

use App\Models\FailedInventorySync;
use App\Services\InventoryManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetrySyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $failedSyncId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $failedSyncId)
    {
        $this->failedSyncId = $failedSyncId;
    }

    /**
     * Execute the job.
     */
    public function handle(InventoryManager $inventoryManager)
    {
        $failedSync = FailedInventorySync::find($this->failedSyncId);
        if (!$failedSync || $failedSync->status === 'success') {
            return;
        }

        $failedSync->increment('retry_count');
        $product = $inventoryManager->resolveProduct($failedSync->product_type, $failedSync->product_id);

        if (!$product) {
            $failedSync->update(['status' => 'failed', 'error_message' => 'Product not found.']);
            return;
        }

        try {
            $syncService = app(\App\Services\CrossStoreInventorySyncService::class);
            $syncService->syncInventoryAcrossStores($failedSync->product_type, $failedSync->product_id);
            
            $failedSync->update(['status' => 'success']);
        } catch (\Throwable $e) {
            $failedSync->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            if ($failedSync->retry_count < 3) {
                // Retry in 5, 10, 15 mins
                self::dispatch($this->failedSyncId)->delay(now()->addMinutes($failedSync->retry_count * 5));
            } else {
                // Send notification to Super Admins
                $superAdmins = \App\Models\User::where('role', 'super_admin')->get();
                foreach ($superAdmins as $super) {
                    $super->notify(new \App\Notifications\SyncFailedNotification($product, $e->getMessage()));
                }
            }
        }
    }
}
