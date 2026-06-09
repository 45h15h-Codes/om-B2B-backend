<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyStore;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Create an Order in the local database.
     *
     * @param array $data
     * @param int $creatorId
     * @return \App\Models\Order
     */
    public function createOrder(array $data, int $creatorId): Order
    {
        $store = ShopifyStore::findOrFail($data['shopify_store_id']);
        
        $storeSnapshot = [
            'store_name' => $store->store_name,
            'shop_domain' => $store->shop_domain,
        ];

        $items = [];
        $subtotal = 0.00;

        foreach ($data['items'] as $itemData) {
            $type = $itemData['product_type'];
            $id = $itemData['product_id'];
            $qty = $itemData['quantity'] ?? 1;

            if ($type === 'diamond') {
                $diamond = Diamond::findOrFail($id);
                
                // Validate product is approved
                if ($diamond->status !== 'Approved') {
                    throw new \Exception("Diamond Stock #{$diamond->stock_no} is not approved and cannot be ordered.");
                }

                $price = $itemData['price_snapshot'] ?? $diamond->asking_price ?? $diamond->cash_price ?? 0.00;
                
                $items[] = [
                    'product_type' => 'diamond',
                    'product_id' => $diamond->id,
                    'stock_no' => $diamond->stock_no,
                    'shape' => $diamond->shape,
                    'carat' => (float) $diamond->size,
                    'color' => $diamond->color,
                    'clarity' => $diamond->clarity,
                    'price_snapshot' => (float) $price,
                    'quantity' => (int) $qty,
                ];
                $subtotal += $price * $qty;

            } elseif ($type === 'jewelry') {
                $jewelry = Jewelery::findOrFail($id);

                // Validate product is approved
                if ($jewelry->status !== 'Approved') {
                    throw new \Exception("Jewelry SKU {$jewelry->sku} is not approved and cannot be ordered.");
                }

                $price = $itemData['price_snapshot'] ?? $jewelry->price ?? 0.00;

                $items[] = [
                    'product_type' => 'jewelry',
                    'product_id' => $jewelry->id,
                    'sku' => $jewelry->sku,
                    'name' => $jewelry->name,
                    'price_snapshot' => (float) $price,
                    'quantity' => (int) $qty,
                ];
                $subtotal += $price * $qty;
            }
        }

        $discount = isset($data['discount']) ? floatval($data['discount']) : 0.00;
        $total = max(0.00, $subtotal - $discount);

        $order = Order::create([
            'uuid' => (string) Str::uuid(),
            'shopify_store_id' => $store->id,
            'shopify_store_snapshot' => $storeSnapshot,
            'email' => $data['email'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'status' => 'pending',
            'created_by' => $creatorId,
        ]);

        $order->logs()->create([
            'user_id' => $creatorId,
            'action' => 'Order Created',
            'message' => "Order created locally with subtotal: {$subtotal}, discount: {$discount}, total: {$total}",
        ]);

        return $order;
    }

    /**
     * Approve the Order and dispatch sync.
     *
     * @param \App\Models\Order $order
     * @param int $approverId
     * @return bool
     */
    public function approveOrder(Order $order, int $approverId): bool
    {
        if ($order->status !== 'pending') {
            return false;
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => $approverId,
        ]);

        $order->logs()->create([
            'user_id' => $approverId,
            'action' => 'Order Approved',
            'message' => 'Order approved by Super Admin. Preparing to queue sync.',
        ]);

        // Shift to syncing immediately to block other processes and dispatch the job
        $order->update(['status' => 'syncing']);
        \App\Jobs\SyncOrderToShopifyJob::dispatch($order->id);

        return true;
    }
}
