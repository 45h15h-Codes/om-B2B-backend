<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ShopifyStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_name',
        'shop_domain',
        'access_token',
        'scopes',
        'is_active',
        'webhook_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Mutator to encrypt access token when setting it.
     */
    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    /**
     * Decrypt the access token when fetched.
     */
    public function getDecryptedAccessToken(): string
    {
        return Crypt::decryptString($this->access_token);
    }

    /**
     * Get the owner admin of this store.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the synced products associated with this store.
     */
    public function shopifyProducts()
    {
        return $this->hasMany(ShopifyProduct::class, 'shopify_store_id');
    }

    /**
     * Get the orders associated with this store.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'shopify_store_id');
    }

    public function diamondAssignments()
    {
        return $this->hasMany(DiamondStoreAssignment::class, 'shopify_store_id');
    }

    public function assignedDiamonds()
    {
        return $this->belongsToMany(Diamond::class, 'diamond_store_assignments', 'shopify_store_id', 'diamond_id')
            ->withPivot('is_published')
            ->withTimestamps();
    }
}


