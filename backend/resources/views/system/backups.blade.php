@extends('layouts.app')

@section('content')
<div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
    
    <!-- Header with action -->
    <div style="padding: 24px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background-color: var(--primary-light);">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-database"></i> Database Backups Manager
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Generate database dump archives, download local backups, or restore system data states.</p>
        </div>
        <div>
            <button onclick="createBackup(event)" class="btn btn-primary" style="height: 38px; line-height: 1; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-file-zipper"></i> Create New Backup
            </button>
        </div>
    </div>

    <!-- Alert banner -->
    <div style="padding: 16px 30px; background-color: #fffaf0; border-bottom: 1px solid #feebc8; display: flex; align-items: flex-start; gap: 12px;">
        <i class="fa-solid fa-triangle-exclamation" style="color: var(--warning-color); font-size: 16px; margin-top: 2px;"></i>
        <div style="font-size: 12.5px; color: #7b341e; line-height: 1.5;">
            <strong>Retention & Restore Notice:</strong> Backups are compressed in ZIP files and automatically deleted after <strong>30 days</strong>. Restoring a backup will overwrite the current database. Proceed with extreme caution and ensure you have a fresh backup before performing restores.
        </div>
    </div>

    <!-- Backups List Table -->
    <div style="background: white;">
        @if(count($backups) > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                    <thead>
                        <tr style="background: #fafbfc; border-bottom: 1px solid var(--border-color); font-weight: bold; color: var(--text-muted);">
                            <th style="padding: 16px 20px;">Backup Filename</th>
                            <th style="padding: 16px 20px; width: 140px;">File Size</th>
                            <th style="padding: 16px 20px; width: 200px;">Created Date</th>
                            <th style="padding: 16px 20px; width: 320px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $backup)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;" onmouseenter="this.style.backgroundColor='#f8fafc'" onmouseleave="this.style.backgroundColor='transparent'">
                                <td style="padding: 16px 20px; font-weight: 700; color: var(--text-color);">
                                    <i class="fa-solid fa-file-archive" style="color: #805ad5; margin-right: 8px;"></i> {{ $backup['filename'] }}
                                </td>
                                <td style="padding: 16px 20px; font-weight: 600;">{{ $backup['size'] }} MB</td>
                                <td style="padding: 16px 20px; color: var(--text-muted);">
                                    {{ $backup['created_at'] }}
                                </td>
                                <td style="padding: 16px 20px; text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        
                                        <!-- Download Link -->
                                        <a href="{{ route('system.backups.download', $backup['filename']) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; height: 30px; line-height: 1; display: inline-flex; align-items: center; gap: 4px; box-sizing: border-box;" title="Download Backup">
                                            <i class="fa-solid fa-download"></i> Download
                                        </a>

                                        <!-- Restore Button -->
                                        <button onclick="restoreBackup(event, '{{ $backup['filename'] }}')" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; height: 30px; line-height: 1; display: inline-flex; align-items: center; gap: 4px; border-color: #cbd5e0; color: var(--warning-color);" title="Restore Database">
                                            <i class="fa-solid fa-clock-rotate-left"></i> Restore
                                        </button>

                                        <!-- Delete Button -->
                                        <button onclick="deleteBackup(event, '{{ $backup['filename'] }}')" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px; height: 30px; line-height: 1; display: inline-flex; align-items: center; gap: 4px; background: white; border-color: #fed7d7; color: var(--error-color);" title="Delete Backup File">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="padding: 50px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-folder-open" style="font-size: 40px; color: var(--border-color); margin-bottom: 12px;"></i>
                <p style="font-size: 14px; font-weight: 500;">No database backup files found.</p>
                <p style="font-size: 12px; margin-top: 4px;">Click "Create New Backup" to generate a compressed data archive.</p>
            </div>
        @endif
    </div>

</div>
@endsection

@section('scripts')
<script>
    function createBackup(event) {
        event.preventDefault();
        
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Creating backup...`;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch('{{ route("system.backups.create") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;

            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Error occurred.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerHTML = originalText;
            showToast('Error creating backup.', 'danger');
        });
    }

    function deleteBackup(event, filename) {
        event.preventDefault();
        if (!confirm(`Are you sure you want to delete the backup file "${filename}" permanently?`)) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch(`/system/backups/delete/${filename}`, {
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
            showToast('Error deleting backup.', 'danger');
        });
    }

    function restoreBackup(event, filename) {
        event.preventDefault();
        
        let confirmRestore = confirm(`CRITICAL WARNING: Are you absolutely sure you want to restore the database from backup "${filename}"? This will overwrite the current database!`);
        if (!confirmRestore) return;

        let doubleConfirm = confirm(`SECOND CONFIRMATION: Restoring the backup will disconnect any currently active operations and reset inventory mappings. Do you still want to proceed?`);
        if (!doubleConfirm) return;

        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Restoring database...`;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch(`/system/backups/restore/${filename}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalText;

            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(data.message || 'Restore failed.', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerHTML = originalText;
            showToast('Error restoring database.', 'danger');
        });
    }
</script>
@endsection
