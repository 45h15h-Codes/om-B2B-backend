<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Diamond;
use App\Models\ShopifyStore;
use App\Models\ShopifyProduct;
use App\Models\ShopifyInventoryReservation;
use App\Models\ShopifyInventoryAudit;
use App\Models\ShopifyWebhookIdempotency;
use App\Models\Jewelery;
use App\Jobs\LockInventoryAcrossStoresJob;
use App\Jobs\ReleaseInventoryAcrossStoresJob;
use App\Services\CrossStoreInventorySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class ShopifyCrossStoreInventorySyncTest extends TestCase
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
     * Test stock_no uniqueness check and validation on creation & update.
     */
    public function test_diamond_stock_no_uniqueness_validation()
    {
        $user = $this->getAdminUser('normal_admin');

        $d1 = Diamond::create([
            'stock_no' => 'DIA-UNIQUE-123',
            'asking_price' => 1000,
            'shape' => 'Round',
            'size' => 1.0,
            'user_id' => $user->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        // Trying to create another with same stock_no should fail at DB level (Unique constraint)
        $this->expectException(\Illuminate\Database\QueryException::class);
        Diamond::create([
            'stock_no' => 'DIA-UNIQUE-123',
            'asking_price' => 2000,
            'shape' => 'Pear',
            'size' => 1.5,
            'user_id' => $user->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);
    }

    /**
     * Test webhook order create triggers lock and dispatches background job.
     */
    public function test_webhook_order_create_locks_and_dispatches_job()
    {
        Queue::fake([
            LockInventoryAcrossStoresJob::class,
            \App\Jobs\UnpublishProductFromStoreJob::class,
        ]);

        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'OM Gems',
            'shop_domain' => 'om-gems.myshopify.com',
            'access_token' => 'token1',
        ]);

        $diamond = Diamond::create([
            'stock_no' => 'DIA0001',
            'asking_price' => 5000,
            'shape' => 'Round',
            'size' => 1.5,
            'user_id' => $user->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        $mapping = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => '1001',
            'shopify_variant_id' => '2001',
            'sync_status' => 'synced',
        ]);

        $payload = [
            'id' => 999333,
            'order_number' => 'OM-1001',
            'line_items' => [
                [
                    'product_id' => 1001,
                    'variant_id' => 2001,
                    'quantity' => 1,
                    'sku' => 'DIA0001',
                ]
            ]
        ];

        // Webhook POST
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/create',
                'X-Shopify-Shop-Domain' => 'om-gems.myshopify.com',
                'X-Shopify-Webhook-Id' => 'webhook_unique_999333',
            ]
        );

        $response->assertStatus(202);

        // Verify status changed to on_hold immediately
        $diamond->refresh();
        $this->assertEquals('on_hold', $diamond->inventory_status);

        // Verify webhook registered in idempotency table
        $this->assertTrue(ShopifyWebhookIdempotency::where('webhook_id', 'webhook_unique_999333')->exists());

        // Verify Lock Job was dispatched
        Queue::assertPushed(LockInventoryAcrossStoresJob::class, function ($job) use ($diamond, $store) {
            return $job->productType === 'diamond' && $job->productId === $diamond->id && $job->originStoreId === $store->id;
        });
    }

    /**
     * Test webhook idempotency blocks duplicate orders/create webhook requests.
     */
    public function test_webhook_idempotency_blocks_duplicate()
    {
        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'OM Gems',
            'shop_domain' => 'om-gems.myshopify.com',
            'access_token' => 'token1',
        ]);

        // Manually record in idempotency table
        ShopifyWebhookIdempotency::create([
            'webhook_id' => 'already_processed_123',
            'topic' => 'orders/create',
        ]);

        $payload = [
            'id' => 12345,
            'line_items' => []
        ];

        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/create',
                'X-Shopify-Shop-Domain' => 'om-gems.myshopify.com',
                'X-Shopify-Webhook-Id' => 'already_processed_123',
            ]
        );

        // Returns 200 with "Already processed"
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Already processed']);
    }

    /**
     * Test CrossStoreInventorySyncService updates inventory and logs audit.
     */
    public function test_sync_service_locks_diamond_on_shopify()
    {
        $user = $this->getAdminUser('normal_admin');

        $storeA = ShopifyStore::create(['user_id' => $user->id, 'store_name' => 'Store A', 'shop_domain' => 'store-a.myshopify.com', 'access_token' => 'tokena']);
        $storeB = ShopifyStore::create(['user_id' => $user->id, 'store_name' => 'Store B', 'shop_domain' => 'store-b.myshopify.com', 'access_token' => 'tokenb']);

        $diamond = Diamond::create([
            'stock_no' => 'DIA0002',
            'asking_price' => 6000,
            'shape' => 'Emerald',
            'size' => 2.0,
            'user_id' => $user->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        // Synced on Store A (tracked) and Store B (not tracked)
        $mappingA = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $storeA->id,
            'shopify_product_id' => '1001',
            'shopify_variant_id' => '2001',
            'sync_status' => 'synced',
        ]);

        $mappingB = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $storeB->id,
            'shopify_product_id' => '1002',
            'shopify_variant_id' => '2002',
            'sync_status' => 'synced',
        ]);

        // Shopify API fakes
        Http::fake([
            // Store A locations (ID: 99)
            'https://store-a.myshopify.com/admin/api/2025-10/locations.json' => Http::response(['locations' => [['id' => 99]]], 200),
            // Store B locations (ID: 88)
            'https://store-b.myshopify.com/admin/api/2025-10/locations.json' => Http::response(['locations' => [['id' => 88]]], 200),
            
            // Store A variant details (tracked)
            'https://store-a.myshopify.com/admin/api/2025-10/variants/2001.json' => Http::response(['variant' => ['id' => 2001, 'inventory_item_id' => 3001, 'inventory_management' => 'shopify']], 200),
            // Store B variant details (not tracked)
            'https://store-b.myshopify.com/admin/api/2025-10/variants/2002.json' => Http::response(['variant' => ['id' => 2002, 'inventory_item_id' => 3002, 'inventory_management' => null]], 200),

            // Store A set inventory level
            'https://store-a.myshopify.com/admin/api/2025-10/inventory_levels/set.json' => Http::response(['inventory_level' => []], 200),
            // Store B unpublish product (sets draft status)
            'https://store-b.myshopify.com/admin/api/2025-10/products/1002.json' => Http::response(['product' => ['status' => 'draft']], 200),
        ]);

        // Create assignments first
        \App\Models\DiamondStoreAssignment::create([
            'diamond_id' => $diamond->id,
            'shopify_store_id' => $storeA->id,
            'assigned_by' => $user->id,
            'is_published' => true,
        ]);
        \App\Models\DiamondStoreAssignment::create([
            'diamond_id' => $diamond->id,
            'shopify_store_id' => $storeB->id,
            'assigned_by' => $user->id,
            'is_published' => true,
        ]);

        $lockService = app(\App\Services\GlobalDiamondLockService::class);
        $lockService->lockDiamond($diamond->id, $storeA->id, 'order_test_123');

        $diamond->refresh();
        $this->assertEquals('on_hold', $diamond->inventory_status);

        // Verify inventory reservation table contains the hold
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_id' => $diamond->id,
            'shopify_store_id' => $storeA->id,
            'shopify_order_id' => 'order_test_123',
            'status' => 'hold',
        ]);

        // Verify audit log entries are created: origin store A is skipped, non-origin store B is unpublished
        $this->assertDatabaseMissing('shopify_inventory_audits', [
            'shopify_store_id' => $storeA->id,
            'diamond_id' => $diamond->id,
            'action' => 'lock_set_zero',
            'new_quantity' => 0,
        ]);

        $this->assertDatabaseHas('shopify_inventory_audits', [
            'shopify_store_id' => $storeB->id,
            'diamond_id' => $diamond->id,
            'action' => 'lock_unpublish',
            'new_quantity' => 0,
        ]);
    }

    /**
     * Test webhook order cancelled releases holds and restores inventory.
     */
    public function test_webhook_order_cancelled_releases_and_restores_inventory()
    {
        Queue::fake([
            ReleaseInventoryAcrossStoresJob::class,
            \App\Jobs\PublishProductToStoreJob::class,
            \App\Jobs\PublishDiamondToShopifyJob::class,
        ]);

        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'OM Gems',
            'shop_domain' => 'om-gems.myshopify.com',
            'access_token' => 'token1',
        ]);

        $diamond = Diamond::create([
            'stock_no' => 'DIA0003',
            'asking_price' => 3000,
            'shape' => 'Oval',
            'size' => 1.0,
            'user_id' => $user->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
        ]);

        $mapping = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => '1003',
            'shopify_variant_id' => '2003',
            'sync_status' => 'synced',
        ]);

        // Create assignment
        \App\Models\DiamondStoreAssignment::create([
            'diamond_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'assigned_by' => $user->id,
            'is_published' => true,
        ]);

        // Create hold reservation
        ShopifyInventoryReservation::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_order_id' => 'order_cancel_123',
            'status' => 'hold',
        ]);

        $payload = [
            'id' => 'order_cancel_123',
            'cancelled_at' => now()->toIso8601String(),
            'line_items' => [
                [
                    'product_id' => 1003,
                    'variant_id' => 2003,
                    'quantity' => 1,
                    'sku' => 'DIA0003',
                ]
            ]
        ];

        // Webhook Cancel
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/cancelled',
                'X-Shopify-Shop-Domain' => 'om-gems.myshopify.com',
                'X-Shopify-Webhook-Id' => 'webhook_unique_cancel_999',
            ]
        );

        $response->assertStatus(202);

        // Verify status changed to available
        $diamond->refresh();
        $this->assertEquals('available', $diamond->inventory_status);

        // Verify reservation updated to released
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_id' => $diamond->id,
            'shopify_order_id' => 'order_cancel_123',
            'status' => 'released',
        ]);

        // Verify Release Job was dispatched
        Queue::assertPushed(ReleaseInventoryAcrossStoresJob::class, function ($job) use ($diamond) {
            return $job->productType === 'diamond' && $job->productId === $diamond->id;
        });
    }

    /**
     * Test CLI reconciliation command corrects mismatches.
     */
    public function test_cli_reconciliation_command_reconciles_mismatch()
    {
        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'OM Gems',
            'shop_domain' => 'om-gems.myshopify.com',
            'access_token' => 'token1',
        ]);

        // Diamond is locally on_hold
        $diamond = Diamond::create([
            'stock_no' => 'DIA0004',
            'asking_price' => 4000,
            'shape' => 'Cushion',
            'size' => 1.8,
            'user_id' => $user->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
        ]);

        $mapping = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => '1004',
            'shopify_variant_id' => '2004',
            'sync_status' => 'synced',
        ]);

        // Create assignment
        \App\Models\DiamondStoreAssignment::create([
            'diamond_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'assigned_by' => $user->id,
            'is_published' => true,
        ]);

        // Shopify currently shows quantity = 1 (mismatch)
        Http::fake([
            'https://om-gems.myshopify.com/admin/api/2025-10/locations.json' => Http::response(['locations' => [['id' => 99]]], 200),
            
            // Returns variant showing quantity 1 (mismatch!)
            'https://om-gems.myshopify.com/admin/api/2025-10/variants/2004.json' => Http::response(['variant' => ['id' => 2004, 'inventory_item_id' => 3004, 'inventory_management' => 'shopify', 'inventory_quantity' => 1]], 200),
            'https://om-gems.myshopify.com/admin/api/2025-10/products/1004.json' => Http::response(['product' => ['id' => 1004, 'status' => 'active']], 200),

            // Corrective update
            'https://om-gems.myshopify.com/admin/api/2025-10/inventory_levels/set.json' => Http::response(['inventory_level' => []], 200),
        ]);

        // Run command
        $this->artisan('shopify:reconcile-inventory', ['--store' => $store->id])
            ->expectsOutput("Reconciling inventory for store: OM Gems (om-gems.myshopify.com)")
            ->expectsOutput("Checking Stock No: DIA0004 (Shopify Product ID: 1004)")
            ->expectsOutput("Quantity mismatch for DIA0004: expected 0, got 1 on Shopify.")
            ->expectsOutput("Fixing discrepancy for DIA0004...")
            ->assertExitCode(0);

        // Verify audit log shows lock_unpublish corrective action
        $this->assertDatabaseHas('shopify_inventory_audits', [
            'shopify_store_id' => $store->id,
            'diamond_id' => $diamond->id,
            'action' => 'lock_unpublish',
            'new_quantity' => 0,
        ]);
    }

    /**
     * Test webhook order create locks jewelry and dispatches LockInventoryAcrossStoresJob.
     */
    public function test_webhook_order_create_locks_jewelry_and_dispatches_job()
    {
        Queue::fake([
            LockInventoryAcrossStoresJob::class,
            \App\Jobs\UnpublishProductFromStoreJob::class,
        ]);

        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'OM Gems',
            'shop_domain' => 'om-gems.myshopify.com',
            'access_token' => 'token1',
        ]);

        $jewelry = Jewelery::create([
            'sku' => 'JW0001',
            'name' => 'Gold Ring',
            'type' => 'Ring',
            'price' => 1000,
            'location' => 'Surat',
            'user_id' => $user->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        $mapping = ShopifyProduct::create([
            'product_type' => 'jewelry',
            'product_id' => $jewelry->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => '1005',
            'shopify_variant_id' => '2005',
            'sync_status' => 'synced',
        ]);

        $payload = [
            'id' => 999444,
            'order_number' => 'OM-1002',
            'line_items' => [
                [
                    'product_id' => 1005,
                    'variant_id' => 2005,
                    'quantity' => 1,
                    'sku' => 'JW0001',
                ]
            ]
        ];

        // Webhook POST
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/create',
                'X-Shopify-Shop-Domain' => 'om-gems.myshopify.com',
                'X-Shopify-Webhook-Id' => 'webhook_unique_999444',
            ]
        );

        $response->assertStatus(202);

        // Verify status changed to on_hold immediately (standardized!)
        $jewelry->refresh();
        $this->assertEquals('on_hold', $jewelry->inventory_status);

        // Verify webhook registered in idempotency table
        $this->assertTrue(ShopifyWebhookIdempotency::where('webhook_id', 'webhook_unique_999444')->exists());

        // Verify Lock Job was dispatched
        Queue::assertPushed(LockInventoryAcrossStoresJob::class, function ($job) use ($jewelry, $store) {
            return $job->productType === 'jewelry' && $job->productId === $jewelry->id && $job->originStoreId === $store->id;
        });
    }

    /**
     * Test webhook order cancelled releases jewelry holds and restores inventory.
     */
    public function test_webhook_order_cancelled_releases_jewelry_and_restores_inventory()
    {
        Queue::fake([
            ReleaseInventoryAcrossStoresJob::class,
            \App\Jobs\PublishProductToStoreJob::class,
        ]);

        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'OM Gems',
            'shop_domain' => 'om-gems.myshopify.com',
            'access_token' => 'token1',
        ]);

        $jewelry = Jewelery::create([
            'sku' => 'JW0002',
            'name' => 'Necklace',
            'type' => 'Necklace',
            'price' => 2000,
            'location' => 'Surat',
            'user_id' => $user->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
        ]);

        $mapping = ShopifyProduct::create([
            'product_type' => 'jewelry',
            'product_id' => $jewelry->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => '1006',
            'shopify_variant_id' => '2006',
            'sync_status' => 'synced',
        ]);

        // Create hold reservation
        ShopifyInventoryReservation::create([
            'product_type' => 'jewelry',
            'product_id' => $jewelry->id,
            'shopify_store_id' => $store->id,
            'shopify_order_id' => 'order_cancel_456',
            'status' => 'hold',
        ]);

        $payload = [
            'id' => 'order_cancel_456',
            'cancelled_at' => now()->toIso8601String(),
            'line_items' => [
                [
                    'product_id' => 1006,
                    'variant_id' => 2006,
                    'quantity' => 1,
                    'sku' => 'JW0002',
                ]
            ]
        ];

        // Webhook Cancel
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/cancelled',
                'X-Shopify-Shop-Domain' => 'om-gems.myshopify.com',
                'X-Shopify-Webhook-Id' => 'webhook_unique_cancel_888',
            ]
        );

        $response->assertStatus(202);

        // Verify status changed to available
        $jewelry->refresh();
        $this->assertEquals('available', $jewelry->inventory_status);

        // Verify reservation updated to released
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_id' => $jewelry->id,
            'shopify_order_id' => 'order_cancel_456',
            'status' => 'released',
        ]);

        // Verify Release Job was dispatched
        Queue::assertPushed(ReleaseInventoryAcrossStoresJob::class, function ($job) use ($jewelry) {
            return $job->productType === 'jewelry' && $job->productId === $jewelry->id;
        });
    }

    /**
     * Test orders/paid webhook keeps the diamond on_hold.
     */
    public function test_webhook_order_paid_keeps_on_hold()
    {
        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'OM Gems',
            'shop_domain' => 'om-gems.myshopify.com',
            'access_token' => 'token1',
        ]);

        $diamond = Diamond::create([
            'stock_no' => 'DIA_PAID_001',
            'asking_price' => 7000,
            'shape' => 'Round',
            'size' => 1.8,
            'user_id' => $user->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
        ]);

        $mapping = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => '1007',
            'shopify_variant_id' => '2007',
            'sync_status' => 'synced',
        ]);

        // Create assignment
        \App\Models\DiamondStoreAssignment::create([
            'diamond_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'assigned_by' => $user->id,
            'is_published' => true,
        ]);

        // Create active hold reservation
        ShopifyInventoryReservation::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_order_id' => 'order_paid_777',
            'status' => 'hold',
        ]);

        // Create local order first
        \App\Models\Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shopify_store_id' => $store->id,
            'shopify_order_id' => 'order_paid_777',
            'shopify_order_number' => 'OM-1007',
            'items' => [],
            'status' => 'paid',
            'created_by' => $user->id,
        ]);

        $payload = [
            'id' => 'order_paid_777',
            'order_id' => 'order_paid_777',
            'line_items' => [
                [
                    'product_id' => 1007,
                    'variant_id' => 2007,
                    'quantity' => 1,
                    'sku' => 'DIA_PAID_001',
                ]
            ]
        ];

        // Webhook Paid
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'orders/paid',
                'X-Shopify-Shop-Domain' => 'om-gems.myshopify.com',
                'X-Shopify-Webhook-Id' => 'webhook_unique_paid_777',
            ]
        );

        $response->assertStatus(202);

        // Verify status remains on_hold in DB
        $diamond->refresh();
        $this->assertEquals('on_hold', $diamond->inventory_status);

        // Verify reservation remains hold
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_id' => $diamond->id,
            'shopify_order_id' => 'order_paid_777',
            'status' => 'hold',
        ]);
    }

    /**
     * Test fulfillments/create webhook updates inventory to sold and unpublishes product.
     */
    public function test_webhook_fulfillment_create_sets_sold_and_drafts_products()
    {
        Queue::fake([
            \App\Jobs\DeleteProductFromStoreJob::class,
        ]);

        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'OM Gems',
            'shop_domain' => 'om-gems.myshopify.com',
            'access_token' => 'token1',
        ]);

        $diamond = Diamond::create([
            'stock_no' => 'DIA_PAID_001',
            'asking_price' => 7000,
            'shape' => 'Round',
            'size' => 1.8,
            'user_id' => $user->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
        ]);

        $mapping = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => '1007',
            'shopify_variant_id' => '2007',
            'sync_status' => 'synced',
        ]);

        // Create assignment
        \App\Models\DiamondStoreAssignment::create([
            'diamond_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'assigned_by' => $user->id,
            'is_published' => true,
        ]);

        // Create active hold reservation
        ShopifyInventoryReservation::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_order_id' => 'order_paid_777',
            'status' => 'hold',
        ]);

        // Create local order first
        $order = \App\Models\Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shopify_store_id' => $store->id,
            'shopify_order_id' => 'order_paid_777',
            'shopify_order_number' => 'OM-1007',
            'items' => [],
            'status' => 'paid',
            'created_by' => $user->id,
        ]);

        $payload = [
            'id' => 'fulfillment_999',
            'order_id' => 'order_paid_777',
            'line_items' => [
                [
                    'product_id' => 1007,
                    'variant_id' => 2007,
                    'quantity' => 1,
                    'sku' => 'DIA_PAID_001',
                ]
            ]
        ];

        // Webhook Fulfillment
        $response = $this->json(
            'POST',
            '/api/shopify/webhooks',
            $payload,
            [
                'X-Shopify-Topic' => 'fulfillments/create',
                'X-Shopify-Shop-Domain' => 'om-gems.myshopify.com',
                'X-Shopify-Webhook-Id' => 'webhook_unique_fulfillment_999',
            ]
        );

        $response->assertStatus(202);

        // Verify status changed to sold in DB
        $diamond->refresh();
        $this->assertEquals('sold', $diamond->inventory_status);

        // Verify reservation completed
        $this->assertDatabaseHas('shopify_inventory_reservations', [
            'product_id' => $diamond->id,
            'shopify_order_id' => 'order_paid_777',
            'status' => 'completed',
        ]);

        // Verify Delete/Draft job was dispatched
        Queue::assertPushed(\App\Jobs\DeleteProductFromStoreJob::class, function ($job) use ($mapping) {
            return $job->shopifyProductId === $mapping->shopify_product_id && $job->storeId === $mapping->shopify_store_id;
        });
    }

    /**
     * Test sys:monitor-health command alerts super admin when thresholds exceeded.
     */
    public function test_monitor_health_command_raises_alerts_when_thresholds_exceeded()
    {
        $superAdmin = $this->getAdminUser('super_admin');

        // Clear existing notifications
        $superAdmin->notifications()->delete();

        // Run health monitor command with low thresholds to force alerts
        $this->artisan('sys:monitor-health', [
            '--failed-threshold' => -1, // Force failed jobs alert
            '--backlog-threshold' => -1, // Force queue backlog alert
            '--delay-threshold' => -1 // Force webhook delay alert
        ])->assertExitCode(0);

        // Since thresholds were forced, it should dispatch alerts
        $notifications = $superAdmin->unreadNotifications;

        $this->assertTrue($notifications->contains(function ($n) {
            return $n->data['title'] === 'System Alert - Failed jobs detected';
        }));

        $this->assertTrue($notifications->contains(function ($n) {
            return $n->data['title'] === 'System Alert - Queue backlog detected';
        }));
    }

    /**
     * Test lock, release, and mark sold propagate to matching duplicate physical diamonds.
     */
    public function test_duplicate_physical_diamond_status_propagation()
    {
        Queue::fake([
            LockInventoryAcrossStoresJob::class,
            ReleaseInventoryAcrossStoresJob::class,
            \App\Jobs\DeleteProductFromStoreJob::class,
            \App\Jobs\PublishDiamondToShopifyJob::class
        ]);

        $adminA = $this->getAdminUser('normal_admin');
        $adminB = $this->getAdminUser('normal_admin');

        $storeA = ShopifyStore::create(['user_id' => $adminA->id, 'store_name' => 'Store A', 'shop_domain' => 'store-a.myshopify.com', 'access_token' => 'tokena']);
        $storeB = ShopifyStore::create(['user_id' => $adminB->id, 'store_name' => 'Store B', 'shop_domain' => 'store-b.myshopify.com', 'access_token' => 'tokenb']);

        // Create two matching diamonds representing the same physical diamond
        $diamondA = Diamond::create([
            'stock_no' => 'DIA1001',
            'asking_price' => 4500,
            'shape' => 'Round',
            'size' => 0.9,
            'color' => 'D',
            'clarity' => 'IF',
            'user_id' => $adminA->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        $diamondB = Diamond::create([
            'stock_no' => 'DIA3001',
            'asking_price' => 4500,
            'shape' => 'Round',
            'size' => 0.9,
            'color' => 'D',
            'clarity' => 'IF',
            'user_id' => $adminB->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamondA->id,
            'shopify_store_id' => $storeA->id,
            'shopify_product_id' => '111',
            'shopify_variant_id' => 'v111',
            'sync_status' => 'synced',
        ]);

        ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamondB->id,
            'shopify_store_id' => $storeB->id,
            'shopify_product_id' => '222',
            'shopify_variant_id' => 'v222',
            'sync_status' => 'synced',
        ]);

        \App\Models\DiamondStoreAssignment::create([
            'diamond_id' => $diamondA->id,
            'shopify_store_id' => $storeA->id,
            'assigned_by' => $adminA->id,
            'is_published' => true,
        ]);

        \App\Models\DiamondStoreAssignment::create([
            'diamond_id' => $diamondB->id,
            'shopify_store_id' => $storeB->id,
            'assigned_by' => $adminB->id,
            'is_published' => true,
        ]);

        $lockService = app(\App\Services\GlobalDiamondLockService::class);

        // 1. Lock diamond A
        $lockService->lockDiamond($diamondA->id, $storeA->id, 'shopify_order_123');

        // Assert both A and B are locked locally in database
        $this->assertEquals('on_hold', $diamondA->fresh()->inventory_status);
        $this->assertEquals('on_hold', $diamondB->fresh()->inventory_status);

        // 2. Release hold
        $lockService->releaseDiamond($diamondA->id, 'Test cancel');

        // Assert both A and B are released
        $this->assertEquals('available', $diamondA->fresh()->inventory_status);
        $this->assertEquals('available', $diamondB->fresh()->inventory_status);

        // 3. Mark sold
        $lockService->markSold($diamondA->id, $storeA->id, 'shopify_order_123');

        // Assert both A and B are sold
        $this->assertEquals('sold', $diamondA->fresh()->inventory_status);
        $this->assertEquals('sold', $diamondB->fresh()->inventory_status);
    }
}
