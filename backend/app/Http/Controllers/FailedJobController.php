<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Services\AuditService;

class FailedJobController extends Controller
{
    /**
     * Display a listing of the failed queue jobs.
     */
    public function index(Request $request)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            abort(403, 'Only Super Admins can manage failed queue jobs.');
        }

        $search = $request->input('search');
        $queue = $request->input('queue');

        $query = DB::table('failed_jobs');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('payload', 'like', "%{$search}%")
                  ->orWhere('exception', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        if ($queue) {
            $query->where('queue', $queue);
        }

        $failedJobs = $query->orderBy('failed_at', 'desc')->paginate(20)->withQueryString();
        
        // Get unique queue names for filter dropdown
        $queues = DB::table('failed_jobs')->distinct()->pluck('queue');

        return view('system.failed_jobs', compact('failedJobs', 'queues'));
    }

    /**
     * Retry a single failed job.
     */
    public function retry($id)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        try {
            Artisan::call('queue:retry', ['id' => [$id]]);
            
            // Record audit log
            app(\App\Services\AuditService::class)->log(
                'retry_failed_job',
                null,
                null,
                ['failed_job_id' => $id]
            );

            return response()->json([
                'status' => 'success',
                'message' => "Failed job #{$id} has been queued for retry."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to retry job #{$id}: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete/forget a single failed job.
     */
    public function destroy($id)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        try {
            Artisan::call('queue:forget', ['id' => $id]);

            // Record audit log
            app(\App\Services\AuditService::class)->log(
                'delete_failed_job',
                null,
                null,
                ['failed_job_id' => $id]
            );

            return response()->json([
                'status' => 'success',
                'message' => "Failed job #{$id} has been deleted."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to delete job #{$id}: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry multiple failed jobs.
     */
    public function retryMultiple(Request $request)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) {
            return response()->json(['status' => 'error', 'message' => 'No jobs selected.'], 400);
        }

        try {
            foreach ($ids as $id) {
                Artisan::call('queue:retry', ['id' => [$id]]);
            }

            // Record audit log
            app(\App\Services\AuditService::class)->log(
                'bulk_retry_failed_jobs',
                null,
                null,
                ['failed_job_ids' => $ids]
            );

            return response()->json([
                'status' => 'success',
                'message' => count($ids) . " failed jobs have been queued for retry."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to retry selected jobs: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete multiple failed jobs.
     */
    public function destroyMultiple(Request $request)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) {
            return response()->json(['status' => 'error', 'message' => 'No jobs selected.'], 400);
        }

        try {
            foreach ($ids as $id) {
                Artisan::call('queue:forget', ['id' => $id]);
            }

            // Record audit log
            app(\App\Services\AuditService::class)->log(
                'bulk_delete_failed_jobs',
                null,
                null,
                ['failed_job_ids' => $ids]
            );

            return response()->json([
                'status' => 'success',
                'message' => count($ids) . " failed jobs have been deleted."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to delete selected jobs: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAll()
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        try {
            Artisan::call('queue:retry', ['id' => ['all']]);

            // Record audit log
            app(\App\Services\AuditService::class)->log(
                'retry_all_failed_jobs',
                null,
                null
            );

            return response()->json([
                'status' => 'success',
                'message' => "All failed jobs have been queued for retry."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to retry all jobs: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete all failed jobs.
     */
    public function destroyAll()
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        try {
            Artisan::call('queue:flush');

            // Record audit log
            app(\App\Services\AuditService::class)->log(
                'delete_all_failed_jobs',
                null,
                null
            );

            return response()->json([
                'status' => 'success',
                'message' => "All failed jobs have been cleared."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Failed to clear all failed jobs: " . $e->getMessage()
            ], 500);
        }
    }
}
