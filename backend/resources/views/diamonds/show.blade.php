@extends('layouts.app')


<style>
    .show-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    .specs-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .specs-card-title {
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

    .specs-section {
        margin-bottom: 30px;
    }

    .specs-section-title {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--primary-color);
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .specs-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .spec-item {
        display: flex;
        border-bottom: 1px solid #f7fafc;
        padding-bottom: 10px;
        font-size: 14px;
    }

    .spec-label {
        font-weight: 600;
        color: var(--text-muted);
        width: 160px;
        flex-shrink: 0;
    }

    .spec-value {
        color: var(--text-color);
        font-weight: 500;
    }

    /* Media panel styling */
    .media-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        gap: 20px;
        height: fit-content;
    }

    .media-preview-box {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
        background-color: #f7fafc;
        aspect-ratio: 4/3;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .media-preview-img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .media-placeholder {
        text-align: center;
        color: var(--text-muted);
        font-weight: 500;
        font-size: 13px;
    }

    .media-placeholder i {
        font-size: 40px;
        color: #cbd5e0;
        margin-bottom: 12px;
        display: block;
    }

    .media-action-link {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        text-decoration: none;
        color: var(--text-color);
        font-weight: 600;
        font-size: 14px;
        background-color: #ffffff;
        transition: all 0.2s ease;
    }

    }

    /* Custom Confirmation Modal Dialog */
    .confirm-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
        display: none;
        z-index: 9999;
        animation: fadeIn 0.2s ease-out;
    }

    .confirm-modal-overlay.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .confirm-modal-box {
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        padding: 32px;
        max-width: 450px;
        width: 90%;
        animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .confirm-modal-header {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .confirm-modal-header i {
        font-size: 24px;
        color: var(--error-color);
    }

    .confirm-modal-message {
        font-size: 14px;
        color: var(--text-muted);
        margin-bottom: 24px;
        line-height: 1.5;
    }

    .confirm-modal-footer {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    .confirm-modal-btn {
        border: none;
        border-radius: 6px;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        pointer-events: auto;
    }

    .confirm-modal-btn-cancel {
        background-color: #f1f5f9;
        color: var(--text-color);
        border: 1px solid var(--border-color);
    }

    .confirm-modal-btn-cancel:hover {
        background-color: #e2e8f0;
    }

    .confirm-modal-btn-confirm {
        background-color: var(--error-color);
        color: white;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Toggle Switch styling for Approval Modal */
    .switch {
        position: relative;
        display: inline-block;
        width: 36px;
        height: 20px;
        vertical-align: middle;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 20px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: var(--primary-color, #108bb6);
    }

    input:checked + .slider:before {
        transform: translateX(16px);
    }
</style>


@section('content')

@php
    $isAdmin = (session('admin_role', 'normal_admin') === 'super_admin');
@endphp

<div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
    <a href="{{ route('diamonds.index') }}" class="btn btn-secondary" style="text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 10px 20px;">
        <i class="fa-solid fa-arrow-left"></i> Back to List
    </a>
    
    <div style="display: flex; gap: 10px;">
        @if($isAdmin || $diamond->user_id === Auth::id())
            <a href="{{ route('diamonds.edit', $diamond->id) }}" class="btn btn-primary" style="text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 10px 20px;">
                <i class="fa-solid fa-pen-to-square"></i> Edit Specifications
            </a>
        @endif
        
        @if($isAdmin)
            @if($diamond->status !== 'Approved')
                <button type="button" class="btn btn-success" 
                        onclick="openApproveModal('{{ route('diamonds.approve', $diamond->id) }}', event)"
                        style="background-color: var(--success-color); color: white; border: none; font-weight: 600; padding: 10px 20px; border-radius: 8px; display: flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(56, 161, 105, 0.2);">
                    <i class="fa-solid fa-circle-check"></i> Approve
                </button>
            @endif
            @if($diamond->status !== 'Rejected')
                <form action="{{ route('diamonds.reject', $diamond->id) }}" method="POST" style="display: inline-block;">
                    @csrf
                    <button type="submit" class="btn btn-danger" style="background-color: var(--error-color); color: white; border: none; font-weight: 600; padding: 10px 20px; border-radius: 8px; display: flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(229, 62, 62, 0.2);">
                        <i class="fa-solid fa-circle-xmark"></i> Reject
                    </button>
                </form>
            @endif
            <form action="{{ route('diamonds.destroy', $diamond->id) }}" method="POST" style="display: inline-block;" class="confirm-delete-form" data-username="{{ $diamond->stock_no ?? $diamond->id }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" style="background-color: var(--error-color); color: white; border: none; font-weight: 600; padding: 10px 20px; border-radius: 8px; display: flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(229, 62, 62, 0.2);">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </form>
        @endif
    </div>
</div>

<div class="show-container">
    <!-- Left Specifications column -->
    <div class="specs-card">
        <div class="specs-card-title">
            <span>Stock Specification Details</span>
            <span class="badge badge-{{ strtolower($diamond->status) }}">{{ $diamond->status }}</span>
        </div>

        <!-- 1. General Information -->
        <div class="specs-section">
            <div class="specs-section-title">
                <i class="fa-solid fa-circle-info"></i> 1. General Information
            </div>
            <div class="specs-grid">
                <div class="spec-item">
                    <span class="spec-label">Stock Number</span>
                    <span class="spec-value">{{ $diamond->stock_no ?: 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Availability</span>
                    <span class="spec-value">{{ $diamond->availability ?: 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Asking Price</span>
                    <span class="spec-value">
                        {{ $diamond->asking_price ? '$' . number_format($diamond->asking_price, 2) . ' (' . $diamond->asking_price_unit . ')' : 'N/A' }}
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Cash Price</span>
                    <span class="spec-value">
                        {{ $diamond->cash_price ? '$' . number_format($diamond->cash_price, 2) . ' (' . $diamond->cash_price_unit . ')' : 'N/A' }}
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Location</span>
                    <span class="spec-value">
                        {{ implode(', ', array_filter([$diamond->city, $diamond->state, $diamond->country])) ?: 'N/A' }}
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Inventory Status</span>
                    <span class="spec-value">
                        @if(($diamond->inventory_status ?? 'available') === 'available')
                            <span class="badge badge-approved" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-left: none; background-color: #dcfce7; color: #166534;">Available</span>
                        @elseif($diamond->inventory_status === 'on_hold')
                            <span class="badge badge-pending" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-left: none; background-color: #fef3c7; color: #92400e;">On Hold</span>
                        @elseif($diamond->inventory_status === 'sold')
                            <span class="badge badge-rejected" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-left: none; background-color: #fee2e2; color: #991b1b;">Sold</span>
                        @endif
                    </span>
                </div>
                @if(($diamond->inventory_status ?? 'available') === 'on_hold')
                    <div class="spec-item">
                        <span class="spec-label">Hold Reason</span>
                        <span class="spec-value">{{ $diamond->hold_reason ?: 'N/A' }}</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Hold At</span>
                        <span class="spec-value">{{ $diamond->hold_at ? \Carbon\Carbon::parse($diamond->hold_at)->format('Y-m-d H:i:s') : 'N/A' }}</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Holding Store</span>
                        <span class="spec-value">
                            @if($diamond->holdShopifyStore)
                                {{ $diamond->holdShopifyStore->store_name }}
                            @elseif($diamond->hold_shopify_store_id)
                                Store ID: {{ $diamond->hold_shopify_store_id }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                @elseif(($diamond->inventory_status ?? 'available') === 'sold')
                    <div class="spec-item">
                        <span class="spec-label">Sold Store</span>
                        <span class="spec-value">
                            @if($diamond->soldStore)
                                {{ $diamond->soldStore->store_name }}
                            @elseif($diamond->sold_store_id)
                                Store ID: {{ $diamond->sold_store_id }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Sold At</span>
                        <span class="spec-value">{{ $diamond->sold_at ? \Carbon\Carbon::parse($diamond->sold_at)->format('Y-m-d H:i:s') : 'N/A' }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. Report Information -->
        <div class="specs-section">
            <div class="specs-section-title">
                <i class="fa-solid fa-file-invoice"></i> 2. Report & Grading Specs
            </div>
            <div class="specs-grid">
                <div class="spec-item">
                    <span class="spec-label">Shape</span>
                    <span class="spec-value">
                        {{ $diamond->shape ?: 'N/A' }}
                        @if($diamond->advance_shape_detail)
                            ({{ $diamond->advance_shape_detail }})
                        @endif
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Carat Size</span>
                    <span class="spec-value">{{ $diamond->size ? number_format($diamond->size, 3) . ' ct' : 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Color</span>
                    <span class="spec-value">{{ $diamond->color ?: 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Clarity</span>
                    <span class="spec-value">{{ $diamond->clarity ?: 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Cut / Polish / Sym</span>
                    <span class="spec-value">
                        {{ implode(' / ', array_filter([$diamond->cut, $diamond->polish, $diamond->symmetry])) ?: 'N/A' }}
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Fluorescence</span>
                    <span class="spec-value">
                        {{ $diamond->fluorescence_intensity ?: 'None' }} 
                        @if($diamond->fluorescence_color && $diamond->fluorescence_color !== 'None')
                            ({{ $diamond->fluorescence_color }})
                        @endif
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Measurements</span>
                    <span class="spec-value">
                        @if($diamond->length || $diamond->width || $diamond->depth)
                            {{ $diamond->length ?: '0' }} x {{ $diamond->width ?: '0' }} x {{ $diamond->depth ?: '0' }} mm
                        @else
                            N/A
                        @endif
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Depth / Table %</span>
                    <span class="spec-value">
                        {{ $diamond->depth_percent ? $diamond->depth_percent . '%' : 'N/A' }} / {{ $diamond->table_percent ? $diamond->table_percent . '%' : 'N/A' }}
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Girdle</span>
                    <span class="spec-value">
                        {{ implode(' - ', array_filter([$diamond->girdle_min, $diamond->girdle_max])) }}
                        @if($diamond->girdle_condition) ({{ $diamond->girdle_condition }}) @endif
                        @if($diamond->girdle_percent) ({{ $diamond->girdle_percent }}%) @endif
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Culet</span>
                    <span class="spec-value">
                        {{ $diamond->culet_condition ?: 'N/A' }} 
                        @if($diamond->culet_size && $diamond->culet_size !== 'None') ({{ $diamond->culet_size }}) @endif
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Lab & Report #</span>
                    <span class="spec-value">
                        {{ $diamond->lab ?: 'N/A' }} 
                        @if($diamond->report_no) (No. {{ $diamond->report_no }}) @endif
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Report Date</span>
                    <span class="spec-value">{{ $diamond->report_date ? date('M d, Y', strtotime($diamond->report_date)) : 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Laser Inscription</span>
                    <span class="spec-value">{{ $diamond->laser_inscription ?: 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Treatment</span>
                    <span class="spec-value">{{ $diamond->treatment ?: 'None' }}</span>
                </div>
            </div>
            
            @if($diamond->fancy_color_enabled)
                <div style="margin-top: 15px; padding: 12px; background-color: #fffaf0; border: 1px solid #feebc8; border-radius: 8px;">
                    <strong style="color: #c05621; font-size: 13px; display: block; margin-bottom: 6px;"><i class="fa-solid fa-palette"></i> Fancy Color Profile Enabled</strong>
                    <div class="specs-grid">
                        <div class="spec-item" style="border: none;">
                            <span class="spec-label">Intensity / Overtone</span>
                            <span class="spec-value">{{ $diamond->fancy_color_intensity ?: 'N/A' }} / {{ $diamond->fancy_color_overtone ?: 'None' }}</span>
                        </div>
                        <div class="spec-item" style="border: none;">
                            <span class="spec-label">Colors</span>
                            <span class="spec-value">{{ implode(' + ', array_filter([$diamond->fancy_color_color1, $diamond->fancy_color_color2])) ?: 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- 3. Other Information -->
        <div class="specs-section">
            <div class="specs-section-title">
                <i class="fa-solid fa-circle-question"></i> 3. Additional & Other Info
            </div>
            <div class="specs-grid">
                <div class="spec-item">
                    <span class="spec-label">Matched Pair</span>
                    <span class="spec-value">
                        @if($diamond->is_matched_pair)
                            Yes (Stock: {{ $diamond->matched_pair_stock_no }} - {{ $diamond->is_pair_separable ? 'Separable' : 'Not Separable' }})
                        @else
                            No
                        @endif
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Parcel details</span>
                    <span class="spec-value">
                        @if($diamond->is_parcel)
                            Yes (Count: {{ $diamond->number_of_diamonds }})
                        @else
                            No
                        @endif
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Brand / Trade Show</span>
                    <span class="spec-value">
                        {{ implode(' / ', array_filter([$diamond->brand, $diamond->trade_show])) ?: 'N/A' }}
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Download Permit</span>
                    <span class="spec-value">{{ $diamond->allow_download ? 'Allowed' : 'Restricted' }}</span>
                </div>
                <div class="spec-item" style="grid-column: span 2;">
                    <span class="spec-label">Supplier Comments</span>
                    <span class="spec-value">{{ $diamond->supplier_comment ?: 'No supplier comments added.' }}</span>
                </div>
            </div>
        </div>

        <!-- 4. System / Audit Information -->
        <div class="specs-section" style="margin-bottom: 0;">
            <div class="specs-section-title">
                <i class="fa-solid fa-clock-rotate-left"></i> System Audit Trail
            </div>
            <div class="specs-grid">
                <div class="spec-item">
                    <span class="spec-label">Created By</span>
                    <span class="spec-value">{{ $diamond->created_by }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Created At</span>
                    <span class="spec-value">{{ $diamond->created_at->format('M d, Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- 5. Visual Inventory Lifecycle Timeline -->
        <div class="specs-section" style="margin-top: 30px;">
            <div class="specs-section-title">
                <i class="fa-solid fa-timeline"></i> 5. Visual Inventory Lifecycle Timeline
            </div>
            
            <div style="position: relative; padding-left: 24px; margin-top: 15px;">
                <!-- Vertical Line -->
                <div style="position: absolute; left: 7px; top: 8px; bottom: 8px; width: 2px; background-color: var(--border-color);"></div>

                <!-- Timeline Entries -->
                <!-- Start Event: Created -->
                <div style="position: relative; margin-bottom: 20px;">
                    <div style="position: absolute; left: -22px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background-color: var(--primary-color); border: 2px solid white; box-shadow: 0 0 0 2px var(--primary-color);"></div>
                    <div style="font-size: 13.5px; font-weight: 700; color: var(--text-color);">Product Created</div>
                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                        {{ $diamond->created_at->format('Y-m-d H:i:s') }} | Initial status set to available
                    </div>
                </div>

                @php
                    $histories = $diamond->inventoryHistories()->with('user')->orderBy('created_at', 'asc')->get();
                @endphp

                @forelse($histories as $history)
                    @php
                        $bulletColor = 'var(--primary-color)';
                        if ($history->new_value === 'on_hold' || $history->new_value === 'hold') {
                            $bulletColor = 'var(--warning-color)';
                        } elseif ($history->new_value === 'sold') {
                            $bulletColor = 'var(--error-color)';
                        } elseif ($history->new_value === 'available') {
                            $bulletColor = 'var(--success-color)';
                        }
                    @endphp
                    <div style="position: relative; margin-bottom: 20px;">
                        <div style="position: absolute; left: -22px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background-color: {{ $bulletColor }}; border: 2px solid white; box-shadow: 0 0 0 2px {{ $bulletColor }};"></div>
                        <div style="font-size: 13.5px; font-weight: 700; color: var(--text-color);">
                            Status Transition: {{ ucfirst($history->old_value) }} &rarr; {{ ucfirst($history->new_value) }}
                        </div>
                        @if($history->remarks)
                            <div style="font-size: 12px; color: var(--text-color); margin-top: 3px; font-style: italic;">
                                &ldquo;{{ $history->remarks }}&rdquo;
                            </div>
                        @endif
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                            {{ $history->created_at->format('Y-m-d H:i:s') }} | Operator: {{ $history->user ? $history->user->name : 'System/Webhook' }}
                        </div>
                    </div>
                @empty
                    <!-- If no status change records exist but status is not available, show current status as a step -->
                    @if($diamond->inventory_status && $diamond->inventory_status !== 'available')
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="position: absolute; left: -22px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background-color: var(--warning-color); border: 2px solid white; box-shadow: 0 0 0 2px var(--warning-color);"></div>
                            <div style="font-size: 13.5px; font-weight: 700; color: var(--text-color);">
                                Current Status: {{ ucfirst($diamond->inventory_status) }}
                            </div>
                            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                                No transition details logged in history database.
                            </div>
                        </div>
                    @endif
                @endforelse
            </div>
        </div>
    </div>

    <!-- Right Media Preview Panel -->
    <div class="media-card">
        <div>
            <h3 style="font-weight: 700; font-size: 15px; margin-bottom: 15px; color: var(--text-color);">Diamond Assets</h3>
            
            <!-- Diamond Image preview -->
            <div class="media-preview-box">
                @if($diamond->diamond_image)
                    @if(str_starts_with($diamond->diamond_image, 'http'))
                        {{-- Cloudinary or external URL --}}
                        <img src="{{ $diamond->diamond_image }}" class="media-preview-img" alt="Diamond Photo">
                    @elseif(file_exists(public_path($diamond->diamond_image)))
                        {{-- Local file in public directory --}}
                        <img src="{{ asset($diamond->diamond_image) }}" class="media-preview-img" alt="Diamond Photo">
                    @else
                        {{-- Local file in storage directory --}}
                        <img src="{{ asset('storage/' . $diamond->diamond_image) }}" class="media-preview-img" alt="Diamond Photo">
                    @endif
                @elseif($diamond->diamond_image_link)
                    <img src="{{ $diamond->diamond_image_link }}" class="media-preview-img" alt="Diamond Photo Link">
                @else
                    <div class="media-placeholder">
                        <i class="fa-solid fa-gem"></i>
                        No Diamond Image uploaded
                    </div>
                @endif
            </div>
        </div>

        <div>
            <h3 style="font-weight: 700; font-size: 15px; margin-bottom: 10px; color: var(--text-color);">Report Scans</h3>
            <div class="media-preview-box" style="aspect-ratio: 16/9; margin-bottom: 15px;">
                @if($diamond->report_file)
                    <div class="media-placeholder">
                        <i class="fa-solid fa-file-pdf" style="color: var(--error-color);"></i>
                        PDF Report Scan Saved
                    </div>
                @else
                    <div class="media-placeholder">
                        <i class="fa-solid fa-file-circle-exclamation"></i>
                        No report file attached
                    </div>
                @endif
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @if($diamond->report_file)
                    @if(str_starts_with($diamond->report_file, 'http'))
                        {{-- Cloudinary or external URL --}}
                        <a href="{{ $diamond->report_file }}" target="_blank" class="media-action-link">
                            <i class="fa-solid fa-download"></i> Download Report PDF
                        </a>
                    @elseif(file_exists(public_path($diamond->report_file)))
                        {{-- Local file in public directory --}}
                        <a href="{{ asset($diamond->report_file) }}" target="_blank" class="media-action-link">
                            <i class="fa-solid fa-download"></i> Download Report PDF
                        </a>
                    @else
                        {{-- Local file in storage directory --}}
                        <a href="{{ asset('storage/' . $diamond->report_file) }}" target="_blank" class="media-action-link">
                            <i class="fa-solid fa-download"></i> Download Report PDF
                        </a>
                    @endif
                @elseif($diamond->report_link)
                    <a href="{{ $diamond->report_link }}" target="_blank" class="media-action-link">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Report Link
                    </a>
                @endif

                @if($diamond->sarine_loupe)
                    <a href="{{ $diamond->sarine_loupe }}" target="_blank" class="media-action-link" style="border-color: #4a5568; color: #4a5568;">
                        <i class="fa-solid fa-binoculars"></i> View Sarine Loupe
                    </a>
                @endif
            </div>
        </div>

        @if($diamond->additional_comments)
            <div style="margin-top: 10px; border-top: 1px solid var(--border-color); padding-top: 15px;">
                <h4 style="font-weight: 700; font-size: 13px; margin-bottom: 6px;">Public Remarks:</h4>
                <p style="font-size: 13px; line-height: 1.5; color: var(--text-muted);">{{ $diamond->additional_comments }}</p>
            </div>
        @endif
    </div>
</div>

@if($isAdmin)
<!-- Super Admin Diamond Approval Modal -->
<div id="approveModalOverlay" class="confirm-modal-overlay">
    <div class="confirm-modal-box" style="max-width: 500px;">
        <div class="confirm-modal-header" style="color: var(--success-color, #2b6cb0);">
            <i class="fa-solid fa-circle-check"></i>
            <span>Approve & Sync Diamond</span>
        </div>
        <form id="approveModalForm" method="POST">
            @csrf
            <div class="confirm-modal-message" style="margin-bottom: 16px;">
                Select the Shopify storefronts to publish this diamond to:
            </div>
            
            <div style="max-height: 250px; overflow-y: auto; margin-bottom: 20px; border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 12px; background-color: #f8fafc;">
                @forelse($shopifyStores as $store)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border-color, #e2e8f0); margin-bottom: 8px;">
                        <label style="display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 14px; cursor: pointer; color: var(--text-color, #2d3748); margin-bottom: 0;">
                            <input type="checkbox" name="store_ids[]" value="{{ $store->id }}" id="store_checkbox_{{ $store->id }}" class="store-checkbox" onchange="toggleStorePublishState({{ $store->id }})" style="width: 18px; height: 18px; cursor: pointer; vertical-align: middle;">
                            <span>{{ $store->store_name }} ({{ $store->shop_domain }})</span>
                        </label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 12px; font-weight: 500; color: var(--text-muted, #718096);">Publish:</span>
                            <!-- Toggle switch -->
                            <label class="switch" style="margin-bottom: 0;">
                                <input type="hidden" name="is_published[{{ $store->id }}]" value="0">
                                <input type="checkbox" name="is_published[{{ $store->id }}]" value="1" id="publish_checkbox_{{ $store->id }}" class="publish-checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                @empty
                    <div style="padding: 16px; text-align: center; color: var(--text-muted, #718096); font-size: 14px;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size: 20px; color: var(--warning-color, #dd6b20); margin-bottom: 8px; display: block;"></i>
                        No Shopify storefronts connected.
                    </div>
                @endforelse
            </div>

            <div class="confirm-modal-footer">
                <button type="button" class="confirm-modal-btn confirm-modal-btn-cancel" onclick="closeApproveModal()">
                    Cancel
                </button>
                @if($shopifyStores->isEmpty())
                    <button type="submit" class="confirm-modal-btn" style="background-color: #cbd5e1; color: #64748b; cursor: not-allowed;" disabled>
                        <i class="fa-solid fa-circle-check"></i> Approve & Sync
                    </button>
                @else
                    <button type="submit" class="confirm-modal-btn" style="background-color: var(--success-color, #48bb78); color: white;">
                        <i class="fa-solid fa-circle-check"></i> Approve & Sync
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
    window.openApproveModal = function(actionUrl, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        const form = document.getElementById('approveModalForm');
        form.action = actionUrl;
        
        // Reset and check all storefront checkboxes and publish switches
        const storeCheckboxes = form.querySelectorAll('.store-checkbox');
        storeCheckboxes.forEach(cb => {
            cb.checked = true;
            const storeId = cb.value;
            const publishCheckbox = document.getElementById(`publish_checkbox_${storeId}`);
            if (publishCheckbox) {
                publishCheckbox.checked = true;
                publishCheckbox.disabled = false;
                const label = publishCheckbox.closest('label');
                if (label) label.style.opacity = '1';
            }
        });
        
        const overlay = document.getElementById('approveModalOverlay');
        if (overlay) {
            overlay.classList.add('active');
        }
    };

    window.closeApproveModal = function() {
        const overlay = document.getElementById('approveModalOverlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
    };

    window.toggleStorePublishState = function(storeId) {
        const storeCheckbox = document.getElementById(`store_checkbox_${storeId}`);
        const publishCheckbox = document.getElementById(`publish_checkbox_${storeId}`);
        if (storeCheckbox && publishCheckbox) {
            const label = publishCheckbox.closest('label');
            if (!storeCheckbox.checked) {
                publishCheckbox.checked = false;
                publishCheckbox.disabled = true;
                if (label) label.style.opacity = '0.5';
            } else {
                publishCheckbox.checked = true;
                publishCheckbox.disabled = false;
                if (label) label.style.opacity = '1';
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.getElementById('approveModalOverlay');
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeApproveModal();
                }
            });
        }
    });
</script>
@endif

@endsection
