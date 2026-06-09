<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory_requests';

    protected $fillable = [
        'user_id',
        'request_type',
        'product_type',
        'product_id',
        'notes',
        'action_payload',
        'priority',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'action_payload' => 'array',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function product()
    {
        return $this->morphTo();
    }
}
