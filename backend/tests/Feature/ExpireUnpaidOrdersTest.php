<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminUser($role = 'normal_admin')
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'admin_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'role' => $role
        ]);
    }

    /**
     * Test expiration of a diamond-only pending order.
     */
    public function test_diamond_only_order_expiration()
    {
        Queue::fake();
        $admin = $this->getAdminUser();

        $store = ShopifyStore::create([
            'user_id' => $admin->id,
            'store_name' => 'Default Store',
            'shop_domain' => 'default.myshopify.com',
            'access_token' => 'token',
        ]);

        $diamond = Diamond::create([
            'stock_no' => 'DIA-EXP-1',
            'asking_price' => 1000.00,
            'shape' => 'Round',
            'size' => 1.0,
            'user_id' => $admin->id,
            'created_by' => 'Admin',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
            'hold_by' => $admin->id,
            'hold_at' => now()->subHours(73),
        ]);

        $order = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shopify_store_id' => $store->id,
            'diamond_id' => $diamond->id,
            'status' => 'pending',
            'created_by' => $admin->id,
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
        ]);

        DB::table('orders')->where('id', $order->id)->update(['created_at' => now()->subHours(73)]);

        $exitCode = Artisan::call('shopify:expire-unpaid');
        $this->assertEquals(0, $exitCode);

        // Assert diamond status reverted to available
        $diamond->refresh();
        $this->assertEquals('available', $diamond->inventory_status);

        // Assert order is cancelled
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertStringContainsString('inventory automatically released', $order->error_message);

        // Assert order log created
        $this->assertDatabaseHas('order_logs', [
            'order_id' => $order->id,
            'action' => 'Hold Expired',
        ]);
    }

    /**
     * Test expiration of a jewelry-only pending order.
     */
    public function test_jewelry_only_order_expiration()
    {
        Queue::fake();
        $admin = $this->getAdminUser();

        $store = ShopifyStore::create([
            'user_id' => $admin->id,
            'store_name' => 'Default Store',
            'shop_domain' => 'default.myshopify.com',
            'access_token' => 'token',
        ]);

        $jewelry = Jewelery::create([
            'sku' => 'JW-EXP-1',
            'price' => 500.00,
            'type' => 'Ring',
            'user_id' => $admin->id,
            'created_by' => 'Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        $order = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shopify_store_id' => $store->id,
            'diamond_id' => null,
            'status' => 'pending',
            'created_by' => $admin->id,
            'items' => [
                [
                    'product_type' => 'jewelry',
                    'product_id' => $jewelry->id,
                    'sku' => $jewelry->sku,
                    'price_snapshot' => 500.00,
                    'quantity' => 1,
                ]
            ],
            'subtotal' => 500.00,
            'total' => 500.00,
        ]);

        DB::table('orders')->where('id', $order->id)->update(['created_at' => now()->subHours(73)]);

        $exitCode = Artisan::call('shopify:expire-unpaid');
        $this->assertEquals(0, $exitCode);

        // Assert order is cancelled
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertStringContainsString('order automatically cancelled', $order->error_message);

        // Assert order log created
        $this->assertDatabaseHas('order_logs', [
            'order_id' => $order->id,
            'action' => 'Hold Expired',
        ]);
    }

    /**
     * Test expiration of a mixed order (diamond + jewelry).
     */
    public function test_mixed_order_expiration()
    {
        Queue::fake();
        $admin = $this->getAdminUser();

        $store = ShopifyStore::create([
            'user_id' => $admin->id,
            'store_name' => 'Default Store',
            'shop_domain' => 'default.myshopify.com',
            'access_token' => 'token',
        ]);

        $diamond = Diamond::create([
            'stock_no' => 'DIA-EXP-2',
            'asking_price' => 1000.00,
            'shape' => 'Round',
            'size' => 1.0,
            'user_id' => $admin->id,
            'created_by' => 'Admin',
            'status' => 'Approved',
            'inventory_status' => 'on_hold',
            'hold_by' => $admin->id,
            'hold_at' => now()->subHours(73),
        ]);

        $jewelry = Jewelery::create([
            'sku' => 'JW-EXP-2',
            'price' => 500.00,
            'type' => 'Ring',
            'user_id' => $admin->id,
            'created_by' => 'Admin',
            'status' => 'Approved',
            'inventory_status' => 'available',
        ]);

        $order = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shopify_store_id' => $store->id,
            'diamond_id' => $diamond->id,
            'status' => 'pending',
            'created_by' => $admin->id,
            'items' => [
                [
                    'product_type' => 'diamond',
                    'product_id' => $diamond->id,
                    'stock_no' => $diamond->stock_no,
                    'price_snapshot' => 1000.00,
                    'quantity' => 1,
                ],
                [
                    'product_type' => 'jewelry',
                    'product_id' => $jewelry->id,
                    'sku' => $jewelry->sku,
                    'price_snapshot' => 500.00,
                    'quantity' => 1,
                ]
            ],
            'subtotal' => 1500.00,
            'total' => 1500.00,
        ]);

        DB::table('orders')->where('id', $order->id)->update(['created_at' => now()->subHours(73)]);

        $exitCode = Artisan::call('shopify:expire-unpaid');
        $this->assertEquals(0, $exitCode);

        // Assert diamond status reverted to available
        $diamond->refresh();
        $this->assertEquals('available', $diamond->inventory_status);

        // Assert order is cancelled
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertStringContainsString('inventory automatically released', $order->error_message);
    }

    /**
     * Test expiration of an order referencing a missing/deleted diamond record.
     */
    public function test_missing_diamond_order_expiration()
    {
        Queue::fake();
        $admin = $this->getAdminUser();

        $store = ShopifyStore::create([
            'user_id' => $admin->id,
            'store_name' => 'Default Store',
            'shop_domain' => 'default.myshopify.com',
            'access_token' => 'token',
        ]);

        $order = Order::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'shopify_store_id' => $store->id,
            'diamond_id' => 99999, // Non-existent ID
            'status' => 'pending',
            'created_by' => $admin->id,
            'items' => [
                [
                    'product_type' => 'diamond',
                    'product_id' => 99999,
                    'stock_no' => 'DIA-DELETED',
                    'price_snapshot' => 2000.00,
                    'quantity' => 1,
                ]
            ],
            'subtotal' => 2000.00,
            'total' => 2000.00,
        ]);

        DB::table('orders')->where('id', $order->id)->update(['created_at' => now()->subHours(73)]);

        $exitCode = Artisan::call('shopify:expire-unpaid');
        $this->assertEquals(0, $exitCode);

        // Assert order is cancelled
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertStringContainsString('diamond not found', $order->error_message);

        // Assert order log created
        $this->assertDatabaseHas('order_logs', [
            'order_id' => $order->id,
            'action' => 'Hold Expired',
        ]);
    }
}
