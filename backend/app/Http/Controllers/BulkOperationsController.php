<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BackgroundJob;

class BulkOperationsController extends Controller
{
    /**
     * Get the status and progress of a background job.
     */
    public function status($id)
    {
        $job = BackgroundJob::find($id);
        if (!$job) {
            return response()->json(['status' => 'error', 'message' => 'Job not found.'], 404);
        }

        // Access check
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin' && $job->user_id !== auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized access.'], 403);
        }

        $progressData = json_decode($job->message, true) ?? [
            'processed' => 0,
            'total' => 0,
            'percent' => 0,
            'errors' => []
        ];

        return response()->json([
            'status' => 'success',
            'job' => [
                'id' => $job->id,
                'job_name' => $job->job_name,
                'status' => $job->status,
                'started_at' => $job->started_at ? $job->started_at->toIso8601String() : null,
                'completed_at' => $job->completed_at ? $job->completed_at->toIso8601String() : null,
                'progress' => $progressData
            ]
        ]);
    }
}
