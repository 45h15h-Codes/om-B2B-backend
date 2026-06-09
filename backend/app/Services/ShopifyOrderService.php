<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Diamond;
use App\Models\Jewelery;
use Illuminate\Support\Facades\Log;

class ShopifyOrderService extends ShopifyService
{
    /**
     * Create a Draft Order on Shopify.
     *
     * @param \App\Models\Order $order
     * @return array
     */
    public function createShopifyDraftOrder(Order $order): array
    {
        // Prevent duplicate Shopify Draft Orders
        if ($order->shopify_draft_id) {
            Log::info("ShopifyOrderService: Order ID {$order->id} already has shopify_draft_id: {$order->shopify_draft_id}. Skipping creation.");
            return $order->shopify_response ?? [];
        }

        $store = $order->shopifyStore;
        if (!$store) {
            throw new \Exception("Shopify store not found for order ID: " . $order->id);
        }

        // Configure ShopifyService context
        $this->forStore($store);

        $lineItems = [];
        foreach ($order->items as $item) {
            $variantId = null;

            if ($item['product_type'] === 'diamond') {
                $diamond = Diamond::find($item['product_id']);
                if (!$diamond) {
                    throw new \Exception("Diamond ID {$item['product_id']} not found in local database.");
                }

                // Verify product exists and is synced on Shopify
                $shopifyProduct = $diamond->shopifyProducts()->where('shopify_store_id', $store->id)->first();
                if (!$shopifyProduct || !$shopifyProduct->shopify_variant_id || $shopifyProduct->sync_status !== 'synced') {
                    Log::info("ShopifyOrderService: Syncing Diamond ID {$diamond->id} on-the-fly to Shopify store {$store->id}.");
                    $syncResponse = $this->syncDiamond($diamond);
                    $variantId = $syncResponse['product']['variants'][0]['id'] ?? null;
                    
                    if (!$shopifyProduct) {
                        $shopifyProduct = $diamond->shopifyProducts()->create([
                            'shopify_store_id' => $store->id,
                            'shopify_product_id' => $syncResponse['product']['id'],
                            'shopify_variant_id' => $variantId,
                            'shopify_product_url' => "https://" . $store->shop_domain . "/admin/products/" . $syncResponse['product']['id'],
                            'product_type' => 'diamond',
                            'product_reference_id' => (string) $diamond->id,
                            'sync_status' => 'synced',
                            'response' => $syncResponse,
                            'synced_at' => now(),
                        ]);
                    } else {
                        $shopifyProduct->update([
                            'shopify_product_id' => $syncResponse['product']['id'],
                            'shopify_variant_id' => $variantId,
                            'shopify_product_url' => "https://" . $store->shop_domain . "/admin/products/" . $syncResponse['product']['id'],
                            'sync_status' => 'synced',
                            'response' => $syncResponse,
                            'synced_at' => now(),
                        ]);
                    }
                } else {
                    $variantId = $shopifyProduct->shopify_variant_id;
                }

            } elseif ($item['product_type'] === 'jewelry') {
                $jewelry = Jewelery::find($item['product_id']);
                if (!$jewelry) {
                    throw new \Exception("Jewelry ID {$item['product_id']} not found in local database.");
                }

                // Verify product exists and is synced on Shopify
                $shopifyProduct = $jewelry->shopifyProducts()->where('shopify_store_id', $store->id)->first();
                if (!$shopifyProduct || !$shopifyProduct->shopify_variant_id || $shopifyProduct->sync_status !== 'synced') {
                    Log::info("ShopifyOrderService: Syncing Jewelry ID {$jewelry->id} on-the-fly to Shopify store {$store->id}.");
                    $syncResponse = $this->syncJewelry($jewelry);
                    $variantId = $syncResponse['product']['variants'][0]['id'] ?? null;
                    
                    if (!$shopifyProduct) {
                        $shopifyProduct = $jewelry->shopifyProducts()->create([
                            'shopify_store_id' => $store->id,
                            'shopify_product_id' => $syncResponse['product']['id'],
                            'shopify_variant_id' => $variantId,
                            'shopify_product_url' => "https://" . $store->shop_domain . "/admin/products/" . $syncResponse['product']['id'],
                            'product_type' => 'jewelry',
                            'product_reference_id' => (string) $jewelry->id,
                            'sync_status' => 'synced',
                            'response' => $syncResponse,
                            'synced_at' => now(),
                        ]);
                    } else {
                        $shopifyProduct->update([
                            'shopify_product_id' => $syncResponse['product']['id'],
                            'shopify_variant_id' => $variantId,
                            'shopify_product_url' => "https://" . $store->shop_domain . "/admin/products/" . $syncResponse['product']['id'],
                            'sync_status' => 'synced',
                            'response' => $syncResponse,
                            'synced_at' => now(),
                        ]);
                    }
                } else {
                    $variantId = $shopifyProduct->shopify_variant_id;
                }
            }

            if (!$variantId) {
                throw new \Exception("Could not resolve Shopify Variant ID for product type {$item['product_type']} ID {$item['product_id']}.");
            }

            $lineItems[] = [
                'variant_id' => (int) $variantId,
                'quantity' => (int) $item['quantity'],
                'price' => (string) $item['price_snapshot'], // Override price to match local snapshot
            ];
        }

        // Build Shopify Payload
        $payload = [
            'draft_order' => [
                'line_items' => $lineItems,
                'note_attributes' => [
                    [
                        'name' => 'local_order_uuid',
                        'value' => $order->uuid
                    ]
                ]
            ]
        ];

        // Add Customer email if present
        if ($order->email) {
            $payload['draft_order']['email'] = $order->email;
        }

        // Add Customer info if present
        if ($order->customer_name || $order->customer_phone) {
            $payload['draft_order']['customer'] = [];
            if ($order->email) {
                $payload['draft_order']['customer']['email'] = $order->email;
            }
            if ($order->customer_name) {
                $parts = explode(' ', trim($order->customer_name), 2);
                $payload['draft_order']['customer']['first_name'] = $parts[0] ?? '';
                $payload['draft_order']['customer']['last_name'] = $parts[1] ?? '';
            }
            if ($order->customer_phone) {
                $payload['draft_order']['customer']['phone'] = $order->customer_phone;
            }
        }

        // Apply discount if present
        if ($order->discount > 0) {
            $payload['draft_order']['applied_discount'] = [
                'description' => 'Custom discount',
                'value_type' => 'fixed_amount',
                'value' => (string) $order->discount,
                'amount' => (string) $order->discount,
            ];
        }

        Log::info("ShopifyOrderService: Creating draft order on Shopify.", ['payload' => $payload]);

        // Make HTTP request using request() helper from parent ShopifyService
        $response = $this->request()->post("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/draft_orders.json", $payload);

        if (!$response->successful()) {
            throw new \Exception("Shopify Draft Order Creation Failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Send Invoice for a Draft Order on Shopify.
     *
     * @param \App\Models\Order $order
     * @return array
     */
    public function sendInvoice(Order $order): array
    {
        if (!$order->shopify_draft_id) {
            throw new \Exception("Order must be synced to Shopify first.");
        }

        $store = $order->shopifyStore;
        if (!$store) {
            throw new \Exception("Shopify store not found for order ID: " . $order->id);
        }

        $this->forStore($store);

        $response = $this->request()->post("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/draft_orders/{$order->shopify_draft_id}/send_invoice.json", [
            'draft_order_invoice' => (object)[]
        ]);

        if (!$response->successful()) {
            throw new \Exception("Shopify Send Invoice Failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Complete a Draft Order on Shopify (mark as paid and convert to real Order).
     *
     * @param \App\Models\Order $order
     * @return array
     */
    public function completeDraftOrder(Order $order): array
    {
        if (!$order->shopify_draft_id) {
            throw new \Exception("Order must be synced to Shopify first.");
        }

        $store = $order->shopifyStore;
        if (!$store) {
            throw new \Exception("Shopify store not found for order ID: " . $order->id);
        }

        $this->forStore($store);

        $response = $this->request()->put("https://{$this->store}/admin/api/" . config('shopify.api_version') . "/draft_orders/{$order->shopify_draft_id}/complete.json", [
            'payment_pending' => false
        ]);

        if (!$response->successful()) {
            throw new \Exception("Shopify Complete Draft Order Failed: " . $response->body());
        }

        return $response->json();
    }
}
