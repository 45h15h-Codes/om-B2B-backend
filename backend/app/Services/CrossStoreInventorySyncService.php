<?php

namespace App\Services;

use App\Models\Diamond;
use App\Models\ShopifyStore;
use App\Models\ShopifyProduct;
use App\Models\ShopifyInventory;
use App\Models\ShopifyInventoryReservation;
use App\Models\ShopifyInventoryAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CrossStoreInventorySyncService
{
    protected ShopifyService $shopify;
    protected array $processingLockIds = [];
    protected array $processingReleaseIds = [];

    public function __construct(ShopifyService $shopify)
    {
        $this->shopify = $shopify;
    }

    /**
     * Set a product to on_hold and update all its Shopify store mappings to 0 inventory.
     */
    public function lockInventoryAcrossStores(string $productType, int $productId, ?int $originStoreId = null, ?string $shopifyOrderId = null)
    {
        $product = null;
        if ($productType === 'diamond') {
            $product = \App\Models\Diamond::find($productId);
        } elseif ($productType === 'jewelry') {
            $product = \App\Models\Jewelery::find($productId);
        }

        if (!$product) {
            Log::warning("lockInventoryAcrossStores: Product not found for type {$productType}, ID {$productId}.");
            return;
        }

        // Propagate lock to duplicate/matching diamonds (representing the same unique physical diamond)
        $isOutermost = empty($this->processingLockIds);
        if (in_array($productId, $this->processingLockIds)) {
            return;
        }
        $this->processingLockIds[] = $productId;

        try {
            if ($productType === 'diamond') {
                $matchingDiamonds = \App\Models\Diamond::where('id', '!=', $product->id)
                    ->where('shape', $product->shape)
                    ->where('size', $product->size)
                    ->where('color', $product->color)
                    ->where('clarity', $product->clarity)
                    ->where('asking_price', $product->asking_price)
                    ->get();

                foreach ($matchingDiamonds as $matching) {
                    $this->lockInventoryAcrossStores('diamond', $matching->id, $originStoreId, $shopifyOrderId);
                }
            }
        } finally {
            if ($isOutermost) {
                $this->processingLockIds = [];
            }
        }

        Log::info("CrossStoreInventorySyncService: Locking {$productType} " . ($product->sku ?? $product->stock_no) . " across all stores.");

        $inventoryService = app(\App\Services\InventoryService::class);
        $inventoryService->updateInventoryStatus($product, 'on_hold', $originStoreId, $shopifyOrderId);

        $mappings = ShopifyProduct::where('product_type', $productType)
            ->where('product_id', $productId)
            ->get();

        foreach ($mappings as $mapping) {
            $store = $mapping->shopifyStore;
            if (!$store) {
                continue;
            }

            try {
                $this->lockSingleMapping($mapping, $store, $product);
            } catch (\Throwable $e) {
                Log::error("Failed to lock mapping ID {$mapping->id} for store {$store->shop_domain}: " . $e->getMessage());
                // Write audit entry with error
                ShopifyInventoryAudit::create([
                    'shopify_store_id' => $store->id,
                    'diamond_id' => ($productType === 'diamond') ? $productId : null,
                    'jewelry_id' => ($productType === 'jewelry') ? $productId : null,
                    'stock_no' => $product->sku ?? $product->stock_no,
                    'action' => 'lock',
                    'shopify_product_id' => $mapping->shopify_product_id,
                    'shopify_variant_id' => $mapping->shopify_variant_id,
                    'new_quantity' => 0,
                    'error_message' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * Backward compatibility wrapper for locking diamonds.
     */
    public function lockDiamondAcrossStores(Diamond $diamond, ?int $originStoreId = null, ?string $shopifyOrderId = null)
    {
        $this->lockInventoryAcrossStores('diamond', $diamond->id, $originStoreId, $shopifyOrderId);
    }

    /**
     * Lock a single product mapping on Shopify.
     */
    protected function lockSingleMapping(ShopifyProduct $mapping, ShopifyStore $store, $product)
    {
        $this->shopify->forStore($store->id);

        $variantId = $mapping->shopify_variant_id;
        $productId = $mapping->shopify_product_id;

        if (!$variantId || !$productId) {
            Log::warning("Mapping ID {$mapping->id} lacks variant_id or product_id. Skipping.");
            return;
        }

        // Fetch variant to inspect inventory tracking
        $response = $this->shopify->getClient($store->id)->get("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/variants/{$variantId}.json");
        if (!$response->successful()) {
            throw new \Exception("Failed to fetch variant {$variantId} from Shopify: " . $response->body());
        }

        $variant = $response->json('variant');
        $inventoryItemId = $variant['inventory_item_id'] ?? null;
        $inventoryManagement = $variant['inventory_management'] ?? null;

        // Fetch previous local quantity for auditing
        $localInv = ShopifyInventory::where('shopify_store_id', $store->id)
            ->where('shopify_variant_id', $variantId)
            ->first();
        $previousQuantity = $localInv ? $localInv->available : null;

        if ($inventoryManagement !== 'shopify' || !$inventoryItemId) {
            // Inventory tracking is unavailable - Archive/Unpublish product
            Log::info("Inventory tracking unavailable for variant {$variantId}. Unpublishing product {$productId}.");
            $success = $this->shopify->unpublishProduct($productId);
            if (!$success) {
                throw new \Exception("Failed to unpublish product {$productId} on Shopify.");
            }

            $mapping->update(['sync_status' => 'synced']);

            ShopifyInventoryAudit::create([
                'shopify_store_id' => $store->id,
                'diamond_id' => ($product instanceof \App\Models\Diamond) ? $product->id : null,
                'jewelry_id' => ($product instanceof \App\Models\Jewelery) ? $product->id : null,
                'stock_no' => $product->sku ?? $product->stock_no,
                'action' => 'lock_unpublish',
                'shopify_product_id' => $productId,
                'shopify_variant_id' => $variantId,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => 0,
                'api_response' => ['status' => 'unpublished'],
            ]);
        } else {
            // Fetch all locations where this inventory item is stocked
            $levelsResponse = $this->shopify->getClient($store->id)->get("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/inventory_levels.json", [
                'inventory_item_ids' => $inventoryItemId
            ]);

            $levels = [];
            if ($levelsResponse->successful()) {
                $levels = $levelsResponse->json('inventory_levels') ?? [];
            }

            $apiResponses = [];

            if (!empty($levels)) {
                foreach ($levels as $level) {
                    $locId = $level['location_id'];
                    Log::info("Setting inventory to 0 for item {$inventoryItemId} at location {$locId} on store {$store->shop_domain}.");
                    $payload = [
                        'location_id' => $locId,
                        'inventory_item_id' => $inventoryItemId,
                        'available' => 0,
                    ];
                    $res = $this->shopify->getClient($store->id)->post("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/inventory_levels/set.json", $payload);
                    if ($res->successful()) {
                        $apiResponses[] = $res->json();
                    } else {
                        Log::error("Failed to set inventory to 0 at location {$locId} on store {$store->shop_domain}: " . $res->body());
                    }
                }
            } else {
                // Fallback to primary location
                $locationId = $this->getPrimaryLocationId($store);
                Log::info("No active inventory levels found. Falling back to primary location {$locationId} to set inventory to 0 on store {$store->shop_domain}.");
                $payload = [
                    'location_id' => $locationId,
                    'inventory_item_id' => $inventoryItemId,
                    'available' => 0,
                ];
                $res = $this->shopify->getClient($store->id)->post("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/inventory_levels/set.json", $payload);
                if (!$res->successful()) {
                    throw new \Exception("Failed to set inventory on Shopify fallback: " . $res->body());
                }
                $apiResponses[] = $res->json();
            }

            // Update local shopify inventory table as well
            if ($localInv) {
                $localInv->update(['available' => 0]);
            }

            ShopifyInventoryAudit::create([
                'shopify_store_id' => $store->id,
                'diamond_id' => ($product instanceof \App\Models\Diamond) ? $product->id : null,
                'jewelry_id' => ($product instanceof \App\Models\Jewelery) ? $product->id : null,
                'stock_no' => $product->sku ?? $product->stock_no,
                'action' => 'lock_set_zero',
                'shopify_product_id' => $productId,
                'shopify_variant_id' => $variantId,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => 0,
                'api_response' => $apiResponses,
            ]);
        }
    }

    /**
     * Release a product back to available and restore its inventory on Shopify.
     */
    public function releaseInventoryAcrossStores(string $productType, int $productId)
    {
        $product = null;
        if ($productType === 'diamond') {
            $product = \App\Models\Diamond::find($productId);
        } elseif ($productType === 'jewelry') {
            $product = \App\Models\Jewelery::find($productId);
        }

        if (!$product) {
            Log::warning("releaseInventoryAcrossStores: Product not found for type {$productType}, ID {$productId}.");
            return;
        }

        // Propagate release to duplicate/matching diamonds (representing the same unique physical diamond)
        $isOutermost = empty($this->processingReleaseIds);
        if (in_array($productId, $this->processingReleaseIds)) {
            return;
        }
        $this->processingReleaseIds[] = $productId;

        try {
            if ($productType === 'diamond') {
                $matchingDiamonds = \App\Models\Diamond::where('id', '!=', $product->id)
                    ->where('shape', $product->shape)
                    ->where('size', $product->size)
                    ->where('color', $product->color)
                    ->where('clarity', $product->clarity)
                    ->where('asking_price', $product->asking_price)
                    ->get();

                foreach ($matchingDiamonds as $matching) {
                    $this->releaseInventoryAcrossStores('diamond', $matching->id);
                }
            }
        } finally {
            if ($isOutermost) {
                $this->processingReleaseIds = [];
            }
        }

        Log::info("CrossStoreInventorySyncService: Releasing {$productType} " . ($product->sku ?? $product->stock_no) . " across all stores.");

        $inventoryService = app(\App\Services\InventoryService::class);
        $inventoryService->updateInventoryStatus($product, 'available');

        $mappings = ShopifyProduct::where('product_type', $productType)
            ->where('product_id', $productId)
            ->get();

        foreach ($mappings as $mapping) {
            $store = $mapping->shopifyStore;
            if (!$store) {
                continue;
            }

            try {
                $this->releaseSingleMapping($mapping, $store, $product);
            } catch (\Throwable $e) {
                Log::error("Failed to release mapping ID {$mapping->id} for store {$store->shop_domain}: " . $e->getMessage());
                ShopifyInventoryAudit::create([
                    'shopify_store_id' => $store->id,
                    'diamond_id' => ($productType === 'diamond') ? $productId : null,
                    'jewelry_id' => ($productType === 'jewelry') ? $productId : null,
                    'stock_no' => $product->sku ?? $product->stock_no,
                    'action' => 'release',
                    'shopify_product_id' => $mapping->shopify_product_id,
                    'shopify_variant_id' => $mapping->shopify_variant_id,
                    'new_quantity' => ($productType === 'diamond') ? ($product->number_of_diamonds ?? 1) : 1,
                    'error_message' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * Backward compatibility wrapper for releasing diamonds.
     */
    public function releaseDiamondAcrossStores(Diamond $diamond)
    {
        $this->releaseInventoryAcrossStores('diamond', $diamond->id);
    }

    /**
     * Release a single product mapping on Shopify.
     */
    public function releaseSingleMapping(ShopifyProduct $mapping, ShopifyStore $store, $product)
    {
        $this->shopify->forStore($store->id);

        $variantId = $mapping->shopify_variant_id;
        $productId = $mapping->shopify_product_id;

        if (!$variantId || !$productId) {
            Log::warning("Mapping ID {$mapping->id} lacks variant_id or product_id. Skipping.");
            return;
        }

        // Fetch variant details
        $response = $this->shopify->getClient($store->id)->get("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/variants/{$variantId}.json");
        if (!$response->successful()) {
            throw new \Exception("Failed to fetch variant {$variantId} from Shopify: " . $response->body());
        }

        $variant = $response->json('variant');
        $inventoryItemId = $variant['inventory_item_id'] ?? null;
        $inventoryManagement = $variant['inventory_management'] ?? null;

        $targetQty = ($product instanceof \App\Models\Diamond) ? ($product->number_of_diamonds ?? 1) : 1;

        // Fetch previous local quantity for auditing
        $localInv = ShopifyInventory::where('shopify_store_id', $store->id)
            ->where('shopify_variant_id', $variantId)
            ->first();
        $previousQuantity = $localInv ? $localInv->available : null;

        // First, ensure the product is set to active (republish it in case it was drafted)
        $publishSuccess = $this->shopify->publishProduct($productId);
        if (!$publishSuccess) {
            throw new \Exception("Failed to publish product {$productId} on Shopify.");
        }

        if ($inventoryManagement === 'shopify' && $inventoryItemId) {
            // Fetch all locations where this inventory item is stocked
            $levelsResponse = $this->shopify->getClient($store->id)->get("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/inventory_levels.json", [
                'inventory_item_ids' => $inventoryItemId
            ]);

            $levels = [];
            if ($levelsResponse->successful()) {
                $levels = $levelsResponse->json('inventory_levels') ?? [];
            }

            $apiResponses = [];

            if (!empty($levels)) {
                foreach ($levels as $level) {
                    $locId = $level['location_id'];
                    Log::info("Restoring inventory to {$targetQty} for item {$inventoryItemId} at location {$locId} on store {$store->shop_domain}.");
                    $payload = [
                        'location_id' => $locId,
                        'inventory_item_id' => $inventoryItemId,
                        'available' => (int) $targetQty,
                    ];
                    $res = $this->shopify->getClient($store->id)->post("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/inventory_levels/set.json", $payload);
                    if ($res->successful()) {
                        $apiResponses[] = $res->json();
                    } else {
                        Log::error("Failed to restore inventory to {$targetQty} at location {$locId} on store {$store->shop_domain}: " . $res->body());
                    }
                }
            } else {
                // Fallback to primary location
                $locationId = $this->getPrimaryLocationId($store);
                Log::info("No active inventory levels found. Falling back to primary location {$locationId} to restore inventory to {$targetQty} on store {$store->shop_domain}.");
                $payload = [
                    'location_id' => $locationId,
                    'inventory_item_id' => $inventoryItemId,
                    'available' => (int) $targetQty,
                ];
                $res = $this->shopify->getClient($store->id)->post("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/inventory_levels/set.json", $payload);
                if (!$res->successful()) {
                    throw new \Exception("Failed to restore inventory on Shopify fallback: " . $res->body());
                }
                $apiResponses[] = $res->json();
            }

            // Update local shopify inventory table
            if ($localInv) {
                $localInv->update(['available' => $targetQty]);
            }

            ShopifyInventoryAudit::create([
                'shopify_store_id' => $store->id,
                'diamond_id' => ($product instanceof \App\Models\Diamond) ? $product->id : null,
                'jewelry_id' => ($product instanceof \App\Models\Jewelery) ? $product->id : null,
                'stock_no' => $product->sku ?? $product->stock_no,
                'action' => 'release_set_qty',
                'shopify_product_id' => $productId,
                'shopify_variant_id' => $variantId,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $targetQty,
                'api_response' => $apiResponses,
            ]);
        } else {
            // Tracking is off, just logging republish
            ShopifyInventoryAudit::create([
                'shopify_store_id' => $store->id,
                'diamond_id' => ($product instanceof \App\Models\Diamond) ? $product->id : null,
                'jewelry_id' => ($product instanceof \App\Models\Jewelery) ? $product->id : null,
                'stock_no' => $product->sku ?? $product->stock_no,
                'action' => 'release_republish',
                'shopify_product_id' => $productId,
                'shopify_variant_id' => $variantId,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $targetQty,
                'api_response' => ['status' => 'published'],
            ]);
        }
    }

    /**
     * Synchronize a product's Shopify state to match its local inventory_status.
     */
    public function syncInventoryAcrossStores(string $productType, int $productId)
    {
        $product = null;
        if ($productType === 'diamond') {
            $product = \App\Models\Diamond::find($productId);
        } elseif ($productType === 'jewelry') {
            $product = \App\Models\Jewelery::find($productId);
        }

        if (!$product) {
            return;
        }

        $status = $product->inventory_status ?? 'available';

        if ($status === 'on_hold') {
            $this->lockInventoryAcrossStores($productType, $productId);
        } elseif ($status === 'available') {
            $this->releaseInventoryAcrossStores($productType, $productId);
        } elseif ($status === 'sold') {
            $this->lockInventoryAcrossStores($productType, $productId);
        }
    }

    /**
     * Backward compatibility wrapper for synchronizing diamonds.
     */
    public function syncDiamondAcrossStores(Diamond $diamond)
    {
        $this->syncInventoryAcrossStores('diamond', $diamond->id);
    }

    /**
     * Fetch primary inventory location ID for a store (with 1-day caching).
     */
    public function getPrimaryLocationId(ShopifyStore $store): int
    {
        $cacheKey = "shopify_store_location_id_" . $store->id;

        return Cache::remember($cacheKey, now()->addDay(), function () use ($store) {
            // Defensive hardcoded fallbacks to bypass read_locations scope error on merchant stores
            if ($store->id == 3 || str_contains($store->shop_domain, 'om-gems')) {
                return 89550717180;
            }
            if ($store->id == 2 || str_contains($store->shop_domain, 'normal-admin')) {
                return 87049175218;
            }

            $this->shopify->forStore($store->id);
            $response = $this->shopify->getClient($store->id)->get("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/locations.json");

            if ($response->successful()) {
                $locations = $response->json('locations') ?? [];
                if (!empty($locations)) {
                    return (int) $locations[0]['id'];
                }
            }

            throw new \Exception("Failed to retrieve locations for store {$store->shop_domain}: " . $response->body());
        });
    }
}
