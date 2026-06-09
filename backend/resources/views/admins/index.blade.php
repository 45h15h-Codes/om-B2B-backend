@extends('layouts.app')

@section('styles')
<style>
    .admin-container {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    /* Top Stats Bar */
    .admin-stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .admin-stat-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.01);
    }

    .admin-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .icon-primary {
        background-color: var(--primary-light);
        color: var(--primary-color);
    }

    .icon-success {
        background-color: #f0fff4;
        color: var(--success-color);
    }

    .icon-warning {
        background-color: #fffaf0;
        color: var(--warning-color);
    }

    .admin-stat-info {
        display: flex;
        flex-direction: column;
    }

    .admin-stat-value {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-color);
    }

    .admin-stat-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Table Grid Card */
    .admin-list-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 20px;
        margin-bottom: 24px;
    }

    .admin-title {
        font-size: 18px;
        font-weight: 700;
        color: #2d3748;
    }

    /* Actions Table */
    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-table th {
        background-color: #f8fafc;
        padding: 14px 18px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border-color);
        text-align: left;
    }

    .admin-table td {
        padding: 16px 18px;
        font-size: 14px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
        font-weight: 500;
    }

    .admin-table tr:hover td {
        background-color: #fcfdfe;
    }

    .admin-table tr:last-child td {
        border-bottom: none;
    }

    .user-avatar-badge {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #edf2f7;
        color: #4a5568;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
    }

    .action-group {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    .btn {
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 6px;
        border: 1px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: #ffffff;
    }

    .btn-primary:hover {
        background-color: var(--primary-hover);
    }

    .btn-success {
        background-color: var(--success-color);
        color: #ffffff;
    }

    .btn-success:hover {
        background-color: #2f855a;
    }

    .btn-danger {
        background-color: #fff5f5;
        border-color: #fed7d7;
        color: var(--error-color);
    }

    .btn-danger:hover {
        background-color: var(--error-color);
        color: #ffffff;
        border-color: var(--error-color);
    }

    /* Right-side offcanvas sidebar (replaces modal) */
    .admin-modal-overlay {
        position: fixed;
        inset: 0; /* top:0; right:0; bottom:0; left:0 */
        background-color: rgba(15, 23, 42, 0.35);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 9999;
        display: none;
        transition: background-color 0.2s ease;
    }

    .admin-offcanvas {
        position: fixed;
        top: 0;
        right: -460px;
        height: 100vh;
        width: 420px;
        max-width: 92vw;
        background-color: var(--card-bg, #ffffff);
        border-left: 1px solid var(--border-color, #e2e8f0);
        box-shadow: -8px 0 30px rgba(15, 23, 42, 0.12);
        z-index: 10000;
        padding: 28px;
        transition: right 0.28s cubic-bezier(0.2, 0.9, 0.2, 1);
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .admin-offcanvas.show {
        right: 0;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.98) translateY(6px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Modal Header */
    .admin-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .admin-modal-title {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }

    .admin-modal-title i {
        color: var(--primary-color, #108bb6);
    }

    .admin-modal-close {
        background: none;
        border: none;
        font-size: 28px;
        font-weight: 300;
        color: #94a3b8;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s ease;
    }

    .admin-modal-close:hover {
        color: #334155;
    }

    .admin-modal-subtitle {
        font-size: 13px;
        color: #64748b;
        margin: 0 0 24px 0;
        font-weight: 500;
    }

    /* Form Layout inside Modal */
    .modal-form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 20px;
        text-align: left;
    }

    .modal-form-group label {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .modal-form-input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        color: #1e293b;
        background-color: #f8fafc;
        transition: all 0.2s ease;
    }

    .modal-form-input:focus {
        outline: none;
        border-color: var(--primary-color, #108bb6);
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(16, 139, 182, 0.15);
    }

    .modal-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        border-top: 1px solid var(--border-color, #e2e8f0);
        padding-top: 20px;
        margin-top: 24px;
    }

    /* Delete confirmation overlay */
    .confirm-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,0.45);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 13000;
    }

    .confirm-box {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        padding: 22px 24px;
        border-radius: 12px;
        width: 420px;
        max-width: 92vw;
        box-shadow: 0 12px 30px rgba(15,23,42,0.12);
        text-align: left;
    }

    .confirm-actions {
        display:flex;
        justify-content:flex-end;
        gap:10px;
        margin-top:16px;
    }
</style>
@endsection

@section('content')
<div class="admin-container">
    
    <!-- Top Stats Row -->
    <div class="admin-stats-row">
        <div class="admin-stat-card">
            <div class="admin-stat-icon icon-primary">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-value">{{ $admins->count() }}</span>
                <span class="admin-stat-label">Normal Admins</span>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-icon icon-success">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-value">1</span>
                <span class="admin-stat-label">Super Admins</span>
            </div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-icon icon-warning">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="admin-stat-info">
                <span class="admin-stat-value">Active</span>
                <span class="admin-stat-label">System Control</span>
            </div>
        </div>
    </div>

    <!-- Admin User Table -->
    <div class="admin-list-card">
        <div class="admin-header">
            <div class="admin-title">Add on User Management</div>
            <button type="button" class="btn btn-primary" onclick="openAdminModal()">
                <i class="fa-solid fa-user-plus"></i> Add New Normal Admin
            </button>
        </div>

        @if($admins->count() > 0)
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Avatar</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Role Badge</th>
                        <th>Created Date</th>
                        <th style="text-align: right;">Console Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($admins as $admin)
                        <tr>
                            <td>
                                <div class="user-avatar-badge">
                                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                                </div>
                            </td>
                            <td style="font-weight: 700; color: var(--primary-color);">{{ $admin->name }}</td>
                            <td style="font-weight: 600;">{{ $admin->email }}</td>
                            <td>
                                <span style="font-size: 11px; font-weight: 700; background-color: var(--primary-light); color: var(--primary-color); padding: 4px 8px; border-radius: 4px;">
                                    Normal Admin
                                </span>
                            </td>
                            <td>{{ $admin->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="action-group">
                                    <!-- Impersonate Button -->
                                    <form action="{{ route('admins.impersonate', $admin->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success" title="Access this admin's workspace panel">
                                            <i class="fa-solid fa-user-secret"></i> Access Panel
                                        </button>
                                    </form>

                                    <!-- Edit Button (opens offcanvas prefilled) -->
                                    <button type="button" class="btn btn-secondary edit-admin-btn" style="border-color: #cbd5e0; color: #4a5568;" title="Edit admin credentials"
                                        data-id="{{ $admin->id }}"
                                        data-name="{{ $admin->name }}"
                                        data-email="{{ $admin->email }}"
                                        data-update-url="{{ route('admins.update', $admin->id) }}">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>

                                    <!-- Delete Button -->
                                    <form action="{{ route('admins.destroy', $admin->id) }}" method="POST" class="confirm-delete-form" data-username="{{ $admin->name }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" title="Remove administrator account">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="padding: 60px; text-align: center; color: var(--text-muted); font-weight: 500;">
                <i class="fa-solid fa-users-slash" style="font-size: 48px; color: #cbd5e0; margin-bottom: 16px; display: block;"></i>
                No additional Normal Admins created yet. Click the button above to register one.
            </div>
        @endif
    </div>

    <!-- Admin Offcanvas Sidebar + Backdrop -->
    <div id="adminModal" class="admin-modal-overlay" aria-hidden="true">
        <div id="adminOffcanvas" class="admin-offcanvas" role="dialog" aria-modal="true" aria-labelledby="adminOffcanvasTitle">
            <div class="admin-modal-header" style="margin-bottom: 12px;">
                <h2 id="adminOffcanvasTitle" class="admin-modal-title" style="font-size:18px;">
                    <i class="fa-solid fa-user-plus"></i> <span id="adminOffcanvasHeading">Add New Normal Admin</span>
                </h2>
                <button class="admin-modal-close" onclick="closeAdminModal()" aria-label="Close sidebar">&times;</button>
            </div>
            <p class="admin-modal-subtitle">Register a new normal administrator account to manage inventory logs.</p>

            <!-- Validation Errors -->
            @if($errors->any())
                <div class="alert alert-error" style="background-color: #fff5f5; border: 1px solid #fed7d7; color: var(--error-color); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form id="adminForm" action="{{ route('admins.store') }}" method="POST">
                @csrf

                <input type="hidden" name="_method" id="adminFormMethod" value="">

                <!-- Name Input -->
                <div class="modal-form-group">
                    <label for="modal-name">Full Name (Username)</label>
                    <input type="text" id="modal-name" name="name" class="modal-form-input" placeholder="e.g. Raj Patel" required value="{{ old('name') }}" autocomplete="off">
                </div>

                <!-- Email Input -->
                <div class="modal-form-group">
                    <label for="modal-email">Email Address</label>
                    <input type="email" id="modal-email" name="email" class="modal-form-input" placeholder="e.g. raj@omgems.com" required value="{{ old('email') }}" autocomplete="off">
                </div>

                <!-- Password Input -->
                <div class="modal-form-group">
                    <label for="modal-password">Security Password</label>
                    <input type="password" id="modal-password" name="password" class="modal-form-input" placeholder="Min 8 characters" required autocomplete="new-password">
                </div>

                <!-- Password Confirmation -->
                <div class="modal-form-group">
                    <label for="modal-password-confirm">Confirm Password</label>
                    <input type="password" id="modal-password-confirm" name="password_confirmation" class="modal-form-input" placeholder="Re-enter password" required autocomplete="new-password">
                </div>

                <!-- Submit and Cancel Actions -->
                <div class="modal-form-actions" style="position: sticky; bottom: 0; background: transparent; margin-top: 18px; padding-top: 12px;">
                    <button type="button" class="btn btn-secondary" onclick="closeAdminModal()">
                        Cancel
                    </button>
                    <button type="submit" id="adminFormSubmit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Register Account
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Delete Confirmation Overlay -->
<div id="confirmOverlay" class="confirm-overlay" aria-hidden="true">
    <div class="confirm-box" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <h3 id="confirmTitle" style="margin:0 0 8px 0; font-size:18px;">Confirm delete</h3>
        <p class="confirm-message" style="margin:0 0 8px 0; color:var(--text-muted); font-weight:600;">Are you sure you want to remove this admin account?</p>
        <div class="confirm-actions">
            <button type="button" class="btn btn-secondary" id="confirmCancel">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmOk">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openAdminModal() {
        const overlay = document.getElementById('adminModal');
        const offcanvas = document.getElementById('adminOffcanvas');
        if (overlay && offcanvas) {
            overlay.style.display = 'block';
            // allow a tiny delay so transition feels natural
            requestAnimationFrame(() => offcanvas.classList.add('show'));
            document.body.style.overflow = 'hidden';
        }
    }

    function closeAdminModal() {
        const overlay = document.getElementById('adminModal');
        const offcanvas = document.getElementById('adminOffcanvas');
        if (overlay && offcanvas) {
            offcanvas.classList.remove('show');
            // wait for transition to finish then hide overlay
            setTimeout(() => {
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            }, 280);
        }
    }

    // Close when clicking on backdrop (but not when clicking inside the offcanvas)
    window.addEventListener('click', function(event) {
        const overlay = document.getElementById('adminModal');
        const offcanvas = document.getElementById('adminOffcanvas');
        if (!overlay || !offcanvas) return;
        if (event.target === overlay) {
            closeAdminModal();
        }
    });

    // Close on Escape key
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const overlay = document.getElementById('adminModal');
            if (overlay && overlay.style.display === 'block') closeAdminModal();
        }
    });

    // Auto-open on validation errors
    @if($errors->any())
    document.addEventListener('DOMContentLoaded', function() {
        openAdminModal();
    });
    @endif

    // Delete confirmation flow
    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.getElementById('confirmOverlay');
        const confirmMsg = overlay ? overlay.querySelector('.confirm-message') : null;
        const btnOk = document.getElementById('confirmOk');
        const btnCancel = document.getElementById('confirmCancel');
        let pendingForm = null;

        function showConfirm(form) {
            pendingForm = form;
            const name = form.dataset.username || '';
            if (confirmMsg) confirmMsg.textContent = name ? `Are you sure you want to delete \"${name}\"? This action cannot be undone.` : 'Are you sure you want to delete this admin account?';
            if (overlay) overlay.style.display = 'flex';
        }

        function hideConfirm() {
            if (overlay) overlay.style.display = 'none';
            pendingForm = null;
        }

        // Attach submit handler to all delete forms
        document.querySelectorAll('.confirm-delete-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                showConfirm(form);
            });
        });

        if (btnCancel) btnCancel.addEventListener('click', hideConfirm);
        if (overlay) overlay.addEventListener('click', function(e) { if (e.target === overlay) hideConfirm(); });
        if (btnOk) btnOk.addEventListener('click', function() {
            if (pendingForm) pendingForm.submit();
        });
    });

    // Edit form flow (pre-fill offcanvas for editing)
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.edit-admin-btn');
        const form = document.getElementById('adminForm');
        const heading = document.getElementById('adminOffcanvasHeading');
        const submitBtn = document.getElementById('adminFormSubmit');
        const methodInput = document.getElementById('adminFormMethod');

        function resetFormToCreate() {
            form.action = "{{ route('admins.store') }}";
            methodInput.value = '';
            heading.textContent = 'Add New Normal Admin';
            submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Register Account';
            form.querySelector('#modal-name').value = '';
            form.querySelector('#modal-email').value = '';
            form.querySelector('#modal-password').value = '';
            form.querySelector('#modal-password-confirm').value = '';
        }

        editButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = btn.dataset.id;
                const name = btn.dataset.name || '';
                const email = btn.dataset.email || '';
                const updateUrl = btn.dataset.updateUrl;

                // set form to update
                form.action = updateUrl;
                methodInput.value = 'PATCH';
                heading.textContent = 'Edit Normal Admin';
                submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Update Account';

                // prefill values
                form.querySelector('#modal-name').value = name;
                form.querySelector('#modal-email').value = email;
                // clear password fields for security
                form.querySelector('#modal-password').value = '';
                form.querySelector('#modal-password-confirm').value = '';

                openAdminModal();
            });
        });

        // When offcanvas closes, reset to create mode after delay
        const offcanvas = document.getElementById('adminOffcanvas');
        const overlay = document.getElementById('adminModal');
        const observer = new MutationObserver(function() {
            // if offcanvas no longer has show class, reset
            if (!offcanvas.classList.contains('show')) {
                setTimeout(resetFormToCreate, 320);
            }
        });
        if (offcanvas) observer.observe(offcanvas, { attributes: true, attributeFilter: ['class'] });
    });

</script>
@endsection
