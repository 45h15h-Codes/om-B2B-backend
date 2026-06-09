<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyOrder extends Model
{
    use HasFactory;

    protected $table = 'shopify_orders';

    protected $fillable = [
        'shopify_store_id',
        'shopify_order_id',
        'order_number',
        'customer_name',
        'customer_email',
        'total_price',
        'currency',
        'financial_status',
        'fulfillment_status',
        'order_json',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'order_json' => 'array',
    ];

    /**
     * Get the store that owns the order.
     */
    public function shopifyStore()
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }

    /**
     * Get parsed items from order_json payload.
     */
    public function getItemsAttribute()
    {
        $payload = $this->order_json;
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
            if (is_string($payload)) {
                $payload = json_decode($payload, true);
            }
        }
        
        if (!is_array($payload) || !isset($payload['line_items'])) {
            return [];
        }

        $items = [];
        foreach ($payload['line_items'] as $li) {
            $sku = $li['sku'] ?? 'N/A';
            
            $diamond = \App\Models\Diamond::where('stock_no', $sku)->first();
            if ($diamond) {
                $items[] = [
                    'product_type' => 'diamond',
                    'stock_no' => $sku,
                    'sku' => $sku,
                    'name' => $li['title'] ?? 'Diamond',
                    'shape' => $diamond->shape,
                    'carat' => $diamond->size,
                    'color' => $diamond->color,
                    'clarity' => $diamond->clarity,
                    'quantity' => $li['quantity'] ?? 1,
                    'price_snapshot' => (float) ($li['price'] ?? 0.00),
                ];
                continue;
            }

            $items[] = [
                'product_type' => 'jewelry',
                'stock_no' => $sku,
                'sku' => $sku,
                'name' => $li['title'] ?? ($li['name'] ?? 'Shopify Product'),
                'shape' => '',
                'carat' => '',
                'color' => '',
                'clarity' => '',
                'quantity' => $li['quantity'] ?? 1,
                'price_snapshot' => (float) ($li['price'] ?? 0.00),
            ];
        }
        return $items;
    }

    /**
     * Get subtotal price from order_json.
     */
    public function getSubtotalAttribute()
    {
        $payload = $this->order_json;
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
            if (is_string($payload)) {
                $payload = json_decode($payload, true);
            }
        }
        if (is_array($payload) && isset($payload['subtotal_price'])) {
            return (float) $payload['subtotal_price'];
        }
        return (float) $this->total_price;
    }

    /**
     * Get total discounts from order_json.
     */
    public function getDiscountAttribute()
    {
        $payload = $this->order_json;
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
            if (is_string($payload)) {
                $payload = json_decode($payload, true);
            }
        }
        if (is_array($payload) && isset($payload['total_discounts'])) {
            return (float) $payload['total_discounts'];
        }
        return 0.00;
    }

    /**
     * Get total price.
     */
    public function getTotalAttribute()
    {
        return (float) $this->total_price;
    }

    /**
     * Alias customer_email as email.
     */
    public function getEmailAttribute()
    {
        return $this->customer_email;
    }

    /**
     * Retrieve phone from order_json.
     */
    public function getCustomerPhoneAttribute()
    {
        $payload = $this->order_json;
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
            if (is_string($payload)) {
                $payload = json_decode($payload, true);
            }
        }
        if (is_array($payload)) {
            return $payload['phone'] ?? $payload['customer']['phone'] ?? $payload['billing_address']['phone'] ?? $payload['shipping_address']['phone'] ?? null;
        }
        return null;
    }

    /**
     * Map status to financial_status.
     */
    public function getStatusAttribute()
    {
        return $this->financial_status ?: 'pending';
    }
}
