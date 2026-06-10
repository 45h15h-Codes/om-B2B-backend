<?php

namespace App\Services;

use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyProduct;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    protected ?int $storeId = null;
    protected string $store = '';
    protected string $token = '';

    public function __construct()
    {
        $this->storeId = null;
        $this->store = '';
        $this->token = '';
    }

    /**
     * Scope the service instance to a specific Shopify store.
     */
    public function forStore($storeId): self
    {
        $store = null;
        if (is_numeric($storeId)) {
            $store = \App\Models\ShopifyStore::find($storeId);
        } else if (is_string($storeId)) {
            $store = \App\Models\ShopifyStore::where('shop_domain', $storeId)->first();
        } else if ($storeId instanceof \App\Models\ShopifyStore) {
            $store = $storeId;
        }

        if ($store) {
            $this->storeId = $store->id;
            $this->store = $store->shop_domain;
            $this->token = $store->getDecryptedAccessToken();
        } else {
            $this->storeId = null;
            $this->store = '';
            $this->token = '';
        }

        return $this;
    }

    /**
     * Configure this service instance to use credentials of a specific user's active store.
     */
    public function forUser($user): self
    {
        if (is_numeric($user)) {
            $user = \App\Models\User::find($user);
        }

        if ($user instanceof \App\Models\User) {
            $activeStore = $user->activeShopifyStore;
            if ($activeStore) {
                return $this->forStore($activeStore);
            }
        }

        $this->storeId = null;
        $this->store = '';
        $this->token = '';

        return $this;
    }

    /**
     * Get the active Shopify store domain.
     */
    public function getStore(): string
    {
        return $this->store;
    }

    /**
     * Get the configured HTTP client wrapper for a specific store.
     */
    public function getClient($storeId)
    {
        $store = null;
        if (is_numeric($storeId)) {
            $store = \App\Models\ShopifyStore::find($storeId);
        } else if (is_string($storeId)) {
            $store = \App\Models\ShopifyStore::where('shop_domain', $storeId)->first();
        } else if ($storeId instanceof \App\Models\ShopifyStore) {
            $store = $storeId;
        }

        if (!$store) {
            throw new \Exception("Shopify store not found for: " . $storeId);
        }

        return Http::withHeaders([
            'X-Shopify-Access-Token' => $store->getDecryptedAccessToken(),
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Helper to make authorized HTTP requests to Shopify Admin API.
     */
    protected function request()
    {
        if (!$this->storeId) {
            throw new \Exception("Shopify service is not configured with a store context.");
        }
        return $this->getClient($this->storeId);
    }

    /**
     * Test the connection to Shopify.
     */
    public function testConnection(): bool
    {
        if (empty($this->store) || empty($this->token)) {
            return false;
        }

        try {
            $response = $this->request()->get("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/shop.json");
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Shopify Connection Test Failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all connected Shopify stores.
     */
    public function getAllStores()
    {
        return \App\Models\ShopifyStore::all();
    }

    /**
     * Get a store by its ID.
     */
    public function getStoreById($id)
    {
        return \App\Models\ShopifyStore::find($id);
    }

    /**
     * Get the active store for a user.
     */
    public function getActiveStoreForUser($userId)
    {
        $user = \App\Models\User::find($userId);
        return $user ? $user->activeShopifyStore : null;
    }

    /**
     * Sync an individual diamond.
     */
    public function syncDiamond(Diamond $diamond): array
    {
        $shopifyProduct = $diamond->shopifyProducts()->where('shopify_store_id', $this->storeId)->first();

        // If mapping exists and has a Shopify ID, and is not already flagged as deleted from Shopify
        if ($shopifyProduct && $shopifyProduct->shopify_product_id && !$shopifyProduct->deleted_from_shopify) {
            try {
                $response = $this->request()->get("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/products/{$shopifyProduct->shopify_product_id}.json");
                
                if ($response->status() === 404) {
                    Log::warning("Diamond #{$diamond->id} (Shopify ID {$shopifyProduct->shopify_product_id}) was deleted on Shopify. Resetting mapping for recreation.");
                    $shopifyProduct->update([
                        'shopify_product_id' => null,
                        'shopify_variant_id' => null,
                        'shopify_product_url' => null,
                        'sync_status' => 'pending',
                        'deleted_from_shopify' => true,
                    ]);
                } else if (!$response->successful()) {
                    throw new \Exception("Failed to verify product existence on Shopify (Status {$response->status()}): " . $response->body());
                } else {
                    // Exists on Shopify, update it
                    return $this->updateDiamondProduct($diamond);
                }
            } catch (\Throwable $e) {
                Log::error("Error verifying Diamond ID {$diamond->id} on Shopify: " . $e->getMessage());
                throw $e;
            }
        }

        // If no active mapping exists, or it has been flagged as deleted from Shopify, recreate it
        return $this->createDiamondProduct($diamond);
    }

    /**
     * Sync an individual jewelry.
     */
    public function syncJewelry(Jewelery $jewelry): array
    {
        $shopifyProduct = $jewelry->shopifyProducts()->where('shopify_store_id', $this->storeId)->first();

        // If mapping exists and has a Shopify ID, and is not already flagged as deleted from Shopify
        if ($shopifyProduct && $shopifyProduct->shopify_product_id && !$shopifyProduct->deleted_from_shopify) {
            try {
                $response = $this->request()->get("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/products/{$shopifyProduct->shopify_product_id}.json");
                
                if ($response->status() === 404) {
                    Log::warning("Jewelry #{$jewelry->id} (Shopify ID {$shopifyProduct->shopify_product_id}) was deleted on Shopify. Resetting mapping for recreation.");
                    $shopifyProduct->update([
                        'shopify_product_id' => null,
                        'shopify_variant_id' => null,
                        'shopify_product_url' => null,
                        'sync_status' => 'pending',
                        'deleted_from_shopify' => true,
                    ]);
                } else if (!$response->successful()) {
                    throw new \Exception("Failed to verify product existence on Shopify (Status {$response->status()}): " . $response->body());
                } else {
                    // Exists on Shopify, update it
                    return $this->updateJewelryProduct($jewelry);
                }
            } catch (\Throwable $e) {
                Log::error("Error verifying Jewelry ID {$jewelry->id} on Shopify: " . $e->getMessage());
                throw $e;
            }
        }

        // If no active mapping exists, or it has been flagged as deleted from Shopify, recreate it
        return $this->createJewelryProduct($jewelry);
    }

    /**
     * Create a Diamond product on Shopify.
     */
    public function createDiamondProduct(Diamond $diamond): array
    {
        // Title: {shape} {carat}ct {color} {clarity}
        $shape = $diamond->shape ?? '';
        $carat = $diamond->size ? floatval($diamond->size) : '';
        $color = $diamond->color ?? '';
        $clarity = $diamond->clarity ?? '';
        $title = trim("{$shape} {$carat}ct {$color} {$clarity}");
        if (empty($title)) {
            $title = "Diamond Stock #" . ($diamond->stock_no ?? $diamond->id);
        }

        $sku = $diamond->report_no ?? $diamond->stock_no ?? ('DM-' . $diamond->id);
        $price = $diamond->asking_price ?? $diamond->cash_price ?? 0.00;
        $description = $diamond->additional_comments ?? $diamond->supplier_comment ?? "Beautiful {$title} Diamond";
        
        $status = 'active';
        $quantity = $diamond->number_of_diamonds ?? 1;
        if ($diamond->inventory_status && in_array(strtolower($diamond->inventory_status), ['hold', 'on_hold', 'sold'])) {
            $status = 'draft';
            $quantity = 0;
        }

        $imageUrls = $this->getDiamondImageUrls($diamond);

        $payload = [
            'product' => [
                'title' => $title,
                'body_html' => $description,
                'status' => $status,
                'product_type' => 'Diamond',
                'images' => array_map(function($url) {
                    return ['src' => $url];
                }, $imageUrls),
                'variants' => [
                    [
                        'sku' => $sku,
                        'price' => (string) $price,
                        'inventory_management' => 'shopify',
                        'inventory_quantity' => (int) $quantity,
                    ]
                ]
            ]
        ];

        Log::info('Shopify Product Create Request (Diamond):', ['diamond_id' => $diamond->id, 'payload' => $payload]);

        $response = $this->request()->post("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/products.json", $payload);

        if (!$response->successful()) {
            throw new \Exception('Failed to create Diamond on Shopify: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Update a Diamond product on Shopify.
     */
    public function updateDiamondProduct(Diamond $diamond): array
    {
        $shopifyProduct = $diamond->shopifyProducts()->where('shopify_store_id', $this->storeId)->first();
        if (!$shopifyProduct || !$shopifyProduct->shopify_product_id) {
            throw new \Exception('Shopify product record not found for diamond ID: ' . $diamond->id);
        }

        $shape = $diamond->shape ?? '';
        $carat = $diamond->size ? floatval($diamond->size) : '';
        $color = $diamond->color ?? '';
        $clarity = $diamond->clarity ?? '';
        $title = trim("{$shape} {$carat}ct {$color} {$clarity}");
        if (empty($title)) {
            $title = "Diamond Stock #" . ($diamond->stock_no ?? $diamond->id);
        }

        $sku = $diamond->report_no ?? $diamond->stock_no ?? ('DM-' . $diamond->id);
        $price = $diamond->asking_price ?? $diamond->cash_price ?? 0.00;
        $description = $diamond->additional_comments ?? $diamond->supplier_comment ?? "Beautiful {$title} Diamond";
        
        $status = 'active';
        $quantity = $diamond->number_of_diamonds ?? 1;
        if ($diamond->inventory_status && in_array(strtolower($diamond->inventory_status), ['hold', 'on_hold', 'sold'])) {
            $status = 'draft';
            $quantity = 0;
        }

        $imageUrls = $this->getDiamondImageUrls($diamond);

        // For update, Shopify updates options, titles, descriptions, and we can also update images.
        $payload = [
            'product' => [
                'id' => $shopifyProduct->shopify_product_id,
                'title' => $title,
                'body_html' => $description,
                'status' => $status,
                'images' => array_map(function($url) {
                    return ['src' => $url];
                }, $imageUrls),
                'variants' => [
                    [
                        'id' => $shopifyProduct->shopify_variant_id,
                        'sku' => $sku,
                        'price' => (string) $price,
                        'inventory_management' => 'shopify',
                        'inventory_quantity' => (int) $quantity,
                    ]
                ]
            ]
        ];

        Log::info('Shopify Product Update Request (Diamond):', ['diamond_id' => $diamond->id, 'payload' => $payload]);

        $response = $this->request()->put("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/products/{$shopifyProduct->shopify_product_id}.json", $payload);

        if (!$response->successful()) {
            throw new \Exception('Failed to update Diamond on Shopify: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Create a Jewelry product on Shopify.
     */
    public function createJewelryProduct(Jewelery $jewelry): array
    {
        $title = $jewelry->name ?? "Jewelry Item #" . ($jewelry->sku ?? $jewelry->id);
        $sku = $jewelry->sku ?? ('JW-' . $jewelry->id);
        $price = $jewelry->price ?? 0.00;
        $description = $jewelry->description ?? "Beautiful {$title}";
        
        $status = 'active';
        $quantity = $jewelry->in_stock ?? 1;
        if ($jewelry->inventory_status && in_array(strtolower($jewelry->inventory_status), ['hold', 'on_hold', 'sold'])) {
            $status = 'draft';
            $quantity = 0;
        }

        $category = $jewelry->type ?? 'Jewelry';

        $imageUrls = [];
        if ($jewelry->image_url) {
            $imageUrls[] = str_starts_with($jewelry->image_url, 'http') ? $jewelry->image_url : url($jewelry->image_url);
        }

        $payload = [
            'product' => [
                'title' => $title,
                'body_html' => $description,
                'status' => $status,
                'product_type' => $category,
                'images' => array_map(function($url) {
                    return ['src' => $url];
                }, $imageUrls),
                'variants' => [
                    [
                        'sku' => $sku,
                        'price' => (string) $price,
                        'inventory_management' => 'shopify',
                        'inventory_quantity' => (int) $quantity,
                    ]
                ]
            ]
        ];

        Log::info('Shopify Product Create Request (Jewelry):', ['jewelry_id' => $jewelry->id, 'payload' => $payload]);

        $response = $this->request()->post("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/products.json", $payload);

        if (!$response->successful()) {
            throw new \Exception('Failed to create Jewelry on Shopify: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Update a Jewelry product on Shopify.
     */
    public function updateJewelryProduct(Jewelery $jewelry): array
    {
        $shopifyProduct = $jewelry->shopifyProducts()->where('shopify_store_id', $this->storeId)->first();
        if (!$shopifyProduct || !$shopifyProduct->shopify_product_id) {
            throw new \Exception('Shopify product record not found for jewelry ID: ' . $jewelry->id);
        }

        $title = $jewelry->name ?? "Jewelry Item #" . ($jewelry->sku ?? $jewelry->id);
        $sku = $jewelry->sku ?? ('JW-' . $jewelry->id);
        $price = $jewelry->price ?? 0.00;
        $description = $jewelry->description ?? "Beautiful {$title}";
        
        $status = 'active';
        $quantity = $jewelry->in_stock ?? 1;
        if ($jewelry->inventory_status && in_array(strtolower($jewelry->inventory_status), ['hold', 'on_hold', 'sold'])) {
            $status = 'draft';
            $quantity = 0;
        }

        $category = $jewelry->type ?? 'Jewelry';

        $imageUrls = [];
        if ($jewelry->image_url) {
            $imageUrls[] = str_starts_with($jewelry->image_url, 'http') ? $jewelry->image_url : url($jewelry->image_url);
        }

        $payload = [
            'product' => [
                'id' => $shopifyProduct->shopify_product_id,
                'title' => $title,
                'body_html' => $description,
                'status' => $status,
                'product_type' => $category,
                'images' => array_map(function($url) {
                    return ['src' => $url];
                }, $imageUrls),
                'variants' => [
                    [
                        'id' => $shopifyProduct->shopify_variant_id,
                        'sku' => $sku,
                        'price' => (string) $price,
                        'inventory_management' => 'shopify',
                        'inventory_quantity' => (int) $quantity,
                    ]
                ]
            ]
        ];

        Log::info('Shopify Product Update Request (Jewelry):', ['jewelry_id' => $jewelry->id, 'payload' => $payload]);

        $response = $this->request()->put("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/products/{$shopifyProduct->shopify_product_id}.json", $payload);

        if (!$response->successful()) {
            throw new \Exception('Failed to update Jewelry on Shopify: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Delete a product on Shopify.
     */
    public function deleteProduct($shopifyProductId): bool
    {
        if (empty($shopifyProductId)) {
            return false;
        }

        try {
            Log::info("Deleting Shopify Product ID: {$shopifyProductId}");
            $response = $this->request()->delete("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/products/{$shopifyProductId}.json");
            
            // 404 means already deleted on Shopify, which counts as success
            return $response->successful() || $response->status() === 404;
        } catch (\Throwable $e) {
            Log::error("Failed to delete Shopify product ID {$shopifyProductId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unpublish a product on Shopify (set status to draft).
     */
    public function unpublishProduct($shopifyProductId): bool
    {
        if (empty($shopifyProductId)) {
            return false;
        }

        try {
            Log::info("Unpublishing Shopify Product ID: {$shopifyProductId}");
            $payload = [
                'product' => [
                    'id' => $shopifyProductId,
                    'status' => 'draft'
                ]
            ];
            $response = $this->request()->put("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/products/{$shopifyProductId}.json", $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Failed to unpublish Shopify product ID {$shopifyProductId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Publish a product on Shopify (set status to active).
     */
    public function publishProduct($shopifyProductId): bool
    {
        if (empty($shopifyProductId)) {
            return false;
        }

        try {
            Log::info("Publishing Shopify Product ID: {$shopifyProductId}");
            $payload = [
                'product' => [
                    'id' => $shopifyProductId,
                    'status' => 'active'
                ]
            ];
            $response = $this->request()->put("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/products/{$shopifyProductId}.json", $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Failed to publish Shopify product ID {$shopifyProductId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync all unsynced or failed diamonds.
     */
    public function syncAllDiamonds(?int $userId = null): int
    {
        // Fetch diamonds that don't have a synced shopify product
        $query = Diamond::whereDoesntHave('shopifyProduct', function($query) {
            $query->where('sync_status', 'synced');
        });

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $diamonds = $query->get();

        $storeId = $this->storeId;
        if (!$storeId && $userId) {
            $user = \App\Models\User::find($userId);
            $storeId = $user ? $user->active_shopify_store_id : null;
        }

        foreach ($diamonds as $diamond) {
            \App\Jobs\PublishDiamondToShopifyJob::dispatch($diamond->id, $storeId);
        }

        return $diamonds->count();
    }

    /**
     * Sync all unsynced or failed jewelry.
     */
    public function syncAllJewelry(?int $userId = null): int
    {
        // Fetch jewelry that don't have a synced shopify product
        $query = Jewelery::whereDoesntHave('shopifyProduct', function($query) {
            $query->where('sync_status', 'synced');
        });

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $jewelries = $query->get();

        $storeId = $this->storeId;
        if (!$storeId && $userId) {
            $user = \App\Models\User::find($userId);
            $storeId = $user ? $user->active_shopify_store_id : null;
        }

        foreach ($jewelries as $jewelry) {
            \App\Jobs\PublishJewelryToShopifyJob::dispatch($jewelry->id, $storeId);
        }

        return $jewelries->count();
    }

    /**
     * Sync all products (both diamonds and jewelry).
     */
    public function syncAllProducts(?int $userId = null): array
    {
        $diamondsCount = $this->syncAllDiamonds($userId);
        $jewelryCount = $this->syncAllJewelry($userId);

        return [
            'diamonds' => $diamondsCount,
            'jewelry' => $jewelryCount,
        ];
    }

    /**
     * Get or create a Custom Collection on Shopify.
     */
    public function getOrCreateCollection(string $title): ?string
    {
        $cacheKey = 'shopify_collection_id_' . md5($title);
        $collectionId = cache($cacheKey);
        if ($collectionId) {
            return (string) $collectionId;
        }

        try {
            // Find existing collection
            $response = $this->request()->get("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/custom_collections.json", [
                'title' => $title
            ]);

            if ($response->successful()) {
                $collections = $response->json('custom_collections') ?? [];
                if (!empty($collections)) {
                    $collectionId = $collections[0]['id'];
                    cache([$cacheKey => $collectionId], now()->addDay());
                    return (string) $collectionId;
                }
            }

            // Create collection if it doesn't exist
            $createResponse = $this->request()->post("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/custom_collections.json", [
                'custom_collection' => [
                    'title' => $title
                ]
            ]);

            if ($createResponse->successful()) {
                $collectionId = $createResponse->json('custom_collection.id');
                cache([$cacheKey => $collectionId], now()->addDay());
                return (string) $collectionId;
            }

            Log::error("Failed to create collection '{$title}' on Shopify: " . $createResponse->body());
        } catch (\Throwable $e) {
            Log::error("Exception in getOrCreateCollection for '{$title}': " . $e->getMessage());
        }

        return null;
    }

    /**
     * Assign a product to a collection on Shopify.
     */
    public function addProductToCollection(string $shopifyProductId, string $collectionId): bool
    {
        try {
            $response = $this->request()->post("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/collects.json", [
                'collect' => [
                    'collection_id' => $collectionId,
                    'product_id' => $shopifyProductId
                ]
            ]);

            // 422 usually means the product is already in the collection
            if ($response->successful() || $response->status() === 422) {
                return true;
            }

            Log::error("Failed to add product {$shopifyProductId} to collection {$collectionId}: " . $response->body());
        } catch (\Throwable $e) {
            Log::error("Exception in addProductToCollection for product {$shopifyProductId}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Reconcile missing Shopify products by comparing local products vs Shopify products.
     * Recreates missing products automatically.
     */
    public function reconcileMissingShopifyProducts($storeId): int
    {
        $this->forStore($storeId);
        if (empty($this->store)) {
            throw new \Exception("Active Shopify store context is not set or valid for Store ID: {$storeId}");
        }

        Log::info("Starting Shopify reconciliation for store: {$this->store} (ID: {$storeId})");

        // Fetch all products from Shopify using cursor pagination
        $shopifyProducts = [];
        $url = "https://{$this->store}/admin/api/" . config('shopify.api_version') . "/products.json?limit=250";
        while ($url) {
            $response = $this->request()->get($url);
            if (!$response->successful()) {
                throw new \Exception("Failed to fetch products from Shopify: " . $response->body());
            }
            $data = $response->json('products') ?? [];
            $shopifyProducts = array_merge($shopifyProducts, $data);
            
            // Parse Shopify Link header for next page
            $url = null;
            $linkHeader = $response->header('Link');
            if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                $url = $matches[1];
            }
        }

        $shopifyIds = collect($shopifyProducts)->pluck('id')->map(fn($id) => (string)$id)->toArray();
        Log::info("Retrieved " . count($shopifyIds) . " products from Shopify store {$this->store}.");

        // Fetch local synced products
        $localProducts = ShopifyProduct::where('shopify_store_id', $storeId)
            ->whereNotNull('shopify_product_id')
            ->where('deleted_from_shopify', false)
            ->get();

        $reconciledCount = 0;

        foreach ($localProducts as $localProduct) {
            $shopifyId = (string) $localProduct->shopify_product_id;
            if (!in_array($shopifyId, $shopifyIds)) {
                Log::warning("Local product reference (Type: {$localProduct->product_type}, ID: {$localProduct->product_id}) with Shopify ID {$shopifyId} is missing on Shopify. Recreating.");
                
                $localProduct->update([
                    'shopify_product_id' => null,
                    'shopify_variant_id' => null,
                    'shopify_product_url' => null,
                    'sync_status' => 'pending',
                    'deleted_from_shopify' => true,
                ]);

                // Dispatch recreate/sync job
                if ($localProduct->product_type === 'diamond') {
                    \App\Jobs\PublishDiamondToShopifyJob::dispatch($localProduct->product_id, $storeId);
                } else if ($localProduct->product_type === 'jewelry') {
                    \App\Jobs\PublishJewelryToShopifyJob::dispatch($localProduct->product_id, $storeId);
                }

                $reconciledCount++;
            }
        }

        Log::info("Reconciliation completed. Recreated {$reconciledCount} missing products.");
        return $reconciledCount;
    }

    /**
     * Verify a list of Shopify Product IDs and delete the local mapping of any that are missing.
     */
    public function verifyAndCleanupProducts(array $shopifyProductIds): array
    {
        if (empty($shopifyProductIds) || empty($this->store) || empty($this->token)) {
            return [];
        }

        $chunks = array_chunk($shopifyProductIds, 250);
        $allMissingIds = [];

        foreach ($chunks as $chunk) {
            try {
                $response = $this->request()->get("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/products.json", [
                    'ids' => implode(',', $chunk),
                    'fields' => 'id',
                    'limit' => 250
                ]);

                if ($response->successful()) {
                    $products = $response->json('products') ?? [];
                    $existingIds = array_map('strval', array_column($products, 'id'));
                    $requestedIds = array_map('strval', $chunk);
                    $missingIds = array_diff($requestedIds, $existingIds);

                    if (!empty($missingIds)) {
                        ShopifyProduct::whereIn('shopify_product_id', $missingIds)->delete();
                        $allMissingIds = array_merge($allMissingIds, $missingIds);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Verification and Cleanup of Shopify products failed: ' . $e->getMessage());
            }
        }

        return $allMissingIds;
    }

    /**
     * Helper to retrieve all image URLs for a diamond and convert local ones to absolute URLs.
     */
    protected function getDiamondImageUrls(Diamond $diamond): array
    {
        $urls = [];

        if ($diamond->diamond_image) {
            $urls[] = str_starts_with($diamond->diamond_image, 'http') ? $diamond->diamond_image : url($diamond->diamond_image);
        }

        if ($diamond->diamond_image_link) {
            $urls[] = str_starts_with($diamond->diamond_image_link, 'http') ? $diamond->diamond_image_link : url($diamond->diamond_image_link);
        }

        return array_unique($urls);
    }
}