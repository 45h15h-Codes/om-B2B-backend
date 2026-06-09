<?php

namespace App\Jobs;

use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyProduct;
use App\Services\ShopifyService;
use App\Services\CrossStoreInventorySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishProductToStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $productType;
    public int $productId;
    public int $storeId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $productType, int $productId, int $storeId)
    {
        $this->productType = $productType;
        $this->productId = $productId;
        $this->storeId = $storeId;
    }

    /**
     * Execute the job.
     */
    public function handle(ShopifyService $shopify)
    {
        Log::info("Running PublishProductToStoreJob for {$this->productType} ID: {$this->productId} on Store: {$this->storeId}");
        
        $shopify->forStore($this->storeId);

        $shopifyProduct = ShopifyProduct::where('product_type', $this->productType)
            ->where('product_id', $this->productId)
            ->where('shopify_store_id', $this->storeId)
            ->first();

        if ($shopifyProduct && $shopifyProduct->shopify_product_id && !$shopifyProduct->deleted_from_shopify) {
            $product = null;
            if ($this->productType === 'diamond') {
                $product = Diamond::find($this->productId);
            } elseif ($this->productType === 'jewelry') {
                $product = Jewelery::find($this->productId);
            }

            if ($product) {
                // Ensure inventory is restored and product is published via CrossStoreInventorySyncService
                $syncService = app(CrossStoreInventorySyncService::class);
                $syncService->releaseSingleMapping($shopifyProduct, $shopifyProduct->shopifyStore, $product);
            } else {
                // Fallback to simple publish if local model not found
                $success = $shopify->publishProduct($shopifyProduct->shopify_product_id);
                if ($success) {
                    $shopifyProduct->update([
                        'sync_status' => 'synced',
                        'sync_message' => null,
                    ]);
                } else {
                    throw new \Exception("Failed to publish product ID {$shopifyProduct->shopify_product_id} on Shopify.");
                }
            }
        } else {
            // Re-sync fully
            if ($this->productType === 'diamond') {
                $diamond = Diamond::find($this->productId);
                if ($diamond) {
                    PublishDiamondToShopifyJob::dispatchSync($diamond->id, $this->storeId);
                }
            } elseif ($this->productType === 'jewelry') {
                $jewelry = Jewelery::find($this->productId);
                if ($jewelry) {
                    PublishJewelryToShopifyJob::dispatchSync($jewelry->id, $this->storeId);
                }
            }
        }
    }
}
