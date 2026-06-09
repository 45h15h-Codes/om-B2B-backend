<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'import_type',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'status',
        'error_log',
        'pending_chunks',
    ];

    protected $casts = [
        'error_log' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
