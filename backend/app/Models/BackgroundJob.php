<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackgroundJob extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    public const JOB_DIAMOND_UPLOAD = 'Diamond Upload';
    public const JOB_DIAMOND_UPLOAD_BATCH = 'Bulk Diamond Import';
    public const JOB_DIAMOND_UPDATE = 'Diamond Update';
    public const JOB_DIAMOND_DELETE = 'Diamond Delete';
    public const JOB_DIAMOND_APPROVE = 'Diamond Approve';
    public const JOB_DIAMOND_REJECT = 'Diamond Reject';
    public const JOB_BULK_DIAMOND_DELETE = 'Bulk Diamond Delete';
    public const JOB_IMAGE_UPLOAD = 'Image Upload';
    public const JOB_IMAGE_DELETE = 'Image Delete';
    public const JOB_CLOUDINARY_UPLOAD = 'Cloudinary Upload';
    public const JOB_CLOUDINARY_DELETE = 'Cloudinary Delete';
    public const JOB_EXPORT_DIAMONDS = 'Export Diamonds';

    public const TYPE_UPLOAD = 'Upload';
    public const TYPE_UPDATE = 'Update';
    public const TYPE_DELETE = 'Delete';
    public const TYPE_APPROVAL = 'Approval';
    public const TYPE_IMPORT = 'Import';
    public const TYPE_MEDIA = 'Media';
    public const TYPE_EXPORT = 'Export';
    public const TYPE_QUEUE = 'Queue';

    protected $table = 'background_jobs';

    protected $fillable = [
        'user_id',
        'job_name',
        'job_type',
        'entity_type',
        'entity_id',
        'status',
        'message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markProcessing(): self
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        return $this;
    }

    public function markSuccess(?string $message = null, ?int $entityId = null): self
    {
        $update = [
            'status' => self::STATUS_SUCCESS,
            'completed_at' => now(),
        ];

        if (!is_null($message)) {
            $update['message'] = $message;
        }

        if (!is_null($entityId)) {
            $update['entity_id'] = $entityId;
        }

        $this->update($update);

        return $this;
    }

    public function markFailed(string $message, ?int $entityId = null): self
    {
        $update = [
            'status' => self::STATUS_FAILED,
            'message' => $message,
            'completed_at' => now(),
        ];

        if (!is_null($entityId)) {
            $update['entity_id'] = $entityId;
        }

        $this->update($update);

        return $this;
    }
}
