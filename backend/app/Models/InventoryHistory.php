<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory_histories';

    protected $fillable = [
        'product_type',
        'product_id',
        'action',
        'old_value',
        'new_value',
        'user_id',
        'remarks',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->morphTo();
    }
}
