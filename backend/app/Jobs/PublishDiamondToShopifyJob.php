<?php

namespace App\Jobs;

use App\Models\Diamond;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishDiamondToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $diamondId;
    public ?int $storeId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $diamondId, ?int $storeId = null)
    {
        $this->diamondId = $diamondId;
        $this->storeId = $storeId;
    }

    /**
     * Execute the job.
     */
    public function handle(ShopifyService $shopify)
    {
        $diamond = Diamond::find($this->diamondId);
        if (!$diamond) {
            return;
        }

        $storeId = $this->storeId;
        if (!$storeId) {
            $activeStore = $diamond->user ? $diamond->user->activeShopifyStore : null;
            if (!$activeStore) {
                Log::warning("No active Shopify store context found for user syncing diamond ID {$this->diamondId}");
                return;
            }
            $storeId = $activeStore->id;
        }

        $shopify->forStore($storeId);

        $shopifyProduct = $diamond->shopifyProducts()->where('shopify_store_id', $storeId)->first();
        if (!$shopifyProduct) {
            $shopifyProduct = $diamond->shopifyProducts()->create([
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
            $response = $shopify->syncDiamond($diamond);

            $shopifyProduct->refresh();
            $shopifyProduct->update([
                'shopify_product_id' => $response['product']['id'],
                'shopify_variant_id' => $response['product']['variants'][0]['id'] ?? null,
                'shopify_product_url' => "https://" . $shopify->getStore() . "/admin/products/" . $response['product']['id'],
                'product_type' => 'diamond',
                'product_reference_id' => (string) $diamond->id,
                'sync_status' => 'synced',
                'sync_message' => null,
                'response' => $response,
                'synced_at' => now(),
                'deleted_from_shopify' => false,
            ]);

            // Assign product to "Diamonds Collection"
            $collectionId = $shopify->getOrCreateCollection('Diamonds Collection');
            if ($collectionId) {
                $shopify->addProductToCollection((string)$response['product']['id'], $collectionId);
            }
        } catch (\Throwable $e) {
            $shopifyProduct->update([
                'sync_status' => 'failed',
                'sync_message' => $e->getMessage(),
            ]);
            Log::error("Shopify Diamond Sync Job Failed for Diamond ID {$this->diamondId}: " . $e->getMessage());
            
            // Re-throw so Laravel queue mechanism can handle configured retries/fails
            throw $e;
        }
    }
}
