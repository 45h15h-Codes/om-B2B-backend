<?php

namespace App\Console\Commands;

use App\Models\ShopifyStore;
use App\Models\ShopifyProduct;
use App\Models\Diamond;
use App\Services\CrossStoreInventorySyncService;
use App\Services\ShopifyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileInventoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:reconcile-inventory {--store= : Optional store ID to reconcile}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile local diamond inventory states with their live Shopify store states';

    /**
     * Execute the console command.
     */
    public function handle(CrossStoreInventorySyncService $syncService, ShopifyService $shopify)
    {
        $storeOption = $this->option('store');
        $stores = $storeOption 
            ? ShopifyStore::where('id', $storeOption)->get()
            : ShopifyStore::all();

        if ($stores->isEmpty()) {
            $this->error("No active Shopify stores found to reconcile.");
            return Command::FAILURE;
        }

        foreach ($stores as $store) {
            $this->info("Reconciling inventory for store: {$store->store_name} ({$store->shop_domain})");
            $shopify->forStore($store->id);

            // 1. Reconcile existing mappings
            $mappings = ShopifyProduct::where('shopify_store_id', $store->id)
                ->where('product_type', 'diamond')
                ->get();

            $reconciledCount = 0;
            $mismatchCount = 0;

            foreach ($mappings as $mapping) {
                $diamond = $mapping->product;
                if (!$diamond) {
                    $this->warn("Local diamond not found for mapping ID {$mapping->id}. Skipping.");
                    continue;
                }

                $this->line("Checking Stock No: {$diamond->stock_no} (Shopify Product ID: {$mapping->shopify_product_id})");

                try {
                    $variantId = $mapping->shopify_variant_id;
                    $productId = $mapping->shopify_product_id;

                    if (!$variantId || !$productId) {
                        continue;
                    }

                    // Fetch variant and product from Shopify
                    $varRes = $shopify->getClient($store->id)->get("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/variants/{$variantId}.json");
                    $prodRes = $shopify->getClient($store->id)->get("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/products/{$productId}.json");

                    if ($varRes->status() === 404 || $prodRes->status() === 404) {
                        $this->warn("Product or variant not found on Shopify for stock_no {$diamond->stock_no}. Marking sync as pending.");
                        $mapping->update(['sync_status' => 'pending']);
                        continue;
                    }

                    if (!$varRes->successful() || !$prodRes->successful()) {
                        $this->error("Shopify API Error checking stock_no {$diamond->stock_no}: " . ($varRes->body() ?: $prodRes->body()));
                        continue;
                    }

                    $shopifyVariant = $varRes->json('variant');
                    $shopifyProduct = $prodRes->json('product');

                    $actualQty = (int) ($shopifyVariant['inventory_quantity'] ?? 0);
                    $actualStatus = $shopifyProduct['status'] ?? 'active';
                    $inventoryManagement = $shopifyVariant['inventory_management'] ?? null;

                    // Expected state
                    $localStatus = $diamond->inventory_status ?? 'available';
                    $expectedQty = in_array($localStatus, ['on_hold', 'hold', 'sold']) ? 0 : ($diamond->number_of_diamonds ?? 1);
                    $expectedStatus = ($inventoryManagement !== 'shopify' && in_array($localStatus, ['on_hold', 'hold', 'sold'])) ? 'draft' : 'active';

                    $hasMismatch = false;

                    if ($inventoryManagement === 'shopify') {
                        if ($actualQty !== $expectedQty) {
                            $hasMismatch = true;
                            $this->warn("Quantity mismatch for {$diamond->stock_no}: expected {$expectedQty}, got {$actualQty} on Shopify.");
                        }
                    }

                    if ($actualStatus !== $expectedStatus) {
                        $hasMismatch = true;
                        $this->warn("Status mismatch for {$diamond->stock_no}: expected '{$expectedStatus}', got '{$actualStatus}' on Shopify.");
                    }

                    // Fix discrepancy
                    if ($hasMismatch) {
                        $mismatchCount++;
                        $this->info("Fixing discrepancy for {$diamond->stock_no}...");
                        $syncService->syncDiamondAcrossStores($diamond);
                        $reconciledCount++;
                    }

                } catch (\Throwable $e) {
                    $this->error("Error reconciling Stock No {$diamond->stock_no}: " . $e->getMessage());
                }
            }

            // 2. Auto-Discovery & Repair of missing mappings
            try {
                $shopifyProducts = [];
                $url = "https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/products.json?limit=250";
                while ($url) {
                    $response = $shopify->getClient($store->id)->get($url);
                    if (!$response->successful()) {
                        break;
                    }
                    $data = $response->json('products') ?? [];
                    $shopifyProducts = array_merge($shopifyProducts, $data);
                    
                    $url = null;
                    $linkHeader = $response->header('Link');
                    if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                        $url = $matches[1];
                    }
                }

                if (!empty($shopifyProducts)) {
                    // Index Shopify variants by SKU (case-insensitive)
                    $skuMap = [];
                    foreach ($shopifyProducts as $p) {
                        foreach ($p['variants'] as $v) {
                            if (!empty($v['sku'])) {
                                $skuMap[strtolower(trim($v['sku']))][] = [
                                    'shopify_product_id' => (string) $p['id'],
                                    'shopify_variant_id' => (string) $v['id'],
                                    'inventory_management' => $v['inventory_management'],
                                    'inventory_quantity' => $v['inventory_quantity'],
                                    'status' => $p['status'] ?? 'active',
                                ];
                            }
                        }
                    }

                    $diamonds = Diamond::all();
                    foreach ($diamonds as $diamond) {
                        $stockNo = strtolower(trim($diamond->stock_no));
                        if (empty($stockNo)) {
                            continue;
                        }

                        if (isset($skuMap[$stockNo])) {
                            $shopifyVariants = $skuMap[$stockNo];
                            
                            foreach ($shopifyVariants as $sv) {
                                $mappingExists = ShopifyProduct::where('shopify_store_id', $store->id)
                                    ->where('product_type', 'diamond')
                                    ->where('product_id', $diamond->id)
                                    ->where('shopify_product_id', $sv['shopify_product_id'])
                                    ->where('shopify_variant_id', $sv['shopify_variant_id'])
                                    ->exists();

                                if (!$mappingExists) {
                                    $this->info("Mapping missing for {$diamond->stock_no} on store {$store->store_name}. Repairing...");
                                    
                                    ShopifyProduct::create([
                                        'product_type' => 'diamond',
                                        'product_id' => $diamond->id,
                                        'shopify_store_id' => $store->id,
                                        'shopify_product_id' => $sv['shopify_product_id'],
                                        'shopify_variant_id' => $sv['shopify_variant_id'],
                                        'shopify_product_url' => "https://{$store->shop_domain}/admin/products/{$sv['shopify_product_id']}",
                                        'sync_status' => 'synced',
                                        'deleted_from_shopify' => false,
                                    ]);

                                    $reconciledCount++;

                                    // Run sync immediately for the new mapping
                                    $syncService->syncDiamondAcrossStores($diamond);
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to run mapping auto-discovery on store {$store->shop_domain}: " . $e->getMessage());
            }

            $this->info("Completed store {$store->store_name}. Detected {$mismatchCount} mismatches, successfully reconciled {$reconciledCount}.");
        }

        return Command::SUCCESS;
    }
}
