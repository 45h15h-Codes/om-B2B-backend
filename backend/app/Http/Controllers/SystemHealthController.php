<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Models\SyncJobHistory;
use App\Models\ShopifyWebhookLog;

class SystemHealthController extends Controller
{
    /**
     * Show the system health status page.
     */
    public function index()
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            abort(403, 'Only Super Admins can access the System Health Dashboard.');
        }

        // 1. Database Health
        $dbStatus = 'Healthy';
        $dbResponseTime = 0;
        $dbError = null;

        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbResponseTime = round((microtime(true) - $start) * 1000, 2); // ms
        } catch (\Exception $e) {
            $dbStatus = 'Unhealthy';
            $dbError = $e->getMessage();
        }

        // 2. System Cache Health
        $cacheStatus = 'Healthy';
        $cacheResponseTime = 0;
        $cacheDriver = config('cache.default', 'file');
        $cacheError = null;

        try {
            $start = microtime(true);
            Cache::put('health_check', true, 10);
            $hasKey = Cache::has('health_check');
            $cacheResponseTime = round((microtime(true) - $start) * 1000, 2); // ms
            if (!$hasKey) {
                throw new \Exception("Cache read verification failed.");
            }
            Cache::forget('health_check');
        } catch (\Exception $e) {
            $cacheStatus = 'Unhealthy';
            $cacheError = $e->getMessage();
        }

        // 3. Queue Health & Backlog Check
        $queuesToCheck = [
            'inventory-locks',
            'webhooks',
            'shopify-sync',
            'imports',
            'notifications',
            'default'
        ];

        $queueBacklog = [];
        $totalBacklog = 0;
        $queueError = null;

        try {
            foreach ($queuesToCheck as $qName) {
                $size = Queue::size($qName);
                $queueBacklog[$qName] = $size;
                $totalBacklog += $size;
            }
        } catch (\Exception $e) {
            $queueError = $e->getMessage();
        }

        // Failed jobs count
        $failedJobsCount = DB::table('failed_jobs')->count();

        // 4. Shopify Sync Logs Check
        $recentSyncs = SyncJobHistory::with('shopifyStore')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $syncSuccessRate = 100;
        $lastSyncTime = null;
        $avgProcessingTime = 0;

        $recentSyncStats = SyncJobHistory::orderBy('created_at', 'desc')->limit(50)->get();
        if ($recentSyncStats->count() > 0) {
            $successes = $recentSyncStats->where('status', 'completed')->count();
            $syncSuccessRate = round(($successes / $recentSyncStats->count()) * 100, 2);
            
            $lastSync = $recentSyncStats->first();
            $lastSyncTime = $lastSync ? $lastSync->created_at : null;

            $totalDuration = 0;
            $durationCount = 0;
            foreach ($recentSyncStats as $stat) {
                if ($stat->started_at && $stat->finished_at) {
                    $totalDuration += $stat->finished_at->diffInSeconds($stat->started_at);
                    $durationCount++;
                }
            }
            $avgProcessingTime = $durationCount > 0 ? round($totalDuration / $durationCount, 2) : 0;
        }

        // 5. Shopify Webhook health status
        $totalWebhooksToday = ShopifyWebhookLog::whereDate('created_at', today())->count();
        $failedWebhooksToday = ShopifyWebhookLog::whereDate('created_at', today())
            ->where('status', 'failed')
            ->count();
        
        $webhookFailureRate = $totalWebhooksToday > 0 ? round(($failedWebhooksToday / $totalWebhooksToday) * 100, 2) : 0;
        $lastWebhook = ShopifyWebhookLog::orderBy('created_at', 'desc')->first();
        $lastWebhookTime = $lastWebhook ? $lastWebhook->created_at : null;

        // 6. Backups Health
        $backupCount = 0;
        $lastBackupTime = null;
        $backupStatus = 'Healthy';
        try {
            $backupDir = storage_path('app/backups');
            if (file_exists($backupDir)) {
                $files = glob($backupDir . '/*.zip');
                $backupCount = count($files);
                if ($backupCount > 0) {
                    $latestTime = 0;
                    foreach ($files as $file) {
                        $mtime = filemtime($file);
                        if ($mtime > $latestTime) {
                            $latestTime = $mtime;
                        }
                    }
                    $lastBackupTime = \Carbon\Carbon::createFromTimestamp($latestTime);
                }
            }
        } catch (\Exception $e) {
            $backupStatus = 'Warning';
        }

        // 7. Recovery Sync Health
        $latestRecovery = \App\Models\ShopifyRecoveryHistory::orderBy('created_at', 'desc')->first();
        $recoveryStatus = 'Healthy';
        if ($latestRecovery && $latestRecovery->status === 'failed') {
            $recoveryStatus = 'Unhealthy';
        }

        return view('system.health', compact(
            'dbStatus', 'dbResponseTime', 'dbError',
            'cacheStatus', 'cacheResponseTime', 'cacheDriver', 'cacheError',
            'queueBacklog', 'totalBacklog', 'queueError', 'failedJobsCount',
            'recentSyncs', 'syncSuccessRate', 'lastSyncTime', 'avgProcessingTime',
            'totalWebhooksToday', 'webhookFailureRate', 'lastWebhookTime',
            'backupCount', 'lastBackupTime', 'backupStatus', 'latestRecovery', 'recoveryStatus'
        ));
    }
}
