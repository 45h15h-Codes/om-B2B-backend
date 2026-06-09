<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyStore;
use App\Models\ShopifyProduct;
use App\Models\ShopifyWebhookLog;
use App\Models\ShopifyInventoryReservation;
use App\Jobs\UnpublishProductFromStoreJob;
use App\Jobs\PublishProductToStoreJob;
use App\Jobs\DeleteProductFromStoreJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

class ShopifyInventoryHoldTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminUser($role = 'normal_admin')
    {
        return User::firstOrCreate(
            ['email' => $role === 'super_admin' ? 'super_hold@omgems.com' : 'admin_hold@omgems.com'],
            [
                'name' => $role === 'super_admin' ? 'OM Super Hold' : 'OM Admin Hold',
                'password' => bcrypt('password'),
                'role' => $role
            ]
        );
    }

    /**
     * Test webhook HMAC signature verification in production-like environment.
     */
    public function test_webhook_hmac_signature_verification()
    {
        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Store 1',
            'shop_domain' => 'store1.myshopify.com',
            'access_token' => 'shpat_token1',
            'webhook_secret' => 'supersecret123',
        ]);

        $payload = ['id' => 12345, 'line_items' => []];
        $jsonPayload = json_encode($payload);

        // Temporarily set env to production to enforce verification
        config(['app.env' => 'production']);

        // 1. Send request with missing signature
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/create',
                'X-Shopify-Shop-Domain' => 'store1.myshopify.com',
                'X-Shopify-Webhook-Id' => 'webhook_signature_test',
            ]
        );
        $response->assertStatus(401);

        // 2. Send request with invalid signature
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/create',
                'X-Shopify-Shop-Domain' => 'store1.myshopify.com',
                'X-Shopify-Webhook-Id' => 'webhook_signature_test',
                'X-Shopify-Hmac-Sha256' => 'invalidsignaturehere',
            ]
        );
        $response->assertStatus(401);

        // 3. Send request with valid signature
        $validSignature = base64_encode(hash_hmac('sha256', $jsonPayload, 'supersecret123', true));
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/create',
                'X-Shopify-Shop-Domain' => 'store1.myshopify.com',
                'X-Shopify-Webhook-Id' => 'webhook_signature_test',
                'X-Shopify-Hmac-Sha256' => $validSignature,
            ]
        );
        $response->assertStatus(202);

        // Reset config
        config(['app.env' => 'testing']);
    }

    /**
     * Test webhook idempotency using X-Shopify-Webhook-Id header.
     */
    public function test_webhook_idempotency_check()
    {
        \Illuminate\Support\Facades\Queue::fake([
            \App\Jobs\LockInventoryAcrossStoresJob::class,
            \App\Jobs\ReleaseInventoryAcrossStoresJob::class,
        ]);

        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Store 1',
            'shop_domain' => 'store1.myshopify.com',
            'access_token' => 'shpat_token1',
        ]);

        $diamond = Diamond::withoutEvents(function () use ($user) {
            return Diamond::create([
                'stock_no' => 'WD-HOLD-001',
                'asking_price' => 2000.00,
                'shape' => 'Round',
                'size' => 1.5,
                'user_id' => $user->id,
                'created_by' => 'Normal Admin',
                'status' => 'Approved',
                'inventory_status' => 'available',
            ]);
        });

        $shopifyProduct = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => '999888',
            'shopify_variant_id' => '111222',
            'sync_status' => 'synced',
        ]);

        $payload = [
            'id' => 998877,
            'order_number' => '1001',
            'total_price' => '2000.00',
            'line_items' => [
                [
                    'product_id' => 999888,
                    'variant_id' => 111222,
                    'quantity' => 1,
                    'price' => '2000.00'
                ]
            ]
        ];

        $webhookId = 'webhook_idempotency_unique_123';

        // First call
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/create',
                'X-Shopify-Shop-Domain' => 'store1.myshopify.com',
                'X-Shopify-Webhook-Id' => $webhookId,
            ]
        );
        $response->assertStatus(202);
        
        $this->assertDatabaseHas('shopify_webhook_logs', [
            'webhook_id' => $webhookId,
            'status' => 'processed'
        ]);

        $this->assertEquals(1, ShopifyInventoryReservation::count());

        // Second call with same webhook ID
        $response2 = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/create',
                'X-Shopify-Shop-Domain' => 'store1.myshopify.com',
                'X-Shopify-Webhook-Id' => $webhookId,
            ]
        );
        
        $response2->assertStatus(200);
        $response2->assertJsonFragment(['message' => 'Already processed']);

        // Assert no duplicate reservation was created
        $this->assertEquals(1, ShopifyInventoryReservation::count());
    }

    /**
     * Test full state transitions: orders/create (HOLD) -> orders/paid (SOLD)
     */
    public function test_state_flow_create_to_paid_for_diamond()
    {
        Queue::fake([
            \App\Jobs\UnpublishProductFromStoreJob::class,
            \App\Jobs\DeleteProductFromStoreJob::class,
            \App\Jobs\LockInventoryAcrossStoresJob::class,
            \App\Jobs\ReleaseInventoryAcrossStoresJob::class,
        ]);

        $user = $this->getAdminUser('normal_admin');
        
        // 3 stores connected
        $storeA = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Store A',
            'shop_domain' => 'store-a.myshopify.com',
            'access_token' => 'shpat_tokena',
        ]);
        $storeB = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Store B',
            'shop_domain' => 'store-b.myshopify.com',
            'access_token' => 'shpat_tokenb',
        ]);
        $storeC = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Store C',
            'shop_domain' => 'store-c.myshopify.com',
            'access_token' => 'shpat_tokenc',
        ]);

        $diamond = Diamond::withoutEvents(function () use ($user) {
            return Diamond::create([
                'stock_no' => 'D1001',
                'asking_price' => 1500.00,
                'shape' => 'Round',
                'size' => 1.2,
                'user_id' => $user->id,
                'created_by' => 'Normal Admin',
                'status' => 'Approved',
                'inventory_status' => 'available',
            ]);
        });

        // Synced across all three stores
        ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $storeA->id,
            'shopify_product_id' => '111',
            'shopify_variant_id' => '112',
            'sync_status' => 'synced',
        ]);
        ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $storeB->id,
            'shopify_product_id' => '222',
            'shopify_variant_id' => '223',
            'sync_status' => 'synced',
        ]);
        ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $storeC->id,
            'shopify_product_id' => '333',
            'shopify_variant_id' => '334',
            'sync_status' => 'synced',
        ]);

        $payload = [
            'id' => 999111,
            'order_number' => 'A-1001',
            'total_price' => '1800.00',
            'subtotal_price' => '1800.00',
            'email' => 'customer@gmail.com',
            'line_items' => [
                [
                    'product_id' => 111,
                    'variant_id' => 112,
                    'quantity' => 1,
                    'price' => '1800.00'
                ]
            ]
        ];

        // --- STEP 1: Send orders/create from Store A ---
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/create',
                'X-Shopify-Shop-Domain' => 'store-a.myshopify.com',
                'X-Shopify-Webhook-Id' => 'wb_create_1',
            ]
        );

        $response->assertStatus(202);
        // Verify status is HOLD (now on_hold in multistore sync)
        $diamond->refresh();
        $this->assertEquals('on_hold', $diamond->inventory_status);
        // Verify local Order was auto-created
        $this->assertDatabaseHas('orders', [
            'shopify_order_id' => '999111',
            'shopify_store_id' => $storeA->id,
        ]);

        // Verify reservation is active
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $storeA->id,
            'shopify_order_id' => '999111',
            'status' => 'hold',
        ]);
        // Verify LockInventoryAcrossStoresJob dispatched to lock across stores
        Queue::assertPushed(\App\Jobs\LockInventoryAcrossStoresJob::class, function ($job) use ($diamond, $storeA) {
            return $job->productType === 'diamond' && $job->productId === $diamond->id && $job->originStoreId === $storeA->id;
        });
        // --- STEP 2: Send orders/paid from Store A (remains on_hold) ---
        $response2 = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/paid',
                'X-Shopify-Shop-Domain' => 'store-a.myshopify.com',
                'X-Shopify-Webhook-Id' => 'wb_paid_1',
            ]
        );

        $response2->assertStatus(202);

        $diamond->refresh();
        $this->assertEquals('on_hold', $diamond->inventory_status);

        // --- STEP 3: Send fulfillments/create from Store A (transitions to sold) ---
        $fulfillmentPayload = [
            'id' => 'ful_111',
            'order_id' => 999111,
            'line_items' => [
                [
                    'product_id' => 111,
                    'variant_id' => 112,
                    'quantity' => 1,
                    'price' => '1800.00'
                ]
            ]
        ];

        $response3 = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $fulfillmentPayload,
            [
                'X-Shopify-Topic' => 'fulfillments/create',
                'X-Shopify-Shop-Domain' => 'store-a.myshopify.com',
                'X-Shopify-Webhook-Id' => 'wb_fulfill_1',
            ]
        );

        $response3->assertStatus(202);

        // Verify status is SOLD
        $diamond->refresh();
        $this->assertEquals('sold', $diamond->inventory_status);

        // Verify reservation completed
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $storeA->id,
            'status' => 'completed',
        ]);

        // Verify delete jobs dispatched to all stores
        Queue::assertPushed(DeleteProductFromStoreJob::class, function ($job) {
            return in_array($job->shopifyProductId, ['111', '222', '333']);
        });
    }

    /**
     * Test full state transitions: orders/create (HOLD) -> orders/cancelled (AVAILABLE)
     */
    public function test_state_flow_create_to_cancelled_for_jewelry()
    {
        Queue::fake([
            \App\Jobs\PublishProductToStoreJob::class,
            \App\Jobs\UnpublishProductFromStoreJob::class,
            \App\Jobs\LockInventoryAcrossStoresJob::class,
            \App\Jobs\ReleaseInventoryAcrossStoresJob::class,
        ]);

        $user = $this->getAdminUser('normal_admin');
        
        $storeA = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Store A',
            'shop_domain' => 'store-a.myshopify.com',
            'access_token' => 'shpat_tokena',
        ]);
        $storeB = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Store B',
            'shop_domain' => 'store-b.myshopify.com',
            'access_token' => 'shpat_tokenb',
        ]);

        $jewelry = Jewelery::create([
            'sku' => 'J1001',
            'name' => 'Gold Ring',
            'type' => 'Ring',
            'price' => 500.00,
            'location' => 'Surat, India',
            'user_id' => $user->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        // Synced across Store A & B
        ShopifyProduct::create([
            'product_type' => 'jewelry',
            'product_id' => $jewelry->id,
            'shopify_store_id' => $storeA->id,
            'shopify_product_id' => 'jewel_111',
            'shopify_variant_id' => 'jewel_112',
            'sync_status' => 'synced',
        ]);
        ShopifyProduct::create([
            'product_type' => 'jewelry',
            'product_id' => $jewelry->id,
            'shopify_store_id' => $storeB->id,
            'shopify_product_id' => 'jewel_222',
            'shopify_variant_id' => 'jewel_223',
            'sync_status' => 'synced',
        ]);

        $payload = [
            'id' => 999222,
            'order_number' => 'A-1002',
            'total_price' => '500.00',
            'subtotal_price' => '500.00',
            'email' => 'customer@gmail.com',
            'line_items' => [
                [
                    'product_id' => 'jewel_111',
                    'variant_id' => 'jewel_112',
                    'quantity' => 1,
                    'price' => '500.00'
                ]
            ]
        ];

        // --- STEP 1: Hold Flow ---
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/create',
                'X-Shopify-Shop-Domain' => 'store-a.myshopify.com',
                'X-Shopify-Webhook-Id' => 'wb_create_2',
            ]
        );
        $response->assertStatus(202);

        $jewelry->refresh();
        $this->assertEquals('on_hold', $jewelry->inventory_status);

        // Verify generic LockInventoryAcrossStoresJob was dispatched
        Queue::assertPushed(\App\Jobs\LockInventoryAcrossStoresJob::class, function ($job) use ($jewelry, $storeA) {
            return $job->productType === 'jewelry' && $job->productId === $jewelry->id && $job->originStoreId === $storeA->id;
        });

        // --- STEP 2: Cancel Flow ---
        $response2 = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/cancelled',
                'X-Shopify-Shop-Domain' => 'store-a.myshopify.com',
                'X-Shopify-Webhook-Id' => 'wb_cancel_2',
            ]
        );
        $response2->assertStatus(202);

        // Verify status reverted to AVAILABLE
        $jewelry->refresh();
        $this->assertEquals('available', $jewelry->inventory_status);

        // Verify reservation was released
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_type' => 'jewelry',
            'product_id' => $jewelry->id,
            'status' => 'released',
        ]);

        // Verify generic ReleaseInventoryAcrossStoresJob was dispatched
        Queue::assertPushed(\App\Jobs\ReleaseInventoryAcrossStoresJob::class, function ($job) use ($jewelry) {
            return $job->productType === 'jewelry' && $job->productId === $jewelry->id;
        });
    }

    /**
     * Test race condition prevention by trying to place a double hold.
     */
    public function test_race_condition_protection_double_hold()
    {
        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Store 1',
            'shop_domain' => 'store1.myshopify.com',
            'access_token' => 'shpat_token1',
        ]);

        $diamond = Diamond::withoutEvents(function () use ($user) {
            return Diamond::create([
                'stock_no' => 'D2002',
                'asking_price' => 3000.00,
                'shape' => 'Oval',
                'size' => 2.0,
                'user_id' => $user->id,
                'created_by' => 'Normal Admin',
                'status' => 'Approved',
                'inventory_status' => 'available',
            ]);
        });

        $inventoryService = app(\App\Services\InventoryService::class);

        // First hold succeeds
        $inventoryService->updateInventoryStatus($diamond, 'hold', $store->id, 'shopify_order_1', null);
        $this->assertEquals('on_hold', $diamond->fresh()->inventory_status);

        // Second hold on the same item with different order ID must throw an exception (preventing duplicate sale)
        $this->expectException(\Exception::class);
        $inventoryService->updateInventoryStatus($diamond, 'hold', $store->id, 'shopify_order_2', null);
    }

    /**
     * Test order deletion by Super Admin reverts active holds to AVAILABLE.
     */
    public function test_super_admin_order_deletion_reverts_hold_to_available()
    {
        Queue::fake();

        $super = $this->getAdminUser('super_admin');
        $normal = $this->getAdminUser('normal_admin');

        $store = ShopifyStore::create([
            'user_id' => $normal->id,
            'store_name' => 'Store 1',
            'shop_domain' => 'store1.myshopify.com',
            'access_token' => 'shpat_token1',
        ]);

        $diamond = Diamond::withoutEvents(function () use ($normal) {
            return Diamond::create([
                'stock_no' => 'D-DEL-HOLD',
                'asking_price' => 1000.00,
                'shape' => 'Round',
                'size' => 1.0,
                'user_id' => $normal->id,
                'created_by' => 'Normal Admin',
                'status' => 'Approved',
                'inventory_status' => 'available',
            ]);
        });

        // Set up Shopify product mapping
        ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => 'prod_del_hold',
            'shopify_variant_id' => 'var_del_hold',
            'sync_status' => 'synced',
        ]);

        // Create an order
        $order = \App\Models\Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shopify_store_id' => $store->id,
            'shopify_store_snapshot' => ['store_name' => 'Store 1', 'shop_domain' => 'store1.myshopify.com'],
            'email' => 'cust@test.com',
            'items' => [
                [
                    'product_type' => 'diamond',
                    'product_id' => $diamond->id,
                    'stock_no' => $diamond->stock_no,
                    'price_snapshot' => 1000.00,
                    'quantity' => 1,
                ]
            ],
            'subtotal' => 1000.00,
            'total' => 1000.00,
            'status' => 'paid',
            'shopify_order_id' => 'shopify_ord_del_hold',
            'created_by' => $normal->id,
        ]);

        // Place on hold
        $inventoryService = app(\App\Services\InventoryService::class);
        $inventoryService->updateInventoryStatus($diamond, 'hold', $store->id, 'shopify_ord_del_hold', $order->id);

        $this->assertEquals('on_hold', $diamond->fresh()->inventory_status);
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_id' => $diamond->id,
            'order_id' => $order->id,
            'status' => 'hold',
        ]);

        // Delete order as Super Admin
        $response = $this->actingAs($super)
            ->withSession(['admin_role' => 'super_admin'])
            ->delete(route('orders.destroy', $order->id));

        $response->assertRedirect(route('orders.index'));

        // Assert order soft deleted
        $this->assertSoftDeleted('orders', ['id' => $order->id]);

        // Assert reservation released
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_id' => $diamond->id,
            'order_id' => $order->id,
            'status' => 'released',
        ]);

        // Assert diamond inventory status reverted to available
        $this->assertEquals('available', $diamond->fresh()->inventory_status);
    }

    /**
     * Test order deletion by Super Admin does not revert SOLD status.
     */
    public function test_super_admin_order_deletion_keeps_sold_status()
    {
        Queue::fake();

        $super = $this->getAdminUser('super_admin');
        $normal = $this->getAdminUser('normal_admin');

        $store = ShopifyStore::create([
            'user_id' => $normal->id,
            'store_name' => 'Store 1',
            'shop_domain' => 'store1.myshopify.com',
            'access_token' => 'shpat_token1',
        ]);

        $diamond = Diamond::withoutEvents(function () use ($normal) {
            return Diamond::create([
                'stock_no' => 'D-DEL-SOLD',
                'asking_price' => 1200.00,
                'shape' => 'Emerald',
                'size' => 1.2,
                'user_id' => $normal->id,
                'created_by' => 'Normal Admin',
                'status' => 'Approved',
                'inventory_status' => 'available',
            ]);
        });

        // Set up Shopify product mapping
        ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => 'prod_del_sold',
            'shopify_variant_id' => 'var_del_sold',
            'sync_status' => 'synced',
        ]);

        // Create an order
        $order = \App\Models\Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shopify_store_id' => $store->id,
            'shopify_store_snapshot' => ['store_name' => 'Store 1', 'shop_domain' => 'store1.myshopify.com'],
            'email' => 'cust@test.com',
            'items' => [
                [
                    'product_type' => 'diamond',
                    'product_id' => $diamond->id,
                    'stock_no' => $diamond->stock_no,
                    'price_snapshot' => 1200.00,
                    'quantity' => 1,
                ]
            ],
            'subtotal' => 1200.00,
            'total' => 1200.00,
            'status' => 'completed',
            'shopify_order_id' => 'shopify_ord_del_sold',
            'created_by' => $normal->id,
        ]);

        // Place on hold then sold
        $inventoryService = app(\App\Services\InventoryService::class);
        $inventoryService->updateInventoryStatus($diamond, 'hold', $store->id, 'shopify_ord_del_sold', $order->id);
        $inventoryService->updateInventoryStatus($diamond, 'sold', $store->id, 'shopify_ord_del_sold', $order->id);

        $this->assertEquals('sold', $diamond->fresh()->inventory_status);
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_id' => $diamond->id,
            'order_id' => $order->id,
            'status' => 'completed',
        ]);

        // Delete order as Super Admin
        $response = $this->actingAs($super)
            ->withSession(['admin_role' => 'super_admin'])
            ->delete(route('orders.destroy', $order->id));

        $response->assertRedirect(route('orders.index'));

        // Assert order soft deleted
        $this->assertSoftDeleted('orders', ['id' => $order->id]);

        // Assert reservation remains completed
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_id' => $diamond->id,
            'order_id' => $order->id,
            'status' => 'completed',
        ]);

        // Assert diamond inventory status remains sold
        $this->assertEquals('sold', $diamond->fresh()->inventory_status);
    }
}
