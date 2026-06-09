<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShopifySyncService;
use App\Models\ShopifyStore;

class SyncProductsCommand extends Command
{
    protected $signature = 'shopify:sync-products {store_id?}';
    protected $description = 'Sync products and inventory details from Shopify';

    public function handle(ShopifySyncService $syncService)
    {
        $storeId = $this->argument('store_id');
        
        if ($storeId) {
            $stores = ShopifyStore::where('id', $storeId)->get();
        } else {
            $stores = ShopifyStore::where('is_active', true)->get();
        }

        if ($stores->isEmpty()) {
            $this->error('No active Shopify stores found.');
            return 1;
        }

        foreach ($stores as $store) {
            $this->info("Syncing products for store: {$store->store_name} ({$store->shop_domain})...");
            try {
                $res = $syncService->syncProducts($store->id);
                $this->info("Success: Synced {$res['records_processed']} products.");
            } catch (\Throwable $e) {
                $this->error("Failed to sync products for store {$store->shop_domain}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
