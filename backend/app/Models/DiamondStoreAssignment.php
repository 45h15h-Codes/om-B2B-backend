<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiamondStoreAssignment extends Model
{
    use HasFactory;

    protected $table = 'diamond_store_assignments';

    protected $fillable = [
        'diamond_id',
        'shopify_store_id',
        'assigned_by',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'diamond_id' => 'integer',
        'shopify_store_id' => 'integer',
        'assigned_by' => 'integer',
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
     * Get the user who assigned the store.
     */
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
