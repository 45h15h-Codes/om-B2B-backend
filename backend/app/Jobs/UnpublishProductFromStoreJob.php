<?php

namespace App\Jobs;

use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UnpublishProductFromStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $shopifyProductId;
    public int $storeId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $shopifyProductId, int $storeId)
    {
        $this->shopifyProductId = $shopifyProductId;
        $this->storeId = $storeId;
    }

    /**
     * Execute the job.
     */
    public function handle(ShopifyService $shopify)
    {
        Log::info("Running UnpublishProductFromStoreJob for Shopify Product ID: {$this->shopifyProductId} on Store: {$this->storeId}");
        $shopify->forStore($this->storeId);
        $shopify->unpublishProduct($this->shopifyProductId);
    }
}
