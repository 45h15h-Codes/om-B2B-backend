<?php

namespace App\Jobs;

use App\Services\CrossStoreInventorySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LockInventoryAcrossStoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $productType;
    public int $productId;
    public ?int $originStoreId;
    public ?string $shopifyOrderId;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [5, 15, 30, 60, 120];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(string $productType, int $productId, ?int $originStoreId = null, ?string $shopifyOrderId = null)
    {
        $this->productType = $productType;
        $this->productId = $productId;
        $this->originStoreId = $originStoreId;
        $this->shopifyOrderId = $shopifyOrderId;
    }

    /**
     * Execute the job.
     */
    public function handle(CrossStoreInventorySyncService $syncService)
    {
        $syncService->lockInventoryAcrossStores($this->productType, $this->productId, $this->originStoreId, $this->shopifyOrderId);
    }
}
