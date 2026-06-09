<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyRecoveryHistory extends Model
{
    use HasFactory;

    protected $table = 'shopify_recovery_histories';

    protected $fillable = [
        'user_id',
        'stores_scanned',
        'products_checked',
        'issues_fixed',
        'drafted_count',
        'republished_count',
        'status',
        'error_message',
    ];

    /**
     * Get the user who executed this recovery.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
