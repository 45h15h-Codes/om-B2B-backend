<?php

namespace App\Http\Controllers;

use App\Models\ShopifyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    /**
     * Handle incoming Shopify webhooks.
     */
    public function handle(Request $request)
    {
        return $this->ingestWebhook($request);
    }

    /**
     * Handle incoming verified orders/create webhooks for Super Admin panel.
     */
    public function handleWebhookOrderCreate(Request $request)
    {
        return $this->ingestWebhook($request);
    }

    /**
     * Ingest incoming webhooks, log them, and dispatch a queue job for processing.
     */
    protected function ingestWebhook(Request $request)
    {
        if (!$this->verifyWebhook($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $webhookId = $request->header('X-Shopify-Webhook-Id');
        $topic = $request->header('X-Shopify-Topic');
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $payload = $request->all();
        $headers = $request->headers->all();

        // 1. Log to shopify.log
        Log::channel('shopify')->info('Shopify Webhook Ingested:', [
            'webhook_id' => $webhookId,
            'topic' => $topic,
            'shop' => $shopDomain,
        ]);

        $lock = null;
        // Cache lock to prevent concurrent execution
        if ($webhookId) {
            $lockKey = "shopify_webhook_lock:{$webhookId}";
            $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 10);
            if (!$lock->get()) {
                return response()->json(['status' => 'ignored', 'message' => 'Concurrent execution in progress.'], 409);
            }
        }

        try {
            // 2. Idempotency check: if already processed, skip
            if ($webhookId) {
                $exists = \App\Models\ShopifyWebhookIdempotency::where('webhook_id', $webhookId)->exists();
                if ($exists) {
                    return response()->json(['status' => 'success', 'message' => 'Already processed']);
                }
            }

            // 3. Log the webhook request into shopify_webhook_logs table
            $webhookLog = \App\Models\ShopifyWebhookLog::firstOrCreate(
                ['webhook_id' => $webhookId ?? uniqid('wh_', true)],
                [
                    'topic' => $topic ?? 'unknown',
                    'shop_domain' => $shopDomain ?? 'unknown',
                    'headers' => $headers,
                    'payload' => $payload,
                    'status' => 'pending',
                    'error_message' => null,
                ]
            );

            if (!$webhookLog->wasRecentlyCreated) {
                return response()->json(['status' => 'success', 'message' => 'Already processed']);
            }

            // 4. Dispatch async processing job
            \App\Jobs\ProcessShopifyWebhookJob::dispatch($webhookLog->id);

            // 5. Respond < 200ms with 202 Accepted
            return response()->json(['status' => 'queued', 'webhook_log_id' => $webhookLog->id], 202);
        } finally {
            if ($lock) {
                $lock->release();
            }
        }
    }

    /**
     * Verify the authenticity of the webhook request.
     */
    protected function verifyWebhook(Request $request): bool
    {
        // For local development / testing, we can allow bypassing HMAC check if it's not provided
        $hmac = $request->header('X-Shopify-Hmac-Sha256');
        if (!$hmac) {
            if (config('app.env') === 'local' || config('app.env') === 'testing') {
                Log::warning('Shopify Webhook: Bypassing signature verification in local/testing environment.');
                return true;
            }
            return false;
        }

        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $secret = null;

        if ($shopDomain) {
            $store = \App\Models\ShopifyStore::where('shop_domain', $shopDomain)->first();
            if ($store && $store->webhook_secret) {
                $secret = $store->webhook_secret;
            }
        }

        if (!$secret) {
            $secret = config('services.shopify.webhook_secret') 
                ?? config('services.shopify.token');
        }

        if (!$secret) {
            Log::error('Shopify Webhook verification failed: Secret token not configured.');
            if (config('app.env') === 'local' || config('app.env') === 'testing') {
                return true;
            }
            return false;
        }

        $data = $request->getContent();
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $secret, true));

        $verified = hash_equals($hmac, $calculatedHmac);
        
        if (!$verified && (config('app.env') === 'local' || config('app.env') === 'testing')) {
            Log::warning('Shopify Webhook: Signature verification failed, but bypassing in local/testing environment.');
            return true;
        }

        return $verified;
    }

    /**
     * Handle storefront auto-order creation.
     */
    protected function autoCreateLocalOrder(array $payload, \App\Models\ShopifyStore $store)
    {
        $shopifyOrderId = (string) ($payload['id'] ?? '');
        if ($shopifyOrderId) {
            $existingOrder = \App\Models\Order::where('shopify_order_id', $shopifyOrderId)->first();
            if ($existingOrder) {
                Log::info("autoCreateLocalOrder: Order with shopify_order_id {$shopifyOrderId} already exists. Skipping creation.");
                return $existingOrder;
            }
        }

        $customer = $payload['customer'] ?? [];
        $email = $payload['email'] ?? $customer['email'] ?? null;
        $firstName = $customer['first_name'] ?? '';
        $lastName = $customer['last_name'] ?? '';
        $customerName = trim("{$firstName} {$lastName}") ?: ($payload['billing_address']['name'] ?? null);
        $customerPhone = $payload['phone'] ?? $customer['phone'] ?? null;

        $items = [];
        $subtotal = floatval($payload['subtotal_price'] ?? 0.00);
        $total = floatval($payload['total_price'] ?? 0.00);
        $discount = floatval($payload['total_discounts'] ?? 0.00);

        $lineItems = $payload['line_items'] ?? [];
        $variantIds = collect($lineItems)->pluck('variant_id')->filter()->map(fn($id) => (string)$id)->toArray();
        $productIds = collect($lineItems)->pluck('product_id')->filter()->map(fn($id) => (string)$id)->toArray();

        $shopifyProducts = \App\Models\ShopifyProduct::with('product')
            ->where(function($query) use ($variantIds, $productIds) {
                if (!empty($variantIds)) {
                    $query->whereIn('shopify_variant_id', $variantIds);
                }
                if (!empty($productIds)) {
                    $query->orWhereIn('shopify_product_id', $productIds);
                }
            })->get();

        $shopifyProductsByVariant = $shopifyProducts->whereNotNull('shopify_variant_id')->keyBy('shopify_variant_id');
        $shopifyProductsByProduct = $shopifyProducts->whereNotNull('shopify_product_id')->keyBy('shopify_product_id');

        foreach ($lineItems as $lineItem) {
            $variantId = $lineItem['variant_id'] ?? null;
            $productId = $lineItem['product_id'] ?? null;

            $shopifyProduct = null;
            if ($variantId && isset($shopifyProductsByVariant[(string)$variantId])) {
                $shopifyProduct = $shopifyProductsByVariant[(string)$variantId];
            } elseif ($productId && isset($shopifyProductsByProduct[(string)$productId])) {
                $shopifyProduct = $shopifyProductsByProduct[(string)$productId];
            }

            if ($shopifyProduct) {
                $product = $shopifyProduct->product;
                if ($product) {
                    if ($shopifyProduct->product_type === 'diamond') {
                        $items[] = [
                            'product_type' => 'diamond',
                            'product_id' => $product->id,
                            'stock_no' => $product->stock_no,
                            'shape' => $product->shape,
                            'carat' => (float) $product->size,
                            'color' => $product->color,
                            'clarity' => $product->clarity,
                            'price_snapshot' => (float) ($lineItem['price'] ?? 0.00),
                            'quantity' => (int) ($lineItem['quantity'] ?? 1),
                        ];
                    } else if ($shopifyProduct->product_type === 'jewelry') {
                        $items[] = [
                            'product_type' => 'jewelry',
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'name' => $product->name,
                            'price_snapshot' => (float) ($lineItem['price'] ?? 0.00),
                            'quantity' => (int) ($lineItem['quantity'] ?? 1),
                        ];
                    }
                }
            }
        }

        $order = \App\Models\Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shopify_store_id' => $store->id,
            'shopify_store_snapshot' => [
                'store_name' => $store->store_name,
                'shop_domain' => $store->shop_domain,
            ],
            'email' => $email,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'status' => $this->mapFinancialStatusToLocalStatus($payload['financial_status'] ?? null),
            'shopify_order_id' => (string) ($payload['id'] ?? ''),
            'shopify_order_number' => (string) ($payload['order_number'] ?? ''),
            'shopify_order_admin_url' => "https://{$store->shop_domain}/admin/orders/" . ($payload['id'] ?? ''),
            'shopify_payload' => $payload,
            'shopify_response' => $payload,
            'created_by' => $store->user_id ?? \App\Models\User::where('role', 'super_admin')->first()->id ?? 1,
        ]);

        $order->logs()->create([
            'action' => 'Storefront Order Synced',
            'message' => "Order #{$order->shopify_order_number} automatically synced from storefront.",
            'payload' => $payload,
        ]);

        return $order;
    }

    /**
     * Handle unique hold logic when orders/create is received.
     */
    protected function handleOrderCreateWithLock(array $payload, string $shopDomain, string $topic)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($payload, $shopDomain, $topic) {
            $store = \App\Models\ShopifyStore::where('shop_domain', $shopDomain)->first();
            if (!$store) {
                throw new \Exception("Shopify store not found for domain: {$shopDomain}");
            }

            $shopifyOrderId = (string) ($payload['id'] ?? '');

            // 1. Create or retrieve the local order record first
            $order = $this->findOrderFromWebhookPayload($payload, $topic);
            if (!$order) {
                Log::info("Webhook: Auto-creating local Order from storefront purchase.");
                $order = $this->autoCreateLocalOrder($payload, $store);
            } else {
                $shopifyOrderNumber = (string) ($payload['order_number'] ?? '');
                $orderAdminUrl = "https://{$store->shop_domain}/admin/orders/{$shopifyOrderId}";
                $financialStatus = $payload['financial_status'] ?? null;
                $mappedStatus = $this->mapFinancialStatusToLocalStatus($financialStatus);

                $order->update([
                    'status' => $mappedStatus,
                    'shopify_order_id' => $shopifyOrderId,
                    'shopify_order_number' => $shopifyOrderNumber,
                    'shopify_order_admin_url' => $orderAdminUrl,
                    'shopify_response' => array_merge($order->shopify_response ?? [], ['order_payload' => $payload]),
                ]);

                $order->logs()->create([
                    'action' => 'Order Created/Synced',
                    'message' => "Order converted to Shopify Order #{$shopifyOrderNumber} and status set to {$mappedStatus}.",
                    'payload' => $payload,
                ]);
            }

            // Send NewShopifyOrderNotification to all admins with deduplication (only for fresh orders)
            $isFresh = true;
            if (isset($payload['created_at'])) {
                $createdAt = \Illuminate\Support\Carbon::parse($payload['created_at']);
                if ($createdAt->lt(now()->subMinutes(15))) {
                    $isFresh = false;
                }
            }

            if ($isFresh) {
                $allAdmins = \App\Models\User::all();
                $storeName = $store->store_name ?? 'Shopify Store';
                $orderNumber = (string) ($payload['order_number'] ?? '');
                $orderIdVal = $order->id;
                
                $orderNotification = new \App\Notifications\NewShopifyOrderNotification($orderNumber, $storeName, $orderIdVal, $shopifyOrderId);
                
                $cacheKey = "notification_sent:{$shopifyOrderId}:new_order";
                if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, true, 300);
                    foreach ($allAdmins as $user) {
                        $user->notify($orderNotification);
                    }
                }
            } else {
                $orderNum = $payload['order_number'] ?? 'unknown';
                Log::info("Suppressing NewShopifyOrderNotification for historical order: #{$orderNum} (ID: {$shopifyOrderId})");
            }

            $lineItems = $payload['line_items'] ?? [];
            $variantIds = collect($lineItems)->pluck('variant_id')->filter()->map(fn($id) => (string)$id)->toArray();
            $productIds = collect($lineItems)->pluck('product_id')->filter()->map(fn($id) => (string)$id)->toArray();
            $skus = collect($lineItems)->pluck('sku')->filter()->toArray();

            $shopifyProducts = \App\Models\ShopifyProduct::with('product')
                ->where(function($query) use ($variantIds, $productIds) {
                    if (!empty($variantIds)) {
                        $query->whereIn('shopify_variant_id', $variantIds);
                    }
                    if (!empty($productIds)) {
                        $query->orWhereIn('shopify_product_id', $productIds);
                    }
                })->get();

            $shopifyProductsByVariant = $shopifyProducts->whereNotNull('shopify_variant_id')->keyBy('shopify_variant_id');
            $shopifyProductsByProduct = $shopifyProducts->whereNotNull('shopify_product_id')->keyBy('shopify_product_id');

            $diamondsBySku = collect();
            $jewelryBySku = collect();
            if (!empty($skus)) {
                $diamondsBySku = \App\Models\Diamond::whereIn('stock_no', $skus)->get()->keyBy('stock_no');
                $jewelryBySku = \App\Models\Jewelery::whereIn('sku', $skus)->get()->keyBy('sku');
            }

            foreach ($lineItems as $lineItem) {
                $variantId = $lineItem['variant_id'] ?? null;
                $productId = $lineItem['product_id'] ?? null;
                $sku = $lineItem['sku'] ?? null;

                $shopifyProduct = null;
                if ($variantId && isset($shopifyProductsByVariant[(string)$variantId])) {
                    $shopifyProduct = $shopifyProductsByVariant[(string)$variantId];
                } elseif ($productId && isset($shopifyProductsByProduct[(string)$productId])) {
                    $shopifyProduct = $shopifyProductsByProduct[(string)$productId];
                }

                $product = null;
                $productType = null;
                if ($shopifyProduct) {
                    $product = $shopifyProduct->product;
                    $productType = $shopifyProduct->product_type;
                } elseif ($sku) {
                    if (isset($diamondsBySku[$sku])) {
                        $product = $diamondsBySku[$sku];
                        $productType = 'diamond';
                    } elseif (isset($jewelryBySku[$sku])) {
                        $product = $jewelryBySku[$sku];
                        $productType = 'jewelry';
                    }
                }

                if ($product && $productType) {
                    if ($productType === 'diamond') {
                        // Associate diamond with order first
                        $order->update(['diamond_id' => $product->id]);

                        Log::info("Webhook orders/create: Attempting lock on diamond ID {$product->id} via GlobalDiamondLockService.");
                        $lockService = app(\App\Services\GlobalDiamondLockService::class);
                        $lockSuccess = $lockService->lockDiamond($product->id, $store->id, $shopifyOrderId, $order->id);

                        if (!$lockSuccess) {
                            Log::warning("Webhook orders/create lock failed: Diamond already locked or sold.");
                            $order->update(['status' => 'inventory_unavailable']);
                            $order->logs()->create([
                                'action' => 'Lock Failed',
                                'message' => "Diamond already locked or sold. Order could not reserve inventory.",
                                'payload' => $payload,
                            ]);
                        }
                    } else {
                        // Fallback for jewelry
                        Log::info("Webhook orders/create: Transitioning jewelry ID {$product->id} (SKU: {$sku}) to hold via InventoryService.");
                        $inventoryService = app(\App\Services\InventoryService::class);
                        try {
                            $inventoryService->updateInventoryStatus($product, 'on_hold', $store->id, $shopifyOrderId, $order->id ?? null);
                        } catch (\Throwable $e) {
                            Log::warning("Inventory status update failed or already handled for jewelry ID {$product->id}: " . $e->getMessage());
                        }
                        \App\Jobs\LockInventoryAcrossStoresJob::dispatch($productType, $product->id, $store->id, $shopifyOrderId);
                    }
                }
            }
        });
    }

    /**
     * Handle cancellation logic when orders/cancelled is received.
     */
    protected function handleOrderCancelledWithLock(array $payload, string $shopDomain, string $topic)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($payload, $shopDomain, $topic) {
            $store = \App\Models\ShopifyStore::where('shop_domain', $shopDomain)->first();
            if (!$store) {
                throw new \Exception("Shopify store not found for domain: {$shopDomain}");
            }

            $shopifyOrderId = (string) ($payload['id'] ?? '');

            $order = $this->findOrderFromWebhookPayload($payload, $topic);
            if ($order) {
                $previousStatus = $order->status;
                if ($previousStatus === 'cancelled') {
                    Log::info("Shopify Webhook: Order ID {$order->id} is already cancelled. Skipping.");
                    return;
                }

                $cancelledAt = $payload['cancelled_at'] ?? null;
                $order->update([
                    'status' => 'cancelled',
                    'shopify_response' => array_merge($order->shopify_response ?? [], [
                        'cancelled_at' => $cancelledAt,
                        'cancelled_payload' => $payload
                    ]),
                ]);

                $order->logs()->create([
                    'action' => 'Order Cancelled',
                    'message' => "Order was marked as cancelled on Shopify.",
                    'payload' => $payload,
                ]);
            }

            $reservations = \App\Models\ShopifyInventoryReservation::with('product')
                ->where('shopify_order_id', $shopifyOrderId)
                ->get();

            $diamondReleased = false;
            $inventoryService = app(\App\Services\InventoryService::class);

            foreach ($reservations as $reservation) {
                $product = $reservation->product;
                $productType = $reservation->product_type;
                if ($product && $productType) {
                    if ($productType === \App\Models\Diamond::class || $productType === 'diamond') {
                        $lockService = app(\App\Services\GlobalDiamondLockService::class);
                        $lockService->releaseDiamond($product->id, "Shopify order cancelled (Order ID: {$shopifyOrderId}).");
                        $diamondReleased = true;
                    } else {
                        // Fallback for jewelry
                        Log::info("Webhook orders/cancelled: Releasing jewelry ID {$product->id} via InventoryService.");
                        try {
                            $inventoryService->updateInventoryStatus($product, 'available', $store->id, $shopifyOrderId, $order->id ?? null);
                        } catch (\Throwable $e) {
                            Log::warning("Inventory status update failed or already handled for jewelry ID {$product->id}: " . $e->getMessage());
                        }
                        \App\Jobs\ReleaseInventoryAcrossStoresJob::dispatch($productType, $product->id);
                    }
                }
            }

            // Fallback for line items if no reservations exist
            if ($reservations->isEmpty()) {
                $lineItems = $payload['line_items'] ?? [];
                foreach ($lineItems as $lineItem) {
                    $variantId = $lineItem['variant_id'] ?? null;
                    if ($variantId) {
                        $shopifyProduct = \App\Models\ShopifyProduct::where('shopify_variant_id', (string)$variantId)->first();
                        if ($shopifyProduct) {
                            $product = $shopifyProduct->product;
                            $productType = $shopifyProduct->product_type;
                            if ($product) {
                                if ($productType === 'diamond') {
                                    $lockService = app(\App\Services\GlobalDiamondLockService::class);
                                    $lockService->releaseDiamond($product->id, "Shopify order cancelled (Order ID: {$shopifyOrderId}).");
                                } else {
                                    Log::info("Webhook orders/cancelled fallback: Releasing jewelry ID {$product->id} via InventoryService.");
                                    try {
                                        $inventoryService->updateInventoryStatus($product, 'available', $store->id, $shopifyOrderId, $order->id ?? null);
                                    } catch (\Throwable $e) {
                                        Log::warning("Inventory status update fallback failed or already handled for jewelry ID {$product->id}: " . $e->getMessage());
                                    }
                                    \App\Jobs\ReleaseInventoryAcrossStoresJob::dispatch($productType, $product->id);
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Handle fulfillment / order complete logic.
     */
    protected function handleOrderCompletedWithLock(array $payload, string $shopDomain, string $topic)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($payload, $shopDomain, $topic) {
            $store = \App\Models\ShopifyStore::where('shop_domain', $shopDomain)->first();
            if (!$store) {
                throw new \Exception("Shopify store not found for domain: {$shopDomain}");
            }

            $shopifyOrderId = (string) ($payload['order_id'] ?? $payload['id'] ?? '');

            $order = $this->findOrderFromWebhookPayload($payload, $topic);
            if ($order && $order->status !== 'completed') {
                $order->update([
                    'status' => 'completed',
                    'shopify_response' => array_merge($order->shopify_response ?? [], ['completed_payload' => $payload]),
                ]);

                $order->logs()->create([
                    'action' => 'Order Completed',
                    'message' => "Order marked as completed.",
                    'payload' => $payload,
                ]);
            }

            $reservations = \App\Models\ShopifyInventoryReservation::with('product')
                ->where('shopify_order_id', $shopifyOrderId)
                ->get();

            $diamondSold = false;
            $inventoryService = app(\App\Services\InventoryService::class);

            foreach ($reservations as $reservation) {
                $product = $reservation->product;
                $productType = $reservation->product_type;
                if ($product && $productType) {
                    if ($productType === \App\Models\Diamond::class || $productType === 'diamond') {
                        // Associate diamond with order first if missing
                        if ($order && !$order->diamond_id) {
                            $order->update(['diamond_id' => $product->id]);
                        }

                        $lockService = app(\App\Services\GlobalDiamondLockService::class);
                        $lockService->markSold($product->id, $store->id, $shopifyOrderId, $order->id ?? null);
                        $diamondSold = true;
                    } else {
                        // Fallback for jewelry
                        Log::info("Webhook orders/completed: Setting jewelry ID {$product->id} to SOLD.");
                        $inventoryService->updateInventoryStatus($product, 'sold', $store->id, $shopifyOrderId, $order->id ?? null);
                    }
                }
            }

            // Fallback for line items if no reservations exist
            if (!$diamondSold) {
                $lineItems = $payload['line_items'] ?? $payload['order']['line_items'] ?? [];
                foreach ($lineItems as $lineItem) {
                    $variantId = $lineItem['variant_id'] ?? null;
                    if ($variantId) {
                        $shopifyProduct = \App\Models\ShopifyProduct::where('shopify_variant_id', (string)$variantId)->first();
                        if ($shopifyProduct) {
                            $product = $shopifyProduct->product;
                            $productType = $shopifyProduct->product_type;
                            if ($product) {
                                if ($productType === 'diamond') {
                                    if ($order && !$order->diamond_id) {
                                        $order->update(['diamond_id' => $product->id]);
                                    }
                                    $lockService = app(\App\Services\GlobalDiamondLockService::class);
                                    $lockService->markSold($product->id, $store->id, $shopifyOrderId, $order->id ?? null);
                                } else {
                                    Log::info("Webhook orders/completed fallback: Setting jewelry ID {$product->id} to SOLD.");
                                    $inventoryService->updateInventoryStatus($product, 'sold', $store->id, $shopifyOrderId, $order->id ?? null);
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Handle products/delete event.
     */
    protected function handleProductDelete(array $payload)
    {
        $shopifyProductId = $payload['id'] ?? null;
        if (!$shopifyProductId) {
            return;
        }

        Log::info("Shopify Webhook: Product deleted on Shopify. Shopify ID: {$shopifyProductId}");

        $shopifyProduct = ShopifyProduct::where('shopify_product_id', $shopifyProductId)->first();
        if ($shopifyProduct) {
            $productType = $shopifyProduct->product_type;
            $productId = $shopifyProduct->product_id;
            
            // Delete the sync record entirely to mark it as Not Synced / Publishable again
            $shopifyProduct->delete();
            
            Log::info("Shopify Webhook: Deleted local mapping for {$productType} ID {$productId}. Re-sync is now allowed.");
        }
    }

    /**
     * Handle products/update event.
     */
    protected function handleProductUpdate(array $payload)
    {
        $shopifyProductId = $payload['id'] ?? null;
        if (!$shopifyProductId) {
            return;
        }

        $shopifyProduct = ShopifyProduct::where('shopify_product_id', $shopifyProductId)->first();
        if ($shopifyProduct) {
            // Update the variant ID and response store
            $variantId = $payload['variants'][0]['id'] ?? null;
            
            $shopifyProduct->update([
                'shopify_variant_id' => $variantId,
                'response' => $payload,
            ]);

            Log::info("Shopify Webhook: Product updated. Synced details locally for Shopify ID: {$shopifyProductId}");
        }
    }

    /**
     * Handle draft_orders/create event.
     */
    protected function handleDraftOrderCreate(array $payload, string $topic)
    {
        Log::info("Shopify Webhook: draft_orders/create");

        $order = $this->findOrderFromWebhookPayload($payload, $topic);
        if ($order) {
            $this->updateLocalOrderFromPayload($order, $payload, 'Webhook Update');
        }
    }

    /**
     * Handle draft_orders/update event.
     */
    protected function handleDraftOrderUpdate(array $payload, string $topic)
    {
        Log::info("Shopify Webhook: draft_orders/update");

        $order = $this->findOrderFromWebhookPayload($payload, $topic);
        if ($order) {
            $this->updateLocalOrderFromPayload($order, $payload, 'Webhook Update');
        }
    }

    /**
     * Handle orders/create event.
     */
    protected function handleOrderCreate(array $payload, string $topic)
    {
        Log::info("Shopify Webhook: orders/create");

        $order = $this->findOrderFromWebhookPayload($payload, $topic);
        if ($order) {
            $shopifyOrderId = (string) ($payload['id'] ?? '');
            $financialStatus = $payload['financial_status'] ?? null;
            $mappedStatus = $this->mapFinancialStatusToLocalStatus($financialStatus);

            // Webhook idempotency
            if ($order->shopify_order_id === $shopifyOrderId && $order->status === $mappedStatus) {
                Log::info("Webhook idempotency: Order ID {$order->id} already processed as {$mappedStatus}. Skipping.");
                return;
            }

            $shopifyOrderNumber = (string) ($payload['order_number'] ?? '');
            $shopDomain = $order->shopifyStore->shop_domain ?? '';
            $orderAdminUrl = $shopDomain ? "https://{$shopDomain}/admin/orders/{$shopifyOrderId}" : null;

            $order->update([
                'status' => $mappedStatus,
                'shopify_order_id' => $shopifyOrderId,
                'shopify_order_number' => $shopifyOrderNumber,
                'shopify_order_admin_url' => $orderAdminUrl,
                'shopify_response' => array_merge($order->shopify_response ?? [], ['order_payload' => $payload]),
            ]);

            $order->logs()->create([
                'action' => 'Order Created/Synced',
                'message' => "Order converted to Shopify Order #{$shopifyOrderNumber} and status set to {$mappedStatus}.",
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle orders/paid event.
     */
    protected function handleOrderPaid(array $payload, string $topic)
    {
        Log::info("Shopify Webhook: orders/paid");

        $order = $this->findOrderFromWebhookPayload($payload, $topic);
        if ($order) {
            $shopifyOrderId = (string) ($payload['id'] ?? '');

            // Webhook idempotency
            if ($order->shopify_order_id === $shopifyOrderId && $order->status === 'paid') {
                Log::info("Webhook idempotency: Order ID {$order->id} already processed as paid. Skipping.");
                return;
            }

            $shopifyOrderNumber = (string) ($payload['order_number'] ?? '');
            $shopDomain = $order->shopifyStore->shop_domain ?? '';
            $orderAdminUrl = $shopDomain ? "https://{$shopDomain}/admin/orders/{$shopifyOrderId}" : null;

            $order->update([
                'status' => 'paid',
                'shopify_order_id' => $shopifyOrderId,
                'shopify_order_number' => $shopifyOrderNumber,
                'shopify_order_admin_url' => $orderAdminUrl,
                'shopify_response' => array_merge($order->shopify_response ?? [], ['payment_payload' => $payload]),
            ]);

            $order->logs()->create([
                'action' => 'Payment Received',
                'message' => "Order payment received on Shopify. Shopify Order ID: {$shopifyOrderId}, Order Number: #{$shopifyOrderNumber}.",
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle orders/cancelled event.
     */
    protected function handleOrderCancelled(array $payload, string $topic)
    {
        Log::info("Shopify Webhook: orders/cancelled webhook received payload:", ['payload' => $payload]);

        $order = $this->findOrderFromWebhookPayload($payload, $topic);
        if ($order) {
            $previousStatus = $order->status;
            if ($previousStatus === 'cancelled') {
                Log::info("Shopify Webhook: Order ID {$order->id} is already cancelled. Skipping.");
                return;
            }

            Log::info("Shopify Webhook: orders/cancelled updating status. Order ID: {$order->id}, Previous status: {$previousStatus}, New status: cancelled");

            $cancelledAt = $payload['cancelled_at'] ?? null;

            $order->update([
                'status' => 'cancelled',
                'shopify_response' => array_merge($order->shopify_response ?? [], [
                    'cancelled_at' => $cancelledAt,
                    'cancelled_payload' => $payload
                ]),
            ]);

            $order->logs()->create([
                'action' => 'Order Cancelled',
                'message' => "Order was marked as cancelled on Shopify. (Shopify Cancelled At: " . ($cancelledAt ?: 'N/A') . ")",
                'payload' => $payload,
            ]);

            $cancelledCount = \App\Models\Order::where('status', 'cancelled')->count();
            Log::info("Shopify Webhook: orders/cancelled completed. Updated cancelled orders count (Super Admin scope): {$cancelledCount}");
        } else {
            Log::warning("Shopify Webhook: orders/cancelled could not find matching local order.");
        }
    }

    /**
     * Webhook Lookup Fallback Priority:
     * a. shopify_order_id
     * b. shopify_draft_id
     * c. local_order_uuid inside note_attributes
     */
    protected function findOrderFromWebhookPayload(array $payload, string $topic)
    {
        // a. shopify_order_id
        $orderId = null;
        if (isset($payload['id'])) {
            $orderId = $payload['id'];
        }
        if ($orderId) {
            $order = \App\Models\Order::where('shopify_order_id', (string) $orderId)->first();
            if ($order) {
                Log::info("Shopify Webhook Lookup: Matched local order ID {$order->id} via shopify_order_id: {$orderId}");
                return $order;
            }
        }

        // b. shopify_draft_id
        $draftId = $payload['draft_order_id'] ?? null;
        if (!$draftId && isset($payload['id']) && str_contains($topic, 'draft_orders')) {
            $draftId = $payload['id'];
        }
        if ($draftId) {
            $order = \App\Models\Order::where('shopify_draft_id', (string) $draftId)->first();
            if ($order) {
                Log::info("Shopify Webhook Lookup: Matched local order ID {$order->id} via shopify_draft_id: {$draftId}");
                return $order;
            }
        }

        // c. local_order_uuid inside note_attributes / note
        $noteAttributes = $payload['note_attributes'] ?? [];
        foreach ($noteAttributes as $attr) {
            if (($attr['name'] ?? '') === 'local_order_uuid' && !empty($attr['value'] ?? '')) {
                $order = \App\Models\Order::where('uuid', $attr['value'])->first();
                if ($order) {
                    Log::info("Shopify Webhook Lookup: Matched local order ID {$order->id} via note_attributes local_order_uuid: {$attr['value']}");
                    return $order;
                }
            }
        }

        $note = $payload['note'] ?? '';
        if (is_string($note) && preg_match('/local_order_uuid:\s*([a-f0-9\-]{36})/i', $note, $matches)) {
            $order = \App\Models\Order::where('uuid', $matches[1])->first();
            if ($order) {
                Log::info("Shopify Webhook Lookup: Matched local order ID {$order->id} via note regex local_order_uuid: {$matches[1]}");
                return $order;
            }
        }

        Log::warning("Shopify Webhook Lookup: No local order matched for payload", ['payload' => $payload]);
        return null;
    }

    /**
     * Map Shopify financial status to local order status.
     */
    protected function mapFinancialStatusToLocalStatus(?string $financialStatus): string
    {
        switch ($financialStatus) {
            case 'paid':
            case 'partially_refunded':
                return 'paid';
            case 'pending':
            case 'authorized':
            case 'partially_paid':
                return 'pending';
            case 'refunded':
            case 'voided':
                return 'cancelled';
            default:
                return 'pending';
        }
    }

    /**
     * Helper to update local order state from Shopify webhook payloads.
     */
    protected function updateLocalOrderFromPayload(\App\Models\Order $order, array $payload, string $action)
    {
        $status = $order->status;
        $shopifyStatus = $payload['status'] ?? null;

        if ($shopifyStatus === 'completed') {
            $status = 'completed';
        } elseif ($shopifyStatus === 'invoice_sent') {
            $status = 'invoice_sent';
        } elseif (!empty($payload['invoice_url']) && !in_array($status, ['completed', 'invoice_sent', 'paid', 'cancelled'])) {
            $status = 'synced';
        }

        $order->update([
            'status' => $status,
            'invoice_url' => $payload['invoice_url'] ?? $order->invoice_url,
            'shopify_response' => $payload,
        ]);

        $order->logs()->create([
            'action' => $action,
            'message' => "Order details updated via Shopify webhook (Shopify Status: '{$shopifyStatus}', Local Status: '{$status}')",
            'payload' => $payload,
        ]);
    }

    /**
     * Process webhook payload in the queue.
     */
    public function processWebhookPayload(string $topic, array $payload, string $shopDomain)
    {
        $orderCreatedAt = null;
        if (isset($payload['created_at'])) {
            $orderCreatedAt = $payload['created_at'];
        } elseif (isset($payload['order']['created_at'])) {
            $orderCreatedAt = $payload['order']['created_at'];
        }

        \App\Services\InventoryService::$currentProcessingOrderCreatedAt = $orderCreatedAt;

        try {
            switch ($topic) {
                case 'products/delete':
                    $this->handleProductDelete($payload);
                    break;

                case 'products/update':
                    $this->handleProductUpdate($payload);
                    event(new \App\Events\ProductCreatedEvent($payload));
                    break;

                case 'products/create':
                    event(new \App\Events\ProductCreatedEvent($payload));
                    break;

                case 'draft_orders/create':
                    $this->handleDraftOrderCreate($payload, $topic);
                    break;

                case 'draft_orders/update':
                    $this->handleDraftOrderUpdate($payload, $topic);
                    break;

                case 'orders/create':
                    $this->handleOrderCreateWithLock($payload, $shopDomain, $topic);
                    $this->syncOrderToSuperAdmin($payload, $shopDomain);
                    break;

                case 'orders/updated':
                    $this->syncOrderToSuperAdmin($payload, $shopDomain);
                    break;

                case 'orders/cancelled':
                    $this->handleOrderCancelledWithLock($payload, $shopDomain, $topic);
                    $this->syncOrderToSuperAdmin($payload, $shopDomain);
                    break;

                case 'orders/paid':
                    $this->handleOrderPaid($payload, $topic);
                    $this->syncOrderToSuperAdmin($payload, $shopDomain);
                    break;

                case 'fulfillments/create':
                    $this->handleOrderCompletedWithLock($payload, $shopDomain, $topic);
                    $this->syncOrderToSuperAdmin($payload, $shopDomain);
                    break;

                case 'inventory_levels/connect':
                case 'inventory_levels/update':
                case 'inventory_levels/set':
                    $this->syncInventoryToShopifyInventories($payload, $shopDomain);
                    break;

                default:
                    Log::channel('shopify')->info("Unhandled Shopify Webhook Topic in Job: {$topic}");
                    break;
            }
        } finally {
            \App\Services\InventoryService::$currentProcessingOrderCreatedAt = null;
        }
    }

    /**
     * Sync Shopify order details to super admin shopify_orders table.
     */
    protected function syncOrderToSuperAdmin(array $payload, string $shopDomain)
    {
        $store = \App\Models\ShopifyStore::where('shop_domain', $shopDomain)->first();
        if (!$store) {
            Log::channel('shopify')->warning("syncOrderToSuperAdmin: Store not found for domain {$shopDomain}");
            return;
        }

        $shopifyOrderId = (string) ($payload['id'] ?? '');
        if (!$shopifyOrderId) {
            return;
        }

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

        $order = \App\Models\ShopifyOrder::where('shopify_store_id', $store->id)
            ->where('shopify_order_id', $shopifyOrderId)
            ->first();

        if ($order) {
            $order->update($orderData);
            event(new \App\Events\OrderUpdatedEvent($order));
        } else {
            $order = \App\Models\ShopifyOrder::create($orderData);
            event(new \App\Events\OrderCreatedEvent($order));
        }
    }

    /**
     * Sync inventory webhook updates to shopify_inventories table.
     */
    protected function syncInventoryToShopifyInventories(array $payload, string $shopDomain)
    {
        $store = \App\Models\ShopifyStore::where('shop_domain', $shopDomain)->first();
        if (!$store) {
            return;
        }

        $inventoryItemId = (string) ($payload['inventory_item_id'] ?? '');
        if (!$inventoryItemId) {
            return;
        }

        $shopifyProduct = \App\Models\ShopifyProduct::where('shopify_store_id', $store->id)
            ->where(function($query) use ($inventoryItemId) {
                $query->where('response->variants->0->inventory_item_id', $inventoryItemId)
                      ->orWhere('response->inventory_item_id', $inventoryItemId);
            })->first();

        $variantId = $shopifyProduct ? $shopifyProduct->shopify_variant_id : 'unknown';
        $productId = $shopifyProduct ? $shopifyProduct->shopify_product_id : 'unknown';
        $sku = $shopifyProduct ? $shopifyProduct->product_reference_id : null;

        $inventory = \App\Models\ShopifyInventory::updateOrCreate(
            [
                'shopify_store_id' => $store->id,
                'inventory_item_id' => $inventoryItemId,
            ],
            [
                'shopify_product_id' => $productId,
                'shopify_variant_id' => $variantId,
                'sku' => $sku,
                'available' => intval($payload['available'] ?? 0),
            ]
        );

        event(new \App\Events\InventoryUpdatedEvent($inventory));
    }
}


