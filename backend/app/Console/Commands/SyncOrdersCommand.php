<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShopifySyncService;
use App\Models\ShopifyStore;

class SyncOrdersCommand extends Command
{
    protected $signature = 'shopify:sync-orders {store_id?}';
    protected $description = 'Sync orders from Shopify for a specific store or all stores';

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
            $this->info("Syncing orders for store: {$store->store_name} ({$store->shop_domain})...");
            try {
                $res = $syncService->syncOrders($store->id);
                $this->info("Success: Synced {$res['records_processed']} orders.");
            } catch (\Throwable $e) {
                $this->error("Failed to sync orders for store {$store->shop_domain}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
