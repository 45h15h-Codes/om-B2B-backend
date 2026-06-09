<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyInventoryReservation extends Model
{
    use HasFactory;

    protected $table = 'shopify_inventory_reservations';

    protected $fillable = [
        'product_type',
        'product_id',
        'shopify_store_id',
        'origin_store_id',
        'order_id',
        'shopify_order_id',
        'status', // hold, released, completed
    ];

    /**
     * Get the owning product model (Diamond or Jewelry).
     */
    public function product()
    {
        return $this->morphTo();
    }

    /**
     * Get the associated Shopify Store.
     */
    public function shopifyStore()
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }

    /**
     * Get the originating Shopify Store of the order.
     */
    public function originStore()
    {
        return $this->belongsTo(ShopifyStore::class, 'origin_store_id');
    }

    /**
     * Get the local Order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
