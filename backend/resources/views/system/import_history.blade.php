@extends('layouts.app')

@section('content')
<div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
    
    <!-- Header -->
    <div style="padding: 24px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background-color: var(--primary-light);">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-file-import"></i> CSV Import History
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Review chunk-processed backgrounds, row-by-row validation logs, and import execution diagnostics.</p>
        </div>
    </div>

    <!-- Imports Table -->
    <div style="background: white;">
        @if($imports->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                    <thead>
                        <tr style="background: #fafbfc; border-bottom: 1px solid var(--border-color); font-weight: bold; color: var(--text-muted);">
                            <th style="padding: 16px 20px;">ID</th>
                            <th style="padding: 16px 20px;">File Name</th>
                            <th style="padding: 16px 20px;">Import Type</th>
                            <th style="padding: 16px 20px;">Uploaded By</th>
                            <th style="padding: 16px 20px;">Status</th>
                            <th style="padding: 16px 20px;">Total Rows</th>
                            <th style="padding: 16px 20px;">Successful</th>
                            <th style="padding: 16px 20px;">Failed</th>
                            <th style="padding: 16px 20px; text-align: right;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($imports as $imp)
                            @php
                                $statusBg = '#f1f5f9';
                                $statusColor = '#475569';
                                if ($imp->status === 'completed') {
                                    $statusBg = '#dcfce7';
                                    $statusColor = '#166534';
                                } elseif ($imp->status === 'failed') {
                                    $statusBg = '#fee2e2';
                                    $statusColor = '#991b1b';
                                } elseif ($imp->status === 'processing') {
                                    $statusBg = '#e0f2fe';
                                    $statusColor = '#0369a1';
                                }
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;" onmouseenter="this.style.backgroundColor='#f8fafc'" onmouseleave="this.style.backgroundColor='transparent'">
                                <td style="padding: 16px 20px; font-weight: bold; color: var(--text-muted);">#{{ $imp->id }}</td>
                                <td style="padding: 16px 20px;">
                                    <div style="font-weight: 700; color: var(--text-color);">{{ $imp->file_name }}</div>
                                    @if($imp->error_log && count(json_decode($imp->error_log, true) ?? []) > 0)
                                        <a href="#" onclick="toggleErrors(event, {{ $imp->id }})" style="font-size: 11px; font-weight: 700; color: var(--error-color); text-decoration: none; display: inline-block; margin-top: 4px;">
                                            <i class="fa-solid fa-triangle-exclamation"></i> View Validation Failures
                                        </a>
                                        
                                        <!-- Collapsible error block -->
                                        <div id="errors-{{ $imp->id }}" style="display: none; margin-top: 8px;">
                                            <div style="background: #fff5f5; border: 1px solid #fed7d7; border-radius: 6px; padding: 10px; max-height: 150px; overflow-y: auto; font-family: monospace; font-size: 11px; color: #c53030; line-height: 1.4;">
                                                @foreach(json_decode($imp->error_log, true) as $err)
                                                    <div>• {{ $err }}</div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 16px 20px; text-transform: uppercase; font-weight: 600; font-size: 11.5px; color: var(--primary-color);">
                                    {{ $imp->import_type }}
                                </td>
                                <td style="padding: 16px 20px;">
                                    {{ $imp->user ? $imp->user->name : 'System' }}
                                </td>
                                <td style="padding: 16px 20px;">
                                    <span style="font-size: 11px; font-weight: bold; padding: 4px 8px; border-radius: 12px; background: {{ $statusBg }}; color: {{ $statusColor }};">
                                        {{ ucfirst($imp->status) }}
                                    </span>
                                </td>
                                <td style="padding: 16px 20px; font-weight: 600;">{{ $imp->total_rows }} rows</td>
                                <td style="padding: 16px 20px; font-weight: 600; color: var(--success-color);">{{ $imp->successful_rows }}</td>
                                <td style="padding: 16px 20px; font-weight: 600; color: {{ $imp->failed_rows > 0 ? 'var(--error-color)' : 'var(--text-muted)' }}">{{ $imp->failed_rows }}</td>
                                <td style="padding: 16px 20px; text-align: right; color: var(--text-muted); font-size: 12px;">
                                    <div>{{ $imp->created_at->format('Y-m-d H:i') }}</div>
                                    <div style="font-size: 11px; margin-top: 2px;">{{ $imp->created_at->diffForHumans() }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div style="padding: 20px 30px; border-top: 1px solid var(--border-color); display: flex; justify-content: center; background: #fafbfc;">
                {{ $imports->links() }}
            </div>
        @else
            <div style="padding: 50px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-file-import" style="font-size: 40px; color: var(--border-color); margin-bottom: 12px;"></i>
                <p style="font-size: 14px; font-weight: 500;">No CSV import history logs registered.</p>
            </div>
        @endif
    </div>

</div>
@endsection

@section('scripts')
<script>
    function toggleErrors(event, id) {
        event.preventDefault();
        const errDiv = document.getElementById(`errors-${id}`);
        if (errDiv.style.display === 'none') {
            errDiv.style.display = 'block';
        } else {
            errDiv.style.display = 'none';
        }
    }
</script>
@endsection
