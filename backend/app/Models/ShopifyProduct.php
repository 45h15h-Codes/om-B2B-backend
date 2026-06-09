<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyProduct extends Model
{
    protected $fillable = [
        'product_type',
        'product_id',
        'shopify_store_id',
        'shopify_product_id',
        'shopify_variant_id',
        'shopify_product_url',
        'sync_status',
        'shopify_status',
        'sync_attempts',
        'sync_message',
        'response',
        'synced_at',
        'deleted_from_shopify',
    ];

    protected $casts = [
        'response' => 'array',
        'synced_at' => 'datetime',
        'sync_attempts' => 'integer',
        'deleted_from_shopify' => 'boolean',
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
     * Get the public storefront URL for this product.
     */
    public function getStorefrontUrlAttribute(): ?string
    {
        if (empty($this->response) || !isset($this->response['product'])) {
            return null;
        }

        $handle = $this->response['product']['handle'] ?? null;
        if (!$handle) {
            return null;
        }

        $host = null;
        if ($this->shopify_product_url) {
            $parsed = parse_url($this->shopify_product_url);
            $host = $parsed['host'] ?? null;
        }

        if (!$host) {
            $store = $this->shopifyStore;
            if ($store) {
                $host = $store->shop_domain;
            } else {
                $product = $this->product;
                if ($product && $product->user && $product->user->activeShopifyStore) {
                    $host = $product->user->activeShopifyStore->shop_domain;
                }
            }
        }

        if (!$host) {
            return null;
        }

        return "https://{$host}/products/{$handle}";
    }

    /**
     * Get the appropriate Shopify URL based on the logged-in user's role.
     */
    public function getShopifyUrlAttribute(): ?string
    {
        $isSuperAdmin = (session('admin_role') === 'super_admin' || (auth()->check() && auth()->user()->role === 'super_admin'));
        
        if ($isSuperAdmin) {
            return $this->storefront_url ?: $this->shopify_product_url;
        }
        
        return $this->shopify_product_url;
    }
}