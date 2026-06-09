<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyWebhookLog extends Model
{
    use HasFactory;

    protected $table = 'shopify_webhook_logs';

    protected $fillable = [
        'webhook_id',
        'topic',
        'shop_domain',
        'payload',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
