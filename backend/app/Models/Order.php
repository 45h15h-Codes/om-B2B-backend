<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orders';

    protected static function booted()
    {
        static::saved(function ($order) {
            static::clearOrderStatsCache($order);
        });

        static::deleted(function ($order) {
            static::clearOrderStatsCache($order);
        });
    }

    public static function clearOrderStatsCache($order)
    {
        // Clear Super Admin cache
        \Illuminate\Support\Facades\Cache::forget('shopify_dashboard_order_stats_super');

        // Clear Normal Admin cache for the store owner
        $storeId = $order->shopify_store_id ?? null;
        if ($storeId) {
            $store = \App\Models\ShopifyStore::find($storeId);
            if ($store && $store->user_id) {
                \Illuminate\Support\Facades\Cache::forget("shopify_dashboard_order_stats_user_{$store->user_id}");
            }
        }
    }

    protected $fillable = [
        'uuid',
        'shopify_store_id',
        'customer_id',
        'email',
        'customer_name',
        'customer_phone',
        'items',
        'subtotal',
        'discount',
        'total',
        'status',
        'shopify_draft_id',
        'invoice_url',
        'invoice_sent_at',
        'shopify_order_id',
        'shopify_order_number',
        'shopify_order_admin_url',
        'shopify_store_snapshot',
        'shopify_payload',
        'shopify_response',
        'error_message',
        'created_by',
        'approved_by',
        'diamond_id',
    ];

    protected $casts = [
        'items' => 'array',
        'shopify_store_snapshot' => 'array',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'invoice_sent_at' => 'datetime',
        'shopify_payload' => 'array',
        'shopify_response' => 'array',
    ];

    /**
     * Get the associated Diamond.
     */
    public function diamond()
    {
        return $this->belongsTo(Diamond::class, 'diamond_id');
    }

    /**
     * Get the associated Shopify Store.
     */
    public function shopifyStore()
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }

    /**
     * Get the user who created the order.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the order.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the audit logs for this order.
     */
    public function logs()
    {
        return $this->hasMany(OrderLog::class, 'order_id');
    }
}
