<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Diamond;
use App\Models\ShopifyProduct;
use App\Models\ShopifyStore;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShopifyWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminUser($role = 'normal_admin')
    {
        return User::firstOrCreate(
            ['email' => $role === 'super_admin' ? 'super_webhook@omgems.com' : 'admin_webhook@omgems.com'],
            [
                'name' => $role === 'super_admin' ? 'OM Webhook Super' : 'OM Webhook Normal',
                'password' => bcrypt('password'),
                'role' => $role
            ]
        );
    }

    /**
     * Test products/delete webhook removes the local mapping record.
     */
    public function test_webhook_product_delete_removes_local_mapping()
    {
        $user = $this->getAdminUser('normal_admin');
        
        // Disable event dispatches for model creation during tests to avoid queueing real API jobs
        $diamond = Diamond::withoutEvents(function () use ($user) {
            return Diamond::create([
                'stock_no' => 'WD-TEST-001',
                'asking_price' => 1500.00,
                'shape' => 'Round',
                'size' => 1.0,
                'user_id' => $user->id,
                'created_by' => 'Normal Admin',
                'status' => 'Approved'
            ]);
        });

        // Create the local Shopify Product mapping link
        $shopifyProduct = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_product_id' => '999888777',
            'shopify_variant_id' => '111222333',
            'shopify_product_url' => 'https://test-normal-admin-store.myshopify.com/admin/products/999888777',
            'sync_status' => 'synced',
            'sync_attempts' => 1,
            'synced_at' => now(),
        ]);

        $this->assertDatabaseHas('shopify_products', [
            'shopify_product_id' => '999888777',
        ]);

        // Send simulated Shopify products/delete webhook
        $response = $this->postJson('/api/shopify/webhooks', [
            'id' => 999888777
        ], [
            'X-Shopify-Topic' => 'products/delete',
            'X-Shopify-Shop-Domain' => 'test-normal-admin-store.myshopify.com',
        ]);

        $response->assertStatus(202);
        $response->assertJson(['status' => 'queued']);

        // Assert that the sync record is deleted from the database
        $this->assertDatabaseMissing('shopify_products', [
            'shopify_product_id' => '999888777',
        ]);
    }

    /**
     * Test that loading the Shopify dashboard on-the-fly cleans up synced products that no longer exist in Shopify.
     */
    public function test_verify_and_cleanup_products_on_dashboard_load()
    {
        $user = $this->getAdminUser('normal_admin');
        
        // Create store connection
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Test Normal Admin Store',
            'shop_domain' => 'test-normal-admin-store.myshopify.com',
            'access_token' => 'shpat_testaccesstoken12345',
            'is_active' => true,
        ]);
        
        $user->update([
            'active_shopify_store_id' => $store->id
        ]);

        $diamond1 = Diamond::withoutEvents(function () use ($user) {
            return Diamond::create([
                'stock_no' => 'WD-TEST-001',
                'asking_price' => 1000.00,
                'shape' => 'Round',
                'size' => 1.0,
                'user_id' => $user->id,
                'created_by' => 'Normal Admin',
                'status' => 'Approved'
            ]);
        });

        $diamond2 = Diamond::withoutEvents(function () use ($user) {
            return Diamond::create([
                'stock_no' => 'WD-TEST-002',
                'asking_price' => 2000.00,
                'shape' => 'Oval',
                'size' => 1.5,
                'user_id' => $user->id,
                'created_by' => 'Normal Admin',
                'status' => 'Approved'
            ]);
        });

        // Synced product 1 (still exists on Shopify)
        ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond1->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => '11111',
            'shopify_variant_id' => '11112',
            'shopify_product_url' => 'https://test-normal-admin-store.myshopify.com/admin/products/11111',
            'sync_status' => 'synced',
        ]);

        // Synced product 2 (deleted on Shopify)
        ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond2->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => '22222',
            'shopify_variant_id' => '22223',
            'shopify_product_url' => 'https://test-normal-admin-store.myshopify.com/admin/products/22222',
            'sync_status' => 'synced',
        ]);

        // Mock the Shopify API response to return only product 1 (meaning product 2 was deleted)
        Http::fake([
            '*products.json*' => Http::response([
                'products' => [
                    ['id' => 11111]
                ]
            ], 200),
            '*shop.json*' => Http::response(['shop' => ['id' => 123]], 200)
        ]);

        // Load the Shopify dashboard as Normal Admin
        $response = $this->actingAs($user)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/shopify');

        $response->assertStatus(200);

        // Assert product 1 is still synced in the database
        $this->assertDatabaseHas('shopify_products', [
            'shopify_product_id' => '11111',
        ]);

        // Assert product 2 was automatically deleted/unsynced from database
        $this->assertDatabaseMissing('shopify_products', [
            'shopify_product_id' => '22222',
        ]);
    }

    /**
     * Test that fresh order webhooks trigger notifications, but historical order webhooks suppress them.
     */
    public function test_historical_order_suppresses_notifications()
    {
        $user = $this->getAdminUser('super_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Test Normal Admin Store',
            'shop_domain' => 'test-normal-admin-store.myshopify.com',
            'access_token' => 'shpat_testaccesstoken12345',
            'is_active' => true,
        ]);

        $diamond = Diamond::withoutEvents(function () use ($user) {
            return Diamond::create([
                'stock_no' => 'WD-TEST-002',
                'asking_price' => 1200.00,
                'shape' => 'Round',
                'size' => 1.0,
                'user_id' => $user->id,
                'created_by' => 'Normal Admin',
                'status' => 'Approved',
                'inventory_status' => 'available'
            ]);
        });

        // 1. Process a fresh order webhook (created now)
        $freshPayload = [
            'id' => 'fresh_order_123',
            'order_number' => '1001',
            'created_at' => now()->toIso8601String(),
            'total_price' => '1200.00',
            'line_items' => [
                [
                    'id' => 'li_1',
                    'variant_id' => 'var_1',
                    'product_id' => 'prod_1',
                    'sku' => 'WD-TEST-002',
                    'quantity' => 1,
                    'price' => '1200.00',
                    'title' => 'Test Diamond'
                ]
            ]
        ];

        // Process webhook payload directly
        $webhookController = app(\App\Http\Controllers\ShopifyWebhookController::class);
        $webhookController->processWebhookPayload('orders/create', $freshPayload, 'test-normal-admin-store.myshopify.com');

        // Verify that notifications were sent to the database
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => 'App\Notifications\NewShopifyOrderNotification'
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => 'App\Notifications\HoldAppliedNotification'
        ]);

        // Clean up notifications for next step
        \Illuminate\Support\Facades\DB::table('notifications')->truncate();
        $diamond->refresh();
        $diamond->update(['inventory_status' => 'available']);
        \App\Models\ShopifyInventoryReservation::truncate();

        // 2. Process a historical order webhook (created 20 minutes ago)
        $historicalPayload = [
            'id' => 'hist_order_123',
            'order_number' => '1002',
            'created_at' => now()->subMinutes(20)->toIso8601String(),
            'total_price' => '1200.00',
            'line_items' => [
                [
                    'id' => 'li_2',
                    'variant_id' => 'var_1',
                    'product_id' => 'prod_1',
                    'sku' => 'WD-TEST-002',
                    'quantity' => 1,
                    'price' => '1200.00',
                    'title' => 'Test Diamond'
                ]
            ]
        ];

        $webhookController->processWebhookPayload('orders/create', $historicalPayload, 'test-normal-admin-store.myshopify.com');

        // Verify that NO notifications were sent for the historical order
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $user->id,
            'type' => 'App\Notifications\NewShopifyOrderNotification'
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $user->id,
            'type' => 'App\Notifications\HoldAppliedNotification'
        ]);

        // Verify that the inventory state transition still succeeded (was set to hold)
        $diamond->refresh();
        $this->assertEquals('on_hold', $diamond->inventory_status);

        // Verify reservation was created
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'shopify_order_id' => 'hist_order_123',
            'status' => 'hold'
        ]);
    }

    /**
     * Test that manual sync of historical orders does not generate database notifications.
     */
    public function test_manual_sync_of_historical_orders_suppresses_notifications()
    {
        $user = $this->getAdminUser('super_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Test Store Sync',
            'shop_domain' => 'test-store-sync.myshopify.com',
            'access_token' => 'shpat_testaccesstoken12345',
            'is_active' => true,
        ]);

        $diamond = Diamond::withoutEvents(function () use ($user) {
            return Diamond::create([
                'stock_no' => 'WD-SYNC-001',
                'asking_price' => 1800.00,
                'shape' => 'Round',
                'size' => 1.05,
                'user_id' => $user->id,
                'created_by' => 'Normal Admin',
                'status' => 'Approved',
                'inventory_status' => 'available'
            ]);
        });

        // Setup mock response for Shopify API returning an old order
        $oldOrderPayload = [
            'id' => '999111',
            'order_number' => '1500',
            'created_at' => now()->subDays(5)->toIso8601String(), // 5 days ago
            'total_price' => '1800.00',
            'financial_status' => 'paid',
            'fulfillment_status' => null,
            'line_items' => [
                [
                    'id' => 'li_sync_1',
                    'variant_id' => 'var_sync_1',
                    'product_id' => 'prod_sync_1',
                    'sku' => 'WD-SYNC-001',
                    'quantity' => 1,
                    'price' => '1800.00',
                    'title' => 'Sync Test Diamond'
                ]
            ]
        ];

        Http::fake([
            '*/admin/api/2025-10/orders.json*' => Http::response([
                'orders' => [$oldOrderPayload]
            ], 200),
            '*/admin/api/2025-10/products.json*' => Http::response([
                'products' => []
            ], 200)
        ]);

        // Run syncOrders
        $syncService = app(\App\Services\ShopifySyncService::class);
        $result = $syncService->syncOrders($store->id);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(1, $result['records_processed']);

        // Assert no notification was sent to database
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $user->id
        ]);

        // Verify product state transition still happened (ends up as sold because order is paid)
        $diamond->refresh();
        $this->assertEquals('sold', $diamond->inventory_status);

        // Verify reservation was completed
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'shopify_order_id' => '999111',
            'status' => 'completed'
        ]);
    }
}
