<?php

namespace App\Jobs;

use App\Models\Jewelery;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishJewelryToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $jewelryId;
    public ?int $storeId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $jewelryId, ?int $storeId = null)
    {
        $this->jewelryId = $jewelryId;
        $this->storeId = $storeId;
    }

    /**
     * Execute the job.
     */
    public function handle(ShopifyService $shopify)
    {
        $jewelry = Jewelery::find($this->jewelryId);
        if (!$jewelry) {
            return;
        }

        $storeId = $this->storeId;
        if (!$storeId) {
            $activeStore = $jewelry->user ? $jewelry->user->activeShopifyStore : null;
            if (!$activeStore) {
                Log::warning("No active Shopify store context found for user syncing jewelry ID {$this->jewelryId}");
                return;
            }
            $storeId = $activeStore->id;
        }

        $shopify->forStore($storeId);

        $shopifyProduct = $jewelry->shopifyProducts()->where('shopify_store_id', $storeId)->first();
        if (!$shopifyProduct) {
            $shopifyProduct = $jewelry->shopifyProducts()->create([
                'shopify_store_id' => $storeId,
                'sync_status' => 'processing',
                'sync_attempts' => 0,
            ]);
        } else {
            $shopifyProduct->update([
                'sync_status' => 'processing',
            ]);
        }

        $shopifyProduct->increment('sync_attempts');

        try {
            $response = $shopify->syncJewelry($jewelry);

            $shopifyProduct->refresh();
            $shopifyProduct->update([
                'shopify_product_id' => $response['product']['id'],
                'shopify_variant_id' => $response['product']['variants'][0]['id'] ?? null,
                'shopify_product_url' => "https://" . $shopify->getStore() . "/admin/products/" . $response['product']['id'],
                'product_type' => 'jewelry',
                'product_reference_id' => (string) $jewelry->id,
                'sync_status' => 'synced',
                'sync_message' => null,
                'response' => $response,
                'synced_at' => now(),
                'deleted_from_shopify' => false,
            ]);

            // Assign product to "Jewelry Collection"
            $collectionId = $shopify->getOrCreateCollection('Jewelry Collection');
            if ($collectionId) {
                $shopify->addProductToCollection((string)$response['product']['id'], $collectionId);
            }
        } catch (\Throwable $e) {
            $shopifyProduct->update([
                'sync_status' => 'failed',
                'sync_message' => $e->getMessage(),
            ]);
            Log::error("Shopify Jewelry Sync Job Failed for Jewelry ID {$this->jewelryId}: " . $e->getMessage());
            
            throw $e;
        }
    }
}
