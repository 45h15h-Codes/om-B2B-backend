<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReconcileShopifyProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:reconcile {store_id : The ID of the Shopify store to reconcile}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare local products vs Shopify products and automatically recreate missing products';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(\App\Services\ShopifyService $shopify)
    {
        $storeId = $this->argument('store_id');
        $this->info("Starting reconciliation for Shopify Store ID: {$storeId}...");

        try {
            $count = $shopify->reconcileMissingShopifyProducts($storeId);
            $this->info("Successfully checked and reconciled. Recreated {$count} missing products.");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Reconciliation failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
