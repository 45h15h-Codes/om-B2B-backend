<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLog extends Model
{
    use HasFactory;

    protected $table = 'order_logs';

    protected $fillable = [
        'order_id',
        'user_id',
        'action',
        'message',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Get the associated Order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the user who triggered the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
