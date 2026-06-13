@extends('layouts.app')

@section('styles')
<style>
    /* Wizard Container & Tabs */
    .wizard-container {
        background-color: var(--card-bg);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .wizard-tabs {
        display: flex;
        background-color: #f7fafc;
        border-bottom: 1px solid var(--border-color);
        padding: 0 20px;
    }

    .wizard-tab {
        padding: 16px 28px;
        font-weight: 600;
        font-size: 14px;
        color: var(--text-muted);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
    }

    .wizard-tab.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
        background-color: var(--card-bg);
    }

    /* Sub-tabs for multiple item wizard entries */
    .item-tab-btn {
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .item-tab-btn.active {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: #ffffff;
    }

    /* Progress Steps Bar */
    .progress-bar-container {
        padding: 24px 40px;
        border-bottom: 1px solid var(--border-color);
        background-color: #fcfdfe;
    }

    .steps-list {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        max-width: 900px;
        margin: 0 auto;
    }

    .steps-list::after {
        content: '';
        position: absolute;
        top: 20px;
        left: 5%;
        right: 5%;
        height: 3px;
        background-color: var(--border-color);
        z-index: 1;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        cursor: pointer;
        flex: 1;
    }

    .step-badge {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #edf2f7;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        border: 3px solid var(--card-bg);
        transition: all 0.3s ease;
        box-shadow: 0 0 0 1px var(--border-color);
    }

    .step-title {
        margin-top: 10px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        text-align: center;
        transition: all 0.3s ease;
    }

    /* Active Step */
    .step-item.active .step-badge {
        background-color: var(--primary-color);
        color: #ffffff;
        box-shadow: 0 0 0 1px var(--primary-color), 0 4px 10px rgba(16, 139, 182, 0.3);
    }

    .step-item.active .step-title {
        color: var(--primary-color);
    }

    /* Completed Step */
    .step-item.completed .step-badge {
        background-color: #2b9cbd;
        color: #ffffff;
        box-shadow: 0 0 0 1px #2b9cbd;
    }

    .step-item.completed .step-title {
        color: #2b9cbd;
    }

    /* Form Content & Cards */
    .form-step-panel {
        display: none;
        padding: 40px;
        animation: fadeIn 0.4s ease;
    }

    .form-step-panel.active {
        display: block;
    }

    .panel-subtitle {
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 24px;
        font-weight: 500;
    }

    /* Grid Layout for Group Cards */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }

    .card-group {
        background-color: #f7fafc;
        border: 1px solid #edf2f7;
        border-radius: 12px;
        padding: 24px;
        position: relative;
    }

    .card-group-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 20px;
        border-bottom: 1px dashed var(--border-color);
        padding-bottom: 8px;
    }

    /* Form Elements */
    .form-row {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
    }

    .form-group {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group.checkbox-group {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
    }

    label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
    }

    input[type="text"],
    input[type="number"],
    input[type="date"],
    select,
    textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        color: var(--text-color);
        background-color: #ffffff;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(16, 139, 182, 0.1);
    }

    /* Toggle Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 24px;
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
        background-color: #cbd5e0;
        transition: .3s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: var(--primary-color);
    }

    input:checked + .slider:before {
        transform: translateX(20px);
    }

    /* CT vs % radio */
    .unit-selector {
        display: flex;
        gap: 12px;
        margin-top: 8px;
    }

    .unit-option {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 500;
        color: var(--text-muted);
        cursor: pointer;
    }

    .unit-option input {
        cursor: pointer;
        accent-color: var(--primary-color);
    }

    /* File Browse Design */
    .upload-zone-wrapper {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .upload-box {
        border: 1px solid var(--border-color);
        border-bottom: none;
        border-radius: 8px 8px 0 0;
        padding: 24px 20px;
        text-align: center;
        background-color: #f8f9fa;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .upload-box:hover {
        background-color: #f0f5f8;
        border-color: var(--primary-color);
    }

    .upload-icon {
        font-size: 28px;
        color: var(--primary-color);
    }

    .upload-btn-label {
        font-weight: 700;
        font-size: 11px;
        color: #ffffff;
        background-color: var(--primary-color);
        padding: 7px 16px;
        border-radius: 5px;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.2s ease;
    }

    .upload-box:hover .upload-btn-label {
        background-color: var(--primary-hover);
    }

    .upload-box span.or-label {
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 0 10px 0;
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
        width: 100%;
        background-color: #fcfdfe;
    }

    .upload-link-input {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 16px 20px;
        border: 1px solid var(--border-color);
        border-top: none;
        border-radius: 0 0 8px 8px;
        background-color: #ffffff;
    }

    .upload-link-input label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
    }

    .upload-link-input input {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-family: inherit;
        font-size: 13px;
        color: var(--text-color);
        background-color: #ffffff;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .upload-link-input input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px var(--primary-light);
    }

    /* Wizard Actions footer */
    .wizard-actions {
        display: flex;
        justify-content: center;
        gap: 16px;
        padding: 24px 40px;
        border-top: 1px solid var(--border-color);
        background-color: #f7fafc;
    }

    .btn {
        padding: 12px 30px;
        border-radius: 8px;
        font-family: inherit;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-secondary {
        background-color: #e2e8f0;
        color: #4a5568;
        border: none;
    }

    .btn-secondary:hover {
        background-color: #cbd5e0;
    }

    .btn-primary {
        background-color: var(--primary-color);
        color: #ffffff;
        border: none;
        box-shadow: 0 4px 12px rgba(16, 139, 182, 0.2);
    }

    .btn-primary:hover {
        background-color: var(--primary-hover);
        box-shadow: 0 6px 16px rgba(16, 139, 182, 0.3);
    }

    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Fancy color conditional section */
    .fancy-color-container {
        display: none;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px dashed var(--border-color);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Bulk Import Styling */
    .bulk-import-panel {
        padding: 60px 40px;
        text-align: center;
        background-color: var(--card-bg);
    }

    .bulk-import-content {
        max-width: 600px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 24px;
    }

    .bulk-icon-box {
        width: 80px;
        height: 80px;
        background-color: #eaf6ec;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .drop-zone {
        width: 100%;
        border: 2px dashed #cbd5e0;
        border-radius: 12px;
        padding: 40px 20px;
        background-color: #fcfdfe;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 14px;
    }

    .drop-zone:hover {
        border-color: var(--primary-color);
        background-color: var(--primary-light);
    }

    /* Class Toggle Switcher for Shapes and Colors */
    .label-toggle-container {
        display: inline-flex;
        gap: 6px;
        font-size: 11px;
        color: #cbd5e0;
        font-weight: 700;
        cursor: pointer;
        user-select: none;
    }

    .label-toggle-container span {
        padding: 0 4px;
        transition: all 0.15s ease;
    }

    .label-toggle-container span.active {
        color: var(--primary-color);
        border-bottom: 1.5px solid var(--primary-color);
        padding-bottom: 1px;
    }
</style>
@endsection

@section('content')
<div class="wizard-container">
    <!-- Wizard Tabs -->
    <div class="wizard-tabs">
        <div class="wizard-tab active" id="tab-single-btn" onclick="switchMainTab('single')">Single</div>
        <div class="wizard-tab" id="tab-multiple-btn" onclick="switchMainTab('multiple')">Multiple</div>
    </div>

    <!-- Dynamic Subtabs for Multiple entries -->
    <div id="multiple-items-tabs" style="display: none; padding: 16px 40px; background-color: #f8fafc; border-bottom: 1px solid var(--border-color); align-items: center; gap: 12px; flex-wrap: wrap;">
        <span style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Manage Items:</span>
        <div id="item-tabs-list" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <!-- Buttons dynamically appended by JS -->
        </div>
        <button type="button" class="btn btn-secondary" style="padding: 6px 14px; font-size: 12px; border-radius: 20px;" onclick="addMultipleItem()">
            <i class="fa-solid fa-plus"></i> Add Item
        </button>
    </div>

    <!-- Progress Steps Bar -->
    <div class="progress-bar-container" id="steps-progress-container">
        <div class="steps-list">
            <div class="step-item active" data-step="1" onclick="jumpToStep(1)">
                <div class="step-badge">1</div>
                <div class="step-title">General Information</div>
            </div>
            <div class="step-item" data-step="2" onclick="jumpToStep(2)">
                <div class="step-badge">2</div>
                <div class="step-title">Report Information</div>
            </div>
            <div class="step-item" data-step="3" onclick="jumpToStep(3)">
                <div class="step-badge">3</div>
                <div class="step-title">Other Information</div>
            </div>
            <div class="step-item" data-step="4" onclick="jumpToStep(4)">
                <div class="step-badge">4</div>
                <div class="step-title">Image & Report Scan</div>
            </div>
            <div class="step-item" data-step="5" onclick="jumpToStep(5)">
                <div class="step-badge">5</div>
                <div class="step-title">Additional Information</div>
            </div>
        </div>
    </div>

    <!-- BULK IMPORT FORM (Visible only in Multiple Mode) -->
    <form action="{{ route('diamonds.import') }}" method="POST" enctype="multipart/form-data" id="bulk-import-form" style="display: none;">
        @csrf
        <div class="bulk-import-panel">
            <div class="bulk-import-content">
                
                <!-- Import Icon (Excel representation) -->
                <div class="bulk-icon-box">
                    <i class="fa-solid fa-file-excel" style="font-size: 40px; color: #2e7d32;"></i>
                </div>

                <h3 style="font-size: 20px; font-weight: 700; color: var(--text-color);">Bulk Diamond Import</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.5;">
                    Upload a .csv, .xlsx or .xls spreadsheet file populated with diamond parameters to import multiple diamonds directly into the single database table.
                </p>

                <div class="alert alert-info">
                    <strong>CSV Format:</strong>
                    <a href="{{ asset('samples/diamond_upload_sample.csv') }}"
                    class="btn btn-sm btn-primary ms-2"
                    download>
                    Download Sample
                    </a>
                </div> 

                <!-- Drag & Drop Zone -->
                <div class="drop-zone" id="drop-zone" onclick="triggerImportFileSelect()">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background-color: var(--primary-light); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-cloud-arrow-up" style="font-size: 20px; color: var(--primary-color);"></i>
                    </div>
                    <button type="button" class="btn btn-primary" style="padding: 10px 24px; font-size: 13px; font-weight: 700; border-radius: 6px; box-shadow: none;">BROWSE FILES</button>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 500;" id="import-filename-label">or drag and drop spreadsheet files here</span>
                    <input type="file" name="import_file" id="import_file" accept=".csv,.xlsx,.xls" style="display: none;" onchange="handleImportFileChange(this)">
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" id="import-submit-btn" style="padding: 12px 40px; font-size: 14px; font-weight: 600; display: none; margin-top: 10px;">
                    <i class="fa-solid fa-file-import" style="margin-right: 8px;"></i> Import Diamonds
                </button>
            </div>
        </div>
    </form>

    <!-- MAIN FORM (Handles both single and multiple uploads) -->
    <form action="{{ route('diamonds.store') }}" method="POST" enctype="multipart/form-data" id="diamond-upload-form">
        @csrf
        
        <!-- Serialized JSON payload for multiple mode -->
        <input type="hidden" name="diamonds_json" id="diamonds-json-input">
        
        <!-- Hidden file inputs container for multiple files tracking -->
        <div id="multiple-files-container" style="display: none;"></div>

        <!-- STEP 1: GENERAL INFORMATION -->
        <div class="form-step-panel active" id="step-panel-1">
            <div class="panel-subtitle" id="step-1-title">Upload single item</div>
            
            <div class="cards-grid">
                <!-- Stock Group -->
                <div class="card-group">
                    <div class="card-group-title">Stock#</div>
                    <div class="form-group">
                        <label for="stock_no">Stock Number</label>
                        <input type="text" id="stock_no" name="stock_no" placeholder="Enter stock number" required onchange="syncField(this)">
                    </div>
                </div>

                <!-- Price Group -->
                <div class="card-group">
                    <div class="card-group-title">Price</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="asking_price">Asking Price</label>
                            <input type="number" step="0.01" id="asking_price" name="asking_price" placeholder="Enter asking price" onchange="syncField(this)">
                            <div class="unit-selector">
                                <label class="unit-option">
                                    <input type="radio" name="asking_price_unit" value="CT" checked onchange="syncField(this)"> CT
                                </label>
                                 <label class="unit-option">
                                     <input type="radio" name="asking_price_unit" value="OM %" onchange="syncField(this)"> OM %
                                 </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="cash_price">Cash Price</label>
                            <input type="number" step="0.01" id="cash_price" name="cash_price" placeholder="Enter cash price" onchange="syncField(this)">
                            <div class="unit-selector">
                                <label class="unit-option">
                                    <input type="radio" name="cash_price_unit" value="CT" checked onchange="syncField(this)"> CT
                                </label>
                                 <label class="unit-option">
                                     <input type="radio" name="cash_price_unit" value="OM %" onchange="syncField(this)"> OM %
                                 </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Availability Group -->
                <div class="card-group">
                    <div class="card-group-title">Availability</div>
                    <div class="form-group">
                        <label for="availability">Availability State</label>
                        <select id="availability" name="availability" onchange="syncField(this)">
                            <option value="">Select availability</option>
                            <option value="Available" selected>Available</option>
                            <option value="Memo">Memo</option>
                            <option value="On Hold">On Hold</option>
                        </select>
                    </div>
                </div>

                <!-- Location Group -->
                <div class="card-group">
                    <div class="card-group-title">Location</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="country">Country</label>
                            <select id="country" name="country" onchange="syncField(this)">
                                <option value="">Select country</option>
                                <option value="India" selected>India</option>
                                <option value="USA">USA</option>
                                <option value="Belgium">Belgium</option>
                                <option value="Israel">Israel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <select id="state" name="state" onchange="syncField(this)">
                                <option value="">Select state</option>
                                <option value="Gujarat" selected>Gujarat</option>
                                <option value="Maharashtra">Maharashtra</option>
                                <option value="New York">New York</option>
                                <option value="Antwerp">Antwerp</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" placeholder="Enter city" value="Surat" onchange="syncField(this)">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2: REPORT INFORMATION -->
        <div class="form-step-panel" id="step-panel-2">
            <div class="cards-grid">
                <!-- Group 1: Core Specs -->
                <div class="card-group">
                    <div class="card-group-title">Key Characteristics</div>
                    <div class="form-row">
                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label for="shape">Shape</label>
                                <div class="label-toggle-container" id="shape-type-toggle">
                                    <span class="active" onclick="setShapeTypeTab('basic')" id="shape-btn-basic">Basic</span>
                                    <span style="color: #cbd5e0; cursor: default;">|</span>
                                    <span onclick="setShapeTypeTab('advance')" id="shape-btn-advance">Advance</span>
                                </div>
                            </div>
                            <select id="shape" name="shape" onchange="toggleShapeType(this); syncField(this);">
                                <option value="">Select shape</option>
                                @foreach($categories['shape'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" data-group="{{ $opt->group ?? 'basic' }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="advance-shape-section" style="display: none;">
                            <label for="advance_shape_detail">Advance Shape Detail</label>
                            <select id="advance_shape_detail" name="advance_shape_detail" onchange="syncField(this)">
                                <option value="">Select detail</option>
                                @foreach($categories['advance_shape_detail'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="size">Size (Carat)</label>
                            <input type="number" step="0.001" id="size" name="size" placeholder="Enter size" onchange="syncField(this)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label for="color">Color</label>
                                <div class="label-toggle-container" id="color-type-toggle">
                                    <span class="active" onclick="setColorTypeTab('white')" id="color-btn-white">White</span>
                                    <span style="color: #cbd5e0; cursor: default;">|</span>
                                    <span onclick="setColorTypeTab('fancy')" id="color-btn-fancy">Fancy</span>
                                </div>
                            </div>
                            <select id="color" name="color" onchange="syncField(this)">
                                <option value="">Select color</option>
                                @foreach($categories['color'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" data-group="{{ $opt->group ?? 'white' }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="clarity">Clarity</label>
                            <select id="clarity" name="clarity" onchange="syncField(this)">
                                <option value="">Select clarity</option>
                                @foreach($categories['clarity'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Group 2: Cut, Polish, Symmetry, Fluorescence -->
                <div class="card-group">
                    <div class="card-group-title">Cut & Polish Specs</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cut">Cut</label>
                            <select id="cut" name="cut" onchange="syncField(this)">
                                <option value="">Select cut</option>
                                @foreach($categories['cut'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="polish">Polish</label>
                            <select id="polish" name="polish" onchange="syncField(this)">
                                <option value="">Select polish</option>
                                @foreach($categories['polish'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="symmetry">Symmetry</label>
                            <select id="symmetry" name="symmetry" onchange="syncField(this)">
                                <option value="">Select symmetry</option>
                                @foreach($categories['symmetry'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fluorescence_intensity">Fluorescence Intensity</label>
                            <select id="fluorescence_intensity" name="fluorescence_intensity" onchange="syncField(this)">
                                <option value="">Select intensity</option>
                                @foreach($categories['fluorescence_intensity'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fluorescence_color">Fluorescence Color</label>
                            <select id="fluorescence_color" name="fluorescence_color" onchange="syncField(this)">
                                <option value="">Select color</option>
                                @foreach($categories['fluorescence_color'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Group 3: Measurements & Proportions -->
                <div class="card-group">
                    <div class="card-group-title">Measurements & Ratios</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="length">Length</label>
                            <input type="number" step="0.01" id="length" name="length" placeholder="Length" onchange="syncField(this)">
                        </div>
                        <div class="form-group">
                            <label for="width">Width</label>
                            <input type="number" step="0.01" id="width" name="width" placeholder="Width" onchange="syncField(this)">
                        </div>
                        <div class="form-group">
                            <label for="depth">Depth</label>
                            <input type="number" step="0.01" id="depth" name="depth" placeholder="Depth" onchange="syncField(this)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="depth_percent">Depth %</label>
                            <input type="number" step="0.1" id="depth_percent" name="depth_percent" placeholder="Depth %" onchange="syncField(this)">
                        </div>
                        <div class="form-group">
                            <label for="table_percent">Table %</label>
                            <input type="number" step="0.1" id="table_percent" name="table_percent" placeholder="Table %" onchange="syncField(this)">
                        </div>
                    </div>
                </div>

                <!-- Group 4: Crown & Pavilion -->
                <div class="card-group">
                    <div class="card-group-title">Crown & Pavilion</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="crown_angle">Crown Angle</label>
                            <input type="number" step="0.1" id="crown_angle" name="crown_angle" placeholder="Angle" onchange="syncField(this)">
                        </div>
                        <div class="form-group">
                            <label for="crown_height">Crown Height</label>
                            <input type="number" step="0.1" id="crown_height" name="crown_height" placeholder="Height" onchange="syncField(this)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pavilion_angle">Pavilion Angle</label>
                            <input type="number" step="0.1" id="pavilion_angle" name="pavilion_angle" placeholder="Angle" onchange="syncField(this)">
                        </div>
                        <div class="form-group">
                            <label for="pavilion_depth">Pavilion Depth</label>
                            <input type="number" step="0.1" id="pavilion_depth" name="pavilion_depth" placeholder="Depth" onchange="syncField(this)">
                        </div>
                    </div>
                </div>

                <!-- Group 5: Girdle & Culet -->
                <div class="card-group">
                    <div class="card-group-title">Girdle & Culet</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="girdle_condition">Girdle Condition</label>
                            <select id="girdle_condition" name="girdle_condition" onchange="syncField(this)">
                                <option value="">Select Condition</option>
                                @foreach($categories['girdle_condition'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="girdle_min">Min Girdle</label>
                            <select id="girdle_min" name="girdle_min" onchange="syncField(this)">
                                <option value="">Min</option>
                                <option value="Thin">Thin</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="Slightly Thick">Slightly Thick</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="girdle_max">Max Girdle</label>
                            <select id="girdle_max" name="girdle_max" onchange="syncField(this)">
                                <option value="">Max</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="Slightly Thick">Slightly Thick</option>
                                <option value="Thick">Thick</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="girdle_percent">Girdle %</label>
                            <input type="number" step="0.1" id="girdle_percent" name="girdle_percent" placeholder="Girdle %" onchange="syncField(this)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="culet_condition">Culet Condition</label>
                            <select id="culet_condition" name="culet_condition" onchange="syncField(this)">
                                <option value="">Condition</option>
                                @foreach($categories['culet_condition'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="culet_size">Culet Size</label>
                            <select id="culet_size" name="culet_size" onchange="syncField(this)">
                                <option value="">Size</option>
                                @foreach($categories['culet_size'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Group 6: Lab & Verification Specs -->
                <div class="card-group">
                    <div class="card-group-title">Certification Details</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lab">Lab</label>
                            <select id="lab" name="lab" onchange="syncField(this)">
                                <option value="">Select Lab</option>
                                @foreach($categories['lab'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="report_no">Report Number</label>
                            <input type="text" id="report_no" name="report_no" placeholder="Enter report number" onchange="syncField(this)">
                        </div>
                        <div class="form-group checkbox-group" style="padding-top: 25px;">
                            <label for="show_on_OM">Show on OM Gems</label>
                            <label class="switch">
                                <input type="checkbox" id="show_on_OM" name="show_on_OM" checked value="1" onchange="syncField(this)">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="report_date">Report Date</label>
                            <input type="date" id="report_date" name="report_date" onchange="syncField(this)">
                        </div>
                        <div class="form-group">
                            <label for="lab_location">Lab Location</label>
                            <input type="text" id="lab_location" name="lab_location" placeholder="Lab location" onchange="syncField(this)">
                        </div>
                    </div>
                </div>

                <!-- Group 7: Comments & Fancy Color Toggle -->
                <div class="card-group">
                    <div class="card-group-title">Additional Info & Fancy Colors</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="treatment">Treatment</label>
                            <select id="treatment" name="treatment" onchange="syncField(this)">
                                <option value="">Select Treatment</option>
                                @foreach($categories['treatment'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="laser_inscription">Laser Inscription</label>
                            <input type="text" id="laser_inscription" name="laser_inscription" placeholder="Laser inscription" onchange="syncField(this)">
                        </div>
                        <div class="form-group">
                            <label for="star_length">Star Length</label>
                            <input type="number" step="0.1" id="star_length" name="star_length" placeholder="Star length" onchange="syncField(this)">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="report_comment">Report Comment</label>
                        <input type="text" id="report_comment" name="report_comment" placeholder="Report Comment" onchange="syncField(this)">
                    </div>
                    
                    <div class="form-row" style="margin-top: 12px;">
                        <div class="form-group">
                            <label for="key_to_symbols">Key To Symbols</label>
                            <select id="key_to_symbols" name="key_to_symbols" onchange="syncField(this)">
                                <option value="">Select symbols</option>
                                <option value="Feather">Feather</option>
                                <option value="Crystal">Crystal</option>
                                <option value="Needle">Needle</option>
                            </select>
                        </div>
                        
                        <div class="form-group checkbox-group" style="padding-top: 25px;">
                            <label for="fancy_color_enabled">Fancy Color</label>
                            <label class="switch">
                                <input type="checkbox" id="fancy_color_enabled" name="fancy_color_enabled" onchange="toggleFancyColor(this); syncField(this);">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Fancy Color Fields -->
                    <div class="fancy-color-container" id="fancy-color-section">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fancy_color_intensity">Intensity</label>
                                <select id="fancy_color_intensity" name="fancy_color_intensity" onchange="syncField(this)">
                                    <option value="">Select intensity</option>
                                    @foreach($categories['fancy_color_intensity'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="fancy_color_overtone">Overtone</label>
                                <select id="fancy_color_overtone" name="fancy_color_overtone" onchange="syncField(this)">
                                    <option value="">Select overtone</option>
                                    @foreach($categories['fancy_color_overtone'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fancy_color_color1">Color 1</label>
                                <select id="fancy_color_color1" name="fancy_color_color1" onchange="syncField(this)">
                                    <option value="">Select color 1</option>
                                    @foreach($categories['fancy_color_color'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label_for="fancy_color_color2">Color 2</label_for>
                                <select id="fancy_color_color2" name="fancy_color_color2" onchange="syncField(this)">
                                    <option value="">Select color 2</option>
                                    <option value="None">None</option>
                                    @foreach($categories['fancy_color_color'] ?? [] as $opt)
                                        @if(strcasecmp($opt->name, 'None') !== 0)
                                            <option value="{{ $opt->name }}">{{ $opt->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3: OTHER INFORMATION -->
        <div class="form-step-panel" id="step-panel-3">
            <div class="cards-grid">
                <!-- Pair details -->
                <div class="card-group">
                    <div class="card-group-title">Matched Pair Info</div>
                    <div class="form-group checkbox-group">
                        <label for="is_matched_pair">Matched Pair</label>
                        <label class="switch">
                            <input type="checkbox" id="is_matched_pair" name="is_matched_pair" onchange="togglePairFields(this); syncField(this);">
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div id="pair-fields" style="display: none; margin-top: 15px;">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label for="matched_pair_stock_no">Stock Number</label>
                            <input type="text" id="matched_pair_stock_no" name="matched_pair_stock_no" placeholder="Enter paired stock number" onchange="syncField(this)">
                        </div>
                        <div class="form-group checkbox-group">
                            <label for="is_pair_separable">Is Pair Separable</label>
                            <label class="switch">
                                <input type="checkbox" id="is_pair_separable" name="is_pair_separable" value="1" onchange="syncField(this)">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Download details -->
                <div class="card-group">
                    <div class="card-group-title">Download Permissions</div>
                    <div class="form-group checkbox-group">
                        <div>
                            <label for="allow_download" style="display: block; margin-bottom: 4px;">Download</label>
                            <span style="font-size: 11px; color: var(--text-muted);">Allow Approved member to download this diamond</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="allow_download" name="allow_download" value="1" onchange="syncField(this)">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Trade show & Brand -->
                <div class="card-group">
                    <div class="card-group-title">Trade Show & Brand</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="trade_show">Trade Show</label>
                            <select id="trade_show" name="trade_show" onchange="syncField(this)">
                                <option value="">Select trade show</option>
                                <option value="JCK Las Vegas">JCK Las Vegas</option>
                                <option value="Hong Kong Jewellery Show">Hong Kong Show</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="brand">Brand</label>
                            <select id="brand" name="brand" onchange="syncField(this)">
                                <option value="">Select brand</option>
                                <option value="OM Signature">OM Signature</option>
                                <option value="Hearts & Arrows">Hearts & Arrows</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Comments & Parcel -->
                <div class="card-group">
                    <div class="card-group-title">Supplier & Parcel details</div>
                    
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="supplier_comment">Supplier Comment</label>
                        <input type="text" id="supplier_comment" name="supplier_comment" placeholder="Supplier comments" onchange="syncField(this)">
                    </div>

                    <div class="form-group checkbox-group" style="margin-bottom: 12px;">
                        <label for="is_parcel">Parcel</label>
                        <label class="switch">
                            <input type="checkbox" id="is_parcel" name="is_parcel" onchange="toggleParcelFields(this); syncField(this);">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div id="parcel-fields" style="display: none;">
                        <div class="form-group">
                            <label for="number_of_diamonds">Number of Diamond</label>
                            <input type="number" id="number_of_diamonds" name="number_of_diamonds" placeholder="Number of diamonds" onchange="syncField(this)">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 4: IMAGE & REPORT SCAN -->
        <div class="form-step-panel" id="step-panel-4">
            <div class="cards-grid">
                
                <!-- Report file upload -->
                <div class="card-group">
                    <div class="card-group-title">Report File</div>
                    
                    <div class="upload-zone-wrapper">
                        <div class="upload-box" onclick="triggerReportUpload()">
                            <i class="fa-solid fa-file-pdf upload-icon"></i>
                            <span class="upload-btn-label">Browse</span>
                            <span id="report_filename_lbl" style="font-size: 12px; color: var(--success-color); font-weight: 500;"></span>
                        </div>
                        
                        <span class="or-label" style="align-self: center;">or</span>
                        
                        <div class="upload-link-input">
                            <label for="report_link">Upload Link</label>
                            <input type="text" id="report_link" name="report_link" placeholder="Enter report file URL" onchange="syncField(this)">
                        </div>
                    </div>
                </div>

                <!-- Diamond Images file upload -->
                <div class="card-group">
                    <div class="card-group-title">Diamond Images</div>
                    
                    <div class="upload-zone-wrapper">
                        <div class="upload-box" onclick="document.getElementById('images_input').click()">
                            <i class="fa-solid fa-images upload-icon"></i>
                            <span class="upload-btn-label">Browse Images</span>
                            <span id="images_lbl" style="font-size: 12px; color: var(--success-color); font-weight: 500;"></span>
                            <input type="file" id="images_input" name="images[]" multiple accept="image/*" style="display: none;" onchange="showMultipleFilenames(this, 'images_lbl')">
                        </div>
                        
                        <span class="or-label" style="align-self: center;">or</span>
                        
                        <div class="upload-link-input">
                            <label for="diamond_image_link">Legacy Single Image URL</label>
                            <input type="text" id="diamond_image_link" name="diamond_image_link" placeholder="Enter image URL" onchange="syncField(this)">
                        </div>
                    </div>
                </div>

                <!-- Diamond Videos file upload -->
                <div class="card-group">
                    <div class="card-group-title">Diamond Videos</div>
                    
                    <div class="upload-zone-wrapper">
                        <div class="upload-box" onclick="document.getElementById('videos_input').click()">
                            <i class="fa-solid fa-video upload-icon"></i>
                            <span class="upload-btn-label">Browse Videos</span>
                            <span id="videos_lbl" style="font-size: 12px; color: var(--success-color); font-weight: 500;"></span>
                            <input type="file" id="videos_input" name="videos[]" multiple accept="video/*" style="display: none;" onchange="showMultipleFilenames(this, 'videos_lbl')">
                        </div>
                        
                        <span class="or-label" style="align-self: center;">or</span>
                        
                        <div class="upload-link-input">
                            <label for="sarine_loupe">Legacy Single Video URL</label>
                            <input type="text" id="sarine_loupe" name="sarine_loupe" placeholder="Enter video/loupe URL" onchange="syncField(this)">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 5: ADDITIONAL INFORMATION -->
        <div class="form-step-panel" id="step-panel-5">
            <div class="cards-grid">
                <div class="card-group">
                    <div class="card-group-title">Additional Comments</div>
                    <div class="form-group">
                        <label for="additional_comments">Public Comments</label>
                        <textarea id="additional_comments" name="additional_comments" rows="4" placeholder="Enter public remarks..." onchange="syncField(this)"></textarea>
                    </div>
                </div>

                <div class="card-group">
                    <div class="card-group-title">Internal Details</div>
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="internal_notes">Internal Admin Notes</label>
                        <textarea id="internal_notes" name="internal_notes" rows="4" placeholder="Internal remarks..." onchange="syncField(this)"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="appraisal_value">Appraisal Value ($)</label>
                        <input type="number" step="0.01" id="appraisal_value" name="appraisal_value" placeholder="Estimated value" onchange="syncField(this)">
                    </div>
                </div>
            </div>
        </div>

        <!-- Wizard Navigation Actions -->
        <div class="wizard-actions">
            <button type="button" class="btn btn-secondary" id="prev-btn" onclick="navigateStep(-1)" disabled>Back</button>
            <button type="button" class="btn btn-primary" id="next-btn" onclick="navigateStep(1)">Next</button>
            <button type="submit" class="btn btn-primary" id="submit-btn" style="display: none;">Upload Diamond</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    let currentStep = 1;
    const totalSteps = 5;
    
    let cachedShapes = [];
    let cachedColors = [];

    // Multiple entries state machine
    let uploadMode = 'single'; // 'single' or 'multiple'
    let multipleDiamonds = []; // Array of diamond objects for multiple uploads
    let activeMultipleIndex = 0; // Current active diamond array index

    document.addEventListener("DOMContentLoaded", function() {
        // Cache shapes
        const shapeSelect = document.getElementById('shape');
        if (shapeSelect) {
            Array.from(shapeSelect.options).forEach(opt => {
                if (opt.value !== "") {
                    cachedShapes.push({
                        value: opt.value,
                        text: opt.text,
                        group: opt.getAttribute('data-group') || 'basic'
                    });
                }
            });
        }
        
        // Cache colors
        const colorSelect = document.getElementById('color');
        if (colorSelect) {
            Array.from(colorSelect.options).forEach(opt => {
                if (opt.value !== "") {
                    cachedColors.push({
                        value: opt.value,
                        text: opt.text,
                        group: opt.getAttribute('data-group') || 'white'
                    });
                }
            });
        }

        // Initialize first multiple diamond item
        addMultipleItemState();

        // Initially filter shapes to basic and colors to white
        setShapeTypeTab('basic', false);
        setColorTypeTab('white', false);
    });

    function setShapeTypeTab(type, resetSelection = true) {
        const shapeBtnBasic = document.getElementById('shape-btn-basic');
        const shapeBtnAdvance = document.getElementById('shape-btn-advance');
        const shapeSelect = document.getElementById('shape');
        
        if (!shapeBtnBasic || !shapeBtnAdvance || !shapeSelect) return;
        
        if (type === 'basic') {
            shapeBtnBasic.classList.add('active');
            shapeBtnAdvance.classList.remove('active');
        } else {
            shapeBtnBasic.classList.remove('active');
            shapeBtnAdvance.classList.add('active');
        }
        
        const currentValue = shapeSelect.value;
        
        // Rebuild options
        shapeSelect.innerHTML = '<option value="">Select shape</option>';
        cachedShapes.forEach(opt => {
            if (opt.group === type) {
                const optEl = document.createElement('option');
                optEl.value = opt.value;
                optEl.text = opt.text;
                optEl.setAttribute('data-group', opt.group);
                if (opt.value === currentValue) {
                    optEl.selected = true;
                }
                shapeSelect.appendChild(optEl);
            }
        });
        
        if (resetSelection && shapeSelect.value !== currentValue) {
            shapeSelect.value = '';
            toggleShapeType(shapeSelect);
            if (typeof syncField === 'function') {
                syncField(shapeSelect);
            }
        }
    }

    function setColorTypeTab(type, resetSelection = true) {
        const colorBtnWhite = document.getElementById('color-btn-white');
        const colorBtnFancy = document.getElementById('color-btn-fancy');
        const colorSelect = document.getElementById('color');
        const fancyCheckbox = document.getElementById('fancy_color_enabled');
        const fancySection = document.getElementById('fancy-color-section');
        
        if (!colorBtnWhite || !colorBtnFancy || !colorSelect) return;
        
        if (type === 'white') {
            colorBtnWhite.classList.add('active');
            colorBtnFancy.classList.remove('active');
            if (fancyCheckbox) fancyCheckbox.checked = false;
            if (fancySection) fancySection.style.display = 'none';
        } else {
            colorBtnWhite.classList.remove('active');
            colorBtnFancy.classList.add('active');
            if (fancyCheckbox) fancyCheckbox.checked = true;
            if (fancySection) fancySection.style.display = 'block';
        }
        
        const currentValue = colorSelect.value;
        
        // Rebuild options
        colorSelect.innerHTML = '<option value="">Select color</option>';
        cachedColors.forEach(opt => {
            if (opt.group === type) {
                const optEl = document.createElement('option');
                optEl.value = opt.value;
                optEl.text = opt.text;
                optEl.setAttribute('data-group', opt.group);
                if (opt.value === currentValue) {
                    optEl.selected = true;
                }
                colorSelect.appendChild(optEl);
            }
        });
        
        let selectedValue = currentValue;
        if (type === 'fancy') {
            const hasFancyOption = Array.from(colorSelect.options).some(o => o.value === 'Fancy');
            if (hasFancyOption) {
                selectedValue = 'Fancy';
            } else if (!selectedValue || !Array.from(colorSelect.options).some(o => o.value === selectedValue)) {
                selectedValue = '';
            }
        } else if (currentValue === 'Fancy') {
            selectedValue = '';
        }
        
        if (selectedValue) {
            colorSelect.value = selectedValue;
        } else {
            colorSelect.selectedIndex = 0;
        }
        
        if (typeof syncField === 'function') {
            syncField(colorSelect);
            if (fancyCheckbox) syncField(fancyCheckbox);
        }
    }

    // Navigate back and forth in steps
    function navigateStep(direction) {
        if (direction === 1 && !validateCurrentStep()) {
            return;
        }

        const targetStep = currentStep + direction;
        if (targetStep >= 1 && targetStep <= totalSteps) {
            jumpToStep(targetStep);
        }
    }

    // Direct click jump to step
    function jumpToStep(stepNumber) {
        if (stepNumber > currentStep) {
            for (let s = currentStep; s < stepNumber; s++) {
                currentStep = s;
                if (!validateCurrentStep()) {
                    highlightStepBadgeError(s);
                    return;
                }
                markStepCompleted(s);
            }
        }

        document.getElementById(`step-panel-${currentStep}`).classList.remove('active');
        document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.remove('active');

        currentStep = stepNumber;
        document.getElementById(`step-panel-${currentStep}`).classList.add('active');
        document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.add('active');

        document.getElementById('prev-btn').disabled = (currentStep === 1);
        
        const labelText = (uploadMode === 'multiple') ? 'Upload Diamonds' : 'Upload Diamond';
        if (currentStep === totalSteps) {
            document.getElementById('next-btn').style.display = 'none';
            document.getElementById('submit-btn').textContent = labelText;
            document.getElementById('submit-btn').style.display = 'inline-block';
        } else {
            document.getElementById('next-btn').style.display = 'inline-block';
            document.getElementById('submit-btn').style.display = 'none';
        }

        document.querySelector('.wizard-container').scrollIntoView({ behavior: 'smooth' });
    }

    function markStepCompleted(stepNumber) {
        document.querySelector(`.step-item[data-step="${stepNumber}"]`).classList.add('completed');
    }

    function highlightStepBadgeError(stepNumber) {
        const badge = document.querySelector(`.step-item[data-step="${stepNumber}"] .step-badge`);
        badge.style.backgroundColor = '#fed7d7';
        badge.style.color = '#e53e3e';
        badge.style.boxShadow = '0 0 0 1px #e53e3e';
        setTimeout(() => {
            badge.style.backgroundColor = '';
            badge.style.color = '';
            badge.style.boxShadow = '';
        }, 1500);
    }

    // Step field validation
    function validateCurrentStep() {
        const activePanel = document.getElementById(`step-panel-${currentStep}`);
        const requiredInputs = activePanel.querySelectorAll('[required]');
        let isValid = true;

        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.style.borderColor = 'var(--error-color)';
                input.addEventListener('input', function removeRedBorder() {
                    input.style.borderColor = '';
                    input.removeEventListener('input', removeRedBorder);
                });
            }
        });

        return isValid;
    }

    // Toggles collapse boxes
    function toggleFancyColor(checkbox) {
        const isChecked = checkbox.checked;
        document.getElementById('fancy-color-section').style.display = isChecked ? 'block' : 'none';
        setColorTypeTab(isChecked ? 'fancy' : 'white', false);
    }

    function toggleShapeType(select) {
        const selectedOption = select.options[select.selectedIndex];
        const group = selectedOption ? selectedOption.getAttribute('data-group') : 'basic';
        const section = document.getElementById('advance-shape-section');
        if (section) {
            section.style.display = (group === 'advance') ? 'block' : 'none';
            if (group !== 'advance') {
                const detailSelect = document.getElementById('advance_shape_detail');
                if (detailSelect) {
                    detailSelect.value = '';
                    if (typeof syncField === 'function') {
                        syncField(detailSelect);
                    }
                }
            }
        }
    }

    function togglePairFields(checkbox) {
        document.getElementById('pair-fields').style.display = checkbox.checked ? 'block' : 'none';
    }

    function toggleParcelFields(checkbox) {
        document.getElementById('parcel-fields').style.display = checkbox.checked ? 'block' : 'none';
    }

    // Switch main tabs: Single vs Multiple
    function switchMainTab(tab) {
        const singleBtn = document.getElementById('tab-single-btn');
        const multipleBtn = document.getElementById('tab-multiple-btn');
        const progressContainer = document.getElementById('steps-progress-container');
        const singleForm = document.getElementById('diamond-upload-form');
        const bulkForm = document.getElementById('bulk-import-form');
        
        singleBtn.classList.remove('active');
        multipleBtn.classList.remove('active');

        if (tab === 'single') {
            uploadMode = 'single';
            singleBtn.classList.add('active');
            progressContainer.style.display = 'block';
            singleForm.style.display = 'block';
            bulkForm.style.display = 'none';

            // Reset wizard step to step 1
            jumpToStep(1);
            clearAllCompletedSteps();
        } else {
            uploadMode = 'multiple';
            multipleBtn.classList.add('active');
            progressContainer.style.display = 'none';
            singleForm.style.display = 'none';
            bulkForm.style.display = 'block';
        }
    }

    function clearAllCompletedSteps() {
        document.querySelectorAll('.step-item').forEach(item => {
            item.classList.remove('completed');
        });
    }

    // Trigger file input click for bulk import
    function triggerImportFileSelect() {
        document.getElementById('import_file').click();
    }

    // Handle file input change for bulk import
    function handleImportFileChange(input) {
        const label = document.getElementById('import-filename-label');
        const submitBtn = document.getElementById('import-submit-btn');
        if (input.files.length > 0) {
            label.textContent = `Selected File: ${input.files[0].name}`;
            label.style.color = 'var(--primary-color)';
            label.style.fontWeight = 'bold';
            submitBtn.style.display = 'inline-block';
        } else {
            label.textContent = 'or drag and drop spreadsheet files here';
            label.style.color = '';
            label.style.fontWeight = '';
            submitBtn.style.display = 'none';
        }
    }

    // Set up drag and drop zone event listeners
    document.addEventListener("DOMContentLoaded", function() {
        const dropZone = document.getElementById('drop-zone');
        if (dropZone) {
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.style.borderColor = 'var(--primary-color)';
                    dropZone.style.backgroundColor = 'var(--primary-light)';
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropZone.style.borderColor = '';
                    dropZone.style.backgroundColor = '';
                }, false);
            });

            dropZone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                const fileInput = document.getElementById('import_file');
                if (files.length > 0) {
                    fileInput.files = files;
                    handleImportFileChange(fileInput);
                }
            }, false);
        }
    });

    // ==========================================
    // MULTIPLE ITEMS STATE MACHINE & FILE TRACKING
    // ==========================================

    // Dynamic item tabs rendering
    function renderItemTabs() {
        const list = document.getElementById('item-tabs-list');
        list.innerHTML = '';

        multipleDiamonds.forEach((item, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `item-tab-btn ${activeMultipleIndex === index ? 'active' : ''}`;
            btn.innerHTML = `<span>Item ${index + 1}</span>`;
            
            // Allow deleting items if count is > 1
            if (multipleDiamonds.length > 1) {
                const trash = document.createElement('i');
                trash.className = 'fa-solid fa-xmark';
                trash.style.fontSize = '9px';
                trash.style.marginLeft = '4px';
                trash.onclick = (e) => {
                    e.stopPropagation();
                    deleteMultipleItem(index);
                };
                btn.appendChild(trash);
            }

            btn.onclick = () => switchActiveItem(index);
            list.appendChild(btn);
        });
    }

    function addMultipleItemState() {
        multipleDiamonds.push({
            stock_no: '',
            asking_price: '',
            asking_price_unit: 'CT',
            cash_price: '',
            cash_price_unit: 'CT',
            availability: 'Available',
            country: 'India',
            state: 'Gujarat',
            city: 'Surat',
            shape: '',
            size: '',
            color: '',
            clarity: '',
            cut: '',
            polish: '',
            symmetry: '',
            fluorescence_intensity: '',
            fluorescence_color: '',
            length: '',
            width: '',
            depth: '',
            depth_percent: '',
            table_percent: '',
            crown_angle: '',
            crown_height: '',
            pavilion_angle: '',
            pavilion_depth: '',
            girdle_condition: '',
            girdle_min: 'Medium',
            girdle_max: 'Medium',
            girdle_percent: '',
            culet_condition: 'Pointed',
            culet_size: 'None',
            lab: '',
            report_no: '',
            show_on_OM: true,
            report_date: '',
            lab_location: '',
            treatment: 'None',
            laser_inscription: '',
            star_length: '',
            report_comment: '',
            key_to_symbols: '',
            fancy_color_enabled: false,
            fancy_color_intensity: '',
            fancy_color_overtone: '',
            fancy_color_color1: '',
            fancy_color_color2: '',
            is_matched_pair: false,
            matched_pair_stock_no: '',
            is_pair_separable: false,
            allow_download: false,
            trade_show: '',
            brand: '',
            supplier_comment: '',
            is_parcel: false,
            number_of_diamonds: '',
            report_link: '',
            diamond_image_link: '',
            sarine_loupe: '',
            additional_comments: '',
            internal_notes: '',
            appraisal_value: ''
        });
    }

    // Add new tab item
    function addMultipleItem() {
        saveFormValuesIntoState(activeMultipleIndex);
        addMultipleItemState();
        activeMultipleIndex = multipleDiamonds.length - 1;
        
        renderItemTabs();
        loadItemDataIntoForm(activeMultipleIndex);
        jumpToStep(1);
    }

    // Switch active tab item
    function switchActiveItem(index) {
        if (index === activeMultipleIndex) return;
        
        saveFormValuesIntoState(activeMultipleIndex);
        activeMultipleIndex = index;
        
        renderItemTabs();
        loadItemDataIntoForm(activeMultipleIndex);
        jumpToStep(1);
    }

    // Delete item
    function deleteMultipleItem(index) {
        if (multipleDiamonds.length <= 1) return;
        
        multipleDiamonds.splice(index, 1);
        
        // Remove file inputs for deleted item
        const filesContainer = document.getElementById('multiple-files-container');
        const reportInput = filesContainer.querySelector(`[name="report_file_item_${index}"]`);
        const imgInput = filesContainer.querySelector(`[name="diamond_image_item_${index}"]`);
        if (reportInput) reportInput.remove();
        if (imgInput) imgInput.remove();

        // Adjust other file inputs indexes downwards
        for (let i = index + 1; i <= multipleDiamonds.length; i++) {
            const rip = filesContainer.querySelector(`[name="report_file_item_${i}"]`);
            const iip = filesContainer.querySelector(`[name="diamond_image_item_${i}"]`);
            if (rip) rip.name = `report_file_item_${i-1}`;
            if (iip) iip.name = `diamond_image_item_${i-1}`;
        }

        if (activeMultipleIndex >= multipleDiamonds.length) {
            activeMultipleIndex = multipleDiamonds.length - 1;
        }

        renderItemTabs();
        loadItemDataIntoForm(activeMultipleIndex);
    }

    // Save active form inputs to state object
    function saveFormValuesIntoState(index) {
        if (index < 0 || index >= multipleDiamonds.length) return;
        
        const data = multipleDiamonds[index];
        const form = document.getElementById('diamond-upload-form');
        
        // Gather text/select inputs
        form.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], select, textarea').forEach(input => {
            if (input.name && input.name !== 'diamonds_json' && !input.name.startsWith('report_file_item_') && !input.name.startsWith('diamond_image_item_')) {
                data[input.name] = input.value;
            }
        });

        // Gather radios
        form.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
            data[radio.name] = radio.value;
        });

        // Gather toggles/checkboxes
        data.show_on_OM = document.getElementById('show_on_OM').checked;
        data.fancy_color_enabled = document.getElementById('fancy_color_enabled').checked;
        data.is_matched_pair = document.getElementById('is_matched_pair').checked;
        data.is_pair_separable = document.getElementById('is_pair_separable').checked;
        data.allow_download = document.getElementById('allow_download').checked;
        data.is_parcel = document.getElementById('is_parcel').checked;
    }

    // Populate form inputs with state values
    function loadItemDataIntoForm(index) {
        if (index < 0 || index >= multipleDiamonds.length) return;
        
        const data = multipleDiamonds[index];
        
        // Load text/select/textarea inputs
        for (const [key, value] of Object.entries(data)) {
            const element = document.getElementById(key);
            if (element) {
                element.value = value;
            }
        }

        // Load asking price unit radio
        document.querySelectorAll('input[name="asking_price_unit"]').forEach(radio => {
            radio.checked = (radio.value === data.asking_price_unit);
        });

        // Load cash price unit radio
        document.querySelectorAll('input[name="cash_price_unit"]').forEach(radio => {
            radio.checked = (radio.value === data.cash_price_unit);
        });

        // Load toggles/checkboxes
        document.getElementById('show_on_OM').checked = data.show_on_OM;
        document.getElementById('fancy_color_enabled').checked = data.fancy_color_enabled;
        document.getElementById('is_matched_pair').checked = data.is_matched_pair;
        document.getElementById('is_pair_separable').checked = data.is_pair_separable;
        document.getElementById('allow_download').checked = data.allow_download;
        document.getElementById('is_parcel').checked = data.is_parcel;

        // Toggle sections based on loaded checkbox values
        toggleFancyColor(document.getElementById('fancy_color_enabled'));
        togglePairFields(document.getElementById('is_matched_pair'));
        toggleParcelFields(document.getElementById('is_parcel'));

        // Load visual file labels
        const filesContainer = document.getElementById('multiple-files-container');
        const currentReport = filesContainer.querySelector(`[name="report_file_item_${index}"]`);
        const currentImg = filesContainer.querySelector(`[name="diamond_image_item_${index}"]`);

        document.getElementById('report_filename_lbl').textContent = (currentReport && currentReport.files.length > 0) ? `Selected: ${currentReport.files[0].name}` : '';
        document.getElementById('image_filename_lbl').textContent = (currentImg && currentImg.files.length > 0) ? `Selected: ${currentImg.files[0].name}` : '';
    }

    // Sync individual input values in real-time when typed, to preserve state on navigation
    function syncField(element) {
        if (uploadMode === 'multiple') {
            saveFormValuesIntoState(activeMultipleIndex);
        }
    }

    // ==========================================
    // MULTIPLE FILE INPUTS DISPATCHER
    // ==========================================

    function triggerReportUpload() {
        const filesContainer = document.getElementById('multiple-files-container');
        const fileKey = (uploadMode === 'multiple') ? `report_file_item_${activeMultipleIndex}` : 'report_file';

        // Check if file input already exists. If not, create it
        let input = document.querySelector(`input[name="${fileKey}"]`);
        if (!input) {
            input = document.createElement('input');
            input.type = 'file';
            input.name = fileKey;
            input.style.display = 'none';
            input.onchange = (e) => {
                const label = document.getElementById('report_filename_lbl');
                label.textContent = (input.files.length > 0) ? `Selected: ${input.files[0].name}` : '';
                syncField();
            };
            
            if (uploadMode === 'multiple') {
                filesContainer.appendChild(input);
            } else {
                document.getElementById('diamond-upload-form').appendChild(input);
            }
        }
        
        input.click();
    }

    function triggerImageUpload() {
        const filesContainer = document.getElementById('multiple-files-container');
        const fileKey = (uploadMode === 'multiple') ? `diamond_image_item_${activeMultipleIndex}` : 'diamond_image';

        // Check if file input already exists. If not, create it
        let input = document.querySelector(`input[name="${fileKey}"]`);
        if (!input) {
            input = document.createElement('input');
            input.type = 'file';
            input.name = fileKey;
            input.style.display = 'none';
            input.onchange = (e) => {
                const label = document.getElementById('image_filename_lbl');
                label.textContent = (input.files.length > 0) ? `Selected: ${input.files[0].name}` : '';
                syncField();
            };

            if (uploadMode === 'multiple') {
                filesContainer.appendChild(input);
            } else {
                document.getElementById('diamond-upload-form').appendChild(input);
            }
        }

        input.click();
    }

    // Intercept form submit to serialize multiple data array
    document.getElementById('diamond-upload-form').onsubmit = function(e) {
        if (uploadMode === 'multiple') {
            saveFormValuesIntoState(activeMultipleIndex);
            
            // Validate all items contain stock number
            let isValid = true;
            multipleDiamonds.forEach((item, index) => {
                if (!item.stock_no || !item.stock_no.trim()) {
                    isValid = false;
                    showToast(`Item ${index + 1} is missing a Stock Number. Please verify.`, 'error');
                    switchActiveItem(index);
                }
            });

            if (!isValid) {
                return false; // Prevent submit
            }

            // Write items data array into hidden text input as JSON
            document.getElementById('diamonds-json-input').value = JSON.stringify(multipleDiamonds);
        }
    };

    function showMultipleFilenames(fileInput, labelId) {
        const label = document.getElementById(labelId);
        if (fileInput.files.length > 0) {
            label.textContent = `Selected: ${fileInput.files.length} file(s)`;
        } else {
            label.textContent = '';
        }
    }
</script>
@endsection