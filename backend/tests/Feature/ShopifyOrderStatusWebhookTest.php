<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyProduct;
use App\Models\ShopifyStore;
use App\Models\Order;
use App\Models\ShopifyOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShopifyOrderStatusWebhookTest extends TestCase
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

    private function setupStoreAndProduct()
    {
        $user = $this->getAdminUser('normal_admin');
        
        $store = ShopifyStore::create([
            'user_id' => $user->id,
            'store_name' => 'Test Store',
            'shop_domain' => 'test-store.myshopify.com',
            'access_token' => 'shpat_testaccesstoken12345',
            'is_active' => true,
        ]);

        $diamond = Diamond::withoutEvents(function () use ($user) {
            return Diamond::create([
                'stock_no' => 'WD-TEST-STATUS-101',
                'asking_price' => 1500.00,
                'shape' => 'Round',
                'size' => 1.0,
                'user_id' => $user->id,
                'created_by' => 'Normal Admin',
                'status' => 'Approved'
            ]);
        });

        $shopifyProduct = ShopifyProduct::create([
            'product_type' => 'diamond',
            'product_id' => $diamond->id,
            'shopify_store_id' => $store->id,
            'shopify_product_id' => '999888777',
            'shopify_variant_id' => '111222333',
            'sync_status' => 'synced',
        ]);

        return [$store, $diamond, $shopifyProduct];
    }

    /**
     * Test COD order (financial_status=pending) creates a local order with status pending.
     */
    public function test_cod_order_pending_status()
    {
        $this->setupStoreAndProduct();

        $payload = [
            'id' => 777111,
            'order_number' => '1001',
            'financial_status' => 'pending',
            'total_price' => '1500.00',
            'line_items' => [
                [
                    'product_id' => 999888777,
                    'variant_id' => 111222333,
                    'quantity' => 1,
                    'price' => '1500.00'
                ]
            ]
        ];

        $response = $this->postJson('/api/shopify/webhooks', $payload, [
            'X-Shopify-Topic' => 'orders/create',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
            'X-Shopify-Webhook-Id' => 'wh_cod_pending_1',
        ]);

        $response->assertStatus(202);

        // Verify local order was created with status = pending
        $this->assertDatabaseHas('orders', [
            'shopify_order_id' => '777111',
            'status' => 'pending',
        ]);

        // Verify shopify_orders has financial_status = pending
        $this->assertDatabaseHas('shopify_orders', [
            'shopify_order_id' => '777111',
            'financial_status' => 'pending',
        ]);
    }

    /**
     * Test paid order (financial_status=paid) creates a local order with status paid.
     */
    public function test_paid_order_status()
    {
        $this->setupStoreAndProduct();

        $payload = [
            'id' => 777222,
            'order_number' => '1002',
            'financial_status' => 'paid',
            'total_price' => '1500.00',
            'line_items' => [
                [
                    'product_id' => 999888777,
                    'variant_id' => 111222333,
                    'quantity' => 1,
                    'price' => '1500.00'
                ]
            ]
        ];

        $response = $this->postJson('/api/shopify/webhooks', $payload, [
            'X-Shopify-Topic' => 'orders/create',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
            'X-Shopify-Webhook-Id' => 'wh_paid_cc_1',
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('orders', [
            'shopify_order_id' => '777222',
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('shopify_orders', [
            'shopify_order_id' => '777222',
            'financial_status' => 'paid',
        ]);
    }

    /**
     * Test refunded/cancelled order (financial_status=refunded) creates a local order with status cancelled.
     */
    public function test_cancelled_order_status()
    {
        $this->setupStoreAndProduct();

        $payload = [
            'id' => 777333,
            'order_number' => '1003',
            'financial_status' => 'refunded',
            'total_price' => '1500.00',
            'line_items' => [
                [
                    'product_id' => 999888777,
                    'variant_id' => 111222333,
                    'quantity' => 1,
                    'price' => '1500.00'
                ]
            ]
        ];

        $response = $this->postJson('/api/shopify/webhooks', $payload, [
            'X-Shopify-Topic' => 'orders/create',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
            'X-Shopify-Webhook-Id' => 'wh_refunded_cancel_1',
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('orders', [
            'shopify_order_id' => '777333',
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('shopify_orders', [
            'shopify_order_id' => '777333',
            'financial_status' => 'refunded',
        ]);
    }

    /**
     * Test transition from pending -> paid when orders/paid webhook is received.
     */
    public function test_orders_paid_transition()
    {
        $this->setupStoreAndProduct();

        $payload = [
            'id' => 777444,
            'order_number' => '1004',
            'financial_status' => 'pending',
            'total_price' => '1500.00',
            'line_items' => [
                [
                    'product_id' => 999888777,
                    'variant_id' => 111222333,
                    'quantity' => 1,
                    'price' => '1500.00'
                ]
            ]
        ];

        // 1. Create COD order (pending)
        $response1 = $this->postJson('/api/shopify/webhooks', $payload, [
            'X-Shopify-Topic' => 'orders/create',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
            'X-Shopify-Webhook-Id' => 'wh_cod_transition_create',
        ]);
        $response1->assertStatus(202);

        $this->assertDatabaseHas('orders', [
            'shopify_order_id' => '777444',
            'status' => 'pending',
        ]);

        // 2. Receive orders/paid webhook (transition to paid)
        $paidPayload = $payload;
        $paidPayload['financial_status'] = 'paid';

        $response2 = $this->postJson('/api/shopify/webhooks', $paidPayload, [
            'X-Shopify-Topic' => 'orders/paid',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
            'X-Shopify-Webhook-Id' => 'wh_cod_transition_paid',
        ]);
        $response2->assertStatus(202);

        // Verify status transitioned to paid
        $this->assertDatabaseHas('orders', [
            'shopify_order_id' => '777444',
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('shopify_orders', [
            'shopify_order_id' => '777444',
            'financial_status' => 'paid',
        ]);
    }

}