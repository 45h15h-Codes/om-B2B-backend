<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnershipRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'business_name',
        'business_type',
        'purpose',
        'status',
        'notes',
        'approved_at',
        'rejected_at',
        'approved_by',
        'rejected_by',
        'converted_to_user_id',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function convertedUser()
    {
        return $this->belongsTo(User::class, 'converted_to_user_id');
    }
}
