<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCartItem extends Model
{
    use HasFactory;

    protected $table = 'customer_cart_items';

    protected $fillable = [
        'customer_id',
        'product_type',
        'product_id',
        'quantity',
    ];

    /**
     * Get the customer that owns the cart item.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
