<?php

namespace App\Jobs;

use App\Models\Diamond;
use App\Services\CrossStoreInventorySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LockDiamondAcrossStoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $diamondId;
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
    public function __construct(int $diamondId, ?int $originStoreId = null, ?string $shopifyOrderId = null)
    {
        $this->diamondId = $diamondId;
        $this->originStoreId = $originStoreId;
        $this->shopifyOrderId = $shopifyOrderId;
    }

    /**
     * Execute the job.
     */
    public function handle(CrossStoreInventorySyncService $syncService)
    {
        $diamond = Diamond::find($this->diamondId);
        if (!$diamond) {
            Log::warning("LockDiamondAcrossStoresJob: Diamond ID {$this->diamondId} not found.");
            return;
        }

        $syncService->lockDiamondAcrossStores($diamond, $this->originStoreId, $this->shopifyOrderId);
    }
}
