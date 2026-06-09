@extends('layouts.app')

@section('content')
<div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
    
    <!-- Header -->
    <div style="padding: 24px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background-color: var(--primary-light);">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-heart-pulse"></i> System Health & Performance
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Real-time health diagnostics for core database servers, system caching, job queues, and Shopify synchronization.</p>
        </div>
        <div>
            <button onclick="window.location.reload()" style="padding: 8px 16px; height: 37px; background: white; color: var(--text-color); border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s;">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh Status
            </button>
        </div>
    </div>

    <!-- Health Overview Cards Grid -->
    <div style="padding: 30px; background: #fafbfc; border-bottom: 1px solid var(--border-color); display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        
        <!-- Database Card -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; background: {{ $dbStatus === 'Healthy' ? '#f0fff4' : '#fff5f5' }}; color: {{ $dbStatus === 'Healthy' ? 'var(--success-color)' : 'var(--error-color)' }};">
                <i class="fa-solid fa-database"></i>
            </div>
            <div>
                <h4 style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Database</h4>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                    <span style="font-size: 16px; font-weight: 700; color: var(--text-color);">{{ $dbStatus }}</span>
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $dbStatus === 'Healthy' ? 'var(--success-color)' : 'var(--error-color)' }};"></span>
                </div>
                <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Latency: {{ $dbResponseTime }}ms</p>
            </div>
        </div>

        <!-- Cache Card -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; background: {{ $cacheStatus === 'Healthy' ? '#f0fff4' : '#fff5f5' }}; color: {{ $cacheStatus === 'Healthy' ? 'var(--success-color)' : 'var(--error-color)' }};">
                <i class="fa-solid fa-server"></i>
            </div>
            <div>
                <h4 style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">System Cache</h4>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                    <span style="font-size: 16px; font-weight: 700; color: var(--text-color);">{{ $cacheStatus }}</span>
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $cacheStatus === 'Healthy' ? 'var(--success-color)' : 'var(--error-color)' }};"></span>
                </div>
                <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Latency: {{ $cacheResponseTime }}ms | Driver: {{ $cacheDriver }}</p>
            </div>
        </div>

        <!-- Queues Card -->
        @php
            $queueHealth = $totalBacklog > 500 ? 'Sluggish' : ($totalBacklog > 100 ? 'Warning' : 'Healthy');
            $queueColor = $queueHealth === 'Healthy' ? 'var(--success-color)' : ($queueHealth === 'Warning' ? 'var(--warning-color)' : 'var(--error-color)');
            $queueBg = $queueHealth === 'Healthy' ? '#f0fff4' : ($queueHealth === 'Warning' ? '#fffaf0' : '#fff5f5');
        @endphp
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; background: {{ $queueBg }}; color: {{ $queueColor }};">
                <i class="fa-solid fa-cubes-stacked"></i>
            </div>
            <div>
                <h4 style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Queues</h4>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                    <span style="font-size: 16px; font-weight: 700; color: var(--text-color);">{{ $queueHealth }}</span>
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $queueColor }};"></span>
                </div>
                <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Size: {{ $totalBacklog }} jobs</p>
            </div>
        </div>

        <!-- Failed Jobs Card -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; background: {{ $failedJobsCount > 0 ? '#fff5f5' : '#f0fff4' }}; color: {{ $failedJobsCount > 0 ? 'var(--error-color)' : 'var(--success-color)' }};">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <h4 style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Failed Jobs</h4>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                    <span style="font-size: 16px; font-weight: 700; color: var(--text-color);">{{ $failedJobsCount > 0 ? 'Action' : 'Clear' }}</span>
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $failedJobsCount > 0 ? 'var(--error-color)' : 'var(--success-color)' }};"></span>
                </div>
                <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                    @if($failedJobsCount > 0)
                        <a href="{{ route('system.failed-jobs.index') }}" style="color: var(--error-color); font-weight: 700; text-decoration: underline;">{{ $failedJobsCount }} failed</a>
                    @else
                        No failures
                    @endif
                </p>
            </div>
        </div>

        <!-- Backups Card -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; background: {{ $backupStatus === 'Healthy' ? '#f0fff4' : '#fffaf0' }}; color: {{ $backupStatus === 'Healthy' ? 'var(--success-color)' : 'var(--warning-color)' }};">
                <i class="fa-solid fa-file-shield"></i>
            </div>
            <div>
                <h4 style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Backups</h4>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                    <span style="font-size: 16px; font-weight: 700; color: var(--text-color);">{{ $backupStatus }}</span>
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $backupStatus === 'Healthy' ? 'var(--success-color)' : 'var(--warning-color)' }};"></span>
                </div>
                <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Count: {{ $backupCount }} | Last: {{ $lastBackupTime ? $lastBackupTime->diffForHumans() : 'Never' }}</p>
            </div>
        </div>

        <!-- Recovery Status Card -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; background: {{ $recoveryStatus === 'Healthy' ? '#f0fff4' : '#fff5f5' }}; color: {{ $recoveryStatus === 'Healthy' ? 'var(--success-color)' : 'var(--error-color)' }};">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <h4 style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Recovery</h4>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                    <span style="font-size: 16px; font-weight: 700; color: var(--text-color);">{{ $recoveryStatus }}</span>
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $recoveryStatus === 'Healthy' ? 'var(--success-color)' : 'var(--error-color)' }};"></span>
                </div>
                <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Last Run: {{ $latestRecovery ? $latestRecovery->created_at->diffForHumans() : 'Never' }}</p>
            </div>
        </div>

    </div>

    <!-- Diagnostic Details Section -->
    <div style="padding: 30px; background: white; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; flex-wrap: wrap;">
        
        <!-- Left Column: Queue backlogs & server parameters -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            
            <!-- Queue backlog break-down -->
            <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                <div style="background: #fafbfc; border-bottom: 1px solid var(--border-color); padding: 14px 20px; font-weight: 700; font-size: 14px; color: var(--text-color);">
                    <i class="fa-solid fa-list-ol" style="margin-right: 6px; color: var(--primary-color);"></i> Queue Backlog Breakdown
                </div>
                <div style="padding: 20px; display: flex; flex-direction: column; gap: 15px;">
                    @if(empty($queueError))
                        @foreach($queueBacklog as $qName => $qSize)
                            @php
                                $barPercent = min(100, max(2, ($qSize / 100) * 100));
                                $barColor = $qSize > 100 ? 'var(--error-color)' : ($qSize > 20 ? 'var(--warning-color)' : 'var(--primary-color)');
                            @endphp
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 12.5px; font-weight: 600; color: var(--text-color); margin-bottom: 6px;">
                                    <span>queue: <strong style="font-family: monospace;">{{ $qName }}</strong></span>
                                    <span>{{ $qSize }} jobs</span>
                                </div>
                                <div style="height: 6px; width: 100%; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                                    <div style="width: {{ $barPercent }}%; height: 100%; background: {{ $barColor }}; border-radius: 3px;"></div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div style="color: var(--error-color); font-size: 13px; font-weight: 500;">
                            <i class="fa-solid fa-circle-exclamation"></i> Error checking queue size: {{ $queueError }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Server Errors & Detailed Logs -->
            @if($dbError || $cacheError)
                <div style="border: 1px solid var(--error-color); border-radius: 8px; overflow: hidden;">
                    <div style="background: #fff5f5; border-bottom: 1px solid var(--error-color); padding: 14px 20px; font-weight: 700; font-size: 14px; color: var(--error-color);">
                        <i class="fa-solid fa-bug" style="margin-right: 6px;"></i> Connection Failures Detected
                    </div>
                    <div style="padding: 20px; display: flex; flex-direction: column; gap: 15px; font-size: 12px; font-family: monospace;">
                        @if($dbError)
                            <div style="background: #fff5f5; border: 1px solid #fed7d7; padding: 10px; border-radius: 4px;">
                                <strong style="color: var(--error-color);">[Database connection error]</strong><br>
                                <span style="color: #c53030;">{{ $dbError }}</span>
                            </div>
                        @endif
                        @if($cacheError)
                            <div style="background: #fff5f5; border: 1px solid #fed7d7; padding: 10px; border-radius: 4px;">
                                <strong style="color: var(--error-color);">[Cache connection/driver error]</strong><br>
                                <span style="color: #c53030;">{{ $cacheError }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Shopify Webhook status metrics -->
            <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                <div style="background: #fafbfc; border-bottom: 1px solid var(--border-color); padding: 14px 20px; font-weight: 700; font-size: 14px; color: var(--text-color);">
                    <i class="fa-brands fa-shopify" style="margin-right: 6px; color: #96bf48;"></i> Shopify Webhook Intake Today
                </div>
                <div style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Total Ingested Today</div>
                        <div style="font-size: 24px; font-weight: 700; color: var(--text-color); margin-top: 4px;">{{ $totalWebhooksToday }}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Webhook Failure Rate</div>
                        <div style="font-size: 24px; font-weight: 700; color: {{ $webhookFailureRate > 5 ? 'var(--error-color)' : 'var(--text-color)' }}; margin-top: 4px;">
                            {{ $webhookFailureRate }}%
                        </div>
                    </div>
                    <div style="grid-column: span 2; border-top: 1px solid var(--border-color); padding-top: 15px;">
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Last Ingested Webhook</div>
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-color); margin-top: 4px;">
                            @if($lastWebhookTime)
                                {{ \Carbon\Carbon::parse($lastWebhookTime)->format('Y-m-d H:i:s') }}
                                <span style="color: var(--text-muted); font-size: 11px; font-weight: normal;">({{ \Carbon\Carbon::parse($lastWebhookTime)->diffForHumans() }})</span>
                            @else
                                No webhooks ingested today
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Shopify Sync performance and logs -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            
            <!-- Shopify Sync health -->
            <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                <div style="background: #fafbfc; border-bottom: 1px solid var(--border-color); padding: 14px 20px; font-weight: 700; font-size: 14px; color: var(--text-color);">
                    <i class="fa-solid fa-arrows-spin" style="margin-right: 6px; color: var(--primary-color);"></i> Shopify Multi-Store Sync Health
                </div>
                <div style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Sync Success Rate (Last 50)</div>
                        <div style="font-size: 24px; font-weight: 700; color: {{ $syncSuccessRate < 90 ? 'var(--error-color)' : 'var(--success-color)' }}; margin-top: 4px;">
                            {{ $syncSuccessRate }}%
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Avg Sync Job Duration</div>
                        <div style="font-size: 24px; font-weight: 700; color: var(--text-color); margin-top: 4px;">{{ $avgProcessingTime }}s</div>
                    </div>
                    <div style="grid-column: span 2; border-top: 1px solid var(--border-color); padding-top: 15px;">
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Last Completed Sync</div>
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-color); margin-top: 4px;">
                            @if($lastSyncTime)
                                {{ \Carbon\Carbon::parse($lastSyncTime)->format('Y-m-d H:i:s') }}
                                <span style="color: var(--text-muted); font-size: 11px; font-weight: normal;">({{ \Carbon\Carbon::parse($lastSyncTime)->diffForHumans() }})</span>
                            @else
                                No sync logs registered
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sync Logs Table -->
            <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                <div style="background: #fafbfc; border-bottom: 1px solid var(--border-color); padding: 14px 20px; font-weight: 700; font-size: 14px; color: var(--text-color);">
                    <i class="fa-solid fa-list-check" style="margin-right: 6px; color: var(--primary-color);"></i> Recent Synchronization Log Entries
                </div>
                <div style="overflow-x: auto;">
                    @if($recentSyncs->count() > 0)
                        <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1px solid var(--border-color); font-weight: bold; color: var(--text-muted);">
                                    <th style="padding: 10px 15px;">Store</th>
                                    <th style="padding: 10px 15px;">Job Type</th>
                                    <th style="padding: 10px 15px;">Status</th>
                                    <th style="padding: 10px 15px;">Processed</th>
                                    <th style="padding: 10px 15px; text-align: right;">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentSyncs as $log)
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 10px 15px; font-weight: 600; color: var(--text-color);">
                                            {{ $log->shopifyStore ? $log->shopifyStore->store_name : 'Default Store' }}
                                        </td>
                                        <td style="padding: 10px 15px; font-family: monospace; font-size: 11.5px;">{{ $log->job_type }}</td>
                                        <td style="padding: 10px 15px;">
                                            <span style="font-size: 10.5px; font-weight: 700; padding: 2px 6px; border-radius: 12px; background: {{ $log->status === 'completed' ? '#f0fff4' : '#fff5f5' }}; color: {{ $log->status === 'completed' ? 'var(--success-color)' : 'var(--error-color)' }};">
                                                {{ ucfirst($log->status) }}
                                            </span>
                                        </td>
                                        <td style="padding: 10px 15px;">{{ $log->records_processed }} items</td>
                                        <td style="padding: 10px 15px; text-align: right; color: var(--text-muted); font-size: 11px;">
                                            {{ $log->created_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 13px;">
                            No recent synchronization log entries recorded.
                        </div>
                    @endif
            </div>

            <!-- Recovery Sync History Log -->
            <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; margin-top: 30px;">
                <div style="background: #fafbfc; border-bottom: 1px solid var(--border-color); padding: 14px 20px; font-weight: 700; font-size: 14px; color: var(--text-color);">
                    <i class="fa-solid fa-shield-halved" style="margin-right: 6px; color: var(--primary-color);"></i> Recovery Audit Records
                </div>
                <div style="overflow-x: auto;">
                    @php
                        $recentRecoveries = \App\Models\ShopifyRecoveryHistory::with('user')->orderBy('created_at', 'desc')->limit(5)->get();
                    @endphp
                    @if($recentRecoveries->count() > 0)
                        <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1px solid var(--border-color); font-weight: bold; color: var(--text-muted);">
                                    <th style="padding: 10px 15px;">User</th>
                                    <th style="padding: 10px 15px;">Stores</th>
                                    <th style="padding: 10px 15px;">Checked</th>
                                    <th style="padding: 10px 15px;">Fixed</th>
                                    <th style="padding: 10px 15px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentRecoveries as $rec)
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 10px 15px; font-weight: 600; color: var(--text-color);">
                                            {{ $rec->user ? $rec->user->name : 'System' }}
                                        </td>
                                        <td style="padding: 10px 15px;">{{ $rec->stores_scanned }}</td>
                                        <td style="padding: 10px 15px;">{{ $rec->products_checked }} items</td>
                                        <td style="padding: 10px 15px;">{{ $rec->issues_fixed }} issues</td>
                                        <td style="padding: 10px 15px;">
                                            <span style="font-size: 10.5px; font-weight: 700; padding: 2px 6px; border-radius: 12px; background: {{ $rec->status === 'completed' ? '#f0fff4' : ($rec->status === 'pending' ? '#fffbeb' : '#fff5f5') }}; color: {{ $rec->status === 'completed' ? 'var(--success-color)' : ($rec->status === 'pending' ? 'var(--warning-color)' : 'var(--error-color)') }};">
                                                {{ ucfirst($rec->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 13px;">
                            No recovery sync history logs registered.
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
