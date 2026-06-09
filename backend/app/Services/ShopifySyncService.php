<?php

namespace App\Services;

use App\Models\ShopifyStore;
use App\Models\ShopifyOrder;
use App\Models\ShopifyProduct;
use App\Models\ShopifyInventory;
use App\Models\SyncJobHistory;
use Illuminate\Support\Facades\Log;

class ShopifySyncService extends ShopifyService
{
    /**
     * Sync orders from Shopify for a specific store.
     */
    public function syncOrders($storeId): array
    {
        $store = ShopifyStore::findOrFail($storeId);
        $this->forStore($store);

        $history = SyncJobHistory::create([
            'shopify_store_id' => $store->id,
            'job_type' => 'orders_sync',
            'status' => 'running',
            'records_processed' => 0,
            'started_at' => now(),
        ]);

        try {
            $processedCount = 0;
            $url = "https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/orders.json?limit=250&status=any";

            while ($url) {
                $response = $this->request()->get($url);

                if (!$response->successful()) {
                    throw new \Exception("Shopify API Error: " . $response->body());
                }

                $orders = $response->json('orders') ?? [];
                foreach ($orders as $payload) {
                    $shopifyOrderId = (string) ($payload['id'] ?? '');
                    if (!$shopifyOrderId) continue;

                    $customer = $payload['customer'] ?? [];
                    $email = $payload['email'] ?? $customer['email'] ?? null;
                    $firstName = $customer['first_name'] ?? '';
                    $lastName = $customer['last_name'] ?? '';
                    $customerName = trim("{$firstName} {$lastName}") ?: ($payload['billing_address']['name'] ?? null);

                    $orderData = [
                        'shopify_store_id' => $store->id,
                        'shopify_order_id' => $shopifyOrderId,
                        'order_number' => (string) ($payload['order_number'] ?? ''),
                        'customer_name' => $customerName,
                        'customer_email' => $email,
                        'total_price' => floatval($payload['total_price'] ?? 0.00),
                        'currency' => $payload['currency'] ?? 'USD',
                        'financial_status' => $payload['financial_status'] ?? null,
                        'fulfillment_status' => $payload['fulfillment_status'] ?? null,
                        'order_json' => json_encode($payload),
                    ];

                    $order = ShopifyOrder::where('shopify_store_id', $store->id)
                        ->where('shopify_order_id', $shopifyOrderId)
                        ->first();

                    if ($order) {
                        $order->update($orderData);
                        event(new \App\Events\OrderUpdatedEvent($order));
                    } else {
                        $order = ShopifyOrder::create($orderData);
                        event(new \App\Events\OrderCreatedEvent($order));
                    }

                    // Emulate webhook processing to update inventory status and propagate cross-store locks/deletes/releases
                    try {
                        $webhookController = app(\App\Http\Controllers\ShopifyWebhookController::class);
                        // Process orders/create
                        $webhookController->processWebhookPayload('orders/create', $payload, $store->shop_domain);
                        
                        // Process orders/paid if paid/completed
                        $financialStatus = $payload['financial_status'] ?? '';
                        $fulfillmentStatus = $payload['fulfillment_status'] ?? '';
                        if ($financialStatus === 'paid' || $fulfillmentStatus === 'fulfilled') {
                            $webhookController->processWebhookPayload('orders/paid', $payload, $store->shop_domain);
                        }

                        // Process orders/cancelled if cancelled
                        if (($payload['status'] ?? '') === 'cancelled' || !empty($payload['cancelled_at'])) {
                            $webhookController->processWebhookPayload('orders/cancelled', $payload, $store->shop_domain);
                        }
                    } catch (\Throwable $e) {
                        Log::channel('shopify')->error("Failed to process inventory transitions for synced order {$shopifyOrderId}: " . $e->getMessage());
                    }

                    $processedCount++;
                }

                // Check Link header pagination
                $url = null;
                $linkHeader = $response->header('Link');
                if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                    $url = $matches[1];
                }
            }

            $history->update([
                'status' => 'completed',
                'records_processed' => $processedCount,
                'finished_at' => now(),
            ]);

            return ['status' => 'success', 'records_processed' => $processedCount];

        } catch (\Throwable $e) {
            Log::channel('shopify')->error("syncOrders error: " . $e->getMessage());

            $history->update([
                'status' => 'failed',
                'errors' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync products from Shopify for a specific store.
     */
    public function syncProducts($storeId): array
    {
        $store = ShopifyStore::findOrFail($storeId);
        $this->forStore($store);

        $history = SyncJobHistory::create([
            'shopify_store_id' => $store->id,
            'job_type' => 'products_sync',
            'status' => 'running',
            'records_processed' => 0,
            'started_at' => now(),
        ]);

        try {
            $processedCount = 0;
            $url = "https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/products.json?limit=250";

            while ($url) {
                $response = $this->request()->get($url);

                if (!$response->successful()) {
                    throw new \Exception("Shopify API Error: " . $response->body());
                }

                $products = $response->json('products') ?? [];
                foreach ($products as $payload) {
                    $shopifyProductId = (string) ($payload['id'] ?? '');
                    if (!$shopifyProductId) continue;

                    // Sync product variants and inventory item IDs
                    $variants = $payload['variants'] ?? [];
                    foreach ($variants as $variant) {
                        $variantId = (string) ($variant['id'] ?? '');
                        $inventoryItemId = (string) ($variant['inventory_item_id'] ?? '');
                        if (!$variantId || !$inventoryItemId) continue;

                        // Upsert stock details into shopify_inventories
                        ShopifyInventory::updateOrCreate(
                            [
                                'shopify_store_id' => $store->id,
                                'inventory_item_id' => $inventoryItemId,
                            ],
                            [
                                'shopify_product_id' => $shopifyProductId,
                                'shopify_variant_id' => $variantId,
                                'sku' => $variant['sku'] ?? null,
                                'available' => intval($variant['inventory_quantity'] ?? 0),
                            ]
                        );
                    }

                    // Check if mapping exists in shopify_products
                    $shopifyProduct = ShopifyProduct::where('shopify_store_id', $store->id)
                        ->where('shopify_product_id', $shopifyProductId)
                        ->first();

                    if ($shopifyProduct) {
                        $shopifyProduct->update([
                            'shopify_variant_id' => isset($variants[0]['id']) ? (string) $variants[0]['id'] : null,
                            'response' => $payload,
                            'sync_status' => 'synced',
                            'synced_at' => now(),
                        ]);
                    }

                    event(new \App\Events\ProductCreatedEvent($payload));
                    $processedCount++;
                }

                // Check Link header pagination
                $url = null;
                $linkHeader = $response->header('Link');
                if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches)) {
                    $url = $matches[1];
                }
            }

            $history->update([
                'status' => 'completed',
                'records_processed' => $processedCount,
                'finished_at' => now(),
            ]);

            return ['status' => 'success', 'records_processed' => $processedCount];

        } catch (\Throwable $e) {
            Log::channel('shopify')->error("syncProducts error: " . $e->getMessage());

            $history->update([
                'status' => 'failed',
                'errors' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Run Auto Recovery Sync comparing local DB against Shopify API.
     */
    public function reconcileRecovery($storeId): array
    {
        $store = ShopifyStore::findOrFail($storeId);
        
        $history = SyncJobHistory::create([
            'shopify_store_id' => $store->id,
            'job_type' => 'recovery_sync',
            'status' => 'running',
            'records_processed' => 0,
            'started_at' => now(),
        ]);

        try {
            Log::channel('shopify')->info("Running sync recovery for store ID: {$storeId}...");
            
            $ordersSync = $this->syncOrders($storeId);
            $productsSync = $this->syncProducts($storeId);

            $totalProcessed = $ordersSync['records_processed'] + $productsSync['records_processed'];

            $history->update([
                'status' => 'completed',
                'records_processed' => $totalProcessed,
                'finished_at' => now(),
            ]);

            return [
                'status' => 'success',
                'orders_processed' => $ordersSync['records_processed'],
                'products_processed' => $productsSync['records_processed'],
                'total_processed' => $totalProcessed
            ];

        } catch (\Throwable $e) {
            Log::channel('shopify')->error("reconcileRecovery error: " . $e->getMessage());

            $history->update([
                'status' => 'failed',
                'errors' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify webhooks registration on Shopify and automatically register if missing.
     */
    public function verifyWebhooks($storeId): array
    {
        $store = ShopifyStore::findOrFail($storeId);
        $this->forStore($store);

        // Fetch registered webhooks from Shopify
        $response = $this->request()->get("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/webhooks.json");
        if (!$response->successful()) {
            throw new \Exception("Failed to fetch webhooks: " . $response->body());
        }

        $webhooks = $response->json('webhooks') ?? [];
        
        // Define required webhooks
        $requiredWebhooks = [
            'orders/create' => config('app.url') . '/api/shopify/webhooks/orders/create',
            'orders/cancelled' => config('app.url') . '/api/shopify/webhooks',
        ];

        $registered = [];
        $created = [];

        foreach ($requiredWebhooks as $topic => $address) {
            $exists = false;
            foreach ($webhooks as $wh) {
                if ($wh['topic'] === $topic && strtolower($wh['address']) === strtolower($address)) {
                    $exists = true;
                    $registered[] = $topic;
                    break;
                }
            }

            if (!$exists) {
                // Register missing webhook
                $payload = [
                    'webhook' => [
                        'topic' => $topic,
                        'address' => $address,
                        'format' => 'json'
                    ]
                ];

                $createResponse = $this->request()->post("https://{$store->shop_domain}/admin/api/" . config('shopify.api_version') . "/webhooks.json", $payload);
                if ($createResponse->successful()) {
                    $created[] = $topic;
                    $registered[] = $topic;
                    Log::channel('shopify')->info("Registered webhook '{$topic}' at '{$address}' on Shopify store.");
                } else {
                    Log::channel('shopify')->error("Failed to register webhook '{$topic}' on Shopify: " . $createResponse->body());
                }
            }
        }

        return [
            'registered' => $registered,
            'created' => $created,
        ];
    }
}
