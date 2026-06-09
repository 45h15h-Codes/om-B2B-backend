@extends('layouts.app')

@section('content')
<div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
    
    <!-- Header with global actions -->
    <div style="padding: 24px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background-color: var(--primary-light);">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-triangle-exclamation"></i> Failed Queue Jobs
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Monitor, retry, or clear failed background queue jobs directly from the database manager.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <button class="btn btn-secondary" onclick="retryAllJobs(event)" style="height: 38px; line-height: 1; font-weight: 600;">
                <i class="fa-solid fa-arrows-rotate"></i> Retry All Jobs
            </button>
            <button class="btn btn-danger" onclick="deleteAllJobs(event)" style="height: 38px; line-height: 1; font-weight: 600;">
                <i class="fa-solid fa-trash-can"></i> Clear All Failed
            </button>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div style="padding: 20px 30px; border-bottom: 1px solid var(--border-color); background: #fafbfc; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
        <form method="GET" action="{{ route('system.failed-jobs.index') }}" style="display: flex; flex-wrap: wrap; gap: 15px; flex: 1;">
            <!-- Search bar -->
            <div style="flex: 1; min-width: 240px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Search Payload / Exception</label>
                <div style="position: relative;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ID, Exception message, payload class..." style="width: 100%; padding: 8px 12px 8px 34px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; font-family: inherit; outline: none; box-sizing: border-box;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: var(--text-muted); font-size: 13px;"></i>
                </div>
            </div>

            <!-- Queue filter -->
            <div style="width: 180px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Queue</label>
                <select name="queue" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; background: white; font-family: inherit; outline: none;">
                    <option value="">All Queues</option>
                    @foreach($queues as $qName)
                        <option value="{{ $qName }}" {{ request('queue') == $qName ? 'selected' : '' }}>{{ $qName }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Buttons -->
            <div style="display: flex; gap: 8px;">
                <button type="submit" style="padding: 8px 16px; height: 37px; background: var(--primary-color); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                    <i class="fa-solid fa-filter"></i> Apply
                </button>
                <a href="{{ route('system.failed-jobs.index') }}" style="padding: 8px 16px; height: 37px; background: white; color: var(--text-color); border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-sizing: border-box; transition: background 0.2s;">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Action Info Bar (Hidden by default) -->
    <div id="bulkJobsBar" style="padding: 12px 30px; background-color: #fffaf0; border-bottom: 1px solid #feebc8; display: none; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <input type="checkbox" id="selectAllJobsCheckbox" onchange="toggleSelectAllJobs(this)" style="width: 16px; height: 16px; cursor: pointer;">
            <span id="selectedJobsCountText" style="font-size: 13px; font-weight: 700; color: #c05621;">0 items selected</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-secondary" onclick="executeBulkJobAction('retry')" style="background: white; border-color: #cbd5e0; color: var(--success-color); padding: 6px 12px; font-size: 12.5px; height: 32px; line-height: 1; box-sizing: border-box;">
                <i class="fa-solid fa-arrows-rotate"></i> Retry Selected
            </button>
            <button class="btn btn-danger" onclick="executeBulkJobAction('delete')" style="background: white; border-color: #fed7d7; color: var(--error-color); padding: 6px 12px; font-size: 12.5px; height: 32px; line-height: 1; box-sizing: border-box;">
                <i class="fa-solid fa-trash-can"></i> Delete Selected
            </button>
        </div>
    </div>

    <!-- Main Content Panel -->
    <div style="background: white;">
        @if($failedJobs->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                    <thead>
                        <tr style="background: #fafbfc; border-bottom: 1px solid var(--border-color); font-weight: bold; color: var(--text-muted);">
                            <th style="padding: 16px 20px; width: 40px;">
                                <input type="checkbox" id="masterJobsCheckbox" onchange="toggleSelectAllJobs(this)" style="width: 16px; height: 16px; cursor: pointer;">
                            </th>
                            <th style="padding: 16px 20px; width: 60px;">ID</th>
                            <th style="padding: 16px 20px; width: 140px;">Connection / Queue</th>
                            <th style="padding: 16px 20px; width: 220px;">Job Class</th>
                            <th style="padding: 16px 20px;">Exception Message</th>
                            <th style="padding: 16px 20px; width: 150px;">Failed At</th>
                            <th style="padding: 16px 20px; width: 140px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedJobs as $job)
                            @php
                                $payload = json_decode($job->payload, true);
                                $jobClass = $payload['displayName'] ?? ($payload['job'] ?? 'Unknown');
                                $jobClassParts = explode('\\', $jobClass);
                                $shortJobClass = end($jobClassParts);
                                
                                // Extract first line of exception message
                                $exceptionMessage = strtok($job->exception, "\n");
                                if (strlen($exceptionMessage) > 100) {
                                    $exceptionMessage = substr($exceptionMessage, 0, 100) . '...';
                                }
                            @endphp
                            <tr class="job-row-{{ $job->id }}" style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;" onmouseenter="this.style.backgroundColor='#f7fafc'" onmouseleave="this.style.backgroundColor='transparent'">
                                <td style="padding: 16px 20px;">
                                    <input type="checkbox" class="job-checkbox" value="{{ $job->id }}" onchange="toggleJobsSelection()" style="width: 16px; height: 16px; cursor: pointer;">
                                </td>
                                <td style="padding: 16px 20px; font-weight: 700; color: var(--text-color);">#{{ $job->id }}</td>
                                <td style="padding: 16px 20px;">
                                    <div style="font-weight: 600; color: var(--text-color);">{{ $job->connection }}</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">Queue: <span style="background: #edf2f7; padding: 2px 5px; border-radius: 4px; font-family: monospace;">{{ $job->queue }}</span></div>
                                </td>
                                <td style="padding: 16px 20px;" title="{{ $jobClass }}">
                                    <div style="font-weight: 700; color: var(--primary-color);">{{ $shortJobClass }}</div>
                                    <div style="font-size: 11px; color: var(--text-muted); word-break: break-all; font-family: monospace;">{{ $jobClass }}</div>
                                </td>
                                <td style="padding: 16px 20px; max-width: 350px;">
                                    <div style="font-weight: 500; color: var(--error-color); word-break: break-all;">
                                        {{ $exceptionMessage }}
                                    </div>
                                    <a href="#" onclick="toggleStackTrace(event, {{ $job->id }})" style="font-size: 11px; font-weight: 700; color: var(--primary-color); text-decoration: none; margin-top: 4px; display: inline-block;">
                                        <i class="fa-solid fa-code"></i> View Stack Trace & Payload
                                    </a>
                                </td>
                                <td style="padding: 16px 20px; color: var(--text-muted); font-size: 12px;">
                                    <div>{{ \Carbon\Carbon::parse($job->failed_at)->format('Y-m-d H:i:s') }}</div>
                                    <div style="font-size: 11px;">{{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}</div>
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button onclick="retrySingleJob(event, {{ $job->id }})" class="btn btn-secondary" style="padding: 6px 10px; font-size: 12px; height: 30px; line-height: 1; box-sizing: border-box; background: white; border-color: #cbd5e0; color: var(--success-color);" title="Retry Job">
                                            <i class="fa-solid fa-arrows-rotate"></i> Retry
                                        </button>
                                        <button onclick="deleteSingleJob(event, {{ $job->id }})" class="btn btn-danger" style="padding: 6px 10px; font-size: 12px; height: 30px; line-height: 1; box-sizing: border-box; background: white; border-color: #fed7d7; color: var(--error-color);" title="Delete Job">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Collapsible Exception Drawer Row -->
                            <tr id="drawer-{{ $job->id }}" style="display: none; background-color: #f8fafc; border-bottom: 1px solid var(--border-color);">
                                <td colspan="7" style="padding: 20px 30px;">
                                    <div style="display: flex; flex-direction: column; gap: 15px;">
                                        
                                        <!-- Exception details -->
                                        <div>
                                            <h4 style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Full Exception Stack Trace</h4>
                                            <pre style="white-space: pre-wrap; font-family: 'Courier New', Courier, monospace; font-size: 11px; background: #1e293b; color: #f8fafc; padding: 15px; border-radius: 6px; overflow-x: auto; max-height: 300px; border: 1px solid #0f172a; line-height: 1.4;">{{ $job->exception }}</pre>
                                        </div>

                                        <!-- Payload details -->
                                        <div>
                                            <h4 style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Job Payload Parameters</h4>
                                            <pre style="white-space: pre-wrap; font-family: 'Courier New', Courier, monospace; font-size: 11px; background: #f1f5f9; color: #334155; padding: 15px; border-radius: 6px; overflow-x: auto; max-height: 250px; border: 1px solid var(--border-color); line-height: 1.4;">{{ json_encode($payload, JSON_PRETTY_PRINT) }}</pre>
                                        </div>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination links -->
            <div style="padding: 20px 30px; border-top: 1px solid var(--border-color); background: #fafbfc; display: flex; justify-content: center;">
                {{ $failedJobs->links() }}
            </div>
        @else
            <div style="padding: 50px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-circle-check" style="font-size: 45px; color: var(--success-color); margin-bottom: 12px;"></i>
                <p style="font-size: 15px; font-weight: 700; color: var(--text-color);">All systems clear!</p>
                <p style="font-size: 13px; margin-top: 4px;">No failed background queue jobs detected in the database.</p>
            </div>
        @endif
    </div>

</div>
@endsection

@section('scripts')
<script>
    function toggleStackTrace(event, id) {
        event.preventDefault();
        const drawer = document.getElementById(`drawer-${id}`);
        if (drawer.style.display === 'none') {
            drawer.style.display = 'table-row';
        } else {
            drawer.style.display = 'none';
        }
    }

    function toggleSelectAllJobs(masterCheckbox) {
        document.querySelectorAll('.job-checkbox').forEach(cb => {
            cb.checked = masterCheckbox.checked;
        });
        toggleJobsSelection();
    }

    function toggleJobsSelection() {
        const checkedBoxes = document.querySelectorAll('.job-checkbox:checked');
        const bulkBar = document.getElementById('bulkJobsBar');
        const countText = document.getElementById('selectedJobsCountText');
        const allBoxes = document.querySelectorAll('.job-checkbox');

        if (checkedBoxes.length > 0) {
            bulkBar.style.display = 'flex';
            countText.textContent = `${checkedBoxes.length} item(s) selected`;
            document.getElementById('masterJobsCheckbox').checked = (checkedBoxes.length === allBoxes.length);
        } else {
            bulkBar.style.display = 'none';
            document.getElementById('masterJobsCheckbox').checked = false;
        }
    }

    function retrySingleJob(event, id) {
        event.preventDefault();
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        fetch(`/system/failed-jobs/retry/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Error occurred.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Error retrying job.', 'danger');
        });
    }

    function deleteSingleJob(event, id) {
        event.preventDefault();
        if (!confirm('Are you sure you want to delete this failed job record? It will be deleted permanently.')) {
            return;
        }
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        fetch(`/system/failed-jobs/delete/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Error occurred.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Error deleting job.', 'danger');
        });
    }

    function executeBulkJobAction(action) {
        const checkedBoxes = document.querySelectorAll('.job-checkbox:checked');
        if (checkedBoxes.length === 0) return;

        if (action === 'delete' && !confirm('Are you sure you want to delete the selected failed jobs permanently?')) {
            return;
        }

        const ids = Array.from(checkedBoxes).map(cb => cb.value);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const url = action === 'delete' ? '{{ route("system.failed-jobs.delete-multiple") }}' : '{{ route("system.failed-jobs.retry-multiple") }}';
        const method = action === 'delete' ? 'DELETE' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Error occurred.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Error executing bulk action.', 'danger');
        });
    }

    function retryAllJobs(event) {
        event.preventDefault();
        if (!confirm('Are you sure you want to retry ALL failed jobs?')) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch('{{ route("system.failed-jobs.retry-all") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Error occurred.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Error retrying all jobs.', 'danger');
        });
    }

    function deleteAllJobs(event) {
        event.preventDefault();
        if (!confirm('Are you sure you want to delete ALL failed job logs permanently?')) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch('{{ route("system.failed-jobs.delete-all") }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Error occurred.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Error deleting all jobs.', 'danger');
        });
    }
</script>
@endsection
