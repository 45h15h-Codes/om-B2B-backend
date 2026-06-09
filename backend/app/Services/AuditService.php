<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Record a new activity log entry.
     *
     * @param string $action
     * @param string|null $modelType
     * @param int|null $modelId
     * @param array|null $payload
     * @param int|null $userId
     * @return \App\Models\ActivityLog
     */
    public static function log(string $action, ?string $modelType = null, ?int $modelId = null, ?array $payload = null, ?int $userId = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'ip_address' => Request::ip(),
            'payload' => $payload,
        ]);
    }
}
