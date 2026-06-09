<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyWebhookIdempotency extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'webhook_id',
        'topic',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];
}
