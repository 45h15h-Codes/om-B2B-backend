<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerWishlist extends Model
{
    use HasFactory;

    protected $table = 'customer_wishlists';

    protected $fillable = [
        'customer_id',
        'product_type',
        'product_id',
    ];

    /**
     * Get the customer that owns the wishlist item.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
