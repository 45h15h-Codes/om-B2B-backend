<?php

namespace App\Jobs;

use App\Models\ShopifyProduct;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteProductFromStoreJob implements ShouldQueue
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
        Log::info("Running DeleteProductFromStoreJob (Drafting) for Shopify Product ID: {$this->shopifyProductId} on Store: {$this->storeId}");
        
        $shopify->forStore($this->storeId);
        $success = $shopify->unpublishProduct($this->shopifyProductId);

        if ($success) {
            $shopifyProduct = ShopifyProduct::where('shopify_product_id', $this->shopifyProductId)
                ->where('shopify_store_id', $this->storeId)
                ->first();

            if ($shopifyProduct) {
                $shopifyProduct->update([
                    'shopify_status' => 'draft',
                    'sync_status' => 'synced',
                ]);
            }
        } else {
            throw new \Exception("Failed to draft product ID {$this->shopifyProductId} on Shopify.");
        }
    }
}
