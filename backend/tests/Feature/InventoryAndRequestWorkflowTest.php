<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AdminPermission;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyStore;
use App\Models\ShopifyProduct;
use App\Models\InventoryRequest;
use App\Models\InventoryHistory;
use App\Services\InventoryManager;
use App\Jobs\BulkOperationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

class InventoryAndRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminUser($role = 'normal_admin')
    {
        return User::create([
            'name' => 'Test ' . ucfirst($role),
            'email' => $role . '_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role' => $role
        ]);
    }

    /**
     * 1. Double Hold Block: Confirm holding an already held item throws validation exceptions.
     */
    public function test_double_hold_block()
    {
        $admin = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $admin->id,
            'store_name' => 'Mock Store',
            'shop_domain' => 'mock-store.myshopify.com',
            'access_token' => 'token123',
        ]);

        $diamond = Diamond::create([
            'stock_no' => 'DIA-DBL-HOLD',
            'asking_price' => 1200,
            'shape' => 'Round',
            'size' => 0.8,
            'user_id' => $admin->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        $manager = app(InventoryManager::class);

        // First hold should succeed
        $manager->hold($diamond, $admin->id, 'Memo hold');
        $this->assertEquals('on_hold', $diamond->fresh()->inventory_status);

        // Second hold should throw validation exception
        $this->expectException(ValidationException::class);
        $manager->hold($diamond, $admin->id, 'Second hold');
    }

    /**
     * 2. Policy Enforcement: Normal Admins cannot view/hold items assigned to others.
     */
    public function test_policy_enforcement()
    {
        $adminA = $this->getAdminUser('normal_admin');
        $adminB = $this->getAdminUser('normal_admin');

        $diamond = Diamond::create([
            'stock_no' => 'DIA-POLICY',
            'asking_price' => 1500,
            'shape' => 'Round',
            'size' => 0.9,
            'user_id' => $adminA->id,
            'assigned_admin_id' => $adminA->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        // Acting as Admin A (Assigned) - should be able to view, hold, sync
        $this->actingAs($adminA);
        session(['admin_role' => 'normal_admin']);
        $this->assertTrue(auth()->user()->can('view', $diamond));
        $this->assertTrue(auth()->user()->can('hold', $diamond));
        $this->assertTrue(auth()->user()->can('sync', $diamond));

        // Acting as Admin B (Not Assigned) - should NOT be able to view, hold, sync
        $this->actingAs($adminB);
        session(['admin_role' => 'normal_admin']);
        $this->assertFalse(auth()->user()->can('view', $diamond));
        $this->assertFalse(auth()->user()->can('hold', $diamond));
        $this->assertFalse(auth()->user()->can('sync', $diamond));
    }

    /**
     * 3. Queue Execution: Confirm bulk holds trigger background jobs.
     */
    public function test_queue_execution()
    {
        Queue::fake([
            BulkOperationJob::class,
        ]);

        $admin = $this->getAdminUser('normal_admin');

        $diamond = Diamond::create([
            'stock_no' => 'DIA-BULK',
            'asking_price' => 2200,
            'shape' => 'Round',
            'size' => 1.2,
            'user_id' => $admin->id,
            'assigned_admin_id' => $admin->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        AdminPermission::create([
            'user_id' => $admin->id,
            'permission' => 'hold_inventory',
        ]);
        $admin->refreshPermissionsCache();

        $this->actingAs($admin);
        session(['admin_role' => 'normal_admin']);

        $response = $this->post(route('inventory.bulk-hold'), [
            'product_type' => 'diamond',
            'product_ids' => [$diamond->id],
            'reason' => 'Bulk Hold Request',
        ]);

        $response->assertSessionHas('success');
        Queue::assertPushed(BulkOperationJob::class);
    }

    /**
     * 4. Shopify Mock Integration: Confirm hold sets Shopify inventory to 0 and release restores it.
     */
    public function test_shopify_mock_integration()
    {
        Queue::fake([\App\Jobs\PublishDiamondToShopifyJob::class]);

        $admin = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $admin->id,
            'store_name' => 'Mock Store',
            'shop_domain' => 'mock-store.myshopify.com',
            'access_token' => 'token123',
        ]);

        $diamond = Diamond::create([
            'stock_no' => 'DIA-MOCK-SHOPIFY',
            'asking_price' => 5000,
            'shape' => 'Round',
            'size' => 1.5,
            'user_id' => $admin->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        $mapping = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => 'prod_99',
            'shopify_variant_id' => 'var_99',
            'sync_status' => 'synced',
        ]);

        \App\Models\DiamondStoreAssignment::create([
            'diamond_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'assigned_by' => $admin->id,
            'is_published' => true,
        ]);

        // Mock Shopify location, variant, and set inventory API calls
        Http::fake([
            'https://mock-store.myshopify.com/admin/api/2025-10/locations.json' => Http::response(['locations' => [['id' => 101]]], 200),
            'https://mock-store.myshopify.com/admin/api/2025-10/variants/var_99.json' => Http::response(['variant' => ['id' => 'var_99', 'inventory_item_id' => 'inv_99', 'inventory_management' => 'shopify']], 200),
            'https://mock-store.myshopify.com/admin/api/2025-10/inventory_levels/set.json' => Http::response(['inventory_level' => []], 200),
            'https://mock-store.myshopify.com/admin/api/2025-10/products/prod_99.json' => Http::response(['product' => ['status' => 'active']], 200),
        ]);

        $manager = app(InventoryManager::class);

        // Execute hold
        $manager->hold($diamond, $admin->id, 'Shopify integration hold');
        $this->assertEquals('on_hold', $diamond->fresh()->inventory_status);

        // Assert set inventory was called with 0 (locking)
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'inventory_levels/set.json') &&
                $request['available'] === 0;
        });

        // Clear HTTP request logs to verify release calls
        Http::fake([
            'https://mock-store.myshopify.com/admin/api/2025-10/locations.json' => Http::response(['locations' => [['id' => 101]]], 200),
            'https://mock-store.myshopify.com/admin/api/2025-10/variants/var_99.json' => Http::response(['variant' => ['id' => 'var_99', 'inventory_item_id' => 'inv_99', 'inventory_management' => 'shopify']], 200),
            'https://mock-store.myshopify.com/admin/api/2025-10/inventory_levels/set.json' => Http::response(['inventory_level' => []], 200),
            'https://mock-store.myshopify.com/admin/api/2025-10/products/prod_99.json' => Http::response(['product' => ['status' => 'active']], 200),
        ]);

        // Execute release
        $manager->release($diamond, $admin->id, 'Shopify integration release');
        $this->assertEquals('available', $diamond->fresh()->inventory_status);

        // Assert set inventory was called with 1 (restoring)
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'inventory_levels/set.json') &&
                $request['available'] === 1;
        });
    }

    /**
     * 5. Transactional Integrity: Requests approvals roll back if any sync error occurs.
     */
    public function test_transactional_integrity()
    {
        $admin = $this->getAdminUser('normal_admin');
        $super = $this->getAdminUser('super_admin');

        $store = ShopifyStore::create([
            'user_id' => $admin->id,
            'store_name' => 'Mock Store',
            'shop_domain' => 'mock-store.myshopify.com',
            'access_token' => 'token123',
        ]);

        $diamond = Diamond::create([
            'stock_no' => 'DIA-INTEGRITY',
            'asking_price' => 5000,
            'shape' => 'Round',
            'size' => 1.5,
            'user_id' => $admin->id,
            'assigned_admin_id' => $admin->id,
            'created_by' => 'Normal Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        $mapping = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => 'prod_99',
            'shopify_variant_id' => 'var_99',
            'sync_status' => 'synced',
        ]);

        \App\Models\DiamondStoreAssignment::create([
            'diamond_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'assigned_by' => $admin->id,
            'is_published' => true,
        ]);

        $request = InventoryRequest::create([
            'user_id' => $admin->id,
            'request_type' => 'Hold Inventory',
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'notes' => 'Hold request',
            'action_payload' => ['reason' => 'Memo hold integrity test'],
            'priority' => 'High',
            'status' => 'Pending',
        ]);

        // Fake Shopify server returning 500 server error
        Http::fake([
            'https://mock-store.myshopify.com/admin/api/2025-10/locations.json' => Http::response(['error' => 'Internal server error'], 500),
            'https://mock-store.myshopify.com/admin/api/2025-10/variants/var_99.json' => Http::response(['error' => 'Internal server error'], 500),
        ]);

        $this->actingAs($super);
        session(['admin_role' => 'super_admin']);

        // Call request approve endpoint
        $response = $this->post(route('inventory.request.approve', $request->id));

        // Response should fail with session error
        $response->assertSessionHas('error');

        // Assert that the request status remains 'Pending' and the product status remains 'available' due to database rollback
        $this->assertEquals('Pending', $request->fresh()->status);
        $this->assertEquals('available', $diamond->fresh()->inventory_status);
        $this->assertNull($diamond->fresh()->hold_by);
    }
}
