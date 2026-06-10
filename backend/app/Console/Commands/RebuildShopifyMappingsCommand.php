<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShopifyStore;
use App\Models\ShopifyProduct;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Services\ShopifyService;
use Illuminate\Support\Facades\Log;

class RebuildShopifyMappingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:rebuild-mappings {--dry-run : Rebuild mappings and report duplicates without deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch products from Shopify, rebuild local product mappings by matching SKU, and remove duplicate Shopify products.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ShopifyService $shopify)
    {
        $dryRun = $this->option('dry-run');
        $stores = ShopifyStore::where('is_active', true)->get();

        if ($stores->isEmpty()) {
            $this->error('No active Shopify stores found in the database.');
            return 1;
        }

        foreach ($stores as $store) {
            $this->info("Processing store: {$store->store_name} ({$store->shop_domain})");
            $shopify->forStore($store->id);

            // Fetch all products from Shopify using cursor pagination
            $shopifyProducts = [];
            $url = "https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/products.json?limit=250";
            
            while ($url) {
                $response = $shopify->request()->get($url);
                if (!$response->successful()) {
                    $this->error("Failed to fetch products: " . $response->body());
                    continue 2;
                }
                $data = $response->json('products') ?? [];
                $shopifyProducts = array_merge($shopifyProducts, $data);
                
                $url = null;
                $linkHeader = $response->header('Link');
                if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                    $url = $matches[1];
                }
            }

            $this->info("Found " . count($shopifyProducts) . " products on Shopify.");

            $mappedCount = 0;
            $duplicateCount = 0;
            $deletedCount = 0;

            foreach ($shopifyProducts as $sp) {
                $shopifyProductId = $sp['id'];
                $variants = $sp['variants'] ?? [];
                if (empty($variants)) {
                    continue;
                }

                $variant = $variants[0];
                $sku = $variant['sku'] ?? null;
                $shopifyVariantId = $variant['id'];

                if (!$sku) {
                    $this->warn("Product ID {$shopifyProductId} has no SKU on Shopify. Skipping.");
                    continue;
                }

                // Search locally by SKU
                // 1. Try to find a Diamond by report_no or stock_no
                $diamond = Diamond::where('report_no', $sku)
                    ->orWhere('stock_no', $sku)
                    ->first();

                $productModel = null;
                $productType = null;

                if ($diamond) {
                    $productModel = $diamond;
                    $productType = 'diamond';
                } else {
                    // 2. Try to find Jewelry by sku
                    $jewelry = Jewelery::where('sku', $sku)->first();
                    if ($jewelry) {
                        $productModel = $jewelry;
                        $productType = 'jewelry';
                    }
                }

                if (!$productModel) {
                    $this->warn("No local product found matching SKU '{$sku}'. Skipping.");
                    continue;
                }

                // Check if a local mapping record already exists for this product in this store
                $existingMapping = ShopifyProduct::where('shopify_store_id', $store->id)
                    ->where('product_type', $productType)
                    ->where('product_id', $productModel->id)
                    ->where('deleted_from_shopify', false)
                    ->first();

                if (!$existingMapping) {
                    // Create new mapping
                    if (!$dryRun) {
                        ShopifyProduct::create([
                            'product_type' => $productType,
                            'product_id' => $productModel->id,
                            'shopify_store_id' => $store->id,
                            'shopify_product_id' => $shopifyProductId,
                            'shopify_variant_id' => $shopifyVariantId,
                            'shopify_product_url' => "https://{$store->shop_domain}/admin/products/{$shopifyProductId}",
                            'sync_status' => 'synced',
                            'shopify_status' => $sp['status'] ?? 'active',
                            'sync_attempts' => 1,
                            'synced_at' => now(),
                            'deleted_from_shopify' => false,
                        ]);
                    }
                    $this->info("Mapped SKU '{$sku}' to local {$productType} ID {$productModel->id}.");
                    $mappedCount++;
                } else {
                    // Mapping already exists. This product is a duplicate on Shopify!
                    $duplicateCount++;
                    
                    if ($existingMapping->shopify_product_id == $shopifyProductId) {
                        // This is the already mapped Shopify product, keep it.
                        continue;
                    }

                    $this->warn("Duplicate Shopify product found for SKU '{$sku}' (Shopify Product ID: {$shopifyProductId}).");

                    if (!$dryRun) {
                        // Delete the duplicate product from Shopify
                        $deleteResponse = $shopify->request()->delete("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/products/{$shopifyProductId}.json");
                        if ($deleteResponse->successful() || $deleteResponse->status() === 404) {
                            $this->error("Deleted duplicate product ID {$shopifyProductId} for SKU '{$sku}' on Shopify.");
                            $deletedCount++;
                        } else {
                            $this->error("Failed to delete duplicate product ID {$shopifyProductId}: " . $deleteResponse->body());
                        }
                    }
                }
            }

            $this->info("Store {$store->store_name} Summary:");
            $this->info("- Mappings Rebuilt: {$mappedCount}");
            $this->info("- Duplicate Products Found: {$duplicateCount}");
            if (!$dryRun) {
                $this->info("- Duplicate Products Deleted: {$deletedCount}");
            }
        }

        return 0;
    }
}
