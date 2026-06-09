<?php

namespace App\Services;

use App\Models\BackgroundJob;
use Illuminate\Support\Facades\Auth;

class BackgroundJobService
{
    /**
     * Create and log a new background job record.
     *
     * @param string $jobKey The config key for the job (e.g. 'diamond_upload')
     * @param string $status The status of the job ('pending', 'processing', 'success', 'failed')
     * @param string|null $message Optional message for details/error
     * @param int|null $userId Optional user ID override (defaults to authenticated user)
     * @return \App\Models\BackgroundJob
     */
    public static function createJob(string $jobKey, string $status, ?string $message = null, ?int $userId = null): BackgroundJob
    {
        // Resolve the human-readable job name from configuration
        $jobName = config("background_jobs.{$jobKey}.name", ucwords(str_replace('_', ' ', $jobKey)));

        return BackgroundJob::create([
            'user_id' => $userId ?? Auth::id() ?? 1, // Fallback to 1 (system/admin) if run from CLI/cron
            'job_name' => $jobName,
            'status' => $status,
            'message' => $message,
            'started_at' => in_array($status, ['processing', 'success']) ? now() : null,
            'completed_at' => in_array($status, ['success', 'failed']) ? now() : null,
        ]);
    }

    /**
     * Run a task callback wrapped in a proper job lifecycle (pending -> processing -> success/failed).
     *
     * @param string $jobKey The config key for the job (e.g. 'diamond_upload')
     * @param callable $callback The code to run. Receives the BackgroundJob instance as its first parameter.
     * @param int|null $userId Optional user ID override
     * @return mixed The return value of the callback
     * @throws \Throwable
     */
    public static function track(string $jobKey, callable $callback, ?int $userId = null)
    {
        // 1. Create a background_jobs record with status = pending
        $job = self::createJob($jobKey, 'pending', null, $userId);

        // 2. Update status = processing and set started_at
        $job->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $result = $callback($job);

            // If the callback returns a string, we treat it as a success message
            $message = is_string($result) ? $result : $job->message;

            // 3. Complete with status = success and set completed_at
            $job->update([
                'status' => 'success',
                'message' => $message,
                'completed_at' => now(),
            ]);

            return $result;
        } catch (\Throwable $e) {
            // 4. Update status = failed, save exception message, and set completed_at
            $job->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark a background job as processing.
     *
     * @param BackgroundJob $job
     * @return BackgroundJob
     */
    public static function markProcessing(BackgroundJob $job): BackgroundJob
    {
        $job->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
        return $job->fresh();
    }

    /**
     * Mark a background job as successfully completed.
     *
     * @param BackgroundJob $job
     * @param string|null $message
     * @return BackgroundJob
     */
    public static function markSuccess(BackgroundJob $job, ?string $message = null): BackgroundJob
    {
        $job->update([
            'status' => 'success',
            'message' => $message ?? $job->message,
            'completed_at' => now(),
        ]);
        return $job->fresh();
    }

    /**
     * Mark a background job as failed.
     *
     * @param BackgroundJob $job
     * @param string|null $message
     * @return BackgroundJob
     */
    public static function markFailed(BackgroundJob $job, ?string $message = null): BackgroundJob
    {
        $job->update([
            'status' => 'failed',
            'message' => $message ?? $job->message,
            'completed_at' => now(),
        ]);
        return $job->fresh();
    }
}
