<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyInventory extends Model
{
    use HasFactory;

    protected $table = 'shopify_inventories';

    protected $fillable = [
        'shopify_store_id',
        'shopify_product_id',
        'shopify_variant_id',
        'inventory_item_id',
        'sku',
        'available',
    ];

    /**
     * Get the store that owns the inventory.
     */
    public function shopifyStore()
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }
}
