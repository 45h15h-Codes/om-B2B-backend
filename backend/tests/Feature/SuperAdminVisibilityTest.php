<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyStore;
use App\Models\ShopifyProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class SuperAdminVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminUser($role = 'normal_admin', $email = null)
    {
        $email = $email ?: ($role === 'super_admin' ? 'super_visibility@omgems.com' : 'admin_visibility@omgems.com');
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $role === 'super_admin' ? 'OM Super Visibility' : 'OM Admin Visibility',
                'password' => bcrypt('password'),
                'role' => $role
            ]
        );
    }

    /**
     * Test Super Admin full visibility on Shopify dashboard:
     * - all inventory states (available, on_hold, sold)
     * - all shopify status states (active, draft, archived)
     * - Draft (Locked) badge is displayed appropriately
     */
    public function test_super_admin_full_visibility_on_dashboard()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        $normalAdmin = $this->getAdminUser('normal_admin');

        $store = ShopifyStore::create([
            'user_id' => $normalAdmin->id,
            'store_name' => 'Visibility Store',
            'shop_domain' => 'visibility-store.myshopify.com',
            'access_token' => 'shpat_token1',
        ]);

        // 1. Create Diamonds with different inventory statuses
        $diamondAvailable = Diamond::create([
            'stock_no' => 'D-AVAIL',
            'shape' => 'Round',
            'size' => 1.5,
            'color' => 'D',
            'clarity' => 'FL',
            'asking_price' => 1000,
            'inventory_status' => 'available',
            'user_id' => $normalAdmin->id,
            'status' => 'Approved',
        ]);

        $diamondOnHold = Diamond::create([
            'stock_no' => 'D-HOLD',
            'shape' => 'Pear',
            'size' => 2.0,
            'color' => 'E',
            'clarity' => 'IF',
            'asking_price' => 2000,
            'inventory_status' => 'on_hold',
            'hold_reason' => 'Shopify Order Lock',
            'hold_at' => now()->subDay(),
            'hold_shopify_store_id' => $store->id,
            'user_id' => $normalAdmin->id,
            'status' => 'Approved',
        ]);

        $diamondSold = Diamond::create([
            'stock_no' => 'D-SOLD',
            'shape' => 'Oval',
            'size' => 2.5,
            'color' => 'F',
            'clarity' => 'VVS1',
            'asking_price' => 3000,
            'inventory_status' => 'sold',
            'sold_store_id' => $store->id,
            'sold_at' => now(),
            'user_id' => $normalAdmin->id,
            'status' => 'Approved',
        ]);

        // 2. Create ShopifyProduct mappings with different states
        $shopifyProductActive = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamondAvailable->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => 'prod_active',
            'shopify_variant_id' => 'var_active',
            'sync_status' => 'synced',
            'shopify_status' => 'active',
        ]);

        $shopifyProductDraftLocked = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamondOnHold->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => 'prod_draft_lock',
            'shopify_variant_id' => 'var_draft_lock',
            'sync_status' => 'synced',
            'shopify_status' => 'draft',
        ]);

        $shopifyProductDraftManual = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamondAvailable->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => 'prod_draft_manual',
            'shopify_variant_id' => 'var_draft_manual',
            'sync_status' => 'synced',
            'shopify_status' => 'draft',
        ]);

        $shopifyProductArchived = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamondAvailable->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => 'prod_archived',
            'shopify_variant_id' => 'var_archived',
            'sync_status' => 'synced',
            'shopify_status' => 'archived',
        ]);

        // Act & Assert as Super Admin on Dashboard (Tab 1: Diamonds)
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get('/shopify?tab=diamonds');

        $response->assertStatus(200);
        $response->assertSee('D-AVAIL');
        $response->assertSee('D-HOLD');
        $response->assertSee('D-SOLD');
        $response->assertSee('On Hold'); // Badge updated from Hold
        $response->assertSee('Sold');

        // Act & Assert as Super Admin on Dashboard (Tab 3: Synced)
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get('/shopify?tab=synced');

        $response->assertStatus(200);
        $response->assertSee('Active');
        $response->assertSee('Draft (Locked)'); // Lock/hold drafted
        $response->assertSee('Draft'); // Manually drafted
        $response->assertSee('Archived');
    }

    /**
     * Test Diamond Details page metadata visibility for Super Admin
     */
    public function test_super_admin_views_metadata_on_diamond_detail_page()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        $normalAdmin = $this->getAdminUser('normal_admin');

        $store = ShopifyStore::create([
            'user_id' => $normalAdmin->id,
            'store_name' => 'Detail Test Store',
            'shop_domain' => 'detail-test.myshopify.com',
            'access_token' => 'shpat_token1',
        ]);

        $diamondOnHold = Diamond::create([
            'stock_no' => 'D-METADATA-HOLD',
            'shape' => 'Pear',
            'size' => 2.0,
            'color' => 'E',
            'clarity' => 'IF',
            'asking_price' => 2000,
            'inventory_status' => 'on_hold',
            'hold_reason' => 'Customer Reserved via Shopify',
            'hold_at' => '2026-06-09 12:00:00',
            'hold_shopify_store_id' => $store->id,
            'user_id' => $normalAdmin->id,
            'status' => 'Approved',
        ]);

        $diamondSold = Diamond::create([
            'stock_no' => 'D-METADATA-SOLD',
            'shape' => 'Oval',
            'size' => 2.5,
            'color' => 'F',
            'clarity' => 'VVS1',
            'asking_price' => 3000,
            'inventory_status' => 'sold',
            'sold_store_id' => $store->id,
            'sold_at' => '2026-06-09 13:00:00',
            'user_id' => $normalAdmin->id,
            'status' => 'Approved',
        ]);

        // 1. Assert Hold Metadata on detail page
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get("/diamonds/{$diamondOnHold->id}");

        $response->assertStatus(200);
        $response->assertSee('On Hold');
        $response->assertSee('Customer Reserved via Shopify');
        $response->assertSee('2026-06-09 12:00:00');
        $response->assertSee('Detail Test Store');

        // 2. Assert Sold Metadata on detail page
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get("/diamonds/{$diamondSold->id}");

        $response->assertStatus(200);
        $response->assertSee('Sold');
        $response->assertSee('2026-06-09 13:00:00');
        $response->assertSee('Detail Test Store');
    }

    /**
     * Confirm graceful rendering of old records where relations/timestamps are null
     */
    public function test_graceful_rendering_of_null_metadata_for_old_records()
    {
        $superAdmin = $this->getAdminUser('super_admin');
        $normalAdmin = $this->getAdminUser('normal_admin');

        $diamondNullHold = Diamond::create([
            'stock_no' => 'D-NULL-HOLD',
            'shape' => 'Pear',
            'size' => 2.0,
            'color' => 'E',
            'clarity' => 'IF',
            'asking_price' => 2000,
            'inventory_status' => 'on_hold',
            'hold_reason' => null,
            'hold_at' => null,
            'hold_shopify_store_id' => null,
            'user_id' => $normalAdmin->id,
            'status' => 'Approved',
        ]);

        $diamondNullSold = Diamond::create([
            'stock_no' => 'D-NULL-SOLD',
            'shape' => 'Oval',
            'size' => 2.5,
            'color' => 'F',
            'clarity' => 'VVS1',
            'asking_price' => 3000,
            'inventory_status' => 'sold',
            'sold_store_id' => null,
            'sold_at' => null,
            'user_id' => $normalAdmin->id,
            'status' => 'Approved',
        ]);

        // 1. Assert null hold rendering works without crashing
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get("/diamonds/{$diamondNullHold->id}");
        $response->assertStatus(200);
        $response->assertSee('On Hold');

        // 2. Assert null sold rendering works without crashing
        $response = $this->actingAs($superAdmin)
            ->withSession(['admin_role' => 'super_admin'])
            ->get("/diamonds/{$diamondNullSold->id}");
        $response->assertStatus(200);
        $response->assertSee('Sold');
    }

    /**
     * Assert normal admin role isolation remains unchanged and intact
     */
    public function test_normal_admin_role_isolation_remains_intact()
    {
        $adminA = $this->getAdminUser('normal_admin', 'admina@omgems.com');
        $adminB = $this->getAdminUser('normal_admin', 'adminb@omgems.com');

        $storeA = ShopifyStore::create([
            'user_id' => $adminA->id,
            'store_name' => 'Store A',
            'shop_domain' => 'store-a.myshopify.com',
            'access_token' => 'shpat_token_a',
        ]);

        $storeB = ShopifyStore::create([
            'user_id' => $adminB->id,
            'store_name' => 'Store B',
            'shop_domain' => 'store-b.myshopify.com',
            'access_token' => 'shpat_token_b',
        ]);

        $diamondA = Diamond::create([
            'stock_no' => 'DIAMOND-A',
            'shape' => 'Round',
            'size' => 1.5,
            'color' => 'D',
            'clarity' => 'FL',
            'asking_price' => 1000,
            'inventory_status' => 'available',
            'user_id' => $adminA->id,
            'assigned_admin_id' => $adminA->id,
            'status' => 'Approved',
        ]);

        $diamondB = Diamond::create([
            'stock_no' => 'DIAMOND-B',
            'shape' => 'Pear',
            'size' => 2.0,
            'color' => 'E',
            'clarity' => 'IF',
            'asking_price' => 2000,
            'inventory_status' => 'available',
            'user_id' => $adminB->id,
            'assigned_admin_id' => $adminB->id,
            'status' => 'Approved',
        ]);

        // Acting as admin A, we should see DIAMOND-A but not DIAMOND-B in the catalog
        $response = $this->actingAs($adminA)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/diamonds?search_active=1');

        $response->assertStatus(200);
        $response->assertSee('DIAMOND-A');
        $response->assertDontSee('DIAMOND-B');
    }

    /**
     * Assert normal admin can see their own uploaded jewelry (where user_id is their ID)
     * even if assigned_admin_id is null and status is Pending.
     */
    public function test_normal_admin_can_see_own_uploaded_jewelery()
    {
        $adminA = $this->getAdminUser('normal_admin', 'admina@omgems.com');
        $adminB = $this->getAdminUser('normal_admin', 'adminb@omgems.com');

        $jewelryA = Jewelery::create([
            'sku' => 'JW-OWN-A',
            'name' => 'Admin A Owned Ring',
            'type' => 'Ring',
            'price' => 1500,
            'location' => 'London',
            'user_id' => $adminA->id,
            'assigned_admin_id' => null,
            'status' => 'Pending',
        ]);

        $jewelryB = Jewelery::create([
            'sku' => 'JW-OWN-B',
            'name' => 'Admin B Owned Ring',
            'type' => 'Ring',
            'price' => 2500,
            'location' => 'London',
            'user_id' => $adminB->id,
            'assigned_admin_id' => null,
            'status' => 'Pending',
        ]);

        // Acting as admin A, we should see JW-OWN-A but not JW-OWN-B
        $response = $this->actingAs($adminA)
            ->withSession(['admin_role' => 'normal_admin'])
            ->get('/jewelery');

        $response->assertStatus(200);
        $response->assertSee('JW-OWN-A');
        $response->assertDontSee('JW-OWN-B');
    }
}
