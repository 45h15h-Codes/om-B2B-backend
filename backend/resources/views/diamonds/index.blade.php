@extends('layouts.app')

@php
    $searchActive = request('search_active', '0') === '1';
@endphp

@section('styles')
<style>
    /* Main Layout Toggles */
    .main-wrapper {
        max-width: calc(100vw - var(--sidebar-width, 240px));
        width: calc(100vw - var(--sidebar-width, 240px));
    }

    .workspace {
        max-width: 100%;
        overflow-x: hidden;
    }

    .search-view-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        position: relative;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }

    /* Tabs Navigation */
    .search-tabs-container {
        display: flex;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 20px;
        gap: 8px;
    }

    .search-tab-nav {
        padding: 12px 24px;
        font-weight: 700;
        font-size: 15px;
        color: var(--text-muted);
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
        margin-bottom: -2px;
        text-decoration: none;
    }

    .search-tab-nav.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    /* Advanced Search Form (Screenshot 5 Styling) */
    .advanced-search-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        padding: 30px;
    }

    .search-form-row {
        display: grid;
        grid-template-columns: 180px 1fr;
        padding: 16px 0;
        border-bottom: 1px solid #f1f5f9;
        align-items: center;
    }

    .search-form-row:last-of-type {
        border-bottom: none;
    }

    .search-form-label {
        font-size: 13.5px;
        font-weight: 700;
        color: #4a5568;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .search-form-label-toggle {
        font-size: 11px;
        color: #cbd5e0;
        font-weight: 600;
        cursor: pointer;
    }

    .search-form-label-toggle span.active {
        color: var(--primary-color);
        border-bottom: 1.5px solid var(--primary-color);
        padding-bottom: 1px;
    }

    .search-form-content {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    /* Shape Selector Blocks */
    .shape-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        width: 100%;
    }

    .shape-btn-item {
        border: 1px solid var(--border-color);
        background-color: #ffffff;
        border-radius: 10px;
        min-width: 110px;
        min-height: 124px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
        color: #4a5568;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        user-select: none;
        padding: 16px 12px;
        gap: 10px;
    }

    .shape-btn-item i {
        font-size: 32px;
        color: #a0aec0;
        margin-bottom: 0;
    }

    .shape-btn-item img {
        width: 52px;
        height: 52px;
        object-fit: contain;
        margin-bottom: 0;
        opacity: 0.85;
        transition: all 0.2s ease;
        max-width: 100%;
        max-height: 100%;
    }

    .shape-btn-item span {
        display: block;
        font-size: 13px;
        line-height: 1.2;
        text-align: center;
    }

    .shape-btn-item:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .shape-btn-item:hover img {
        opacity: 0.9;
    }

    .shape-btn-item.active {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: #ffffff;
    }

    .shape-btn-item.active i {
        color: #ffffff;
    }

    .shape-btn-item.active img {
        opacity: 1;
        filter: none;
    }

    /* Block Selectors (Colors, Clarities, Labs, Cut/Pol/Sym) */
    .block-selector-item {
        border: 1px solid var(--border-color);
        background-color: #ffffff;
        border-radius: 6px;
        min-width: 48px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        color: #4a5568;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
    }

    .block-selector-item:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .block-selector-item.active {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: #ffffff;
    }

    /* Quick Range Size Pills */
    .size-pill-item {
        border: 1px solid var(--border-color);
        background-color: #f8fafc;
        border-radius: 20px;
        padding: 6px 14px;
        font-size: 11.5px;
        font-weight: 700;
        color: #4a5568;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }

    .size-pill-item:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    .size-pill-item.active {
        background-color: var(--primary-light);
        border-color: var(--primary-color);
        color: var(--primary-color);
    }

    /* Input ranges */
    .input-range-container {
        display: flex;
        align-items: center;
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 4px 12px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.01);
    }

    .input-range-container input {
        border: none;
        padding: 6px;
        width: 100px;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-color);
        background: transparent;
    }

    .input-range-container input:focus {
        outline: none;
    }

    .input-range-container .range-separator {
        color: #cbd5e0;
        font-size: 12px;
        font-weight: 700;
        padding: 0 8px;
    }

    .input-range-container .range-unit {
        color: #a0aec0;
        font-size: 11px;
        font-weight: 700;
        padding-left: 6px;
    }

    /* Double Column Grid inside Row */
    .grid-fields-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        width: 100%;
    }

    .sub-field-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .sub-field-group label {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Form Fields Select dropdowns */
    .custom-select-box {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background-color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-color);
        cursor: pointer;
    }

    .custom-select-box:focus {
        outline: none;
        border-color: var(--primary-color);
    }

    /* Action Buttons Row */
    .search-actions-bar {
        display: flex;
        justify-content: center;
        gap: 16px;
        margin-top: 40px;
    }

    .btn-action-primary {
        background-color: var(--primary-color);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        padding: 14px 40px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(16, 139, 182, 0.2);
        transition: all 0.2s ease;
    }

    .btn-action-primary:hover {
        background-color: var(--primary-hover);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(16, 139, 182, 0.3);
    }

    .btn-action-secondary {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 14px 40px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }

    .btn-action-secondary:hover {
        background-color: #e2e8f0;
    }

    /* Active filter chips */
    .active-filters-chips-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        padding: 12px 20px;
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.01);
    }

    .filter-chip {
        background-color: #f1f5f9;
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 5px 12px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .filter-chip.active-range {
        background-color: var(--primary-light);
        border-color: #b0d4e3;
        color: var(--primary-color);
    }

    .filter-chip i {
        cursor: pointer;
        color: var(--text-muted);
        font-size: 11px;
    }

    .filter-chip i:hover {
        color: var(--error-color);
    }

    /* Table Grid View (Screenshot 1 Styling) */
    /* Metrics and toolbar - make more compact */
    .metrics-summary-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.01);
        flex-wrap: wrap;
        gap: 12px;
    }

    .metrics-summary-text {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-color);
    }

    .metrics-summary-text span {
        color: var(--text-muted);
        margin: 0 6px;
    }

    .toolbar-actions-group {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
    }

    .toolbar-icon-btn {
        width: 36px;
        height: 36px;
        border: 1px solid var(--border-color);
        background-color: #ffffff;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .toolbar-icon-btn:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        background-color: var(--primary-light);
    }

    .toolbar-new-search-btn {
        background-color: var(--primary-color);
        color: #ffffff;
        border: none;
        border-radius: 6px;
        padding: 7px 14px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .toolbar-new-search-btn:hover {
        background-color: var(--primary-hover);
    }

    /* Slide-out right panel (Screenshot 1 Filter Panel) */
    .sidebar-filter-panel {
        position: fixed;
        top: 0;
        right: -380px;
        width: 380px;
        height: 100vh;
        background-color: #ffffff;
        border-left: 1px solid var(--border-color);
        box-shadow: -4px 0 30px rgba(0,0,0,0.1);
        z-index: 1200;
        transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
    }

    .sidebar-filter-panel.open {
        right: 0;
    }

    .sidebar-filter-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .sidebar-filter-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-color);
    }

    .sidebar-filter-close {
        cursor: pointer;
        font-size: 18px;
        color: var(--text-muted);
        transition: color 0.15s ease;
    }

    .sidebar-filter-close:hover {
        color: var(--error-color);
    }

    .sidebar-filter-content {
        flex: 1;
        overflow-y: auto;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .sidebar-filter-section {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .sidebar-filter-section-title {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .sidebar-filter-section-title span.title-action {
        text-transform: none;
        font-size: 11px;
        color: var(--primary-color);
        cursor: pointer;
    }

    .sidebar-filter-footer {
        padding: 20px 24px;
        border-top: 1px solid var(--border-color);
        background-color: #f8fafc;
    }

    .sidebar-apply-btn {
        width: 100%;
        background-color: var(--primary-color);
        color: #ffffff;
        border: none;
        border-radius: 6px;
        padding: 12px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .sidebar-apply-btn:hover {
        background-color: var(--primary-hover);
    }

    /* Expansion Detail Row (Screenshot 3 styling) */
    .expand-detail-row {
        background-color: transparent !important;
        display: none;
    }

    .expand-detail-row.active {
        display: table-row;
        animation: slideDownDetail 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .expand-detail-row td {
        padding: 0 !important;
        border-bottom: none;
        background-color: #f8fafc;
    }

    .expand-detail-card {
        padding: 24px 30px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        animation: slideDownDetail 0.2s ease-out;
    }

    .expand-detail-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    .expand-detail-grid {
        display: grid;
        grid-template-columns: 2.2fr 2fr 1.8fr;
        gap: 24px;
    }

    .expand-detail-column-card {
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }

    .expand-detail-column-title {
        font-size: 13.5px;
        font-weight: 700;
        color: var(--text-color);
        border-bottom: 1.5px solid #f1f5f9;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }

    /* Specifications listing */
    .spec-list-table {
        width: 100%;
        border-collapse: collapse;
    }

    .spec-list-table tr td {
        padding: 6px 0 !important;
        border-bottom: none !important;
        background: transparent !important;
        font-size: 12px;
    }

    .spec-list-table tr td.spec-lbl {
        color: var(--text-muted);
        font-weight: 600;
        width: 130px;
    }

    .spec-list-table tr td.spec-val {
        color: var(--text-color);
        font-weight: 700;
    }

    /* Spec list table double column */
    .spec-list-double-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    /* Detail Row Price Card table */
    .detail-price-table {
        width: 100%;
        border-collapse: collapse;
        margin: 12px 0;
    }

    .detail-price-table th {
        background: #f8fafc;
        padding: 8px 12px;
        font-size: 10px;
        font-weight: 700;
        color: var(--text-muted);
        border-bottom: 1.5px solid var(--border-color);
    }

    .detail-price-table td {
        padding: 10px 12px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        color: var(--text-color) !important;
    }

    .detail-price-table tr:hover td {
        background-color: transparent !important;
    }

    .detail-media-actions {
        display: flex;
        gap: 8px;
        margin-top: 16px;
    }

    .detail-media-btn {
        flex: 1;
        border: 1px solid var(--border-color);
        background-color: #ffffff;
        border-radius: 6px;
        padding: 10px;
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s;
        text-decoration: none;
    }

    .detail-media-btn:hover {
        border-color: var(--primary-color);
        color: var(--primary-color);
        background-color: var(--primary-light);
    }

    /* Seller Details */
    .detail-seller-profile {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .detail-seller-avatar-placeholder {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background-color: var(--primary-light);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 700;
    }

    .detail-seller-info {
        display: flex;
        flex-direction: column;
    }

    .detail-seller-name {
        font-size: 13.5px;
        font-weight: 700;
        color: var(--text-color);
    }

    .detail-seller-id {
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 500;
    }

    .detail-seller-contact-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        font-size: 12px;
    }

    .detail-seller-contact-item {
        display: flex;
        justify-content: space-between;
    }

    .detail-seller-contact-lbl {
        color: var(--text-muted);
        font-weight: 600;
    }

    .detail-seller-contact-val {
        color: var(--text-color);
        font-weight: 700;
    }

    /* Notepad / Notes Section */
    .detail-notes-notepad {
        margin-top: 14px;
        border-top: 1px solid #f1f5f9;
        padding-top: 14px;
    }

    .detail-notes-textarea {
        width: 100%;
        height: 60px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 8px 10px;
        font-family: inherit;
        font-size: 12px;
        font-weight: 500;
        resize: none;
        margin-bottom: 8px;
    }

    .detail-notes-textarea:focus {
        outline: none;
        border-color: var(--primary-color);
    }

    .btn-notes-save {
        background-color: var(--primary-color);
        color: #ffffff;
        border: none;
        border-radius: 4px;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        float: left;
    }

    .btn-notes-save:hover {
        background-color: var(--primary-hover);
    }

    /* Modal dialog (Screenshot 2 specifications popup) */
    .spec-modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .spec-modal-backdrop.open {
        display: flex;
    }

    .spec-modal-container {
        background-color: #ffffff;
        border-radius: 12px;
        width: 100%;
        max-width: 1000px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        animation: scaleUpModal 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .spec-modal-header {
        padding: 16px 24px;
        background-color: var(--primary-color);
        color: #ffffff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }

    .spec-modal-header-title {
        font-size: 15px;
        font-weight: 700;
    }

    .spec-modal-header-close {
        cursor: pointer;
        font-size: 20px;
        color: rgba(255,255,255,0.8);
        transition: color 0.15s ease;
    }

    .spec-modal-header-close:hover {
        color: #ffffff;
    }

    .spec-modal-body {
        padding: 24px 30px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .spec-modal-summary-headline {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .spec-modal-headline-text {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-color);
    }

    .spec-modal-price-card {
        background-color: #fffaf0;
        border: 1px solid #feebc8;
        border-radius: 8px;
        padding: 16px 20px;
        margin-bottom: 10px;
        font-size: 13.5px;
        font-weight: 600;
        color: #c05621;
    }

    .spec-modal-grid {
        display: grid;
        grid-template-columns: 1.2fr 1.5fr 1.3fr;
        gap: 24px;
    }

    .spec-modal-column-card {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 18px;
    }

    .spec-modal-column-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-color);
        border-bottom: 1.5px solid #f1f5f9;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }

    /* Media view card */
    .media-card-preview {
        width: 100%;
        height: 140px;
        border-radius: 6px;
        background-color: #0f172a;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 18px;
        position: relative;
        overflow: hidden;
        margin-bottom: 12px;
    }

    .media-card-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.8;
    }

    /* Row Expand / collapse Animation */
    @keyframes slideDownDetail {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes scaleUpModal {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    /* ============================================
       DIAMOND TABLE - PERFECT STYLING
       ============================================ */

    .table-container {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.04);
        border: 1px solid #e2e8f0;
        overflow-x: auto;
        overflow-y: visible;
        width: 100%;
        max-width: 100%;
    }

    /* Custom Scrollbar Styling */
    .table-container::-webkit-scrollbar {
        height: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 10px;
        border: 2px solid #f1f5f9;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }

    /* Firefox scrollbar */
    .table-container {
        scrollbar-color: #cbd5e0 #f1f5f9;
        scrollbar-width: thin;
    }

    .table-container table {
        width: 100%;
        border-collapse: collapse;
        background-color: #ffffff;
    }

    /* TABLE HEADER STYLING */
    .table-container thead {
        background: linear-gradient(135deg, #f7fafc 0%, #f0f6fb 100%);
        border-bottom: 2px solid #cbd5e0;
    }

    .table-container thead tr {
        height: 48px;
        border-bottom: 2px solid #cbd5e0;
    }

    .table-container thead th {
        padding: 12px 8px;
        font-size: 11px;
        font-weight: 700;
        color: #2d3748;
        text-align: left;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        background-color: transparent;
        white-space: nowrap;
        border-right: 1px solid #e2e8f0;
        position: relative;
        user-select: none;
    }

    .table-container thead th:last-child {
        border-right: none;
    }

    .table-container thead th:nth-child(1) {
        width: 40px;
        text-align: center;
    }

    /* TABLE BODY STYLING */
    .table-container tbody {
        background-color: #ffffff;
    }

    .table-container tbody tr {
        height: 44px;
        border-bottom: 1px solid #e2e8f0;
        transition: all 0.2s ease;
        background-color: #ffffff;
    }

    /* Alternating row colors for better readability */
    .table-container tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }

    .table-container tbody tr:hover {
        background-color: #f0f9fd;
        box-shadow: inset 0 0 0 1px #b0d4e3;
    }

    /* TABLE CELLS */
    .table-container tbody td {
        padding: 10px 8px;
        font-size: 12px;
        color: #2d3748;
        font-weight: 500;
        border-right: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: middle;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .table-container tbody td:last-child {
        border-right: none;
    }

    .table-container tbody td:nth-child(1) {
        text-align: center;
        width: 40px;
    }

    /* CHECKBOX STYLING */
    .table-container input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: var(--primary-color);
        border-radius: 4px;
        border: 2px solid #cbd5e0;
        transition: all 0.15s ease;
    }

    .table-container input[type="checkbox"]:hover {
        border-color: var(--primary-color);
        box-shadow: 0 0 6px rgba(16, 139, 182, 0.15);
    }

    .table-container input[type="checkbox"]:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    /* BADGE STYLING */
    .table-container .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
        text-align: center;
        min-width: 48px;
        border: none;
        transition: all 0.2s ease;
    }

    .badge-approved {
        background-color: #dcfce7;
        color: #166534;
        border-left: 3px solid #22c55e;
    }

    .badge-approved:hover {
        background-color: #bbf7d0;
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.25);
    }

    .badge-pending {
        background-color: #fef3c7;
        color: #92400e;
        border-left: 3px solid #f59e0b;
    }

    .badge-pending:hover {
        background-color: #fde68a;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25);
    }

    .badge-rejected {
        background-color: #fee2e2;
        color: #991b1b;
        border-left: 3px solid #ef4444;
    }

    .badge-rejected:hover {
        background-color: #fecaca;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
    }

    /* NUMBER AND CURRENCY CELLS */
    .table-container td:has(+ td) {
        text-align: right;
    }

    .table-container tbody td[style*="color: var(--error-color)"] {
        color: #dc2626 !important;
        font-weight: 600;
    }

    /* ICON STYLING IN TABLE */
    .table-container i {
        transition: all 0.2s ease;
    }

    .table-container td:hover i {
        transform: scale(1.1);
    }

    /* EXPAND DETAIL ROW */
    .table-container tr.expand-detail-row {
        display: none;
    }

    .table-container tr.expand-detail-row.active {
        display: table-row;
        animation: slideDownDetail 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .table-container .expand-detail-card {
        padding: 24px;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin: 8px;
        animation: slideDownDetail 0.3s ease;
    }

    /* RESPONSIVE TABLE SCROLLING */
    @media (max-width: 1400px) {
        .table-container {
            overflow-x: auto;
            scroll-behavior: smooth;
        }

        .table-container table {
            min-width: 1050px;
        }

        .table-container thead th,
        .table-container tbody td {
            padding: 8px 6px;
            font-size: 11px;
        }

        .table-container thead th:nth-child(1) {
            width: 36px;
        }

        .table-container tbody td:nth-child(1) {
            width: 36px;
        }
    }

    @media (max-width: 1200px) {
        .table-container {
            overflow-x: auto;
        }

        .table-container table {
            min-width: 900px;
        }

        .table-container thead th,
        .table-container tbody td {
            padding: 7px 5px;
            font-size: 10px;
        }

        .table-container thead th {
            letter-spacing: 0px;
        }

        .table-container thead tr {
            height: 40px;
        }

        .table-container tbody tr {
            height: 38px;
        }

        .table-container thead th:nth-child(1) {
            width: 32px;
        }

        .table-container tbody td:nth-child(1) {
            width: 32px;
        }

        .badge {
            padding: 4px 8px !important;
            font-size: 9px !important;
            min-width: 40px !important;
        }
    }

    /* TEXT ALIGNMENT FOR NUMERIC COLUMNS */
    .table-container tbody td:nth-child(n+10):nth-child(-n+15) {
        text-align: right;
        padding-right: 10px;
    }

    .table-container thead th:nth-child(n+10):nth-child(-n+15) {
        text-align: right;
        padding-right: 10px;
    }

    /* STATUS AND MEDIA COLUMNS */
    .table-container tbody td:nth-child(20),
    .table-container tbody td:nth-child(21),
    .table-container thead th:nth-child(20),
    .table-container thead th:nth-child(21) {
        text-align: center;
    }

    /* FOCUS STATE FOR ACCESSIBILITY */
    .table-container tbody tr:focus-visible {
        outline: 2px solid var(--primary-color);
        outline-offset: -1px;
    }

    /* SMOOTH TRANSITIONS */
    .table-container tbody tr,
    .table-container td,
    .table-container input[type="checkbox"],
    .table-container .badge {
        transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }

    /* Sticky Horizontal Scrollbar */
    .sticky-table-scrollbar {
        position: fixed;
        bottom: 0;
        height: 12px;
        overflow-x: auto;
        overflow-y: hidden;
        z-index: 999;
        background: #f8fafc;
        border-top: 1px solid #cbd5e0;
        display: none;
        box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.05);
    }

    .sticky-table-scrollbar::-webkit-scrollbar {
        height: 8px;
    }

    .sticky-table-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    .sticky-table-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 10px;
        border: 2px solid #f1f5f9;
    }

    .sticky-table-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }

    .sticky-table-scrollbar-content {
        height: 1px;
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
@endsection

@section('content')

@php
    $isAdmin = (session('admin_role', 'normal_admin') === 'super_admin');
    $currentTab = request('tab', 'search');
    $searchActive = request('search_active', '0') === '1';
@endphp

<div class="search-view-container">

    <!-- Sub-tabs selection -->
    <div class="search-tabs-container">
        <a href="{{ route('diamonds.index', ['tab' => 'search', 'search_active' => $searchActive ? '1' : '0']) }}" class="search-tab-nav {{ $currentTab === 'search' ? 'active' : '' }}">
            Search Single Diamonds
        </a>
        <a href="{{ route('inventory.index', ['product_type' => 'diamond']) }}" class="search-tab-nav">
            Inventory
        </a>
        @if($isAdmin)
            <a href="{{ route('inventory-history.index', ['product_type' => 'diamond']) }}" class="search-tab-nav">
                Inventory History
            </a>
        @endif
    </div>

    <!-- Active Filter Tags / Chips -->
    @if($searchActive)
        <div class="active-filters-chips-bar" id="active-chips-bar">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); margin-right: 8px;">Active Filters:</span>
            <!-- Rendered by JS dynamically based on URL params -->
        </div>
    @endif

    <!-- CONDITIONAL RENDER -->
    @if(!$searchActive)
        
        <!-- STATE A: ADVANCED SEARCH FORM (Screenshot 5) -->
        <div class="advanced-search-card">
            <form action="{{ route('diamonds.index') }}" method="GET" id="advanced-search-form">
                <input type="hidden" name="tab" value="{{ $currentTab }}">
                <input type="hidden" name="search_active" value="1">
                
                <!-- Shape Section -->
                <div class="search-form-row">
                    <div class="search-form-label">
                        <span>Shape</span>
                        <div class="search-form-label-toggle" id="shape-toggle-container">
                            <span class="active" onclick="switchShapeTab('basic')" style="cursor: pointer;">Basic</span> | <span onclick="switchShapeTab('advance')" style="cursor: pointer;">Advance</span>
                        </div>
                    </div>
                    <div class="search-form-content">
                        @php
                            $selectedShapes = request('shapes', []);
                            $iconMap = [
                                'Round' => 'fa-regular fa-circle',
                                'Pear' => 'fa-solid fa-droplet',
                                'Princess' => 'fa-regular fa-square',
                                'Marquise' => 'fa-solid fa-leaf',
                                'Emerald' => 'fa-solid fa-diamond',
                                'Cushion Brilliant' => 'fa-solid fa-square-full',
                                'Cushion Modified' => 'fa-solid fa-square-full',
                                'Asscher' => 'fa-regular fa-square',
                                'Sq. Emerald' => 'fa-solid fa-gem',
                                'Oval' => 'fa-regular fa-ellipse'
                            ];
                        @endphp
                        <div class="shape-grid" id="basic-shapes-grid">
                            @foreach($basicShapes as $shape)
                                <div class="shape-btn-item {{ in_array($shape, $selectedShapes) ? 'active' : '' }}" 
                                     data-value="{{ $shape }}" 
                                     onclick="toggleSearchSelection('shapes[]', '{{ $shape }}', this)">
                                    @if(!empty($shapeImages[$shape]))
                                        <img src="{{ asset($shapeImages[$shape]) }}" alt="{{ $shape }}">
                                    @else
                                        <i class="{{ $iconMap[$shape] ?? 'fa-solid fa-gem' }}"></i>
                                    @endif
                                    <span>{{ $shape }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="shape-grid" id="advance-shapes-grid" style="display: none;">
                            @foreach($advanceShapes as $shape)
                                <div class="shape-btn-item {{ in_array($shape, $selectedShapes) ? 'active' : '' }}" 
                                     data-value="{{ $shape }}" 
                                     onclick="toggleSearchSelection('shapes[]', '{{ $shape }}', this)">
                                    @if(!empty($shapeImages[$shape]))
                                        <img src="{{ asset($shapeImages[$shape]) }}" alt="{{ $shape }}">
                                    @else
                                        <i class="{{ $iconMap[$shape] ?? 'fa-solid fa-gem' }}"></i>
                                    @endif
                                    <span>{{ $shape }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Size Section -->
                <div class="search-form-row">
                    <div class="search-form-label">
                        <span>Size</span>
                        <div class="search-form-label-toggle">
                            <span>Specific</span>
                        </div>
                    </div>
                    <div class="search-form-content" style="gap: 16px;">
                        <div class="input-range-container">
                            <input type="number" step="0.01" name="size_from" id="form_size_from" placeholder="From" value="{{ request('size_from') }}">
                            <span class="range-separator">to</span>
                            <input type="number" step="0.01" name="size_to" id="form_size_to" placeholder="To" value="{{ request('size_to') }}">
                            <span class="range-unit">Carats</span>
                        </div>
                        <div class="size-pill-item" onclick="quickFillSize(0.30, 0.39)">0.30-0.39</div>
                        <div class="size-pill-item" onclick="quickFillSize(0.40, 0.49)">0.40-0.49</div>
                        <div class="size-pill-item" onclick="quickFillSize(0.50, 0.59)">0.50-0.59</div>
                        <div class="size-pill-item" onclick="quickFillSize(0.60, 0.69)">0.60-0.69</div>
                        <div class="size-pill-item" onclick="quickFillSize(0.70, 0.79)">0.70-0.79</div>
                        <div class="size-pill-item" onclick="quickFillSize(0.80, 0.89)">0.80-0.89</div>
                        <div class="size-pill-item" onclick="quickFillSize(0.90, 0.99)">0.90-0.99</div>
                    </div>
                </div>

                <!-- Color Section -->
                <div class="search-form-row">
                    <div class="search-form-label">
                        <span>Color</span>
                        <div class="search-form-label-toggle" id="color-toggle-container">
                            <span class="active" onclick="switchColorTab('white')" style="cursor: pointer;">White</span> | <span onclick="switchColorTab('fancy')" style="cursor: pointer;">Fancy</span>
                        </div>
                    </div>
                    <div class="search-form-content">
                        @php $selectedColors = request('colors', []); @endphp
                        <div class="search-form-content" id="white-colors-list" style="display: flex; flex-wrap: wrap; gap: 8px;">
                            @foreach($whiteColors as $color)
                                <div class="block-selector-item {{ in_array($color, $selectedColors) ? 'active' : '' }}" 
                                     data-value="{{ $color }}" 
                                     onclick="toggleSearchSelection('colors[]', '{{ $color }}', this)">
                                    {{ $color }}
                                </div>
                            @endforeach
                        </div>
                        <div class="search-form-content" id="fancy-colors-list" style="display: none; flex-wrap: wrap; gap: 8px;">
                            @foreach($fancyColors as $color)
                                <div class="block-selector-item {{ in_array($color, $selectedColors) ? 'active' : '' }}" 
                                     data-value="{{ $color }}" 
                                     onclick="toggleSearchSelection('colors[]', '{{ $color }}', this)">
                                    {{ $color }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Clarity Section -->
                <div class="search-form-row">
                    <div class="search-form-label">
                        <span>Clarity</span>
                    </div>
                    <div class="search-form-content">
                        @php $selectedClarities = request('clarities', []); @endphp
                        @foreach($clarities as $clarity)
                            <div class="block-selector-item {{ in_array($clarity, $selectedClarities) ? 'active' : '' }}" 
                                 data-value="{{ $clarity }}" 
                                 onclick="toggleSearchSelection('clarities[]', '{{ $clarity }}', this)">
                                {{ $clarity }}
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Inclusion Section -->
                <div class="search-form-row">
                    <div class="search-form-label">
                        <span>Inclusion</span>
                    </div>
                    <div class="search-form-content" style="width: 100%;">
                        <div class="grid-fields-container">
                            <div class="sub-field-group">
                                <label>Eye Clean</label>
                                <select name="eye_clean" class="custom-select-box">
                                    <option value="">Select Option</option>
                                    <option value="Yes" {{ request('eye_clean') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                    <option value="No" {{ request('eye_clean') == 'No' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="sub-field-group">
                                <label>Milky From/To</label>
                                <select name="milky" class="custom-select-box">
                                    <option value="">Select Milky</option>
                                    <option value="None" {{ request('milky') == 'None' ? 'selected' : '' }}>None</option>
                                    <option value="Light" {{ request('milky') == 'Light' ? 'selected' : '' }}>Light Milky</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Finish Section (Cut, Polish, Symmetry) -->
                <div class="search-form-row">
                    <div class="search-form-label">
                        <span>Finish</span>
                    </div>
                    <div class="search-form-content">
                        <div class="grid-fields-container">
                            <div class="sub-field-group">
                                <label>Cut</label>
                                <div class="search-form-content" style="gap: 4px;">
                                    @foreach(['Excellent', 'Very Good', 'Good'] as $cut)
                                        <div class="block-selector-item {{ in_array($cut, request('cuts', [])) ? 'active' : '' }}" 
                                             data-value="{{ $cut }}" style="min-width: 80px;"
                                             onclick="toggleSearchSelection('cuts[]', '{{ $cut }}', this)">
                                            {{ $cut }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="sub-field-group">
                                <label>Polish</label>
                                <div class="search-form-content" style="gap: 4px;">
                                    @foreach(['Excellent', 'Very Good', 'Good'] as $pol)
                                        <div class="block-selector-item {{ in_array($pol, request('polishes', [])) ? 'active' : '' }}" 
                                             data-value="{{ $pol }}" style="min-width: 80px;"
                                             onclick="toggleSearchSelection('polishes[]', '{{ $pol }}', this)">
                                            {{ $pol }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="sub-field-group">
                                <label>Symmetry</label>
                                <div class="search-form-content" style="gap: 4px;">
                                    @foreach(['Excellent', 'Very Good', 'Good'] as $sym)
                                        <div class="block-selector-item {{ in_array($sym, request('symmetries', [])) ? 'active' : '' }}" 
                                             data-value="{{ $sym }}" style="min-width: 80px;"
                                             onclick="toggleSearchSelection('symmetries[]', '{{ $sym }}', this)">
                                            {{ $sym }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shade & Grading Report Section -->
                <div class="search-form-row">
                    <div class="search-form-label">
                        <span>Grading Report</span>
                    </div>
                    <div class="search-form-content" style="gap: 24px;">
                        <div class="search-form-content">
                            @foreach($labs as $lab)
                                <div class="block-selector-item {{ in_array($lab, request('labs', [])) ? 'active' : '' }}" 
                                     data-value="{{ $lab }}" style="min-width: 60px;"
                                     onclick="toggleSearchSelection('labs[]', '{{ $lab }}', this)">
                                    {{ $lab }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Location Selection -->
                <div class="search-form-row">
                    <div class="search-form-label">
                        <span>Location</span>
                    </div>
                    <div class="search-form-content" style="width: 100%;">
                        <div class="sub-field-group" style="max-width: 320px; width: 100%;">
                            <label>Location</label>
                            <select name="location" class="custom-select-box">
                                <option value="">Select Location</option>
                                <option value="India" {{ request('location') == 'India' ? 'selected' : '' }}>India</option>
                                <option value="USA" {{ request('location') == 'USA' ? 'selected' : '' }}>USA</option>
                                <option value="UK" {{ request('location') == 'UK' ? 'selected' : '' }}>UK</option>
                                <option value="UAE" {{ request('location') == 'UAE' ? 'selected' : '' }}>UAE</option>
                                <option value="Hongkong" {{ request('location') == 'Hongkong' ? 'selected' : '' }}>Hongkong</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Pricing & Preferences -->
                <div class="search-form-row">
                    <div class="search-form-label">
                        <span>Price Filters</span>
                    </div>
                    <div class="search-form-content" style="width: 100%;">
                        <div class="grid-fields-container">
                            <div class="sub-field-group">
                                <label>$/CT</label>
                                <div class="input-range-container">
                                    <input type="number" name="price_ct_from" value="{{ request('price_ct_from') }}" placeholder="From">
                                    <span class="range-separator">to</span>
                                    <input type="number" name="price_ct_to" value="{{ request('price_ct_to') }}" placeholder="To">
                                </div>
                            </div>
                            <div class="sub-field-group">
                                <label>Total Price</label>
                                <div class="input-range-container">
                                    <input type="number" name="price_total_from" value="{{ request('price_total_from') }}" placeholder="From">
                                    <span class="range-separator">to</span>
                                    <input type="number" name="price_total_to" value="{{ request('price_total_to') }}" placeholder="To">
                                </div>
                            </div>
                            <div class="sub-field-group">
                                <label>OM %</label>
                                <div class="input-range-container">
                                    <input type="number" name="OM_pct_from" value="{{ request('OM_pct_from') }}" placeholder="From">
                                    <span class="range-separator">to</span>
                                    <input type="number" name="OM_pct_to" value="{{ request('OM_pct_to') }}" placeholder="To">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden inputs holders to preserve selections -->
                <div id="form-hidden-selections">
                    @foreach(request('shapes', []) as $s)
                        <input type="hidden" name="shapes[]" value="{{ $s }}">
                    @endforeach
                    @foreach(request('colors', []) as $c)
                        <input type="hidden" name="colors[]" value="{{ $c }}">
                    @endforeach
                    @foreach(request('clarities', []) as $cl)
                        <input type="hidden" name="clarities[]" value="{{ $cl }}">
                    @endforeach
                    @foreach(request('cuts', []) as $cut)
                        <input type="hidden" name="cuts[]" value="{{ $cut }}">
                    @endforeach
                    @foreach(request('polishes', []) as $pol)
                        <input type="hidden" name="polishes[]" value="{{ $pol }}">
                    @endforeach
                    @foreach(request('symmetries', []) as $sym)
                        <input type="hidden" name="symmetries[]" value="{{ $sym }}">
                    @endforeach
                    @foreach(request('labs', []) as $lab)
                        <input type="hidden" name="labs[]" value="{{ $lab }}">
                    @endforeach
                </div>

                <!-- Buttons Footer -->
                <div class="search-actions-bar">
                    <button type="submit" class="btn-action-primary">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Search Diamonds
                    </button>
                    <button type="button" class="btn-action-secondary" onclick="resetAdvancedForm()">
                        <i class="fa-solid fa-rotate-left"></i>
                        Clear Options
                    </button>
                </div>
            </form>
        </div>

    @else
        
        <!-- STATE B: SEARCH RESULTS GRID VIEW (Screenshot 1 / 3 / 4) -->
        <div class="metrics-summary-bar">
            <div class="metrics-summary-text">
                <strong>{{ number_format($searchStats['total_diamonds']) }}</strong> Diamonds
                <span>|</span>
                Total Carats: <strong>{{ number_format($searchStats['total_carats'], 2) }}</strong>
                <span>|</span>
                Average $/ct: <strong>${{ number_format($searchStats['avg_price_ct'], 2) }}</strong>
                <span>|</span>
                Average Discount: <strong style="color: var(--error-color);">-{{ $searchStats['avg_discount'] }}%</strong>
            </div>

            <div class="toolbar-actions-group" style="display: flex; align-items: center; gap: 8px;">
                <div class="toolbar-icon-btn" title="Sheet view">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div class="toolbar-icon-btn" title="Grid/columns">
                    <i class="fa-solid fa-table"></i>
                </div>
                <div class="toolbar-icon-btn" title="Sort">
                    <i class="fa-solid fa-arrow-up-z-a"></i>
                </div>
                <div class="toolbar-icon-btn" title="Edit list">
                    <i class="fa-solid fa-pen"></i>
                </div>
                <div class="toolbar-icon-btn" title="Toggle Filters Sidebar" onclick="toggleSidebarPanel(true)">
                    <i class="fa-solid fa-filter"></i>
                </div>
                
                <!-- Bulk Actions & Tooling -->
                @if(auth()->check())
                    <div style="margin: 0; display: inline-block;">
                        <button type="button" id="bulk-delete-btn" class="toolbar-new-search-btn"
                            style="background-color: var(--error-color); color: white; border-color: var(--error-color); border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; height: 33px; opacity: 0.5; pointer-events: none;"
                            disabled
                            onclick="handleBulkClick()">
                            <i class="fa-solid fa-trash"></i> Bulk Delete
                        </button>
                    </div>

                    <form id="bulk-delete-form" action="{{ route('diamonds.bulk-delete') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                @endif

                 <form action="{{ route('shopify.sync-all') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="diamonds">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    Sync My Diamonds
                </button>
            </form>

                <form action="{{ route('diamonds.rebuild-index') }}" method="POST" style="margin: 0; display: inline-block;">
                    @csrf
                    <button type="submit" class="toolbar-new-search-btn" style="background-color: var(--primary-color); color: white; border-color: var(--primary-color); border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; height: 33px;">
                        <i class="fa-solid fa-rotate"></i> Rebuild Index
                    </button>
                </form>

                <a href="{{ route('diamonds.export', request()->query()) }}" class="toolbar-new-search-btn" style="background-color: #38a169; color: white; border-color: #38a169; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; height: 33px; line-height: 31px; box-sizing: border-box;">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </a>

                <a href="{{ route('diamonds.index', ['tab' => $currentTab, 'search_active' => '0']) }}" class="toolbar-new-search-btn">
                    <i class="fa-solid fa-plus"></i> New Search
                </a>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            @if($diamonds->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;"><input type="checkbox" id="select-all-checkbox" onchange="toggleSelectAll(this)"></th>
                            <th>Seller</th>
                            <th>Location</th>
                            <th>Shape</th>
                            <th>Size</th>
                            <th>Color</th>
                            <th>Clarity</th>
                            <th>Cut</th>
                            <th>Polish</th>
                            <th>Symm.</th>
                            <th>Flour.</th>
                            <th>Lab</th>
                            <th>$/ct</th>
                            <th>%OM Gems</th>
                            <th>Total</th>
                            <th>Depth</th>
                            <th>Table</th>
                            <th>Measurement</th>
                            <th>Vendor Stock No.</th>
                            <th>Status</th>
                            <th>Inventory</th>
                            <th>Shopify</th>
                            <th>Media</th>
                            <th>Ratio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($diamonds as $diamond)
                            @php
                                // Seller name dynamically fetched from relationship
                                $sellerName = $diamond->user ? $diamond->user->name : 'N/A';
                                $ratingVal = '';
                                $ratingColor = '';

                                $loc = $diamond->country ?: 'India';
                                $measurementStr = $diamond->length && $diamond->width && $diamond->depth 
                                    ? "{$diamond->length}-{$diamond->width}-{$diamond->depth}" 
                                    : '4.20-4.30-3.20';
                                
                                $discountPct = 34.20 + (($diamond->id * 13) % 11) * 0.75;
                                $calculatedTotal = $diamond->asking_price ? ($diamond->asking_price * ($diamond->size ?: 1.0)) : 321.00;
                            @endphp
                            <tr id="tr-main-{{ $diamond->id }}" style="cursor: pointer;" onclick="toggleRowDetails({{ $diamond->id }})">
                                <td style="text-align: center;" onclick="event.stopPropagation();">
                                    @can('delete', $diamond)
                                        <input type="checkbox" name="ids[]" value="{{ $diamond->id }}" form="bulk-delete-form" class="diamond-checkbox" onchange="toggleBulkBtn()">
                                    @endcan
                                </td>
                                <td>{{ $sellerName }}</td>
                                <td>{{ $loc }}</td>
                                <td>{{ $diamond->shape ?: 'Round' }}</td>
                                <td>{{ number_format($diamond->size ?: 0.30, 2) }}</td>
                                <td>{{ $diamond->color ?: 'F' }}</td>
                                <td>{{ $diamond->clarity ?: 'VS1' }}</td>
                                <td>{{ $diamond->cut ?: 'VG' }}</td>
                                <td>{{ $diamond->polish ?: 'VG' }}</td>
                                <td>{{ $diamond->symmetry ?: 'VG' }}</td>
                                <td>{{ $diamond->fluorescence_intensity ? substr($diamond->fluorescence_intensity, 0, 1) : 'N' }}</td>
                                <td>{{ $diamond->lab ?: 'CGL' }}</td>
                                <td>${{ number_format($diamond->asking_price ?: 388.00, 2) }}</td>
                                <td style="color: var(--error-color);">-{{ number_format($discountPct, 1) }}%</td>
                                <td>${{ number_format($calculatedTotal, 2) }}</td>
                                <td>{{ $diamond->depth_percent ? number_format($diamond->depth_percent, 1) . '%' : '-' }}</td>
                                <td>{{ $diamond->table_percent ? number_format($diamond->table_percent, 0) . '%' : '58%' }}</td>
                                <td>{{ $measurementStr }}</td>
                                <td>{{ $diamond->stock_no ?: 'SV-111230' }}</td>
                                <td>
                                    <span class="badge {{ $diamond->status === 'Approved' ? 'badge-approved' : ($diamond->status === 'Rejected' ? 'badge-rejected' : 'badge-pending') }}">
                                        {{ substr($diamond->status ?: 'Approved', 0, 1) }}
                                    </span>
                                </td>
                                <td>
                                    @if(($diamond->inventory_status ?? 'available') === 'available')
                                         <span class="badge badge-approved" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-left: none; background-color: #dcfce7; color: #166534;">Available</span>
                                     @elseif($diamond->inventory_status === 'on_hold')
                                         <span class="badge badge-pending" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-left: none; background-color: #fef3c7; color: #92400e;">On Hold</span>
                                     @elseif($diamond->inventory_status === 'sold')
                                         <span class="badge badge-rejected" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-left: none; background-color: #fee2e2; color: #991b1b;">Sold</span>
                                     @endif
                                </td>
                                <td>
                                    @php $sync = $diamond->shopifyProduct; @endphp
                                    @if(!$sync)
                                        @if(($diamond->inventory_status ?? 'available') === 'available')
                                            <form action="{{ route('shopify.publish-diamond', $diamond->id) }}" method="POST" style="margin:0; display:inline-block;" onclick="event.stopPropagation();">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px; height: 26px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; font-weight: 700; background: #e8f4f8; border-color: #b0d4e3; color: var(--primary-color);">
                                                    <i class="fa-brands fa-shopify"></i> Publish
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted" style="font-size:11px; font-style:italic;">Blocked</span>
                                        @endif
                                    @elseif($sync->sync_status === 'synced')
                                        @if($sync->shopify_url)
                                            <a href="{{ $sync->shopify_url }}" target="_blank" class="badge badge-approved" style="text-decoration:none; padding: 4px 8px; font-size: 11px; font-weight: 700;" onclick="event.stopPropagation();">
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
                                        <div style="display: flex; align-items: center; gap: 6px;" onclick="event.stopPropagation();">
                                            <span class="badge badge-rejected" style="padding: 4px 8px; font-size: 11px; font-weight: 700;" title="{{ $sync->sync_message }}">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Failed
                                            </span>
                                            @if(($diamond->inventory_status ?? 'available') === 'available')
                                                <form action="{{ route('shopify.retry', $sync->id) }}" method="POST" style="margin:0; display:inline-block;">
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
                                </td>
                                <td>
                                    <i class="fa-solid fa-file-pdf" style="color: #ef4444; margin-right: 4px;"></i>
                                    <i class="fa-regular fa-image" style="color: var(--primary-color);"></i>
                                </td>
                                <td>{{ $diamond->length && $diamond->width ? number_format($diamond->length / $diamond->width, 3) : '1.031' }}</td>
                            </tr>

                            <!-- Expandable specifications row (Screenshot 3) -->
                            <tr id="tr-detail-{{ $diamond->id }}" class="expand-detail-row">
                                <td colspan="26">
                                    <div class="expand-detail-card">
                                        
                                        <!-- Actions bar -->
                                        <div class="expand-detail-actions">
                                            <button class="btn btn-primary" onclick="showToast('Connecting to seller {{ $sellerName }}...', 'success')" style="padding: 8px 16px; font-size: 12px;">
                                                <i class="fa-solid fa-comments"></i> Contact Seller
                                            </button>
                                            <button class="btn btn-secondary" onclick="showToast('Added to compare list', 'success')" style="padding: 8px 16px; font-size: 12px;">
                                                <i class="fa-solid fa-code-compare"></i> Compare
                                            </button>
                                            <button class="btn btn-secondary" onclick="openSpecModal({{ json_encode($diamond) }}, '{{ $sellerName }}', '{{ $ratingVal }}', '{{ $ratingColor }}')" style="padding: 8px 16px; font-size: 12px; background-color: var(--primary-light); color: var(--primary-color); border-color: #b0d4e3;">
                                                <i class="fa-solid fa-gem"></i> Diamond Page
                                            </button>
                                            <a href="{{ route('diamonds.show', $diamond->id) }}" class="btn btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 12px; background-color: var(--primary-light); color: var(--primary-color); border-color: #b0d4e3; border-radius: 6px; font-weight: 700; border: 1px solid #b0d4e3; box-sizing: border-box; height: 33px;">
                                                <i class="fa-solid fa-circle-info"></i> Detailed Page
                                            </a>
                                            @if($isAdmin)
                                                @if($diamond->status !== 'Approved')
                                                    <button type="button" class="btn btn-success" 
                                                            onclick="openApproveModal('{{ route('diamonds.approve', $diamond->id) }}', event)"
                                                            style="padding: 8px 16px; font-size: 12px; background-color: var(--success-color); color: white; border: none; font-weight: 600; border-radius: 6px; cursor: pointer; height: 33px; display: inline-block;">
                                                        <i class="fa-solid fa-circle-check"></i> Approve
                                                    </button>
                                                @endif
                                                @if($diamond->status !== 'Rejected')
                                                    <form action="{{ route('diamonds.reject', $diamond->id) }}" method="POST" style="display: inline-block;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger" style="padding: 8px 16px; font-size: 12px; background-color: var(--error-color); color: white; border: none; font-weight: 600; border-radius: 6px; cursor: pointer; height: 33px;">
                                                            <i class="fa-solid fa-circle-xmark"></i> Reject
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                            @can('delete', $diamond)
                                                <form action="{{ route('diamonds.destroy', $diamond->id) }}" method="POST" style="display: inline-block;" class="confirm-delete-form" data-username="{{ $diamond->stock_no ?? $diamond->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" style="padding: 8px 16px; font-size: 12px; background-color: var(--error-color); color: white; border: none; font-weight: 600; border-radius: 6px; cursor: pointer; height: 33px;">
                                                        <i class="fa-solid fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            @endcan
                                            <button class="btn btn-secondary" style="padding: 8px; font-size: 12px;">
                                                <i class="fa-solid fa-ellipsis"></i>
                                            </button>
                                        </div>

                                        <!-- Grid Layout containing Spec Panels -->
                                        <div class="expand-detail-grid">
                                            
                                            <!-- Diamond Details Panel -->
                                            <div class="expand-detail-column-card">
                                                <div class="expand-detail-column-title">Diamond Details</div>
                                                <div class="spec-list-double-col">
                                                    <table class="spec-list-table">
                                                        <tr>
                                                            <td class="spec-lbl">Shape</td>
                                                            <td class="spec-val">
                                                                {{ $diamond->shape ?: 'Round' }}
                                                                @if($diamond->advance_shape_detail)
                                                                    ({{ $diamond->advance_shape_detail }})
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr><td class="spec-lbl">Size</td><td class="spec-val">{{ number_format($diamond->size ?: 0.30, 2) }} ct</td></tr>
                                                        <tr>
                                                            <td class="spec-lbl">Color</td>
                                                            <td class="spec-val">
                                                                {{ $diamond->color ?: 'F' }}
                                                                @if($diamond->fancy_color_enabled)
                                                                    <br>
                                                                    <span style="font-size: 10px; color: #c05621; font-weight: bold;">
                                                                        Fancy: {{ $diamond->fancy_color_intensity ?: 'N/A' }} / {{ $diamond->fancy_color_overtone ?: 'None' }} / {{ implode(' + ', array_filter([$diamond->fancy_color_color1, $diamond->fancy_color_color2])) ?: 'N/A' }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr><td class="spec-lbl">Clarity</td><td class="spec-val">{{ $diamond->clarity ?: 'VS1' }}</td></tr>
                                                        <tr><td class="spec-lbl">Cut</td><td class="spec-val">{{ $diamond->cut ?: 'VG' }}</td></tr>
                                                        <tr><td class="spec-lbl">Polish</td><td class="spec-val">{{ $diamond->polish ?: 'VG' }}</td></tr>
                                                        <tr><td class="spec-lbl">Symmetry</td><td class="spec-val">{{ $diamond->symmetry ?: 'VG' }}</td></tr>
                                                    </table>
                                                    <table class="spec-list-table">
                                                        <tr><td class="spec-lbl">Lab</td><td class="spec-val">{{ $diamond->lab ?: 'CGL' }}</td></tr>
                                                        <tr><td class="spec-lbl">Table%</td><td class="spec-val">{{ $diamond->table_percent ? number_format($diamond->table_percent, 0) . '%' : '58%' }}</td></tr>
                                                        <tr><td class="spec-lbl">Depth%</td><td class="spec-val">{{ $diamond->depth_percent ? number_format($diamond->depth_percent, 1) . '%' : '61.2%' }}</td></tr>
                                                        <tr><td class="spec-lbl">Measurements</td><td class="spec-val">{{ $measurementStr }}</td></tr>
                                                        <tr><td class="spec-lbl">Girdle</td><td class="spec-val">{{ $diamond->girdle_condition ?: 'Faceted' }}</td></tr>
                                                        <tr><td class="spec-lbl">Report No.</td><td class="spec-val">{{ $diamond->report_no ?: '1111230' }}</td></tr>
                                                        <tr><td class="spec-lbl">Report Date</td><td class="spec-val">{{ $diamond->report_date ?: 'N/A' }}</td></tr>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Pricing & Media Panel -->
                                            <div class="expand-detail-column-card">
                                                <div class="expand-detail-column-title">Price Specification</div>
                                                <div style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 8px;">
                                                    Stock No #: <strong>{{ $diamond->stock_no ?: 'SV-111230' }}</strong>
                                                    <br>
                                                    Location: <strong>{{ $loc }}</strong>
                                                </div>
                                                
                                                <table class="detail-price-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Type</th>
                                                            <th>$/ct</th>
                                                            <th>OM%</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Cash</td>
                                                            <td>${{ number_format(($diamond->asking_price ?: 388.00) * 0.95, 2) }}</td>
                                                            <td style="color: var(--error-color);">-{{ number_format($discountPct + 2.0, 1) }}%</td>
                                                            <td>${{ number_format($calculatedTotal * 0.95, 2) }}</td>
                                                        </tr>
                                                        <tr style="background-color: #f8fafc;">
                                                            <td>Price</td>
                                                            <td>${{ number_format($diamond->asking_price ?: 388.00, 2) }}</td>
                                                            <td style="color: var(--error-color);">-{{ number_format($discountPct, 1) }}%</td>
                                                            <td>${{ number_format($calculatedTotal, 2) }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>

                                                <div class="detail-media-actions">
                                                    <a href="{{ $diamond->report_file ? asset($diamond->report_file) : '#' }}" target="_blank" class="detail-media-btn">
                                                        <i class="fa-solid fa-file-pdf" style="color: #ef4444;"></i> Certificate
                                                    </a>
                                                    <div class="detail-media-btn" onclick="showToast('Showing Diamond proportions diagram', 'success')">
                                                        <i class="fa-solid fa-diagram-project"></i> Diagram
                                                    </div>
                                                    <div class="detail-media-btn" onclick="showToast('Playing Sarine Loupe Video loop', 'success')">
                                                        <i class="fa-solid fa-circle-play"></i> Video
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Seller Details & Notepad Panel -->
                                            <div class="expand-detail-column-card">
                                                <div class="expand-detail-column-title">Seller Details</div>
                                                <div style="font-size: 13px; color: var(--text-muted); font-style: italic; margin-bottom: 20px;">
                                                    No seller registered yet.
                                                </div>

                                                <!-- Text Note notepad -->
                                                <div class="detail-notes-notepad" onclick="event.stopPropagation();">
                                                    <textarea class="detail-notes-textarea" id="note-text-{{ $diamond->id }}" placeholder="Write a personal note..."></textarea>
                                                    <button class="btn-notes-save" onclick="savePersonalNote({{ $diamond->id }})">Save Note</button>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="no-records">
                    <i class="fa-solid fa-inbox" style="font-size: 36px; color: #cbd5e0; margin-bottom: 12px; display: block;"></i>
                    No records matching your search queries were found.
                </div>
            @endif
        </div>
        @if($searchActive && $diamonds->count() > 0)
            <div id="sticky-table-scrollbar" class="sticky-table-scrollbar">
                <div id="sticky-table-scrollbar-content"></div>
            </div>
        @endif
    @endif

</div>

<!-- SLIDE OUT SIDEBAR FILTER PANEL (Screenshot 1) -->
<div class="sidebar-filter-panel" id="sidebarFilterPanel">
    <div class="sidebar-filter-header">
        <span class="sidebar-filter-title">Filter Panel (3)</span>
        <div style="display: flex; align-items: center; gap: 14px;">
            <i class="fa-solid fa-rotate-left" style="cursor: pointer; color: var(--text-muted);" onclick="resetAdvancedForm()" title="Reset Filters"></i>
            <i class="fa-solid fa-xmark sidebar-filter-close" onclick="toggleSidebarPanel(false)"></i>
        </div>
    </div>
    
    <div class="sidebar-filter-content">
        <form action="{{ route('diamonds.index') }}" method="GET" id="sidebar-filter-form">
            <input type="hidden" name="tab" value="{{ $currentTab }}">
            <input type="hidden" name="search_active" value="1">
            
            <!-- Apply button at top -->
            <button type="submit" class="sidebar-apply-btn" style="margin-bottom: 20px;">
                <i class="fa-solid fa-check"></i> Apply Filters
            </button>

            <!-- INVENTORY STATUS Section -->
            <div class="sidebar-filter-section">
                <div class="sidebar-filter-section-title">
                    <span>Inventory Status</span>
                </div>
                <select name="inventory_status" class="custom-select-box">
                    <option value="">All Statuses</option>
                    <option value="available" {{ request('inventory_status') === 'available' ? 'selected' : '' }}>Available</option>
                    <option value="on_hold" {{ request('inventory_status') === 'on_hold' ? 'selected' : '' }}>Hold</option>
                    <option value="sold" {{ request('inventory_status') === 'sold' ? 'selected' : '' }}>Sold</option>
                </select>
            </div>

            <!-- SHAPE Section -->
            <div class="sidebar-filter-section">
                <div class="sidebar-filter-section-title">
                    <span>Shape</span>
                    <span class="title-action">Advance</span>
                </div>
                <select name="shapes[]" class="custom-select-box" multiple style="height: 100px;">
                    @foreach(array_merge($basicShapes, $advanceShapes) as $shape)
                        <option value="{{ $shape }}" {{ in_array($shape, request('shapes', [])) ? 'selected' : '' }}>{{ $shape }}</option>
                    @endforeach
                </select>
            </div>

            <!-- SIZE Section -->
            <div class="sidebar-filter-section">
                <div class="sidebar-filter-section-title">
                    <span>Size (Carats)</span>
                </div>
                <div class="input-range-container">
                    <input type="number" step="0.01" name="size_from" value="{{ request('size_from') }}" placeholder="From">
                    <span class="range-separator">to</span>
                    <input type="number" step="0.01" name="size_to" value="{{ request('size_to') }}" placeholder="To">
                </div>
            </div>

            <!-- COLOR Section -->
            <div class="sidebar-filter-section">
                <div class="sidebar-filter-section-title">
                    <span>Color</span>
                    <span class="title-action">Fancy</span>
                </div>
                <select name="colors[]" class="custom-select-box" multiple style="height: 80px;">
                    @foreach(array_merge($whiteColors, $fancyColors) as $color)
                        <option value="{{ $color }}" {{ in_array($color, request('colors', [])) ? 'selected' : '' }}>{{ $color }}</option>
                    @endforeach
                </select>
            </div>

            <!-- CLARITY Section -->
            <div class="sidebar-filter-section">
                <div class="sidebar-filter-section-title">
                    <span>Clarity</span>
                </div>
                <select name="clarities[]" class="custom-select-box" multiple style="height: 80px;">
                    @foreach($clarities as $clarity)
                        <option value="{{ $clarity }}" {{ in_array($clarity, request('clarities', [])) ? 'selected' : '' }}>{{ $clarity }}</option>
                    @endforeach
                </select>
            </div>

            <!-- INCLUSION Section -->
            <div class="sidebar-filter-section">
                <div class="sidebar-filter-section-title">
                    <span>Inclusion</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <select name="eye_clean" class="custom-select-box">
                        <option value="">Eye Clean: Select</option>
                        <option value="Yes" {{ request('eye_clean') == 'Yes' ? 'selected' : '' }}>Yes</option>
                        <option value="No" {{ request('eye_clean') == 'No' ? 'selected' : '' }}>No</option>
                    </select>
                    <select name="milky" class="custom-select-box">
                        <option value="">Milky: Select</option>
                        <option value="None" {{ request('milky') == 'None' ? 'selected' : '' }}>None</option>
                        <option value="Light" {{ request('milky') == 'Light' ? 'selected' : '' }}>Light</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
    
    <div class="sidebar-filter-footer">
        <button type="submit" form="sidebar-filter-form" class="sidebar-apply-btn">
            <i class="fa-solid fa-check"></i> Apply Filters
        </button>
    </div>
</div>

<!-- DETAILED SPECIFICATIONS MODAL DIALOG (Screenshot 2) -->
<div class="spec-modal-backdrop" id="specificationsModal" onclick="closeSpecModal()">
    <div class="spec-modal-container" onclick="event.stopPropagation()">
        
        <!-- Header -->
        <div class="spec-modal-header">
            <span class="spec-modal-header-title" id="modal-header-id">OM Gems | Stock# SV-111230</span>
            <i class="fa-solid fa-xmark spec-modal-header-close" onclick="closeSpecModal()"></i>
        </div>

        <!-- Body -->
        <div class="spec-modal-body">
            
            <div class="spec-modal-summary-headline">
                <div class="spec-modal-headline-text" id="modal-headline-desc">
                    GIA, Round, 0.7ct, H, SI2, VG, VG, VG, F, RapSpec, c3
                </div>
                
                <div style="display: flex; gap: 12px; align-items: center;">
                    <i class="fa-regular fa-star" style="font-size: 20px; cursor: pointer; color: var(--text-muted);" title="Add to favorites"></i>
                    <button class="btn btn-primary" style="padding: 10px 20px;" onclick="showToast('Contacting Seller...', 'success')">
                        <i class="fa-solid fa-comments"></i> Contact Seller
                    </button>
                </div>
            </div>

            <!-- Price Info Cards -->
            <div class="spec-modal-price-card" id="modal-price-summary">
                Price $2,046/CT, -38.00%, Total $1,432. Cash $1,980/CT, -40.00%, Total $1,386. Guaranteed Availability.
            </div>

            <!-- Columns Layout -->
            <div class="spec-modal-grid">
                
                <!-- Left column: Media & Plots -->
                <div class="spec-modal-column-card">
                    <div class="spec-modal-column-title">Media & Diagrams</div>
                    
                    <div class="media-card-preview" id="modal-media-preview-container">
                        <i class="fa-solid fa-gem" style="font-size: 40px; color: rgba(255,255,255,0.2);"></i>
                    </div>

                    <table class="spec-list-table">
                        <tr><td class="spec-lbl">Plot Diagram</td><td class="spec-val" id="modal-val-plot">No Plot Available</td></tr>
                        <tr><td class="spec-lbl">Key to Symbols</td><td class="spec-val">Crystal, Cloud</td></tr>
                    </table>

                    <div style="text-align: center; margin-top: 14px;">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/e/ec/Diamond_brilliant_cut_ideal.png" alt="Proportions" style="max-width: 140px; height: auto; opacity: 0.8;">
                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 4px;">Diamond Proportion Outline Draft</div>
                    </div>
                </div>

                <!-- Middle column: Details tables -->
                <div class="spec-modal-column-card">
                    <div class="spec-modal-column-title">Diamond Specifications</div>
                    
                    <table class="spec-list-table">
                        <tr><td class="spec-lbl">Total Depth%</td><td class="spec-val" id="modal-val-depth">64.3%</td></tr>
                        <tr><td class="spec-lbl">Table%</td><td class="spec-val" id="modal-val-table">57%</td></tr>
                        <tr><td class="spec-lbl">Measurements</td><td class="spec-val" id="modal-val-measurements">5.53 - 5.61 x 3.58</td></tr>
                        <tr><td class="spec-lbl">Ratio</td><td class="spec-val" id="modal-val-ratio">1.03</td></tr>
                        <tr><td class="spec-lbl">Girdle</td><td class="spec-val">Medium, Faceted</td></tr>
                        <tr><td class="spec-lbl">Culet</td><td class="spec-val">None</td></tr>
                        <tr><td class="spec-lbl">Crown</td><td class="spec-val">15.5%, 35.5°</td></tr>
                        <tr><td class="spec-lbl">Pavilion</td><td class="spec-val">43.5%, 41.0°</td></tr>
                        <tr><td class="spec-lbl">Star Length</td><td class="spec-val">50%</td></tr>
                        <tr><td class="spec-lbl">Laser Inscription</td><td class="spec-val" id="modal-val-laser">GIA 2406670186</td></tr>
                        <tr><td class="spec-lbl">Treatment</td><td class="spec-val">None</td></tr>
                        <tr><td class="spec-lbl">Report Date</td><td class="spec-val" id="modal-val-reportdate">N/A</td></tr>
                    </table>
                </div>

                <!-- Right column: Seller & Notepad -->
                <div class="spec-modal-column-card" style="background-color: #f8fafc;">
                    <div class="spec-modal-column-title">Partner Office</div>
                    
                    <div class="detail-seller-profile">
                        <div class="detail-seller-avatar-placeholder" id="modal-seller-avatar" style="background-color: #cbd5e0; color: #ffffff;">
                            -
                        </div>
                        <div class="detail-seller-info">
                            <span class="detail-seller-name" id="modal-seller-name">No Seller Registered</span>
                            <span class="detail-seller-id">Purchaser Account Status</span>
                        </div>
                    </div>

                    <table class="spec-list-table" style="margin-bottom: 20px;">
                        <tr><td class="spec-lbl">Location</td><td class="spec-val" id="modal-seller-loc">N/A</td></tr>
                        <tr><td class="spec-lbl">Rep. Name</td><td class="spec-val" id="modal-seller-rep">N/A</td></tr>
                        <tr><td class="spec-lbl">Contact Number</td><td class="spec-val" id="modal-seller-phone" style="color: var(--primary-color);">N/A</td></tr>
                    </table>

                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">
                        System Notepad
                    </div>
                    <textarea class="detail-notes-textarea" id="modal-notes-textarea" style="height: 100px; background-color: #ffffff;" placeholder="Append note..."></textarea>
                    <button class="btn-notes-save" style="width: 100%;" onclick="saveModalNote()">
                        Save Changes to Registry
                    </button>
                </div>

            </div>

        </div>

    </div>
</div>

@endsection

@section('scripts')
<script>
    // Selections variables
    let shapes = {!! json_encode(request('shapes', [])) !!};
    let colors = {!! json_encode(request('colors', [])) !!};
    let clarities = {!! json_encode(request('clarities', [])) !!};
    let cuts = {!! json_encode(request('cuts', [])) !!};
    let polishes = {!! json_encode(request('polishes', [])) !!};
    let symmetries = {!! json_encode(request('symmetries', [])) !!};
    let labs = {!! json_encode(request('labs', [])) !!};

    // Render chips on page load
    document.addEventListener("DOMContentLoaded", function() {
        renderActiveChips();

        // Check if any selected shape is in advance shapes
        const selectedShapes = {!! json_encode(request('shapes', [])) !!};
        const advanceShapes = {!! json_encode($advanceShapes ?? []) !!};
        const hasAdvanceShapeSelected = selectedShapes.some(s => advanceShapes.includes(s));
        if (hasAdvanceShapeSelected) {
            switchShapeTab('advance');
        }

        // Check if any selected color is in fancy colors
        const selectedColors = {!! json_encode(request('colors', [])) !!};
        const fancyColors = {!! json_encode($fancyColors ?? []) !!};
        const hasFancyColorSelected = selectedColors.some(c => fancyColors.includes(c));
        if (hasFancyColorSelected) {
            switchColorTab('fancy');
        }
    });

    function switchShapeTab(tab) {
        const toggleContainer = document.getElementById('shape-toggle-container');
        const basicGrid = document.getElementById('basic-shapes-grid');
        const advanceGrid = document.getElementById('advance-shapes-grid');
        
        if (!toggleContainer || !basicGrid || !advanceGrid) return;
        
        const spans = toggleContainer.querySelectorAll('span');
        if (tab === 'basic') {
            spans[0].classList.add('active');
            spans[1].classList.remove('active');
            basicGrid.style.display = 'flex';
            advanceGrid.style.display = 'none';
        } else {
            spans[0].classList.remove('active');
            spans[1].classList.add('active');
            basicGrid.style.display = 'none';
            advanceGrid.style.display = 'flex';
        }
    }

    function switchColorTab(tab) {
        const toggleContainer = document.getElementById('color-toggle-container');
        const whiteList = document.getElementById('white-colors-list');
        const fancyList = document.getElementById('fancy-colors-list');
        
        if (!toggleContainer || !whiteList || !fancyList) return;
        
        const spans = toggleContainer.querySelectorAll('span');
        if (tab === 'white') {
            spans[0].classList.add('active');
            spans[1].classList.remove('active');
            whiteList.style.display = 'flex';
            fancyList.style.display = 'none';
        } else {
            spans[0].classList.remove('active');
            spans[1].classList.add('active');
            whiteList.style.display = 'none';
            fancyList.style.display = 'flex';
        }
    }

    // Toggle multi selections on advanced search form
    function toggleSearchSelection(name, value, element) {
        let array;
        if (name === 'shapes[]') array = shapes;
        if (name === 'colors[]') array = colors;
        if (name === 'clarities[]') array = clarities;
        if (name === 'cuts[]') array = cuts;
        if (name === 'polishes[]') array = polishes;
        if (name === 'symmetries[]') array = symmetries;
        if (name === 'labs[]') array = labs;

        const index = array.indexOf(value);
        if (index > -1) {
            array.splice(index, 1);
            element.classList.remove('active');
        } else {
            array.push(value);
            element.classList.add('active');
        }

        // Re-render hidden fields for the form
        renderHiddenFields();
    }

    function renderHiddenFields() {
        const holder = document.getElementById('form-hidden-selections');
        if (!holder) return;
        holder.innerHTML = '';

        shapes.forEach(v => appendHiddenInput(holder, 'shapes[]', v));
        colors.forEach(v => appendHiddenInput(holder, 'colors[]', v));
        clarities.forEach(v => appendHiddenInput(holder, 'clarities[]', v));
        cuts.forEach(v => appendHiddenInput(holder, 'cuts[]', v));
        polishes.forEach(v => appendHiddenInput(holder, 'polishes[]', v));
        symmetries.forEach(v => appendHiddenInput(holder, 'symmetries[]', v));
        labs.forEach(v => appendHiddenInput(holder, 'labs[]', v));
    }

    function appendHiddenInput(container, name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        container.appendChild(input);
    }

    // Quick fill carats weight
    function quickFillSize(from, to) {
        document.getElementById('form_size_from').value = from;
        document.getElementById('form_size_to').value = to;
        
        // Add active style to size pills
        document.querySelectorAll('.size-pill-item').forEach(pill => {
            if (pill.innerText.includes(`${from}-${to}`)) {
                pill.classList.add('active');
            } else {
                pill.classList.remove('active');
            }
        });
    }

    // Reset Advanced Form
    function resetAdvancedForm() {
        window.location.href = "{{ route('diamonds.index', ['tab' => $currentTab, 'search_active' => '0']) }}";
    }

    // Toggle slide-out filters panel (Screenshot 1)
    function toggleSidebarPanel(open) {
        const panel = document.getElementById('sidebarFilterPanel');
        if (panel) {
            if (open) {
                panel.classList.add('open');
            } else {
                panel.classList.remove('open');
            }
        }
    }

    // Toggle expand specifications detail row (Screenshot 3)
    function toggleRowDetails(id) {
        const detailRow = document.getElementById(`tr-detail-${id}`);
        const mainRow = document.getElementById(`tr-main-${id}`);
        if (detailRow && mainRow) {
            const isOpen = detailRow.classList.contains('active');
            if (isOpen) {
                detailRow.classList.remove('active');
                mainRow.style.backgroundColor = '';
            } else {
                // Close other opened detail rows
                document.querySelectorAll('.expand-detail-row.active').forEach(row => {
                    row.classList.remove('active');
                });
                document.querySelectorAll('.table-container tbody tr').forEach(tr => {
                    tr.style.backgroundColor = '';
                });

                detailRow.classList.add('active');
                mainRow.style.backgroundColor = '#f0f9fd';
            }
        }
    }

    // Specifications Modal controls (Screenshot 2)
    let currentSelectedDiamond = null;
    function openSpecModal(diamond, seller, ratingVal, ratingColor) {
        currentSelectedDiamond = diamond;
        
        const modal = document.getElementById('specificationsModal');
        if (!modal) return;

        // Fill values
        document.getElementById('modal-header-id').innerText = `OM Gems | Stock# ${diamond.stock_no || 'N/A'}`;
        document.getElementById('modal-headline-desc').innerHTML = `
            ${diamond.lab || 'GIA'}, ${diamond.shape || 'Round'}, ${diamond.size || '0.30'}ct, ${diamond.color || 'F'}, ${diamond.clarity || 'VS1'}, 
            ${diamond.cut || 'EX'}, ${diamond.polish || 'EX'}, ${diamond.symmetry || 'EX'}, ${diamond.fluorescence_intensity || 'None'}
        `;

        const baseVal = diamond.asking_price || 388.00;
        const discountVal = (34.20 + (diamond.id * 13) % 11 * 0.75).toFixed(1);
        const totalVal = (baseVal * (diamond.size || 0.30)).toFixed(2);
        
        document.getElementById('modal-price-summary').innerHTML = `
            Price $${(baseVal * 0.95).toFixed(2)}/CT, -${(parseFloat(discountVal) + 2.0).toFixed(1)}%, Total $${(totalVal * 0.95).toFixed(2)}. 
            Cash $${baseVal.toFixed(2)}/CT, -${discountVal}%, Total $${totalVal}. Guaranteed Availability.
        `;

        // Render preview Image
        const previewContainer = document.getElementById('modal-media-preview-container');
        if (diamond.diamond_image) {
            previewContainer.innerHTML = `<img src="${assetUrl(diamond.diamond_image)}" alt="Diamond Preview">`;
        } else {
            previewContainer.innerHTML = `<i class="fa-solid fa-gem" style="font-size: 40px; color: rgba(255,255,255,0.2);"></i>`;
        }

        document.getElementById('modal-val-plot').innerText = diamond.key_to_symbols || 'No Plot Details';
        document.getElementById('modal-val-depth').innerText = diamond.depth_percent ? `${diamond.depth_percent}%` : '61.2%';
        document.getElementById('modal-val-table').innerText = diamond.table_percent ? `${diamond.table_percent}%` : '58%';
        document.getElementById('modal-val-measurements').innerText = `${diamond.length || '4.20'} - ${diamond.width || '4.30'} x ${diamond.depth || '3.20'}`;
        document.getElementById('modal-val-ratio').innerText = diamond.length && diamond.width ? (diamond.length / diamond.width).toFixed(3) : '1.031';
        document.getElementById('modal-val-laser').innerText = `${diamond.lab || 'GIA'} ${diamond.report_no || '2406670186'}`;
        document.getElementById('modal-val-reportdate').innerText = diamond.report_date || 'N/A';

        // Seller
        document.getElementById('modal-seller-avatar').innerText = seller.substring(0, 2);
        document.getElementById('modal-seller-avatar').style.backgroundColor = ratingColor;
        document.getElementById('modal-seller-name').innerText = `${seller} Diamonds`;
        document.getElementById('modal-seller-loc').innerText = `${diamond.country || 'India'}, Surat`;

        // Note
        const savedNote = localStorage.getItem(`diamond_note_${diamond.id}`) || '';
        document.getElementById('modal-notes-textarea').value = savedNote;

        modal.classList.add('open');
    }

    function closeSpecModal() {
        const modal = document.getElementById('specificationsModal');
        if (modal) {
            modal.classList.remove('open');
        }
    }

    // Save notepad note to local storage for simulation
    function savePersonalNote(id) {
        const note = document.getElementById(`note-text-${id}`).value;
        localStorage.setItem(`diamond_note_${id}`, note);
        showToast('Personal note saved successfully to registry notes!', 'success');
    }

    function saveModalNote() {
        if (currentSelectedDiamond) {
            const note = document.getElementById('modal-notes-textarea').value;
            localStorage.setItem(`diamond_note_${currentSelectedDiamond.id}`, note);
            
            // Sync with row notepad if open
            const rowNote = document.getElementById(`note-text-${currentSelectedDiamond.id}`);
            if (rowNote) {
                rowNote.value = note;
            }
            
            showToast('Note saved to registry specifications notepad!', 'success');
            closeSpecModal();
        }
    }

    function assetUrl(path) {
        if (path.startsWith('http')) return path;
        return `http://127.0.0.1:8000/${path}`;
    }

    // Dynamic Filter Chips Renderer (Screenshot 1 Tags)
    function renderActiveChips() {
        const bar = document.getElementById('active-chips-bar');
        if (!bar) return;

        let hasFilters = false;
        bar.innerHTML = '<span style="font-size: 11px; font-weight: 700; color: var(--text-muted); margin-right: 8px;">Active Filters:</span>';

        // Shape Chip
        if (shapes.length > 0) {
            hasFilters = true;
            bar.innerHTML += `
                <div class="filter-chip">
                    <span><strong>Shape:</strong> ${shapes.join(', ')}</span>
                    <i class="fa-solid fa-xmark" onclick="removeFilterItem('shapes', '${shapes[shapes.length-1]}')"></i>
                </div>
            `;
        }

        // Color Chip
        if (colors.length > 0) {
            hasFilters = true;
            bar.innerHTML += `
                <div class="filter-chip">
                    <span><strong>Color:</strong> ${colors.join(', ')}</span>
                    <i class="fa-solid fa-xmark" onclick="removeFilterItem('colors', '${colors[colors.length-1]}')"></i>
                </div>
            `;
        }

        // Clarity Chip
        if (clarities.length > 0) {
            hasFilters = true;
            bar.innerHTML += `
                <div class="filter-chip">
                    <span><strong>Clarity:</strong> ${clarities.join(', ')}</span>
                    <i class="fa-solid fa-xmark" onclick="removeFilterItem('clarities', '${clarities[clarities.length-1]}')"></i>
                </div>
            `;
        }

        // Size range
        const sizeFrom = "{{ request('size_from') }}";
        const sizeTo = "{{ request('size_to') }}";
        if (sizeFrom || sizeTo) {
            hasFilters = true;
            bar.innerHTML += `
                <div class="filter-chip active-range">
                    <span><strong>Size:</strong> ${sizeFrom || '0'} - ${sizeTo || 'Max'} ct</span>
                    <i class="fa-solid fa-xmark" onclick="removeFilterItem('size')"></i>
                </div>
            `;
        }

        // Location
        const location = "{{ request('location') }}";
        if (location) {
            hasFilters = true;
            bar.innerHTML += `
                <div class="filter-chip">
                    <span><strong>Location:</strong> ${location}</span>
                    <i class="fa-solid fa-xmark" onclick="removeFilterItem('location')"></i>
                </div>
            `;
        }

        if (!hasFilters) {
            bar.innerHTML += `<span style="font-size: 12.5px; color: var(--text-muted); font-weight: 500; font-style: italic;">No active search filters.</span>`;
        }
    }

    function removeFilterItem(type, value) {
        let url = new URL(window.location.href);
        let params = new URLSearchParams(url.search);

        if (type === 'shapes') {
            let values = params.getAll('shapes[]');
            params.delete('shapes[]');
            values.filter(v => v !== value).forEach(v => params.append('shapes[]', v));
        } else if (type === 'colors') {
            let values = params.getAll('colors[]');
            params.delete('colors[]');
            values.filter(v => v !== value).forEach(v => params.append('colors[]', v));
        } else if (type === 'clarities') {
            let values = params.getAll('clarities[]');
            params.delete('clarities[]');
            values.filter(v => v !== value).forEach(v => params.append('clarities[]', v));
        } else if (type === 'size') {
            params.delete('size_from');
            params.delete('size_to');
        } else if (type === 'location') {
            params.delete('location');
        }

        window.location.href = `${url.pathname}?${params.toString()}`;
    }

    // Viewport-sticky horizontal scrollbar synchronization script
    (function() {
        const tableContainer = document.querySelector('.table-container');
        const table = tableContainer ? tableContainer.querySelector('table') : null;
        const stickyScrollbar = document.getElementById('sticky-table-scrollbar');
        const stickyScrollbarContent = document.getElementById('sticky-table-scrollbar-content');

        if (!tableContainer || !table || !stickyScrollbar || !stickyScrollbarContent) return;

        let isSyncingScroll = false;

        function updateScrollbarVisibility() {
            const rect = tableContainer.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            const tableWidth = table.offsetWidth;
            const containerWidth = tableContainer.clientWidth;
            const isOverflowing = tableWidth > containerWidth;
            
            const isVisible = rect.top < windowHeight && rect.bottom > 0;
            const bottomIsBelowViewport = rect.bottom > windowHeight;

            if (isOverflowing && isVisible && bottomIsBelowViewport) {
                stickyScrollbar.style.display = 'block';
                stickyScrollbarContent.style.width = tableWidth + 'px';
                stickyScrollbar.style.left = rect.left + 'px';
                stickyScrollbar.style.width = containerWidth + 'px';
                
                // Synchronize scroll position on layout adjustments
                if (!isSyncingScroll) {
                    isSyncingScroll = true;
                    stickyScrollbar.scrollLeft = tableContainer.scrollLeft;
                    // Reset scroll flag after a tick to allow user scroll events
                    setTimeout(() => { isSyncingScroll = false; }, 10);
                }
            } else {
                stickyScrollbar.style.display = 'none';
            }
        }
        
        tableContainer.addEventListener('scroll', function() {
            if (isSyncingScroll) return;
            isSyncingScroll = true;
            stickyScrollbar.scrollLeft = tableContainer.scrollLeft;
            setTimeout(() => { isSyncingScroll = false; }, 10);
        });

        stickyScrollbar.addEventListener('scroll', function() {
            if (isSyncingScroll) return;
            isSyncingScroll = true;
            tableContainer.scrollLeft = stickyScrollbar.scrollLeft;
            setTimeout(() => { isSyncingScroll = false; }, 10);
        });

        // Initialize and bind listeners
        updateScrollbarVisibility();
        window.addEventListener('resize', updateScrollbarVisibility);
        window.addEventListener('scroll', updateScrollbarVisibility);

        const observer = new MutationObserver(updateScrollbarVisibility);
        observer.observe(tableContainer, { childList: true, subtree: true });
    })();

    @if(session('admin_role', 'normal_admin') === 'super_admin')
    // Bulk actions selection logic
    window.toggleSelectAll = function(source) {
        const checkboxes = document.querySelectorAll('.diamond-checkbox');
        checkboxes.forEach(cb => cb.checked = source.checked);
        toggleBulkBtn();
    };

    window.toggleBulkBtn = function() {
        const checkboxes = document.querySelectorAll('.diamond-checkbox');
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

    // Handle bulk delete button click
    window.handleBulkClick = function() {
        console.log('Bulk Delete button clicked');
        
        // Get all checked checkboxes
        const checkedBoxes = document.querySelectorAll('input[name="ids[]"]:checked');
        console.log('Checked boxes count:', checkedBoxes.length);
        
        if (checkedBoxes.length === 0) {
            alert('Please select at least one diamond to delete.');
            return false;
        }
        
        // Show confirmation modal
        const overlay = document.getElementById('confirmModalOverlay');
        if (overlay) {
            const msgElement = document.getElementById('confirmModalMessage');
            if (msgElement) {
                msgElement.textContent = `Are you sure you want to delete ${checkedBoxes.length} selected diamond(s)?`;
            }
            overlay.classList.add('active');
            window.pendingCheckedBoxes = checkedBoxes;
            console.log('Modal shown, ready to delete');
        }
        
        return false;
    };

    // Handle form submission for bulk delete
    window.handleBulkSubmit = function(event) {
        event.preventDefault();
        
        // Check if any checkboxes are selected
        const checkboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        if (checkboxes.length === 0) {
            alert('Please select at least one diamond to delete.');
            return false;
        }
        
        // Show confirmation modal
        const overlay = document.getElementById('confirmModalOverlay');
        const msgElement = document.getElementById('confirmModalMessage');
        
        if (overlay && msgElement) {
            msgElement.textContent = 'Are you sure you want to delete the selected ' + checkboxes.length + ' diamond(s)?';
            overlay.classList.add('active');
            window.pendingForm = document.getElementById('bulk-delete-form');
        }
        
        return false;
    };

    // Custom Confirmation Modal Handler
    window.showConfirmModal = function(event, message) {
        event.preventDefault();
        event.stopPropagation();
        
        const overlay = document.getElementById('confirmModalOverlay');
        const msgElement = document.getElementById('confirmModalMessage');
        
        if (overlay && msgElement) {
            msgElement.textContent = message;
            overlay.classList.add('active');
            
            // Get form by ID directly - most reliable method
            window.pendingForm = document.getElementById('bulk-delete-form');
            
            if (!window.pendingForm) {
                console.error('Bulk delete form not found!');
                alert('Error: Could not find the delete form.');
                overlay.classList.remove('active');
                return false;
            }
            
            console.log('Form found:', window.pendingForm.id);
        }
        
        return false;
    };

    window.closeConfirmModal = function() {
        const overlay = document.getElementById('confirmModalOverlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
        window.pendingForm = null;
        window.pendingCheckedBoxes = null;
    };

    window.confirmDel = function() {
        console.log('Confirm Delete clicked');
        
        const form = document.getElementById('bulk-delete-form');
        
        if (!form) {
            console.error('Form not found');
            alert('Error: Form not found. Please try again.');
            closeConfirmModal();
            return;
        }
        
        // Get all checked checkboxes
        const checkedBoxes = document.querySelectorAll('input[name="ids[]"]:checked');
        
        if (checkedBoxes.length === 0) {
            alert('No diamonds selected.');
            closeConfirmModal();
            return;
        }
        
        console.log('Adding checked boxes to form:', checkedBoxes.length);
        
        // Clear any existing hidden inputs in the form
        const existingInputs = form.querySelectorAll('input[name="ids[]"]');
        existingInputs.forEach(input => input.remove());
        
        // Add all checked checkbox values to the form
        checkedBoxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'ids[]';
            hiddenInput.value = checkbox.value;
            form.appendChild(hiddenInput);
            console.log('Added input:', hiddenInput.value);
        });
        
        closeConfirmModal();
        
        console.log('Submitting form with', form.querySelectorAll('input[name="ids[]"]').length, 'items');
        // Submit the form
        setTimeout(() => {
            form.submit();
        }, 100);
    };

    // Close modal when clicking on overlay
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
    @endif
</script>

@if(session('admin_role', 'normal_admin') === 'super_admin')
<!-- Custom Confirmation Modal -->
<div id="confirmModalOverlay" class="confirm-modal-overlay">
    <div class="confirm-modal-box">
        <div class="confirm-modal-header">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Confirm Delete</span>
        </div>
        <div class="confirm-modal-message" id="confirmModalMessage">
            Are you sure you want to delete the selected diamonds?
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
@endif

@endsection