@extends('layouts.app')

@section('content')
<div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
    
    <!-- Header -->
    <div style="padding: 24px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background-color: var(--primary-light);">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-arrows-spin"></i> Shopify Sync Center
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Control, trigger, and monitor real-time and background inventory synchronizations across connected stores.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <form action="{{ route('shopify.sync-all') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-primary" style="height: 38px; line-height: 1; font-weight: 600;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Sync All Storefronts
                </button>
            </form>
            <form action="{{ route('shopify.retry-failed') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-secondary" style="height: 38px; line-height: 1; font-weight: 600; background: white; border-color: #cbd5e0; color: var(--warning-color);">
                    <i class="fa-solid fa-arrows-rotate"></i> Retry Failed Syncs
                </button>
            </form>
        </div>
    </div>

    <!-- Active Tasks and Metrics Grid -->
    <div style="padding: 30px; background: #fafbfc; border-bottom: 1px solid var(--border-color); display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px;">
        
        <!-- Active Processing Syncs -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; display: flex; flex-direction: column; gap: 12px;">
            <h4 style="font-size: 13px; font-weight: 700; color: var(--text-color); border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 5px;">
                <i class="fa-solid fa-spinner fa-spin" style="color: var(--primary-color); margin-right: 6px;"></i> Active Sync Operations
            </h4>
            @if($activeSyncs->count() > 0)
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach($activeSyncs as $sync)
                        <div style="background: var(--primary-light); border: 1px solid #b0d4e3; border-radius: 6px; padding: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 13px; font-weight: 700; color: var(--primary-color);">
                                    Store: {{ $sync->shopifyStore ? $sync->shopifyStore->store_name : 'Default Store' }}
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                    Type: {{ $sync->job_type }} | Started: {{ $sync->started_at ? $sync->started_at->diffForHumans() : 'Just now' }}
                                </div>
                            </div>
                            <span style="font-size: 11px; font-weight: bold; background: white; color: var(--primary-color); padding: 4px 8px; border-radius: 12px; border: 1px solid #b0d4e3;">
                                Processing
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 12.5px;">
                    <i class="fa-solid fa-circle-check" style="color: var(--success-color); margin-right: 6px;"></i> No active sync processes running.
                </div>
            @endif
        </div>

        <!-- Sync Failure Alert Box -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; background: {{ $failedProductsCount > 0 ? '#fff5f5' : '#f0fff4' }}; color: {{ $failedProductsCount > 0 ? 'var(--error-color)' : 'var(--success-color)' }};">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div>
                <h4 style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Failed Product Mappings</h4>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                    <span style="font-size: 18px; font-weight: 700; color: var(--text-color);">{{ $failedProductsCount }} Products</span>
                </div>
                <p style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                    @if($failedProductsCount > 0)
                        Items failed in Shopify syncing. Click "Retry Failed" to resolve.
                    @else
                        All products synchronized successfully.
                    @endif
                </p>
            </div>
        </div>

    </div>

    <!-- Filters Toolbar -->
    <div style="padding: 20px 30px; border-bottom: 1px solid var(--border-color); background: #ffffff;">
        <form method="GET" action="{{ route('shopify.sync-center.index') }}" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
            
            <!-- Store filter -->
            <div style="width: 200px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Filter By Store</label>
                <select name="shopify_store_id" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; background: white; font-family: inherit; outline: none;">
                    <option value="">All Stores</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ request('shopify_store_id') == $st->id ? 'selected' : '' }}>{{ $st->store_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status filter -->
            <div style="width: 160px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Status</label>
                <select name="status" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; background: white; font-family: inherit; outline: none;">
                    <option value="">All Statuses</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            <!-- Search field -->
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Search Logs</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search job type, errors..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; font-family: inherit; outline: none; box-sizing: border-box;">
            </div>

            <!-- Buttons -->
            <div style="display: flex; gap: 8px;">
                <button type="submit" style="padding: 8px 16px; height: 37px; background: var(--primary-color); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-filter"></i> Apply
                </button>
                <a href="{{ route('shopify.sync-center.index') }}" style="padding: 8px 16px; height: 37px; background: white; color: var(--text-color); border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-sizing: border-box;">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- History Logs Table -->
    <div style="background: white;">
        @if($syncHistories->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                    <thead>
                        <tr style="background: #fafbfc; border-bottom: 1px solid var(--border-color); font-weight: bold; color: var(--text-muted);">
                            <th style="padding: 16px 20px;">ID</th>
                            <th style="padding: 16px 20px;">Shopify Store</th>
                            <th style="padding: 16px 20px;">Job Type</th>
                            <th style="padding: 16px 20px;">Status</th>
                            <th style="padding: 16px 20px;">Processed</th>
                            <th style="padding: 16px 20px;">Duration</th>
                            <th style="padding: 16px 20px;">Details / Errors</th>
                            <th style="padding: 16px 20px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($syncHistories as $log)
                            @php
                                $duration = '-';
                                if ($log->started_at && $log->finished_at) {
                                    $diff = $log->finished_at->diffInSeconds($log->started_at);
                                    $duration = $diff . 's';
                                }
                                
                                $statusBg = '#f1f5f9';
                                $statusColor = '#475569';
                                if ($log->status === 'completed') {
                                    $statusBg = '#dcfce7';
                                    $statusColor = '#166534';
                                } elseif ($log->status === 'failed') {
                                    $statusBg = '#fee2e2';
                                    $statusColor = '#991b1b';
                                } elseif ($log->status === 'processing') {
                                    $statusBg = '#e0f2fe';
                                    $statusColor = '#0369a1';
                                }
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;" onmouseenter="this.style.backgroundColor='#f8fafc'" onmouseleave="this.style.backgroundColor='transparent'">
                                <td style="padding: 16px 20px; font-weight: bold; color: var(--text-muted);">#{{ $log->id }}</td>
                                <td style="padding: 16px 20px;">
                                    <div style="font-weight: 700; color: var(--text-color);">
                                        {{ $log->shopifyStore ? $log->shopifyStore->store_name : 'Default Store' }}
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-muted);">
                                        {{ $log->shopifyStore ? $log->shopifyStore->myshopify_domain : 'N/A' }}
                                    </div>
                                </td>
                                <td style="padding: 16px 20px; font-family: monospace;">{{ $log->job_type }}</td>
                                <td style="padding: 16px 20px;">
                                    <span style="font-size: 11px; font-weight: bold; padding: 4px 8px; border-radius: 12px; background: {{ $statusBg }}; color: {{ $statusColor }};">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td style="padding: 16px 20px; font-weight: 600;">{{ $log->records_processed }} items</td>
                                <td style="padding: 16px 20px;">{{ $duration }}</td>
                                <td style="padding: 16px 20px; max-width: 250px; word-break: break-all; color: {{ $log->status === 'failed' ? 'var(--error-color)' : 'var(--text-color)' }};">
                                    {{ $log->errors ?: 'No errors' }}
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <form action="{{ route('shopify.retry', $log->id) }}" method="POST" style="margin: 0;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px; height: 26px; background: white; border-color: #cbd5e0; color: var(--primary-color);" title="Retry Sync Operation">
                                                <i class="fa-solid fa-arrows-rotate"></i> Retry
                                            </button>
                                        </form>
                                        <form action="{{ route('shopify.delete-sync', $log->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Delete sync history record?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px; height: 26px; background: white; border-color: #fed7d7; color: var(--error-color);" title="Delete Record">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div style="padding: 20px 30px; border-top: 1px solid var(--border-color); display: flex; justify-content: center; background: #fafbfc;">
                {{ $syncHistories->links() }}
            </div>
        @else
            <div style="padding: 50px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-timeline" style="font-size: 40px; color: var(--border-color); margin-bottom: 12px;"></i>
                <p style="font-size: 14px; font-weight: 500;">No synchronization records found matching your filters.</p>
            </div>
        @endif
    </div>

</div>
@endsection
