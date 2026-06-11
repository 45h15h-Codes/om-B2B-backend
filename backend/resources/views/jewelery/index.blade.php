@extends('layouts.app')

@section('styles')
<style>
    /* Main Layout Split-Pane */
    .jewelery-layout {
        display: flex;
        gap: 30px;
        align-items: flex-start;
    }

    /* Left Sidebar Filter */
    .filter-sidebar {
        width: 250px;
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
    }

    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 12px;
    }

    .filter-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-color);
    }

    .filter-reset-btn {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 14px;
        transition: color 0.2s ease;
    }

    .filter-reset-btn:hover {
        color: var(--primary-color);
    }

    .filter-search-wrapper {
        position: relative;
    }

    .filter-search-input {
        width: 100%;
        padding: 10px 36px 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: inherit;
        font-size: 13px;
        background-color: #f7fafc;
    }

    .filter-search-icon {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 13px;
    }

    .filter-section {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .filter-section-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-color);
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .filter-checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 500;
        color: var(--text-muted);
        cursor: pointer;
        transition: color 0.2s ease;
    }

    .filter-checkbox-label:hover {
        color: var(--text-color);
    }

    .filter-checkbox-label input {
        accent-color: var(--primary-color);
        width: 15px;
        height: 15px;
        cursor: pointer;
    }

    /* Right Main Panel Content */
    .jewelery-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 30px;
    }

    /* Top Tabs Navigation */
    .jewelery-tabs {
        display: flex;
        border-bottom: 2px solid var(--border-color);
        gap: 30px;
        margin-bottom: 10px;
    }

    .jewelery-tab-link {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-muted);
        text-decoration: none;
        padding-bottom: 12px;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .jewelery-tab-link.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    /* Horizontal Categories Slider */
    .categories-slider {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        padding-bottom: 8px;
        scrollbar-width: thin;
    }

    .category-slider-btn {
        flex-shrink: 0;
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 12px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 90px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        color: var(--text-muted);
    }

    .category-slider-btn i {
        font-size: 20px;
        color: var(--primary-color);
    }

    .category-slider-btn span {
        font-size: 11px;
        font-weight: 700;
    }

    .category-slider-btn:hover, .category-slider-btn.active {
        border-color: var(--primary-color);
        background-color: var(--primary-light);
        color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(16, 139, 182, 0.08);
    }

    /* Grid Panel Sizing */
    .grid-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .grid-title-stats {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-color);
    }

    .grid-actions {
        display: flex;
        gap: 10px;
    }

    .grid-view-btn {
        padding: 8px 16px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background-color: var(--card-bg);
        color: var(--text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }

    .grid-view-btn:hover {
        background-color: var(--primary-light);
        color: var(--primary-color);
        border-color: var(--primary-color);
    }

    /* Jewelry Product Cards Grid */
    .jewelery-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .jewelery-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    }

    .jewelery-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }

    .card-img-wrapper {
        width: 100%;
        height: 180px;
        overflow: hidden;
        position: relative;
        background-color: #f7fafc;
    }

    .card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .jewelery-card:hover .card-img {
        transform: scale(1.05);
    }

    /* Favorite Overlay Badge */
    .favorite-overlay-btn {
        position: absolute;
        bottom: 12px;
        left: 50%;
        transform: translateX(-50%) translateY(10px);
        opacity: 0;
        background-color: rgba(26, 131, 173, 0.9);
        color: #ffffff;
        border: none;
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: all 0.25s ease;
        z-index: 10;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    .card-img-wrapper:hover .favorite-overlay-btn {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    .favorite-overlay-btn:hover {
        background-color: #ffffff;
        color: var(--primary-color);
    }

    .favorite-overlay-btn.active {
        background-color: #dd6b20;
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    /* Sku & Badges Row */
    .card-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 14px 4px 14px;
    }

    .card-sku {
        font-size: 10px;
        font-weight: 700;
        color: var(--text-muted);
    }

    .card-type-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
        background-color: var(--primary-light);
        color: var(--primary-color);
    }

    .card-name {
        padding: 0 14px 8px 14px;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-color);
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        height: 34px;
    }

    .card-price {
        padding: 0 14px 12px 14px;
        font-size: 15px;
        font-weight: 800;
        color: var(--text-color);
        border-bottom: 1px dashed var(--border-color);
    }

    .card-footer {
        padding: 10px 14px;
        display: flex;
        flex-direction: column;
        gap: 2px;
        background-color: #fcfdfe;
    }

    .card-seller {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-color);
    }

    .card-location {
        font-size: 10px;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Uploader panel */
    .uploader-panel {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        max-width: 700px;
        margin: 0 auto;
    }

    .uploader-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 24px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 10px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group.full-width {
        grid-column: span 2;
    }

    .form-input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        color: var(--text-color);
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(16, 139, 182, 0.1);
    }

    /* File Select */
    .file-input-wrapper {
        border: 2px dashed var(--border-color);
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background-color: #fcfdfe;
    }

    .file-input-wrapper:hover {
        border-color: var(--primary-color);
        background-color: var(--primary-light);
    }

    /* Stepper Styling */
    .stepper-container {
        padding: 20px 40px;
        border-bottom: 1px solid var(--border-color);
        background-color: #fcfdfe;
        margin-bottom: 24px;
    }

    .stepper-list {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 40px;
        position: relative;
        max-width: 600px;
        margin: 0 auto;
    }

    .stepper-item {
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative;
        z-index: 2;
        cursor: pointer;
    }

    .stepper-badge {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #edf2f7;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        border: 2px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .stepper-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
        transition: all 0.3s ease;
    }

    .stepper-item.active .stepper-badge {
        background-color: var(--primary-color);
        color: #ffffff;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(16, 139, 182, 0.15);
    }

    .stepper-item.active .stepper-title {
        color: var(--primary-color);
    }

    .stepper-item.completed .stepper-badge {
        background-color: #2b9cbd;
        color: #ffffff;
        border-color: #2b9cbd;
    }

    .stepper-item.completed .stepper-title {
        color: #2b9cbd;
    }

    /* Form Panel Sections matching Image grid boxes */
    .form-panel-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
        margin-bottom: 24px;
    }

    .form-section-card {
        background-color: #f7fafc;
        border: 1px solid #edf2f7;
        border-radius: 12px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .form-section-card-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-color);
        border-bottom: 1px dashed var(--border-color);
        padding-bottom: 10px;
        margin-bottom: 4px;
    }

    .form-row-multi {
        display: flex;
        gap: 12px;
    }

    .form-row-multi .form-group {
        flex: 1;
    }

    /* Toggle and Custom layout inputs */
    .checkbox-row {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
    }

    .radio-group-row {
        display: flex;
        gap: 16px;
        align-items: center;
        padding: 6px 0;
    }

    .radio-option {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
    }

    .radio-option input {
        cursor: pointer;
        accent-color: var(--primary-color);
    }

    /* Layout tabs style within Upload tab */
    .subtab-navigation {
        display: flex;
        background-color: #f7fafc;
        border-bottom: 1px solid var(--border-color);
        padding: 10px 20px 0 20px;
        gap: 10px;
        margin-bottom: 24px;
    }

    .subtab-btn {
        padding: 10px 24px;
        font-weight: 700;
        font-size: 13px;
        color: var(--text-muted);
        cursor: pointer;
        border: 1px solid var(--border-color);
        border-bottom: none;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
        background-color: #f7fafc;
        transition: all 0.2s ease;
        margin-bottom: -1px;
    }

    .subtab-btn.active {
        color: var(--primary-color);
        background-color: #ffffff;
        border-bottom: 1px solid #ffffff;
    }

    /* Drag & Drop Zone Styling */
    .drop-zone {
        border: 2px dashed var(--border-color);
        border-radius: 12px;
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        background-color: #fcfdfe;
    }

    .drop-zone:hover {
        border-color: var(--primary-color);
        background-color: var(--primary-light);
    }

    .drop-zone.dragover {
        border-color: var(--primary-color);
        background-color: var(--primary-light);
        transform: scale(1.02);
    }

    .bulk-icon-box {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #e8f5e9;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    /* Status Badges */
    .badge {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-block;
    }
    .badge-approved {
        background-color: #dcfce7;
        color: #166534;
    }
    .badge-rejected {
        background-color: #fee2e2;
        color: #991b1b;
    }
    .badge-pending {
        background-color: #fef3c7;
        color: #92400e;
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
        color: #ffffff;
    }

    .confirm-modal-btn-confirm:hover {
        background-color: #dc2626;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
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
</style>
@endsection

@section('content')
@php
    $isAdmin = (session('admin_role', 'normal_admin') === 'super_admin');
    $currentTab = request('tab', 'search');
@endphp
<!-- Header title and tab-links -->
<div class="jewelery-tabs">
    <div class="jewelery-tab-link {{ $currentTab === 'search' ? 'active' : '' }}" id="tab-search-btn" onclick="toggleJeweleryTab('search')">Search</div>
    @if(!$isAdmin)
        <div class="jewelery-tab-link {{ $currentTab === 'upload' ? 'active' : '' }}" id="tab-upload-btn" onclick="toggleJeweleryTab('upload')">Upload Jwelery</div>
    @endif
    <a href="{{ route('inventory.index', ['product_type' => 'jewelry']) }}" class="jewelery-tab-link" style="text-decoration: none;">Inventory</a>
    @if($isAdmin)
        <a href="{{ route('inventory-history.index', ['product_type' => 'jewelry']) }}" class="jewelery-tab-link" style="text-decoration: none;">Inventory History</a>
    @endif
</div>

<!-- PANEL 1: SEARCH GRID -->
<div class="jewelery-layout" id="panel-search" style="display: {{ $currentTab === 'search' ? 'flex' : 'none' }};">
    <!-- Left Filter Panel -->
    <div class="filter-sidebar">
        <div class="filter-header">
            <h4 class="filter-title">Filter</h4>
            <button class="filter-reset-btn" onclick="resetFilters()">
                <i class="fa-solid fa-rotate-right"></i>
            </button>
        </div>

        <form action="{{ route('jewelery.index') }}" method="GET" id="filter-form">
            <!-- Keyword search -->
            <div class="filter-section">
                <div class="filter-search-wrapper">
                    <input type="text" name="keyword" class="filter-search-input" placeholder="Type Keyword..." value="{{ request('keyword') }}">
                    <i class="fa-solid fa-magnifying-glass filter-search-icon"></i>
                </div>
            </div>

            <!-- Country filter -->
            <div class="filter-section" style="margin-top: 10px;">
                <span class="filter-section-title">Country</span>
                <select name="location" class="filter-search-input" onchange="submitFilterForm()">
                    <option value="">Select Country</option>
                    <option value="London" {{ request('location') === 'London' ? 'selected' : '' }}>United Kingdom</option>
                    <option value="India" {{ request('location') === 'India' ? 'selected' : '' }}>India</option>
                    <option value="USA" {{ request('location') === 'USA' ? 'selected' : '' }}>USA</option>
                </select>
            </div>

            <!-- Inventory Status filter -->
            <div class="filter-section" style="margin-top: 15px;">
                <span class="filter-section-title">Inventory Status</span>
                <select name="inventory_status" class="filter-search-input" onchange="submitFilterForm()">
                    <option value="">All Statuses</option>
                    <option value="available" {{ request('inventory_status') === 'available' ? 'selected' : '' }}>Available</option>
                    <option value="on_hold" {{ request('inventory_status') === 'on_hold' ? 'selected' : '' }}>Hold</option>
                    <option value="sold" {{ request('inventory_status') === 'sold' ? 'selected' : '' }}>Sold</option>
                </select>
            </div>

            <!-- See Only options -->
            <div class="filter-section" style="margin-top: 15px;">
                <span class="filter-section-title">See Only</span>
                <div class="filter-checkbox-group">
                    <label class="filter-checkbox-label">
                        <input type="checkbox" name="see_only[]" value="available" checked> Available (34,423)
                    </label>
                    <label class="filter-checkbox-label">
                        <input type="checkbox" name="see_only[]" value="new"> New (33,475)
                    </label>
                    <label class="filter-checkbox-label">
                        <input type="checkbox" name="see_only[]" value="untreated"> Not treated (21,900)
                    </label>
                </div>
            </div>

            <!-- Jewelry type checkboxes -->
            <div class="filter-section" style="margin-top: 15px;">
                <span class="filter-section-title">Jewelery Type</span>
                <div class="filter-checkbox-group">
                    @foreach($categories['jewelery_type'] ?? [] as $opt)
                        <label class="filter-checkbox-label">
                            <input type="checkbox" name="types[]" value="{{ $opt->name }}" 
                                   {{ is_array(request('types')) && in_array($opt->name, request('types')) ? 'checked' : '' }}
                                   onchange="submitFilterForm()"> 
                            {{ $opt->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    <!-- Right Main Panel -->
    <div class="jewelery-main">
        <!-- Top Category Scroll Slider -->
        <div class="categories-slider">
            <a href="{{ route('jewelery.index') }}" class="category-slider-btn {{ !request('type') ? 'active' : '' }}">
                <i class="fa-solid fa-grip-vertical"></i>
                <span>All Items</span>
            </a>
            @foreach($categories['jewelery_type'] ?? [] as $opt)
                <a href="{{ route('jewelery.index', ['type' => $opt->name]) }}" class="category-slider-btn {{ request('type') === $opt->name ? 'active' : '' }}">
                    @if(!empty($opt->image))
                        <img src="{{ asset($opt->image) }}" alt="{{ $opt->name }}" style="width: 20px; height: 20px; object-fit: contain; margin-bottom: 2px;">
                    @else
                        @php
                            $iconClass = 'fa-gem';
                            if ($opt->name === 'Ring') $iconClass = 'fa-ring';
                            elseif ($opt->name === 'Bracelet') $iconClass = 'fa-circle-notch';
                            elseif ($opt->name === 'Earings') $iconClass = 'fa-circle-play';
                            elseif ($opt->name === 'Necklace') $iconClass = 'fa-certificate';
                            elseif ($opt->name === 'Watch') $iconClass = 'fa-stopwatch';
                            elseif ($opt->name === 'Pendent') $iconClass = 'fa-gem';
                        @endphp
                        <i class="fa-solid {{ $iconClass }}" style="{{ $opt->name === 'Earings' ? 'transform: rotate(45deg);' : '' }}"></i>
                    @endif
                    <span>{{ $opt->name }}</span>
                </a>
            @endforeach
        </div>

        <!-- Grid Header metadata -->
        <div>
            <div class="grid-header-row">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <span class="grid-title-stats">{{ $items->count() }} Items Listed</span>
                    @if($items->count() > 0 && $isAdmin)
                        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 700; cursor: pointer; color: var(--text-color); margin-bottom: 0; margin-left: 10px;">
                            <input type="checkbox" id="select-all-checkbox" onchange="toggleSelectAll(this)" style="accent-color: var(--primary-color); width: 16px; height: 16px; cursor: pointer; vertical-align: middle;">
                            Select All
                        </label>

                        <button type="button" id="bulk-delete-btn" class="btn btn-danger"
                            style="background-color: var(--error-color); color: white; border-color: var(--error-color); border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; height: 33px; padding: 0 12px; font-size: 12.5px; font-weight: 700; border-radius: 6px; opacity: 0.5; pointer-events: none;"
                            disabled
                            onclick="handleBulkClick()">
                            <i class="fa-solid fa-trash"></i> Bulk Delete
                        </button>

                        <form id="bulk-delete-form" action="{{ route('jewelery.bulk-delete') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    @endif
                </div>
                <div class="grid-actions">
                    <button class="grid-view-btn">
                        <i class="fa-solid fa-star"></i> Favorites
                    </button>
                    <button class="grid-view-btn">
                        <i class="fa-solid fa-list"></i> Filter Results
                    </button>
                </div>
            </div>

            <!-- Product Card Grid -->
            <div class="jewelery-grid">
                @forelse($items as $item)
                    <div class="jewelery-card">
                        <div class="card-img-wrapper">
                            @if($isAdmin)
                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" form="bulk-delete-form" class="jewelry-checkbox" onchange="toggleBulkBtn()" style="position: absolute; top: 12px; left: 12px; z-index: 11; width: 18px; height: 18px; accent-color: var(--primary-color); cursor: pointer; filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.15));">
                            @endif
                            @if(str_starts_with($item->image_url, 'http'))
                                <img src="{{ $item->image_url }}" class="card-img" alt="{{ $item->name }}">
                            @else
                                <img src="{{ asset($item->image_url) }}" class="card-img" alt="{{ $item->name }}">
                            @endif
                            <button class="favorite-overlay-btn" onclick="toggleFavorite(this)">
                                <i class="fa-regular fa-star"></i> Add to Favorite
                            </button>
                        </div>
                        
                        <div class="card-meta-row">
                            <span class="card-sku">{{ $item->sku }}</span>
                            <div style="display: flex; gap: 4px; align-items: center; flex-wrap: wrap;">
                                <span class="card-type-badge">{{ $item->type }}</span>
                                <span class="badge {{ $item->status === 'Approved' ? 'badge-approved' : ($item->status === 'Rejected' ? 'badge-rejected' : 'badge-pending') }}">
                                    {{ $item->status ?: 'Approved' }}
                                </span>
                                @if(($item->inventory_status ?? 'available') === 'available')
                                    <span class="badge badge-approved" style="background-color: #dcfce7; color: #166534;">Available</span>
                                @elseif($item->inventory_status === 'on_hold')
                                    <span class="badge badge-pending" style="background-color: #fef3c7; color: #92400e;">Hold</span>
                                @elseif($item->inventory_status === 'sold')
                                    <span class="badge badge-rejected" style="background-color: #fee2e2; color: #991b1b;">Sold</span>
                                @endif
                            </div>
                        </div>
                        
                        <h4 class="card-name" title="{{ $item->name }}">{{ $item->name }}</h4>
                        
                        <div class="card-price">${{ number_format($item->price, 2) }}</div>
                        
                        <div class="card-footer" style="display: flex; flex-direction: column; gap: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <span class="card-seller">{{ $item->created_by }}</span>
                                <span class="card-location">
                                    <i class="fa-solid fa-location-dot"></i> {{ $item->location }}
                                </span>
                            </div>

                            <!-- Shopify Integration -->
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; border-top: 1px dashed var(--border-color); padding-top: 8px; margin-top: 4px;">
                                <span style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fa-brands fa-shopify" style="color: #96bf48;"></i> Shopify
                                </span>
                                @php $sync = $item->shopifyProduct; @endphp
                                @if(!$sync)
                                    @if(($item->inventory_status ?? 'available') === 'available')
                                        <form action="{{ route('shopify.publish-jewelry', $item->id) }}" method="POST" style="margin: 0;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px; height: 26px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 700; background: #fdf2f8; border-color: #fbcfe8; color: #db2777;">
                                                Publish to Shopify
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted" style="font-size:11px; font-style:italic;">Blocked</span>
                                    @endif
                                @elseif($sync->sync_status === 'synced')
                                    @if($sync->shopify_url)
                                        <a href="{{ $sync->shopify_url }}" target="_blank" class="badge badge-approved" style="text-decoration:none; padding: 4px 8px; font-size: 11px; font-weight: 700;">
                                            <i class="fa-solid fa-check-double"></i> Published
                                        </a>
                                    @else
                                        <span class="badge badge-approved" style="padding: 4px 8px; font-size: 11px; font-weight: 700;">
                                            <i class="fa-solid fa-check-double"></i> Published
                                        </span>
                                    @endif
                                @elseif($sync->sync_status === 'processing')
                                    <span class="badge badge-pending" style="padding: 4px 8px; font-size: 11px; font-weight: 700;">
                                        <i class="fa-solid fa-spinner fa-spin"></i> Syncing...
                                    </span>
                                @elseif($sync->sync_status === 'failed')
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <span class="badge badge-rejected" style="padding: 4px 8px; font-size: 11px; font-weight: 700;" title="{{ $sync->sync_message }}">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Failed
                                        </span>
                                        @if(($item->inventory_status ?? 'available') === 'available')
                                            <form action="{{ route('shopify.retry', $sync->id) }}" method="POST" style="margin:0;">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary" style="padding: 2px 6px; font-size: 10px; height: 22px; border-radius: 4px; display: inline-flex; align-items: center; gap: 2px; font-weight: 700; border-color: #fed7d7; background: #fff5f5; color: var(--error-color);" title="Retry sync">
                                                    <i class="fa-solid fa-arrow-rotate-right"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted" style="font-size:11px; font-style:italic;">Blocked</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="badge badge-pending" style="padding: 4px 8px; font-size: 11px; font-weight: 700;">
                                        <i class="fa-solid fa-clock"></i> Pending
                                    </span>
                                @endif
                            </div>

                            @if($isAdmin && $item->status !== 'Approved')
                                <div style="display: flex; gap: 8px; margin-top: 6px; width: 100%;">
                                    <form action="{{ route('jewelery.approve', $item->id) }}" method="POST" style="flex: 1; margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 6px 12px; font-size: 11px; justify-content: center; background-color: var(--success-color); border-color: var(--success-color); height: 30px;">
                                            <i class="fa-solid fa-circle-check"></i> Approve
                                        </button>
                                    </form>
                                    @if($item->status !== 'Rejected')
                                        <form action="{{ route('jewelery.reject', $item->id) }}" method="POST" style="flex: 1; margin: 0;">
                                            @csrf
                                            <button type="submit" class="btn btn-danger" style="width: 100%; padding: 6px 12px; font-size: 11px; justify-content: center; height: 30px;">
                                                <i class="fa-solid fa-circle-xmark"></i> Reject
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif

                            @php
                                $canEdit = ($isAdmin || $item->user_id === Auth::id());
                                $canDelete = $isAdmin;
                            @endphp

                            @if($canEdit || $canDelete)
                                <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center; border-top: 1px solid var(--border-color); padding-top: 8px; margin-top: 4px;">
                                    @if($canEdit)
                                        <a href="{{ route('jewelery.edit', $item->id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px; height: 30px; margin: 0; color: var(--primary-color); border-color: var(--border-color); background-color: #ffffff;" title="Edit Jewelry">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                    @endif

                                    @if($canDelete)
                                        <form action="{{ route('jewelery.destroy', $item->id) }}" method="POST" class="confirm-delete-form" data-username="{{ $item->sku }}" style="margin: 0; display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px; height: 30px; margin: 0; background-color: #fff5f5; border-color: #fed7d7; color: var(--error-color);" title="Delete Jewelry">
                                                <i class="fa-solid fa-trash-can"></i> Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="grid-column: span 4; text-align: center; padding: 60px; color: var(--text-muted);">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 32px; margin-bottom: 12px; opacity: 0.5;"></i>
                        <p style="font-size: 14px; font-weight: 600;">No items found matching your filters.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- PANEL 2: UPLOAD FORM -->
<div id="panel-upload" style="display: {{ $currentTab === 'upload' ? 'block' : 'none' }};">
    <!-- Sub-Tab Navigation for Single vs Multiple Upload -->
    <div class="subtab-navigation">
        <div class="subtab-btn active" id="subtab-single-btn" onclick="switchUploadSubTab('single')">Single</div>
        <div class="subtab-btn" id="subtab-multiple-btn" onclick="switchUploadSubTab('multiple')">Multiple</div>
    </div>

    <!-- SINGLE ITEM UPLOAD PANEL -->
    <div class="uploader-panel" id="upload-single-pane">
        <!-- Progress Stepper list -->
        <div class="stepper-container">
            <div class="stepper-list">
                <div class="stepper-item active" id="step-general-indicator" onclick="switchStep(1)">
                    <div class="stepper-badge">1</div>
                    <div class="stepper-title">General Information</div>
                </div>
                <div class="stepper-item" id="step-report-indicator" onclick="switchStep(2)">
                    <div class="stepper-badge">2</div>
                    <div class="stepper-title">Report Information</div>
                </div>
            </div>
        </div>

        <form action="{{ route('jewelery.store') }}" method="POST" enctype="multipart/form-data" id="jewelery-single-form">
            @csrf

            <!-- STEP 1: GENERAL INFORMATION -->
            <div id="step-panel-1" class="step-pane">
                <div class="form-panel-grid">
                    <!-- Box 1: Product Description -->
                    <div class="form-section-card">
                        <div class="form-section-card-title">Product Description</div>
                        <div class="form-group">
                            <label for="name">Item Title</label>
                            <input type="text" name="name" placeholder="Enter title" class="form-input" required>
                        </div>
                        <div class="form-row-multi">
                            <div class="form-group" style="flex: 2;">
                                <label for="sku">Stock #</label>
                                <input type="text" name="sku" placeholder="Enter stock number" class="form-input" required>
                            </div>
                            <div class="form-group checkbox-row" style="padding-top: 25px; flex: 1;">
                                <label class="filter-checkbox-label">
                                    <input type="checkbox" name="is_available" value="1" checked> Available
                                </label>
                                <label class="filter-checkbox-label">
                                    <input type="checkbox" name="is_available_for_memo" value="1"> Available for Memo
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" placeholder="Enter description" class="form-input" style="height: 100px;"></textarea>
                        </div>
                    </div>

                    <!-- Box 2: Product Information -->
                    <div class="form-section-card">
                        <div class="form-section-card-title">Product Information</div>
                        <div class="form-row-multi">
                            <div class="form-group">
                                <label for="type">Jewelry Type</label>
                                <select name="type" class="form-input" required>
                                    <option value="">Select Type</option>
                                    @foreach($categories['jewelery_type'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}" {{ $opt->name === 'Ring' ? 'selected' : '' }}>{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="type_style">Type Style</label>
                                <select name="type_style" class="form-input">
                                    <option value="">Select Style</option>
                                    <option value="Solitaire">Solitaire</option>
                                    <option value="Halo">Halo</option>
                                    <option value="Vintage">Vintage</option>
                                    <option value="Classic">Classic</option>
                                    <option value="Modern">Modern</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row-multi">
                            <div class="form-group">
                                <label for="category">Jewelry Category</label>
                                <select name="category" class="form-input">
                                    <option value="">Select Category</option>
                                    <option value="Fine Jewelry">Fine Jewelry</option>
                                    <option value="Fashion Jewelry">Fashion Jewelry</option>
                                    <option value="Bridal">Bridal</option>
                                    <option value="Custom">Custom</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="condition">Condition</label>
                                <select name="condition" class="form-input">
                                    <option value="">Select Condition</option>
                                    <option value="New">New</option>
                                    <option value="Pre-owned">Pre-owned</option>
                                    <option value="Refurbished">Refurbished</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="brand">Brand</label>
                                <select name="brand" class="form-input">
                                    <option value="">Select Brand</option>
                                    <option value="OM Gems" selected>OM Gems</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row-multi">
                            <div class="form-group">
                                <label for="quality">Jewelry Quality</label>
                                <select name="quality" class="form-input">
                                    <option value="">Select Quality</option>
                                    <option value="Excellent">Excellent</option>
                                    <option value="Very Good">Very Good</option>
                                    <option value="Good">Good</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="designer">Designer Maker</label>
                                <input type="text" name="designer" placeholder="Enter designer name" class="form-input">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="keywords">Keyword Description (Comma Separated)</label>
                            <input type="text" name="keywords" placeholder="Enter keywords" class="form-input">
                        </div>
                    </div>

                    <!-- Box 3: Price & Location -->
                    <div class="form-section-card">
                        <div class="form-section-card-title">Price & Location</div>
                        <div class="form-row-multi">
                            <div class="form-group">
                                <label for="shipping_from">Shipping From</label>
                                <select name="shipping_from" class="form-input">
                                    <option value="">Select location</option>
                                    <option value="London" selected>London, United Kingdom</option>
                                    <option value="Surat">Surat, India</option>
                                    <option value="New York">New York, USA</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="price">Price/Piece</label>
                                <input type="number" step="0.01" name="price" placeholder="$ Enter price" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="currency">Currency</label>
                                <input type="text" name="currency" value="USD" class="form-input">
                            </div>
                        </div>
                        <div class="form-row-multi">
                            <div class="form-group">
                                <label for="terms">Terms</label>
                                <select name="terms" class="form-input">
                                    <option value="">Select terms</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Net 30">Net 30</option>
                                    <option value="COD">COD</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="msrp">MSRP</label>
                                <input type="number" step="0.01" name="msrp" placeholder="$ Enter MSRP" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="delivery_time">Delivery Time (Days)</label>
                                <input type="number" name="delivery_time" placeholder="Enter days" class="form-input">
                            </div>
                        </div>
                        <div class="form-row-multi" style="margin-top: 10px;">
                            <div style="flex: 1;">
                                <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 11px;">+ ADD</button>
                            </div>
                        </div>
                        <div class="form-row-multi">
                            <div class="form-group">
                                <label for="in_stock">Item In Stock</label>
                                <input type="number" name="in_stock" placeholder="Enter stock count" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="min_order">Minimum Order</label>
                                <input type="number" name="min_order" placeholder="Enter min count" class="form-input">
                            </div>
                        </div>
                    </div>

                    <!-- Box 4: Metal & Gemstone -->
                    <div class="form-section-card">
                        <div class="form-section-card-title">Metal & Gemstone</div>
                        <div class="form-row-multi">
                             <div class="form-group">
                                <label for="metal_type">Metal Type</label>
                                <select name="metal_type" class="form-input">
                                    <option value="">Select metal</option>
                                    @foreach($categories['metal_type'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="metal_karat">Metal Karat</label>
                                <select name="metal_karat" class="form-input">
                                    <option value="">Select karat</option>
                                    @foreach($categories['metal_karat'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="total_weight">Jewelry Total Weight (gr)</label>
                                <input type="number" step="0.01" name="total_weight" placeholder="Enter grams" class="form-input">
                            </div>
                        </div>
                        <div class="form-row-multi">
                            <div class="form-group">
                                <label for="gemstone_type">Gemstone Type</label>
                                <select name="gemstone_type" class="form-input">
                                    <option value="">Select gem</option>
                                    @foreach($categories['gemstone_type'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="gemstone_shape">Shape</label>
                                <select name="gemstone_shape" class="form-input">
                                    <option value="">Select shape</option>
                                    @foreach($categories['shape'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="carat_weight">Carat Weight</label>
                                <input type="number" step="0.001" name="carat_weight" placeholder="Enter carats" class="form-input">
                            </div>
                        </div>
                        <div class="form-row-multi">
                            <div class="form-group">
                                <label for="lab">Lab</label>
                                <select name="lab" class="form-input">
                                    <option value="">Select lab</option>
                                    @foreach($categories['lab'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" style="flex: 2;">
                                <label for="lab_no">Lab#</label>
                                <input type="text" name="lab_no" placeholder="Enter lab#" class="form-input">
                            </div>
                        </div>
                        <div class="form-row-multi">
                            <div class="form-group">
                                <label for="treatment">Treatment</label>
                                <select name="treatment" class="form-input">
                                    <option value="">Select treatment</option>
                                    @foreach($categories['treatment'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group radio-group-row" style="padding-top: 25px;">
                                <label class="radio-option">
                                    <input type="radio" name="treatment_yes_no" value="0" checked> No
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="treatment_yes_no" value="1"> Yes
                                </label>
                            </div>
                        </div>
                        <div class="form-row-multi">
                            <div class="form-group">
                                <label for="stone_count"># Stone</label>
                                <input type="number" name="stone_count" placeholder="Enter count" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="lot_no">OM Lot#</label>
                                <input type="text" name="lot_no" placeholder="Enter lot#" class="form-input">
                            </div>
                        </div>
                        <div class="form-row-multi" style="margin-top: 10px;">
                            <div style="flex: 1;">
                                <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 11px;">+ ADD</button>
                            </div>
                        </div>
                    </div>

                    <!-- Box 5: Suppliers Comment -->
                    <div class="form-section-card">
                        <div class="form-section-card-title">Suppliers Comment</div>
                        <div class="form-group">
                            <label for="supplier_comment">Type</label>
                            <textarea name="supplier_comment" placeholder="Enter suppliers comment" class="form-input" style="height: 80px;"></textarea>
                        </div>
                    </div>

                    <!-- Box 6: Measurements & Visibility -->
                    <div class="form-section-card" style="justify-content: space-between;">
                        <div>
                            <div class="form-section-card-title">Measurements</div>
                            <div class="form-row-multi">
                                <div class="form-group">
                                    <label for="size">Size</label>
                                    <input type="text" name="size" placeholder="Enter size" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="ring_size">Ring Size</label>
                                    <input type="text" name="ring_size" placeholder="Enter ring size" class="form-input">
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <div class="form-section-card-title">Visibility</div>
                            <div class="checkbox-row">
                                <label class="filter-checkbox-label">
                                    <input type="checkbox" name="is_unpublished" value="1"> Unpublished
                                </label>
                                <label class="filter-checkbox-label">
                                    <input type="checkbox" name="is_shareable" value="1" checked> Shareable
                                </label>
                                <label class="filter-checkbox-label">
                                    <input type="checkbox" name="is_own_stock" value="1" checked> Own stock for instant inventory
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: center; gap: 16px; border-top: 1px solid var(--border-color); padding-top: 24px;">
                    <button type="button" class="btn btn-secondary" onclick="toggleJeweleryTab('search')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="switchStep(2)">Next</button>
                </div>
            </div>

            <!-- STEP 2: REPORT INFORMATION -->
            <div id="step-panel-2" class="step-pane" style="display: none;">
                <div class="form-section-card" style="max-width: 500px; margin: 0 auto 24px auto;">
                    <div class="form-section-card-title">Product Image Upload</div>
                    <div class="form-group">
                        <label for="image_file">Upload Image</label>
                        <div class="file-input-wrapper" onclick="triggerImageFileSelect()">
                            <i class="fa-solid fa-cloud-arrow-up" style="font-size: 32px; color: var(--primary-color); margin-bottom: 12px;"></i>
                            <h5 style="font-size: 13px; font-weight: 700; color: var(--text-color); margin-bottom: 4px;" id="file-label-title">Choose File</h5>
                            <p style="font-size: 11px; color: var(--text-muted);" id="file-label-name">Supports JPEG, PNG, JPG, WEBP (Max 5MB)</p>
                            <input type="file" id="image_file" name="image_file" accept="image/*" style="display: none;" onchange="handleImageFileChange(this)" required>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: center; gap: 16px; border-top: 1px solid var(--border-color); padding-top: 24px;">
                    <button type="button" class="btn btn-secondary" onclick="switchStep(1)">Back</button>
                    <button type="submit" class="btn btn-primary">Save Jewelry</button>
                </div>
            </div>
        </form>
    </div>

    <!-- MULTIPLE ITEMS BULK UPLOAD PANEL -->
    <div class="uploader-panel" id="upload-multiple-pane" style="display: none;">
        <form action="{{ route('jewelery.import') }}" method="POST" enctype="multipart/form-data" id="jewelery-bulk-form">
            @csrf
            <div class="bulk-import-content" style="max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; gap: 24px; text-align: center;">
                
                <!-- Import Icon -->
                <div class="bulk-icon-box">
                    <i class="fa-solid fa-file-excel" style="font-size: 40px; color: #2e7d32;"></i>
                </div>

                <h3 style="font-size: 20px; font-weight: 700; color: var(--text-color);">Bulk Jewelry Import</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.5;">
                    Upload a .csv, .xlsx or .xls spreadsheet file populated with jewelry parameters to import multiple jewelries directly into the database.
                </p>

                <div class="alert alert-info" style="display: inline-flex; align-items: center; gap: 8px; background-color: #eaf6ec; color: #2e7d32; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; border: 1px solid #c8e6c9; margin-bottom: 0;">
                    <strong>CSV Format:</strong>
                    <a href="{{ asset('samples/jewellery_sample.csv') }}"
                       class="btn btn-sm btn-primary"
                       style="padding: 4px 10px; font-size: 12px; border-radius: 4px; text-decoration: none; background-color: #2e7d32; color: #ffffff; border: none; cursor: pointer;"
                       download>
                        Download Sample
                    </a>
                </div>

                <!-- Drag & Drop Zone -->
                <div class="drop-zone" id="jewelery-drop-zone" onclick="triggerImportFileSelect()" style="width: 100%;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background-color: var(--primary-light); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-cloud-arrow-up" style="font-size: 20px; color: var(--primary-color);"></i>
                    </div>
                    <button type="button" class="btn btn-primary" style="padding: 10px 24px; font-size: 13px; font-weight: 700; border-radius: 6px; box-shadow: none;">BROWSE FILES</button>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 500;" id="jewelery-import-filename-label">or drag and drop spreadsheet files here</span>
                    <input type="file" name="import_file" id="jewelery_import_file" accept=".csv,.xlsx,.xls" style="display: none;" onchange="handleJeweleryImportFileChange(this)">
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" id="jewelery-import-submit-btn" style="padding: 12px 40px; font-size: 14px; font-weight: 600; display: none; margin-top: 10px;">
                    <i class="fa-solid fa-file-import" style="margin-right: 8px;"></i> Import Jewelry
                </button>
            </div>
        </form>
    </div>
</div>

@if($isAdmin)
<!-- Custom Confirmation Modal -->
<div id="confirmModalOverlay" class="confirm-modal-overlay">
    <div class="confirm-modal-box">
        <div class="confirm-modal-header">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Confirm Delete</span>
        </div>
        <div class="confirm-modal-message" id="confirmModalMessage">
            Are you sure you want to delete the selected jewelry items?
        </div>
        <div class="confirm-modal-footer">
            <button class="confirm-modal-btn confirm-modal-btn-cancel" onclick="closeConfirmModal()">
                Cancel
            </button>
            <button class="confirm-modal-btn confirm-modal-btn-confirm" onclick="confirmDel()">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
    // Tab switching between Search and Upload
    function toggleJeweleryTab(tab) {
        const searchBtn = document.getElementById('tab-search-btn');
        const uploadBtn = document.getElementById('tab-upload-btn');
        const panelSearch = document.getElementById('panel-search');
        const panelUpload = document.getElementById('panel-upload');

        searchBtn.classList.remove('active');
        uploadBtn.classList.remove('active');

        if (tab === 'search') {
            searchBtn.classList.add('active');
            panelSearch.style.display = 'flex';
            panelUpload.style.display = 'none';
        } else {
            uploadBtn.classList.add('active');
            panelSearch.style.display = 'none';
            panelUpload.style.display = 'block';
        }
    }

    // Trigger image upload click
    function triggerImageFileSelect() {
        document.getElementById('image_file').click();
    }

    // Handle image file input change and show selected filename
    function handleImageFileChange(input) {
        const title = document.getElementById('file-label-title');
        const name = document.getElementById('file-label-name');

        if (input.files.length > 0) {
            title.textContent = "Selected Image File";
            name.textContent = input.files[0].name;
            name.style.color = "var(--primary-color)";
            name.style.fontWeight = "bold";
        } else {
            title.textContent = "Choose File";
            name.textContent = "Supports JPEG, PNG, JPG, WEBP (Max 5MB)";
            name.style.color = "";
            name.style.fontWeight = "";
        }
    }

    // Auto submit filter form on change
    function submitFilterForm() {
        document.getElementById('filter-form').submit();
    }

    // Reset filters and reload list
    function resetFilters() {
        const form = document.getElementById('filter-form');
        form.querySelectorAll('input[type="text"]').forEach(input => input.value = '');
        form.querySelectorAll('select').forEach(select => select.value = '');
        form.querySelectorAll('input[type="checkbox"]').forEach(chk => chk.checked = false);
        form.submit();
    }

    // Toggle favorite state visually
    function toggleFavorite(btn) {
        btn.classList.toggle('active');
        const icon = btn.querySelector('i');
        if (btn.classList.contains('active')) {
            btn.innerHTML = '<i class="fa-solid fa-star"></i> Favorited';
            btn.style.backgroundColor = '#dd6b20';
        } else {
            btn.innerHTML = '<i class="fa-regular fa-star"></i> Add to Favorite';
            btn.style.backgroundColor = '';
        }
    }

    // Switch between Step 1 (General) and Step 2 (Report) in Single upload wizard
    function switchStep(step) {
        document.getElementById('step-panel-1').style.display = (step === 1) ? 'block' : 'none';
        document.getElementById('step-panel-2').style.display = (step === 2) ? 'block' : 'none';
        
        const generalIndicator = document.getElementById('step-general-indicator');
        const reportIndicator = document.getElementById('step-report-indicator');
        
        if (step === 1) {
            generalIndicator.classList.add('active');
            generalIndicator.classList.remove('completed');
            reportIndicator.classList.remove('active');
            reportIndicator.classList.remove('completed');
        } else if (step === 2) {
            generalIndicator.classList.remove('active');
            generalIndicator.classList.add('completed');
            reportIndicator.classList.add('active');
            reportIndicator.classList.remove('completed');
        }
    }

    // Switch between Single and Multiple sub-tabs under Upload
    function switchUploadSubTab(tab) {
        document.getElementById('upload-single-pane').style.display = (tab === 'single') ? 'block' : 'none';
        document.getElementById('upload-multiple-pane').style.display = (tab === 'multiple') ? 'block' : 'none';
        
        const singleBtn = document.getElementById('subtab-single-btn');
        const multipleBtn = document.getElementById('subtab-multiple-btn');
        
        if (tab === 'single') {
            singleBtn.classList.add('active');
            multipleBtn.classList.remove('active');
        } else {
            singleBtn.classList.remove('active');
            multipleBtn.classList.add('active');
        }
    }

    // Trigger bulk file selection
    function triggerImportFileSelect() {
        document.getElementById('jewelery_import_file').click();
    }

    // Handle bulk import file selection change
    function handleJeweleryImportFileChange(input) {
        const label = document.getElementById('jewelery-import-filename-label');
        const submitBtn = document.getElementById('jewelery-import-submit-btn');
        if (input.files.length > 0) {
            label.textContent = "Selected file: " + input.files[0].name;
            label.style.color = "var(--primary-color)";
            label.style.fontWeight = "bold";
            submitBtn.style.display = "inline-block";
        } else {
            label.textContent = "or drag and drop spreadsheet files here";
            label.style.color = "";
            label.style.fontWeight = "";
            submitBtn.style.display = "none";
        }
    }

    // Setup drag and drop for bulk importer drop zone
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('jewelery-drop-zone');
        const fileInput = document.getElementById('jewelery_import_file');

        if (dropZone && fileInput) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            dropZone.addEventListener('drop', handleDrop, false);

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            function highlight(e) {
                dropZone.classList.add('dragover');
            }

            function unhighlight(e) {
                dropZone.classList.remove('dragover');
            }

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    fileInput.files = files;
                    handleJeweleryImportFileChange(fileInput);
                }
            }
        }
    });

    @if($isAdmin)
    // Select All and Bulk Delete logic
    window.toggleSelectAll = function(source) {
        const checkboxes = document.querySelectorAll('.jewelry-checkbox');
        checkboxes.forEach(cb => cb.checked = source.checked);
        toggleBulkBtn();
    };

    window.toggleBulkBtn = function() {
        const checkboxes = document.querySelectorAll('.jewelry-checkbox');
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const btn = document.getElementById('bulk-delete-btn');
        if (btn) {
            if (checkedCount > 0) {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            } else {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.pointerEvents = 'none';
            }
        }
    };

    window.handleBulkClick = function() {
        const checkedBoxes = document.querySelectorAll('input[name="ids[]"]:checked');
        
        if (checkedBoxes.length === 0) {
            alert('Please select at least one jewelry item to delete.');
            return false;
        }
        
        const overlay = document.getElementById('confirmModalOverlay');
        if (overlay) {
            const msgElement = document.getElementById('confirmModalMessage');
            if (msgElement) {
                msgElement.textContent = `Are you sure you want to delete the ${checkedBoxes.length} selected jewelry item(s)?`;
            }
            overlay.classList.add('active');
            window.pendingCheckedBoxes = checkedBoxes;
        }
        
        return false;
    };

    window.closeConfirmModal = function() {
        const overlay = document.getElementById('confirmModalOverlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
        window.pendingCheckedBoxes = null;
    };

    window.confirmDel = function() {
        const form = document.getElementById('bulk-delete-form');
        if (!form) {
            alert('Error: Form not found. Please try again.');
            closeConfirmModal();
            return;
        }
        
        const checkedBoxes = document.querySelectorAll('input[name="ids[]"]:checked');
        if (checkedBoxes.length === 0) {
            alert('No jewelry items selected.');
            closeConfirmModal();
            return;
        }
        
        // Clear any existing hidden inputs in the form
        const existingInputs = form.querySelectorAll('input[name="ids[]"]');
        existingInputs.forEach(input => input.remove());
        
        // Add checked checkboxes as hidden inputs to form
        checkedBoxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'ids[]';
            hiddenInput.value = checkbox.value;
            form.appendChild(hiddenInput);
        });
        
        closeConfirmModal();
        
        setTimeout(() => {
            form.submit();
        }, 100);
    };

    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.getElementById('confirmModalOverlay');
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeConfirmModal();
                }
            });
        }
    });
    @endif
</script>
@endsection
