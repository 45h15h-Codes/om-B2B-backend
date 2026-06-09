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
</style>

@section('content')

@php
    $isAdmin = (session('admin_role', 'normal_admin') === 'super_admin');
@endphp

<div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
    <a href="{{ route('jewelery.index') }}" class="btn btn-secondary" style="text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 10px 20px;">
        <i class="fa-solid fa-arrow-left"></i> Back to List
    </a>
    
    <div style="display: flex; gap: 10px;">
        @if($isAdmin || $jewelery->user_id === Auth::id())
            <a href="{{ route('jewelery.edit', $jewelery->id) }}" class="btn btn-primary" style="text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 10px 20px;">
                <i class="fa-solid fa-pen-to-square"></i> Edit Specifications
            </a>
        @endif
        
        @if($isAdmin)
            @if($jewelery->status !== 'Approved')
                <form action="{{ route('jewelery.approve', $jewelery->id) }}" method="POST" style="display: inline-block;">
                    @csrf
                    <button type="submit" class="btn btn-success" style="background-color: var(--success-color); color: white; border: none; font-weight: 600; padding: 10px 20px; border-radius: 8px; display: flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(56, 161, 105, 0.2);">
                        <i class="fa-solid fa-circle-check"></i> Approve
                    </button>
                </form>
            @endif
            @if($jewelery->status !== 'Rejected')
                <form action="{{ route('jewelery.reject', $jewelery->id) }}" method="POST" style="display: inline-block;">
                    @csrf
                    <button type="submit" class="btn btn-danger" style="background-color: var(--error-color); color: white; border: none; font-weight: 600; padding: 10px 20px; border-radius: 8px; display: flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(229, 62, 62, 0.2);">
                        <i class="fa-solid fa-circle-xmark"></i> Reject
                    </button>
                </form>
            @endif
            <form action="{{ route('jewelery.destroy', $jewelery->id) }}" method="POST" style="display: inline-block;" class="confirm-delete-form" data-username="{{ $jewelery->sku ?? $jewelery->id }}">
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
            <span>Jewelry Specification Details</span>
            <span class="badge badge-{{ strtolower($jewelery->status) }}">{{ $jewelery->status }}</span>
        </div>

        <!-- 1. General Information -->
        <div class="specs-section">
            <div class="specs-section-title">
                <i class="fa-solid fa-circle-info"></i> 1. General Information
            </div>
            <div class="specs-grid">
                <div class="spec-item">
                    <span class="spec-label">SKU</span>
                    <span class="spec-value">{{ $jewelery->sku ?: 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Name</span>
                    <span class="spec-value">{{ $jewelery->name ?: 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Type</span>
                    <span class="spec-value">{{ $jewelery->type ?: 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Price</span>
                    <span class="spec-value">{{ $jewelery->price ? '$' . number_format($jewelery->price, 2) : 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Location</span>
                    <span class="spec-value">{{ $jewelery->location ?: 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- 2. Detail Attributes -->
        <div class="specs-section">
            <div class="specs-section-title">
                <i class="fa-solid fa-ring"></i> 2. Details & Grading Specs
            </div>
            <div class="specs-grid">
                <div class="spec-item">
                    <span class="spec-label">Metal Type / Karat</span>
                    <span class="spec-value">{{ implode(' ', array_filter([$jewelery->metal_type, $jewelery->metal_karat])) ?: 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Total Weight</span>
                    <span class="spec-value">{{ $jewelery->total_weight ? $jewelery->total_weight . ' g' : 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Gemstone Type</span>
                    <span class="spec-value">{{ $jewelery->gemstone_type ?: 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Gemstone Shape</span>
                    <span class="spec-value">{{ $jewelery->gemstone_shape ?: 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Carat Weight</span>
                    <span class="spec-value">{{ $jewelery->carat_weight ? $jewelery->carat_weight . ' ct' : 'N/A' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Lab & Lab No</span>
                    <span class="spec-value">{{ $jewelery->lab }} @if($jewelery->lab_no) (No. {{ $jewelery->lab_no }}) @endif</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Treatment</span>
                    <span class="spec-value">{{ $jewelery->treatment ?: 'None' }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">In Stock</span>
                    <span class="spec-value">{{ $jewelery->in_stock ?? 1 }}</span>
                </div>
            </div>
        </div>

        <!-- 3. System / Audit Information -->
        <div class="specs-section" style="margin-bottom: 0;">
            <div class="specs-section-title">
                <i class="fa-solid fa-clock-rotate-left"></i> System Audit Trail
            </div>
            <div class="specs-grid">
                <div class="spec-item">
                    <span class="spec-label">Created By</span>
                    <span class="spec-value">{{ $jewelery->created_by }}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Created At</span>
                    <span class="spec-value">{{ $jewelery->created_at->format('M d, Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- 4. Visual Inventory Lifecycle Timeline -->
        <div class="specs-section" style="margin-top: 30px;">
            <div class="specs-section-title">
                <i class="fa-solid fa-timeline"></i> 4. Visual Inventory Lifecycle Timeline
            </div>
            
            <div style="position: relative; padding-left: 24px; margin-top: 15px;">
                <div style="position: absolute; left: 7px; top: 8px; bottom: 8px; width: 2px; background-color: var(--border-color);"></div>

                <!-- Product Created entry -->
                <div style="position: relative; margin-bottom: 20px;">
                    <div style="position: absolute; left: -22px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background-color: var(--primary-color); border: 2px solid white; box-shadow: 0 0 0 2px var(--primary-color);"></div>
                    <div style="font-size: 13.5px; font-weight: 700; color: var(--text-color);">Product Created</div>
                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                        {{ $jewelery->created_at->format('Y-m-d H:i:s') }} | Initial status set to available
                    </div>
                </div>

                @php
                    $histories = $jewelery->inventoryHistories()->with('user')->orderBy('created_at', 'asc')->get();
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
                    @if($jewelery->inventory_status && $jewelery->inventory_status !== 'available')
                        <div style="position: relative; margin-bottom: 20px;">
                            <div style="position: absolute; left: -22px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background-color: var(--warning-color); border: 2px solid white; box-shadow: 0 0 0 2px var(--warning-color);"></div>
                            <div style="font-size: 13.5px; font-weight: 700; color: var(--text-color);">
                                Current Status: {{ ucfirst($jewelery->inventory_status) }}
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
            <h3 style="font-weight: 700; font-size: 15px; margin-bottom: 15px; color: var(--text-color);">Jewelry Assets</h3>
            <div class="media-preview-box">
                @if($jewelery->image_url)
                    @if(str_starts_with($jewelery->image_url, 'http'))
                        <img src="{{ $jewelery->image_url }}" class="media-preview-img" alt="Jewelry Photo">
                    @elseif(file_exists(public_path($jewelery->image_url)))
                        <img src="{{ asset($jewelery->image_url) }}" class="media-preview-img" alt="Jewelry Photo">
                    @else
                        <img src="{{ asset('storage/' . $jewelery->image_url) }}" class="media-preview-img" alt="Jewelry Photo">
                    @endif
                @else
                    <div class="media-placeholder">
                        <i class="fa-solid fa-ring"></i>
                        No Jewelry Image uploaded
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
