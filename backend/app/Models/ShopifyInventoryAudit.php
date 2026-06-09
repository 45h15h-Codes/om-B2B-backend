<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyInventoryAudit extends Model
{
    protected $fillable = [
        'shopify_store_id',
        'diamond_id',
        'jewelry_id',
        'stock_no',
        'action',
        'shopify_product_id',
        'shopify_variant_id',
        'previous_quantity',
        'new_quantity',
        'api_response',
        'error_message',
    ];

    protected $casts = [
        'api_response' => 'array',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
    ];

    public function shopifyStore()
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }

    public function diamond()
    {
        return $this->belongsTo(Diamond::class, 'diamond_id');
    }

    public function jewelry()
    {
        return $this->belongsTo(Jewelery::class, 'jewelry_id');
    }
}
