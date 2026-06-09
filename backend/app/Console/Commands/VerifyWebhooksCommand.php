<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShopifySyncService;
use App\Models\ShopifyStore;

class VerifyWebhooksCommand extends Command
{
    protected $signature = 'shopify:verify-webhooks {store_id?}';
    protected $description = 'Verify and automatically register missing webhooks on Shopify';

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
            $this->info("Verifying webhooks for store: {$store->store_name} ({$store->shop_domain})...");
            try {
                $res = $syncService->verifyWebhooks($store->id);
                $this->info("Verification complete: Topic registered count: " . count($res['registered']) . ", Newly created: " . count($res['created']));
            } catch (\Throwable $e) {
                $this->error("Failed to verify webhooks for store {$store->shop_domain}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
