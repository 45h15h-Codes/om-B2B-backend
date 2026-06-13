@extends('layouts.app')

@section('styles')
<style>
    .details-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    .info-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .card-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 24px;
        color: var(--text-color);
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .info-section {
        margin-bottom: 30px;
    }

    .info-section-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--primary-color);
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
        border-bottom: 1px solid #f7fafc;
        padding-bottom: 10px;
        font-size: 14.5px;
    }

    .info-item.full-width {
        grid-column: span 2;
    }

    .info-label {
        font-weight: 700;
        color: var(--text-muted);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        color: var(--text-color);
        font-weight: 500;
    }

    .sidebar-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        height: fit-content;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .badge-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
        text-transform: capitalize;
    }

    .badge-status.Pending {
        background-color: #fef3c7;
        color: #92400e;
    }

    .badge-status.Approved {
        background-color: #dcfce7;
        color: #166534;
    }

    .badge-status.Rejected {
        background-color: #fee2e2;
        color: #991b1b;
    }

    /* Modal Form / Action styling */
    .action-box {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 16px;
        background-color: #fcfdfe;
    }

    .action-header {
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-textarea {
        width: 100%;
        min-height: 80px;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-family: inherit;
        font-size: 13.5px;
        resize: vertical;
        background-color: #ffffff;
        margin-bottom: 12px;
    }

    .form-textarea:focus {
        outline: none;
        border-color: var(--primary-color);
    }
</style>
@endsection

@section('content')
<div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
    <a href="{{ route('partnership-requests.index') }}" class="btn btn-secondary" style="text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 10px 20px;">
        <i class="fa-solid fa-arrow-left"></i> Back to List
    </a>
</div>

<!-- Warning for Email Conflict -->
@if($emailConflict && $partnershipRequest->status === 'Pending')
    <div class="alert alert-error" style="background-color: #fff5f5; border: 1px solid #fed7d7; color: var(--error-color); padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 600;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 18px; margin-right: 8px;"></i>
        <span><strong>Warning:</strong> A user account with the email <code>{{ $partnershipRequest->email }}</code> already exists in the system. Approving this request will fail until the email is resolved or changed.</span>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error" style="background-color: #fff5f5; border: 1px solid #fed7d7; color: var(--error-color); padding: 14px 18px; border-radius: 8px; margin-bottom: 24px; font-size: 13.5px; font-weight: 600;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning" style="background-color: #fffaf0; border: 1px solid #feebc8; color: #dd6b20; padding: 14px 18px; border-radius: 8px; margin-bottom: 24px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-exclamation" style="font-size: 16px;"></i>
        <span>{{ session('warning') }}</span>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="details-container">
    <!-- Left column: Submitted Information Details -->
    <div class="info-card">
        <div class="card-title">
            <span>Inquiry Details</span>
            <span class="badge-status {{ $partnershipRequest->status }}">{{ $partnershipRequest->status }}</span>
        </div>

        <!-- 1. Applicant Profile -->
        <div class="info-section">
            <div class="info-section-title">
                <i class="fa-solid fa-user"></i> 1. Applicant Information
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value">{{ $partnershipRequest->full_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email Address</span>
                    <span class="info-value" style="font-weight: 700;">{{ $partnershipRequest->email }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Phone Number</span>
                    <span class="info-value">{{ $partnershipRequest->phone_number }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Submitted Date</span>
                    <span class="info-value">{{ $partnershipRequest->created_at->format('M d, Y \a\t H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- 2. Business Profile -->
        <div class="info-section">
            <div class="info-section-title">
                <i class="fa-solid fa-building"></i> 2. Business Profile
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Business Name</span>
                    <span class="info-value" style="font-weight: 700; color: var(--primary-color);">{{ $partnershipRequest->business_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Business Type</span>
                    <span class="info-value">{{ $partnershipRequest->business_type }}</span>
                </div>
                <div class="info-item full-width">
                    <span class="info-label">Purpose Of Partnership</span>
                    <span class="info-value" style="white-space: pre-wrap; line-height: 1.5; background-color: #f8fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: 6px; display: block; margin-top: 4px;">{{ $partnershipRequest->purpose }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Audit Log Details -->
        <div class="info-section" style="margin-bottom: 0;">
            <div class="info-section-title">
                <i class="fa-solid fa-clock-rotate-left"></i> 3. Review History & Audit Trail
            </div>
            <div class="info-grid">
                @if($partnershipRequest->status === 'Approved')
                    <div class="info-item">
                        <span class="info-label">Approved By</span>
                        <span class="info-value">{{ $partnershipRequest->approvedBy ? $partnershipRequest->approvedBy->name : 'System/Admin' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Approved At</span>
                        <span class="info-value">{{ $partnershipRequest->approved_at ? $partnershipRequest->approved_at->format('M d, Y H:i') : '-' }}</span>
                    </div>
                    @if($partnershipRequest->convertedUser)
                        <div class="info-item full-width" style="border-top: 1px dashed var(--border-color); padding-top: 12px; margin-top: 8px;">
                            <span class="info-label">Linked Admin Account</span>
                            <span class="info-value" style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                <i class="fa-solid fa-user-gear" style="color: var(--primary-color);"></i>
                                <strong>{{ $partnershipRequest->convertedUser->name }}</strong> ({{ $partnershipRequest->convertedUser->email }})
                                @if(session('admin_role') === 'super_admin')
                                    <a href="{{ route('admins.index') }}" class="btn btn-secondary" style="padding: 4px 10px; font-size: 11px; margin-left: 10px;">
                                        <i class="fa-solid fa-users-gear"></i> Manage Users
                                    </a>
                                @endif
                            </span>
                        </div>
                    @endif
                @elseif($partnershipRequest->status === 'Rejected')
                    <div class="info-item">
                        <span class="info-label">Rejected By</span>
                        <span class="info-value">{{ $partnershipRequest->rejectedBy ? $partnershipRequest->rejectedBy->name : 'System/Admin' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Rejected At</span>
                        <span class="info-value">{{ $partnershipRequest->rejected_at ? $partnershipRequest->rejected_at->format('M d, Y H:i') : '-' }}</span>
                    </div>
                @else
                    <div class="info-item full-width">
                        <span class="info-value" style="color: var(--text-muted); font-style: italic;">This inquiry is currently Pending review and has not yet been processed.</span>
                    </div>
                @endif

                @if(!empty($partnershipRequest->notes))
                    <div class="info-item full-width" style="border-top: 1px dashed var(--border-color); padding-top: 12px; margin-top: 8px;">
                        <span class="info-label">Auditor Notes / Remarks</span>
                        <span class="info-value" style="background-color: #f7fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: 6px; display: block; margin-top: 4px; font-style: italic; color: #4a5568;">&ldquo;{{ $partnershipRequest->notes }}&rdquo;</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right column: Status and Action Forms -->
    <div class="sidebar-card">
        <h3 style="font-weight: 700; font-size: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 10px;">Review Actions</h3>

        @if($partnershipRequest->status === 'Pending')
            <!-- Action Form for Approval -->
            <div class="action-box" style="border-color: #c6f6d5; background-color: #f0fff4;">
                <div class="action-header" style="color: var(--success-color);">
                    <i class="fa-solid fa-circle-check"></i> Approve & Activate Partner
                </div>
                <p style="font-size: 12px; color: #4a5568; margin-bottom: 12px;">This will register a new Normal Admin account, seed standard permissions, and email credentials to the partner.</p>
                
                <form action="{{ route('partnership-requests.approve', $partnershipRequest->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to approve this partnership inquiry?')">
                    @csrf
                    <textarea name="notes" class="form-textarea" placeholder="Add optional approval notes..."></textarea>
                    
                    @if($emailConflict)
                        <button type="submit" class="btn btn-primary" style="width: 100%; background-color: #cbd5e0; border-color: #cbd5e0; color: #718096; cursor: not-allowed;" disabled>
                            <i class="fa-solid fa-user-lock"></i> Email Conflicted
                        </button>
                    @else
                        <button type="submit" class="btn btn-primary" style="width: 100%; background-color: var(--success-color); border-color: var(--success-color); color: #ffffff;">
                            <i class="fa-solid fa-circle-check"></i> Approve Inquiry
                        </button>
                    @endif
                </form>
            </div>

            <!-- Action Form for Rejection -->
            <div class="action-box" style="border-color: #fed7d7; background-color: #fff5f5;">
                <div class="action-header" style="color: var(--error-color);">
                    <i class="fa-solid fa-circle-xmark"></i> Reject Inquiry
                </div>
                <p style="font-size: 12px; color: #4a5568; margin-bottom: 12px;">Reject this application and send a polite notification update to the applicant.</p>
                
                <form action="{{ route('partnership-requests.reject', $partnershipRequest->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to reject this partnership inquiry?')">
                    @csrf
                    <textarea name="notes" class="form-textarea" placeholder="Reason for rejection (included in email)..." required></textarea>
                    <button type="submit" class="btn btn-danger" style="width: 100%; color: #ffffff; background-color: var(--error-color); border-color: var(--error-color);">
                        <i class="fa-solid fa-circle-xmark"></i> Reject Inquiry
                    </button>
                </form>
            </div>
        @else
            <div style="text-align: center; padding: 20px 10px; color: var(--text-muted); font-size: 13.5px; font-weight: 600; background-color: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px;">
                <i class="fa-solid fa-lock" style="font-size: 24px; margin-bottom: 8px; display: block; color: #cbd5e0;"></i>
                Processed Application Locked
            </div>
        @endif
    </div>
</div>
@endsection
