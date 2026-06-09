<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncJobHistory extends Model
{
    use HasFactory;

    protected $table = 'sync_jobs_history';

    protected $fillable = [
        'shopify_store_id',
        'job_type',
        'status',
        'records_processed',
        'errors',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Get the store that owns the sync job.
     */
    public function shopifyStore()
    {
        return $this->belongsTo(ShopifyStore::class, 'shopify_store_id');
    }
}
