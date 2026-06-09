<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShopifySyncService;
use App\Models\ShopifyStore;

class SyncRecoveryCommand extends Command
{
    protected $signature = 'shopify:sync-recovery {store_id?}';
    protected $description = 'Recover missed/failed webhook data by pulling latest orders and products';

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
            $this->info("Running recovery sync for store: {$store->store_name} ({$store->shop_domain})...");
            try {
                $res = $syncService->reconcileRecovery($store->id);
                $this->info("Success: Recovered and reconciled {$res['total_processed']} total records (Orders: {$res['orders_processed']}, Products: {$res['products_processed']}).");
            } catch (\Throwable $e) {
                $this->error("Failed to run recovery for store {$store->shop_domain}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
