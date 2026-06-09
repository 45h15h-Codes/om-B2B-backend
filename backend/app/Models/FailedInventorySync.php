<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedInventorySync extends Model
{
    use HasFactory;

    protected $table = 'failed_inventory_syncs';

    protected $fillable = [
        'product_type',
        'product_id',
        'shopify_store_id',
        'error_message',
        'retry_count',
        'status',
    ];

    public function store()
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }

    public function product()
    {
        return $this->morphTo();
    }
}
