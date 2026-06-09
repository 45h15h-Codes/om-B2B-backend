@extends('layouts.app')

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
        display: {{ $diamond->fancy_color_enabled ? 'block' : 'none' }};
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px dashed var(--border-color);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
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


@section('content')
<div style="margin-bottom: 24px;">
    <a href="{{ route('diamonds.index') }}" class="btn btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;">
        <i class="fa-solid fa-arrow-left"></i> Back to List
    </a>
</div>

<div class="wizard-container">
    <!-- Wizard Tabs -->
    <div class="wizard-tabs">
        <div class="wizard-tab active">Edit Diamond Specs</div>
    </div>

    <!-- Wizard Steps Checklist -->
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

    <!-- EDIT FORM -->
    <form action="{{ route('diamonds.update', $diamond->id) }}" method="POST" enctype="multipart/form-data" id="diamond-upload-form">
        @csrf
        @method('PUT')

        <!-- STEP 1: GENERAL INFORMATION -->
        <div class="form-step-panel active" id="step-panel-1">
            <div class="panel-subtitle">Edit diamond specs</div>
            
            <div class="cards-grid">
                <!-- Stock Group -->
                <div class="card-group">
                    <div class="card-group-title">Stock#</div>
                    <div class="form-group">
                        <label for="stock_no">Stock Number</label>
                        <input type="text" id="stock_no" name="stock_no" value="{{ $diamond->stock_no }}" placeholder="Enter stock number" required>
                    </div>
                </div>

                <!-- Price Group -->
                <div class="card-group">
                    <div class="card-group-title">Price</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="asking_price">Asking Price</label>
                            <input type="number" step="0.01" id="asking_price" name="asking_price" value="{{ $diamond->asking_price }}" placeholder="Enter asking price">
                            <div class="unit-selector">
                                <label class="unit-option">
                                    <input type="radio" name="asking_price_unit" value="CT" {{ $diamond->asking_price_unit == 'CT' ? 'checked' : '' }}> CT
                                </label>
                                <label class="unit-option">
                                    <input type="radio" name="asking_price_unit" value="OM %" {{ $diamond->asking_price_unit == 'OM %' ? 'checked' : '' }}> OM %
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="cash_price">Cash Price</label>
                            <input type="number" step="0.01" id="cash_price" name="cash_price" value="{{ $diamond->cash_price }}" placeholder="Enter cash price">
                            <div class="unit-selector">
                                <label class="unit-option">
                                    <input type="radio" name="cash_price_unit" value="CT" {{ $diamond->cash_price_unit == 'CT' ? 'checked' : '' }}> CT
                                </label>
                                <label class="unit-option">
                                    <input type="radio" name="cash_price_unit" value="OM %" {{ $diamond->cash_price_unit == 'OM %' ? 'checked' : '' }}> OM %
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
                        <select id="availability" name="availability">
                            <option value="">Select availability</option>
                            <option value="Available" {{ $diamond->availability == 'Available' ? 'selected' : '' }}>Available</option>
                            <option value="Memo" {{ $diamond->availability == 'Memo' ? 'selected' : '' }}>Memo</option>
                            <option value="On Hold" {{ $diamond->availability == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                        </select>
                    </div>
                </div>

                <!-- Location Group -->
                <div class="card-group">
                    <div class="card-group-title">Location</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="country">Country</label>
                            <select id="country" name="country">
                                <option value="">Select country</option>
                                <option value="India" {{ $diamond->country == 'India' ? 'selected' : '' }}>India</option>
                                <option value="USA" {{ $diamond->country == 'USA' ? 'selected' : '' }}>USA</option>
                                <option value="Belgium" {{ $diamond->country == 'Belgium' ? 'selected' : '' }}>Belgium</option>
                                <option value="Israel" {{ $diamond->country == 'Israel' ? 'selected' : '' }}>Israel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <select id="state" name="state">
                                <option value="">Select state</option>
                                <option value="Gujarat" {{ $diamond->state == 'Gujarat' ? 'selected' : '' }}>Gujarat</option>
                                <option value="Maharashtra" {{ $diamond->state == 'Maharashtra' ? 'selected' : '' }}>Maharashtra</option>
                                <option value="New York" {{ $diamond->state == 'New York' ? 'selected' : '' }}>New York</option>
                                <option value="Antwerp" {{ $diamond->state == 'Antwerp' ? 'selected' : '' }}>Antwerp</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="{{ $diamond->city }}" placeholder="Enter city">
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
                            <select id="shape" name="shape" onchange="toggleShapeType(this)">
                                <option value="">Select shape</option>
                                @foreach($categories['shape'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" data-group="{{ $opt->group ?? 'basic' }}" {{ $diamond->shape == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="advance-shape-section" style="display: none;">
                            <label for="advance_shape_detail">Advance Shape Detail</label>
                            <select id="advance_shape_detail" name="advance_shape_detail">
                                <option value="">Select detail</option>
                                @foreach($categories['advance_shape_detail'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->advance_shape_detail == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="size">Size (Carat)</label>
                            <input type="number" step="0.001" id="size" name="size" value="{{ $diamond->size }}" placeholder="Enter size">
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
                            <select id="color" name="color">
                                <option value="">Select color</option>
                                @foreach($categories['color'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" data-group="{{ $opt->group ?? 'white' }}" {{ $diamond->color == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="clarity">Clarity</label>
                            <select id="clarity" name="clarity">
                                <option value="">Select clarity</option>
                                @foreach($categories['clarity'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->clarity == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
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
                            <select id="cut" name="cut">
                                <option value="">Select cut</option>
                                @foreach($categories['cut'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->cut == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="polish">Polish</label>
                            <select id="polish" name="polish">
                                <option value="">Select polish</option>
                                @foreach($categories['polish'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->polish == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="symmetry">Symmetry</label>
                            <select id="symmetry" name="symmetry">
                                <option value="">Select symmetry</option>
                                @foreach($categories['symmetry'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->symmetry == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fluorescence_intensity">Fluorescence Intensity</label>
                            <select id="fluorescence_intensity" name="fluorescence_intensity">
                                <option value="">Select intensity</option>
                                @foreach($categories['fluorescence_intensity'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->fluorescence_intensity == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fluorescence_color">Fluorescence Color</label>
                            <select id="fluorescence_color" name="fluorescence_color">
                                <option value="">Select color</option>
                                @foreach($categories['fluorescence_color'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->fluorescence_color == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
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
                            <input type="number" step="0.01" id="length" name="length" value="{{ $diamond->length }}" placeholder="Length">
                        </div>
                        <div class="form-group">
                            <label for="width">Width</label>
                            <input type="number" step="0.01" id="width" name="width" value="{{ $diamond->width }}" placeholder="Width">
                        </div>
                        <div class="form-group">
                            <label for="depth">Depth</label>
                            <input type="number" step="0.01" id="depth" name="depth" value="{{ $diamond->depth }}" placeholder="Depth">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="depth_percent">Depth %</label>
                            <input type="number" step="0.1" id="depth_percent" name="depth_percent" value="{{ $diamond->depth_percent }}" placeholder="Depth %">
                        </div>
                        <div class="form-group">
                            <label for="table_percent">Table %</label>
                            <input type="number" step="0.1" id="table_percent" name="table_percent" value="{{ $diamond->table_percent }}" placeholder="Table %">
                        </div>
                    </div>
                </div>

                <!-- Group 4: Crown & Pavilion -->
                <div class="card-group">
                    <div class="card-group-title">Crown & Pavilion</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="crown_angle">Crown Angle</label>
                            <input type="number" step="0.1" id="crown_angle" name="crown_angle" value="{{ $diamond->crown_angle }}" placeholder="Angle">
                        </div>
                        <div class="form-group">
                            <label for="crown_height">Crown Height</label>
                            <input type="number" step="0.1" id="crown_height" name="crown_height" value="{{ $diamond->crown_height }}" placeholder="Height">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pavilion_angle">Pavilion Angle</label>
                            <input type="number" step="0.1" id="pavilion_angle" name="pavilion_angle" value="{{ $diamond->pavilion_angle }}" placeholder="Angle">
                        </div>
                        <div class="form-group">
                            <label for="pavilion_depth">Pavilion Depth</label>
                            <input type="number" step="0.1" id="pavilion_depth" name="pavilion_depth" value="{{ $diamond->pavilion_depth }}" placeholder="Depth">
                        </div>
                    </div>
                </div>

                <!-- Group 5: Girdle & Culet -->
                <div class="card-group">
                    <div class="card-group-title">Girdle & Culet</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="girdle_condition">Girdle Condition</label>
                            <select id="girdle_condition" name="girdle_condition">
                                <option value="">Select Condition</option>
                                @foreach($categories['girdle_condition'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->girdle_condition == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="girdle_min">Min Girdle</label>
                            <select id="girdle_min" name="girdle_min">
                                <option value="">Min</option>
                                <option value="Thin" {{ $diamond->girdle_min == 'Thin' ? 'selected' : '' }}>Thin</option>
                                <option value="Medium" {{ $diamond->girdle_min == 'Medium' ? 'selected' : '' }}>Medium</option>
                                <option value="Slightly Thick" {{ $diamond->girdle_min == 'Slightly Thick' ? 'selected' : '' }}>Slightly Thick</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="girdle_max">Max Girdle</label>
                            <select id="girdle_max" name="girdle_max">
                                <option value="">Max</option>
                                <option value="Medium" {{ $diamond->girdle_max == 'Medium' ? 'selected' : '' }}>Medium</option>
                                <option value="Slightly Thick" {{ $diamond->girdle_max == 'Slightly Thick' ? 'selected' : '' }}>Slightly Thick</option>
                                <option value="Thick" {{ $diamond->girdle_max == 'Thick' ? 'selected' : '' }}>Thick</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="girdle_percent">Girdle %</label>
                            <input type="number" step="0.1" id="girdle_percent" name="girdle_percent" value="{{ $diamond->girdle_percent }}" placeholder="Girdle %">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="culet_condition">Culet Condition</label>
                            <select id="culet_condition" name="culet_condition">
                                <option value="">Condition</option>
                                @foreach($categories['culet_condition'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->culet_condition == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="culet_size">Culet Size</label>
                            <select id="culet_size" name="culet_size">
                                <option value="">Size</option>
                                @foreach($categories['culet_size'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->culet_size == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
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
                            <select id="lab" name="lab">
                                <option value="">Select Lab</option>
                                @foreach($categories['lab'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->lab == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="report_no">Report Number</label>
                            <input type="text" id="report_no" name="report_no" value="{{ $diamond->report_no }}" placeholder="Enter report number">
                        </div>
                        <div class="form-group checkbox-group" style="padding-top: 25px;">
                            <label for="show_on_OM">Show on OM Gems</label>
                            <label class="switch">
                                <input type="checkbox" id="show_on_OM" name="show_on_OM" {{ $diamond->show_on_OM ? 'checked' : '' }} value="1">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="report_date">Report Date</label>
                            <input type="date" id="report_date" name="report_date" value="{{ $diamond->report_date }}">
                        </div>
                        <div class="form-group">
                            <label for="lab_location">Lab Location</label>
                            <input type="text" id="lab_location" name="lab_location" value="{{ $diamond->lab_location }}" placeholder="Lab location">
                        </div>
                    </div>
                </div>

                <!-- Group 7: Comments & Fancy Color Toggle -->
                <div class="card-group">
                    <div class="card-group-title">Additional Info & Fancy Colors</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="treatment">Treatment</label>
                            <select id="treatment" name="treatment">
                                <option value="">Select Treatment</option>
                                @foreach($categories['treatment'] ?? [] as $opt)
                                    <option value="{{ $opt->name }}" {{ $diamond->treatment == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="laser_inscription">Laser Inscription</label>
                            <input type="text" id="laser_inscription" name="laser_inscription" value="{{ $diamond->laser_inscription }}" placeholder="Laser inscription">
                        </div>
                        <div class="form-group">
                            <label for="star_length">Star Length</label>
                            <input type="number" step="0.1" id="star_length" name="star_length" value="{{ $diamond->star_length }}" placeholder="Star length">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="report_comment">Report Comment</label>
                        <input type="text" id="report_comment" name="report_comment" value="{{ $diamond->report_comment }}" placeholder="Report Comment">
                    </div>
                    
                    <div class="form-row" style="margin-top: 12px;">
                        <div class="form-group">
                            <label for="key_to_symbols">Key To Symbols</label>
                            <select id="key_to_symbols" name="key_to_symbols">
                                <option value="">Select symbols</option>
                                <option value="Feather" {{ $diamond->key_to_symbols == 'Feather' ? 'selected' : '' }}>Feather</option>
                                <option value="Crystal" {{ $diamond->key_to_symbols == 'Crystal' ? 'selected' : '' }}>Crystal</option>
                                <option value="Needle" {{ $diamond->key_to_symbols == 'Needle' ? 'selected' : '' }}>Needle</option>
                            </select>
                        </div>
                        
                        <div class="form-group checkbox-group" style="padding-top: 25px;">
                            <label for="fancy_color_enabled">Fancy Color</label>
                            <label class="switch">
                                <input type="checkbox" id="fancy_color_enabled" name="fancy_color_enabled" {{ $diamond->fancy_color_enabled ? 'checked' : '' }} onchange="toggleFancyColor(this)">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Fancy Color Fields -->
                    <div class="fancy-color-container" id="fancy-color-section">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fancy_color_intensity">Intensity</label>
                                <select id="fancy_color_intensity" name="fancy_color_intensity">
                                    <option value="">Select intensity</option>
                                    @foreach($categories['fancy_color_intensity'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}" {{ $diamond->fancy_color_intensity == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="fancy_color_overtone">Overtone</label>
                                <select id="fancy_color_overtone" name="fancy_color_overtone">
                                    <option value="">Select overtone</option>
                                    @foreach($categories['fancy_color_overtone'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}" {{ $diamond->fancy_color_overtone == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fancy_color_color1">Color 1</label>
                                <select id="fancy_color_color1" name="fancy_color_color1">
                                    <option value="">Select color 1</option>
                                    @foreach($categories['fancy_color_color'] ?? [] as $opt)
                                        <option value="{{ $opt->name }}" {{ $diamond->fancy_color_color1 == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="fancy_color_color2">Color 2</label>
                                <select id="fancy_color_color2" name="fancy_color_color2">
                                    <option value="">Select color 2</option>
                                    <option value="None">None</option>
                                    @foreach($categories['fancy_color_color'] ?? [] as $opt)
                                        @if(strcasecmp($opt->name, 'None') !== 0)
                                            <option value="{{ $opt->name }}" {{ $diamond->fancy_color_color2 == $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
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
                            <input type="checkbox" id="is_matched_pair" name="is_matched_pair" {{ $diamond->is_matched_pair ? 'checked' : '' }} onchange="togglePairFields(this)">
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <div id="pair-fields" style="display: {{ $diamond->is_matched_pair ? 'block' : 'none' }}; margin-top: 15px;">
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label for="matched_pair_stock_no">Stock Number</label>
                            <input type="text" id="matched_pair_stock_no" name="matched_pair_stock_no" value="{{ $diamond->matched_pair_stock_no }}" placeholder="Enter paired stock number">
                        </div>
                        <div class="form-group checkbox-group">
                            <label for="is_pair_separable">Is Pair Separable</label>
                            <label class="switch">
                                <input type="checkbox" id="is_pair_separable" name="is_pair_separable" {{ $diamond->is_pair_separable ? 'checked' : '' }} value="1">
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
                            <span style="font-size: 11px; color: var(--text-muted);">Allow Approved member to download this diamond via OMlink diamond link service or instant inventory</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="allow_download" name="allow_download" {{ $diamond->allow_download ? 'checked' : '' }} value="1">
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
                            <select id="trade_show" name="trade_show">
                                <option value="">Select trade show</option>
                                <option value="JCK Las Vegas" {{ $diamond->trade_show == 'JCK Las Vegas' ? 'selected' : '' }}>JCK Las Vegas</option>
                                <option value="Hong Kong Jewellery Show" {{ $diamond->trade_show == 'Hong Kong Jewellery Show' ? 'selected' : '' }}>Hong Kong Show</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="brand">Brand</label>
                            <select id="brand" name="brand">
                                <option value="">Select brand</option>
                                <option value="OM Signature" {{ $diamond->brand == 'OM Signature' ? 'selected' : '' }}>OM Signature</option>
                                <option value="Hearts & Arrows" {{ $diamond->brand == 'Hearts & Arrows' ? 'selected' : '' }}>Hearts & Arrows</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Comments & Parcel -->
                <div class="card-group">
                    <div class="card-group-title">Supplier & Parcel details</div>
                    
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="supplier_comment">Supplier Comment</label>
                        <input type="text" id="supplier_comment" name="supplier_comment" value="{{ $diamond->supplier_comment }}" placeholder="Supplier comments">
                    </div>

                    <div class="form-group checkbox-group" style="margin-bottom: 12px;">
                        <label for="is_parcel">Parcel</label>
                        <label class="switch">
                            <input type="checkbox" id="is_parcel" name="is_parcel" {{ $diamond->is_parcel ? 'checked' : '' }} onchange="toggleParcelFields(this)">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div id="parcel-fields" style="display: {{ $diamond->is_parcel ? 'block' : 'none' }};">
                        <div class="form-group">
                            <label for="number_of_diamonds">Number of Diamond</label>
                            <input type="number" id="number_of_diamonds" name="number_of_diamonds" value="{{ $diamond->number_of_diamonds }}" placeholder="Number of diamonds in parcel">
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
                        <!-- Custom file Browse -->
                        <div class="upload-box" onclick="document.getElementById('report_file_input').click()">
                            <i class="fa-solid fa-file-pdf upload-icon"></i>
                            <span class="upload-btn-label">Browse</span>
                            <span id="report_filename_lbl" style="font-size: 12px; color: var(--success-color); font-weight: 500;">
                                @if($diamond->report_file) Selected: {{ str_contains($diamond->report_file, 'cloudinary') ? 'Cloudinary PDF' : basename($diamond->report_file) }} @endif
                            </span>
                            <input type="file" id="report_file_input" name="report_file" style="display: none;" onchange="showFilename(this, 'report_filename_lbl')">
                        </div>
                        
                        <span class="or-label" style="align-self: center;">or</span>
                        
                        <!-- Link upload -->
                        <div class="upload-link-input">
                            <label for="report_link">Upload Link</label>
                            <input type="text" id="report_link" name="report_link" value="{{ $diamond->report_link }}" placeholder="Enter URL/Link to report file">
                        </div>
                    </div>
                </div>

                <!-- Diamond Image file upload -->
                <div class="card-group">
                    <div class="card-group-title">Diamond Image</div>
                    
                    <div class="upload-zone-wrapper">
                        <!-- Custom file Browse -->
                        <div class="upload-box" onclick="document.getElementById('diamond_image_input').click()">
                            <i class="fa-solid fa-image upload-icon"></i>
                            <span class="upload-btn-label">Browse</span>
                            <span id="image_filename_lbl" style="font-size: 12px; color: var(--success-color); font-weight: 500;">
                                @if($diamond->diamond_image) Selected: {{ str_contains($diamond->diamond_image, 'cloudinary') ? 'Cloudinary Image' : basename($diamond->diamond_image) }} @endif
                            </span>
                            <input type="file" id="diamond_image_input" name="diamond_image" style="display: none;" onchange="showFilename(this, 'image_filename_lbl')">
                        </div>
                        
                        <span class="or-label" style="align-self: center;">or</span>
                        
                        <!-- Link upload -->
                        <div class="upload-link-input">
                            <label for="diamond_image_link">Upload Link</label>
                            <input type="text" id="diamond_image_link" name="diamond_image_link" value="{{ $diamond->diamond_image_link }}" placeholder="Enter URL/Link to diamond image">
                        </div>
                    </div>
                </div>

                <!-- Sarine Loupe -->
                <div class="card-group">
                    <div class="card-group-title">Sarine Loupe</div>
                    <div class="form-group">
                        <label for="sarine_loupe">Sarine Loupe Link</label>
                        <input type="text" id="sarine_loupe" name="sarine_loupe" value="{{ $diamond->sarine_loupe }}" placeholder="Enter sarine loupe path or link">
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
                        <textarea id="additional_comments" name="additional_comments" rows="4" placeholder="Enter any extra comments for public display...">{{ $diamond->additional_comments }}</textarea>
                    </div>
                </div>

                <div class="card-group">
                    <div class="card-group-title">Internal Details</div>
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="internal_notes">Internal Admin Notes</label>
                        <textarea id="internal_notes" name="internal_notes" rows="4" placeholder="Only visible to admins...">{{ $diamond->internal_notes }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="appraisal_value">Appraisal Value ($)</label>
                        <input type="number" step="0.01" id="appraisal_value" name="appraisal_value" value="{{ $diamond->appraisal_value }}" placeholder="Estimated value">
                    </div>
                </div>
            </div>
        </div>

        <!-- Wizard Navigation Actions -->
        <div class="wizard-actions">
            <button type="button" class="btn btn-secondary" id="prev-btn" onclick="navigateStep(-1)" disabled>Back</button>
            <button type="button" class="btn btn-primary" id="next-btn" onclick="navigateStep(1)">Next</button>
            <button type="submit" class="btn btn-primary" id="submit-btn" style="display: none;">Save Changes</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    let currentStep = 1;
    const totalSteps = 5;

    // Navigate back and forth in steps
    function navigateStep(direction) {
        // Validate current step fields before going next
        if (direction === 1 && !validateCurrentStep()) {
            return;
        }

        // Change current step index
        const targetStep = currentStep + direction;
        if (targetStep >= 1 && targetStep <= totalSteps) {
            jumpToStep(targetStep);
        }
    }

    // Direct click jump to step
    function jumpToStep(stepNumber) {
        // If jumping forward, require validation of intermediary steps
        if (stepNumber > currentStep) {
            for (let s = currentStep; s < stepNumber; s++) {
                currentStep = s;
                if (!validateCurrentStep()) {
                    highlightStepBadgeError(s);
                    return; // Stop jumping
                }
                markStepCompleted(s);
            }
        }

        // Deactivate previous step panel
        document.getElementById(`step-panel-${currentStep}`).classList.remove('active');
        document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.remove('active');

        // Activate new step panel
        currentStep = stepNumber;
        document.getElementById(`step-panel-${currentStep}`).classList.add('active');
        document.querySelector(`.step-item[data-step="${currentStep}"]`).classList.add('active');

        // Adjust navigation buttons display
        document.getElementById('prev-btn').disabled = (currentStep === 1);
        
        if (currentStep === totalSteps) {
            document.getElementById('next-btn').style.display = 'none';
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

        if (!isValid) {
            showToast('Please fill out all required fields before proceeding.', 'error');
        }

        return isValid;
    }

    let cachedShapes = [];
    let cachedColors = [];

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

        // Determine saved shape group
        let savedShapeGroup = 'basic';
        if (shapeSelect && shapeSelect.selectedIndex >= 0) {
            const activeShapeOption = shapeSelect.options[shapeSelect.selectedIndex];
            if (activeShapeOption) {
                savedShapeGroup = activeShapeOption.getAttribute('data-group') || 'basic';
            }
        }
        
        // Determine saved color group
        let savedColorGroup = 'white';
        const fancyCheckbox = document.getElementById('fancy_color_enabled');
        if (fancyCheckbox && fancyCheckbox.checked) {
            savedColorGroup = 'fancy';
        } else if (colorSelect && colorSelect.selectedIndex >= 0) {
            const activeColorOption = colorSelect.options[colorSelect.selectedIndex];
            if (activeColorOption) {
                savedColorGroup = activeColorOption.getAttribute('data-group') || 'white';
            }
        }
        
        // Filter options based on saved state
        setShapeTypeTab(savedShapeGroup, false);
        setColorTypeTab(savedColorGroup, false);
        
        // Also toggle the section visibility based on saved state
        if (savedShapeGroup === 'advance') {
            const section = document.getElementById('advance-shape-section');
            if (section) section.style.display = 'block';
        }
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
                if (opt.value === currentValue || (type === 'fancy' && opt.value === 'Fancy')) {
                    optEl.selected = true;
                }
                colorSelect.appendChild(optEl);
            }
        });
        
        if (type === 'fancy') {
            colorSelect.value = 'Fancy';
        } else if (currentValue === 'Fancy') {
            colorSelect.value = '';
        }
        
        if (typeof syncField === 'function') {
            syncField(colorSelect);
            if (fancyCheckbox) syncField(fancyCheckbox);
        }
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
                }
            }
        }
    }

    // Toggle Fancy Color panel
    function toggleFancyColor(checkbox) {
        const section = document.getElementById('fancy-color-section');
        const isChecked = checkbox.checked;
        if (isChecked) {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }
        setColorTypeTab(isChecked ? 'fancy' : 'white', false);
    }

    // Toggle Pair Stock Fields
    function togglePairFields(checkbox) {
        const fields = document.getElementById('pair-fields');
        if (checkbox.checked) {
            fields.style.display = 'block';
        } else {
            fields.style.display = 'none';
        }
    }

    // Toggle Parcel Fields
    function toggleParcelFields(checkbox) {
        const fields = document.getElementById('parcel-fields');
        if (checkbox.checked) {
            fields.style.display = 'block';
        } else {
            fields.style.display = 'none';
        }
    }

    // Display chosen filename in custom dropzone
    function showFilename(fileInput, labelId) {
        const label = document.getElementById(labelId);
        if (fileInput.files.length > 0) {
            label.textContent = `Selected: ${fileInput.files[0].name}`;
        } else {
            label.textContent = '';
        }
    }
</script>
@endsection
