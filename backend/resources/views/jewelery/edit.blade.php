@extends('layouts.app')

@section('styles')
<style>
    .uploader-panel {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        max-width: 800px;
        margin: 20px auto;
    }

    .uploader-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 24px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

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

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        color: var(--text-color);
        background-color: #ffffff;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(16, 139, 182, 0.1);
    }

    .form-row-multi {
        display: flex;
        gap: 12px;
    }

    .form-row-multi .form-group {
        flex: 1;
    }

    .checkbox-row {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
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

    .file-input-wrapper {
        border: 2px dashed var(--border-color);
        border-radius: 8px;
        padding: 24px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background-color: #fcfdfe;
        margin-top: 8px;
    }

    .file-input-wrapper:hover {
        border-color: var(--primary-color);
        background-color: var(--primary-light);
    }
</style>
@endsection

@section('content')
<div class="uploader-panel">
    <div class="uploader-title">
        <i class="fa-solid fa-pen-to-square" style="color: var(--primary-color);"></i>
        Edit Jewelry Item: {{ $jewelery->sku }}
    </div>

    <form action="{{ route('jewelery.update', $jewelery->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-panel-grid">
            <!-- Box 1: Product Description -->
            <div class="form-section-card">
                <div class="form-section-card-title">Product Description</div>
                <div class="form-group">
                    <label for="name">Item Title</label>
                    <input type="text" name="name" value="{{ old('name', $jewelery->name) }}" placeholder="Enter title" class="form-input" required>
                </div>
                <div class="form-row-multi">
                    <div class="form-group" style="flex: 1.5;">
                        <label for="sku">Stock #</label>
                        <input type="text" name="sku" value="{{ old('sku', $jewelery->sku) }}" placeholder="Enter stock number" class="form-input" required>
                    </div>
                    <div class="form-group checkbox-row" style="padding-top: 25px; flex: 1;">
                        <label class="filter-checkbox-label">
                            <input type="checkbox" name="is_available" value="1" {{ old('is_available', $jewelery->is_available) ? 'checked' : '' }}> Available
                        </label>
                        <label class="filter-checkbox-label">
                            <input type="checkbox" name="is_available_for_memo" value="1" {{ old('is_available_for_memo', $jewelery->is_available_for_memo) ? 'checked' : '' }}> Memo Available
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" placeholder="Enter description" class="form-input" style="height: 100px;">{{ old('description', $jewelery->description) }}</textarea>
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
                                <option value="{{ $opt->name }}" {{ old('type', $jewelery->type) === $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="type_style">Type Style</label>
                        <select name="type_style" class="form-input">
                            <option value="">Select Style</option>
                            @foreach(['Solitaire', 'Halo', 'Vintage', 'Classic', 'Modern'] as $style)
                                <option value="{{ $style }}" {{ old('type_style', $jewelery->type_style) === $style ? 'selected' : '' }}>{{ $style }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row-multi">
                    <div class="form-group">
                        <label for="category">Jewelry Category</label>
                        <select name="category" class="form-input">
                            <option value="">Select Category</option>
                            @foreach(['Fine Jewelry', 'Fashion Jewelry', 'Bridal', 'Custom'] as $cat)
                                <option value="{{ $cat }}" {{ old('category', $jewelery->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="condition">Condition</label>
                        <select name="condition" class="form-input">
                            <option value="">Select Condition</option>
                            @foreach(['New', 'Pre-owned', 'Refurbished'] as $cond)
                                <option value="{{ $cond }}" {{ old('condition', $jewelery->condition) === $cond ? 'selected' : '' }}>{{ $cond }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <select name="brand" class="form-input">
                            <option value="">Select Brand</option>
                            <option value="OM Gems" {{ old('brand', $jewelery->brand) === 'OM Gems' ? 'selected' : '' }}>OM Gems</option>
                        </select>
                    </div>
                </div>
                <div class="form-row-multi">
                    <div class="form-group">
                        <label for="quality">Jewelry Quality</label>
                        <select name="quality" class="form-input">
                            <option value="">Select Quality</option>
                            @foreach(['Excellent', 'Very Good', 'Good'] as $qual)
                                <option value="{{ $qual }}" {{ old('quality', $jewelery->quality) === $qual ? 'selected' : '' }}>{{ $qual }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="designer">Designer Maker</label>
                        <input type="text" name="designer" value="{{ old('designer', $jewelery->designer) }}" placeholder="Enter designer name" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label for="keywords">Keyword Description</label>
                    <input type="text" name="keywords" value="{{ old('keywords', $jewelery->keywords) }}" placeholder="Enter keywords (comma separated)" class="form-input">
                </div>
            </div>

            <!-- Box 3: Price & Location -->
            <div class="form-section-card">
                <div class="form-section-card-title">Price & Location</div>
                <div class="form-row-multi">
                    <div class="form-group">
                        <label for="location">Shipping From</label>
                        <select name="location" class="form-input">
                            <option value="">Select location</option>
                            <option value="London" {{ old('location', $jewelery->location) === 'London' ? 'selected' : '' }}>London, United Kingdom</option>
                            <option value="Surat" {{ old('location', $jewelery->location) === 'Surat' ? 'selected' : '' }}>Surat, India</option>
                            <option value="New York" {{ old('location', $jewelery->location) === 'New York' ? 'selected' : '' }}>New York, USA</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="price">Price/Piece</label>
                        <input type="number" step="0.01" name="price" value="{{ old('price', $jewelery->price) }}" placeholder="$ Enter price" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="currency">Currency</label>
                        <input type="text" name="currency" value="{{ old('currency', $jewelery->currency ?? 'USD') }}" class="form-input">
                    </div>
                </div>
                <div class="form-row-multi">
                    <div class="form-group">
                        <label for="terms">Terms</label>
                        <select name="terms" class="form-input">
                            <option value="">Select terms</option>
                            @foreach(['Cash', 'Net 30', 'COD'] as $term)
                                <option value="{{ $term }}" {{ old('terms', $jewelery->terms) === $term ? 'selected' : '' }}>{{ $term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="msrp">MSRP</label>
                        <input type="number" step="0.01" name="msrp" value="{{ old('msrp', $jewelery->msrp) }}" placeholder="$ Enter MSRP" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="delivery_time">Delivery Time (Days)</label>
                        <input type="number" name="delivery_time" value="{{ old('delivery_time', $jewelery->delivery_time) }}" placeholder="Enter days" class="form-input">
                    </div>
                </div>
                <div class="form-row-multi">
                    <div class="form-group">
                        <label for="in_stock">Item In Stock</label>
                        <input type="number" name="in_stock" value="{{ old('in_stock', $jewelery->in_stock) }}" placeholder="Enter stock count" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="min_order">Minimum Order</label>
                        <input type="number" name="min_order" value="{{ old('min_order', $jewelery->min_order) }}" placeholder="Enter min count" class="form-input">
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
                                <option value="{{ $opt->name }}" {{ old('metal_type', $jewelery->metal_type) === $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="metal_karat">Metal Karat</label>
                        <select name="metal_karat" class="form-input">
                            <option value="">Select karat</option>
                            @foreach($categories['metal_karat'] ?? [] as $opt)
                                <option value="{{ $opt->name }}" {{ old('metal_karat', $jewelery->metal_karat) === $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="total_weight">Jewelry Total Weight (gr)</label>
                        <input type="number" step="0.01" name="total_weight" value="{{ old('total_weight', $jewelery->total_weight) }}" placeholder="Enter grams" class="form-input">
                    </div>
                </div>
                <div class="form-row-multi">
                    <div class="form-group">
                        <label for="gemstone_type">Gemstone Type</label>
                        <select name="gemstone_type" class="form-input">
                            <option value="">Select gem</option>
                            @foreach($categories['gemstone_type'] ?? [] as $opt)
                                <option value="{{ $opt->name }}" {{ old('gemstone_type', $jewelery->gemstone_type) === $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gemstone_shape">Shape</label>
                        <select name="gemstone_shape" class="form-input">
                            <option value="">Select shape</option>
                            @foreach($categories['shape'] ?? [] as $opt)
                                <option value="{{ $opt->name }}" {{ old('gemstone_shape', $jewelery->gemstone_shape) === $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="carat_weight">Carat Weight</label>
                        <input type="number" step="0.001" name="carat_weight" value="{{ old('carat_weight', $jewelery->carat_weight) }}" placeholder="Enter carats" class="form-input">
                    </div>
                </div>
                <div class="form-row-multi">
                    <div class="form-group">
                        <label for="lab">Lab</label>
                        <select name="lab" class="form-input">
                            <option value="">Select lab</option>
                            @foreach($categories['lab'] ?? [] as $opt)
                                <option value="{{ $opt->name }}" {{ old('lab', $jewelery->lab) === $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label for="lab_no">Lab#</label>
                        <input type="text" name="lab_no" value="{{ old('lab_no', $jewelery->lab_no) }}" placeholder="Enter lab#" class="form-input">
                    </div>
                </div>
                <div class="form-row-multi">
                    <div class="form-group">
                        <label for="treatment">Treatment</label>
                        <select name="treatment" class="form-input">
                            <option value="">Select treatment</option>
                            @foreach($categories['treatment'] ?? [] as $opt)
                                <option value="{{ $opt->name }}" {{ old('treatment', $jewelery->treatment) === $opt->name ? 'selected' : '' }}>{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group radio-group-row" style="padding-top: 25px;">
                        <label class="radio-option">
                            <input type="radio" name="treatment_yes_no" value="0" {{ !old('treatment_yes_no', $jewelery->treatment_yes_no) ? 'checked' : '' }}> No
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="treatment_yes_no" value="1" {{ old('treatment_yes_no', $jewelery->treatment_yes_no) ? 'checked' : '' }}> Yes
                        </label>
                    </div>
                </div>
                <div class="form-row-multi">
                    <div class="form-group">
                        <label for="stone_count"># Stone</label>
                        <input type="number" name="stone_count" value="{{ old('stone_count', $jewelery->stone_count) }}" placeholder="Enter count" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="lot_no">OM Lot#</label>
                        <input type="text" name="lot_no" value="{{ old('lot_no', $jewelery->lot_no) }}" placeholder="Enter lot#" class="form-input">
                    </div>
                </div>
            </div>

            <!-- Box 5: Suppliers Comment -->
            <div class="form-section-card">
                <div class="form-section-card-title">Suppliers Comment</div>
                <div class="form-group">
                    <label for="supplier_comment">Comment</label>
                    <textarea name="supplier_comment" placeholder="Enter suppliers comment" class="form-input" style="height: 80px;">{{ old('supplier_comment', $jewelery->supplier_comment) }}</textarea>
                </div>
            </div>

            <!-- Box 6: Measurements & Visibility -->
            <div class="form-section-card" style="justify-content: space-between;">
                <div>
                    <div class="form-section-card-title">Measurements</div>
                    <div class="form-row-multi">
                        <div class="form-group">
                            <label for="size">Size</label>
                            <input type="text" name="size" value="{{ old('size', $jewelery->size) }}" placeholder="Enter size" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="ring_size">Ring Size</label>
                            <input type="text" name="ring_size" value="{{ old('ring_size', $jewelery->ring_size) }}" placeholder="Enter ring size" class="form-input">
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <div class="form-section-card-title">Visibility</div>
                    <div class="checkbox-row">
                        <label class="filter-checkbox-label">
                            <input type="checkbox" name="is_unpublished" value="1" {{ old('is_unpublished', $jewelery->is_unpublished) ? 'checked' : '' }}> Unpublished
                        </label>
                        <label class="filter-checkbox-label">
                            <input type="checkbox" name="is_shareable" value="1" {{ old('is_shareable', $jewelery->is_shareable) ? 'checked' : '' }}> Shareable
                        </label>
                        <label class="filter-checkbox-label">
                            <input type="checkbox" name="is_own_stock" value="1" {{ old('is_own_stock', $jewelery->is_own_stock) ? 'checked' : '' }}> Own stock for instant inventory
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Images Section -->
        <div class="form-section-card" style="max-width: 500px; margin: 0 auto 24px auto;">
            <div class="form-section-card-title">Product Images</div>
            
            @if($jewelery->images && count($jewelery->images) > 0)
                <div class="existing-media-gallery" style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; padding: 10px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; justify-content: center;">
                    @foreach($jewelery->images as $img)
                        <div class="media-item" style="position: relative; width: 80px; text-align: center;">
                            <img src="{{ (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) ? $img : asset('storage/' . $img) }}" style="width: 70px; height: 70px; object-fit: contain; border-radius: 6px; border: 1px solid var(--border-color);">
                            <label style="display: block; font-size: 11px; margin-top: 4px; color: var(--error-color); cursor: pointer; font-weight: 700;">
                                <input type="checkbox" name="remove_images[]" value="{{ $img }}"> Remove
                            </label>
                        </div>
                    @endforeach
                </div>
            @elseif(!empty($jewelery->image_url))
                <div style="text-align: center; margin-bottom: 12px;">
                    <p style="font-size: 11px; font-weight: 600; color: var(--text-muted); margin-bottom: 4px;">Legacy Image Preview:</p>
                    <img src="{{ str_starts_with($jewelery->image_url, 'http') ? $jewelery->image_url : asset($jewelery->image_url) }}" style="max-height: 100px; object-fit: contain; border-radius: 8px; border: 1px solid var(--border-color); padding: 4px; background: #fff;">
                </div>
            @endif

            <div class="form-group">
                <label for="images_input">Upload Additional Images</label>
                <div class="file-input-wrapper" onclick="document.getElementById('images_input').click()">
                    <i class="fa-solid fa-images" style="font-size: 24px; color: var(--primary-color); margin-bottom: 8px;"></i>
                    <h5 style="font-size: 13px; font-weight: 700; color: var(--text-color); margin-bottom: 2px;" id="images-label-title">Choose Images</h5>
                    <p style="font-size: 11px; color: var(--text-muted);" id="images-label-name">Supports JPEG, PNG, JPG, WEBP (Max 10MB per image)</p>
                    <input type="file" id="images_input" name="images[]" multiple accept="image/*" style="display: none;" onchange="handleMultipleImagesChange(this)">
                </div>
            </div>

            <div style="text-align: center; margin: 10px 0;">
                <span style="font-size: 11px; font-weight: 700; color: var(--text-muted);">or replace legacy single image:</span>
            </div>

            <div class="form-group">
                <div class="file-input-wrapper" onclick="triggerImageFileSelect()" style="padding: 16px;">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size: 20px; color: var(--primary-color); margin-bottom: 6px;"></i>
                    <h5 style="font-size: 12px; font-weight: 700; color: var(--text-color); margin-bottom: 2px;" id="file-label-title">Choose Single File</h5>
                    <p style="font-size: 10px; color: var(--text-muted);" id="file-label-name">Supports JPEG, PNG, JPG, WEBP (Max 5MB)</p>
                    <input type="file" id="image_file" name="image_file" accept="image/*" style="display: none;" onchange="handleImageFileChange(this)">
                </div>
            </div>
        </div>

        <!-- Product Videos Section -->
        <div class="form-section-card" style="max-width: 500px; margin: 0 auto 24px auto;">
            <div class="form-section-card-title">Product Videos</div>

            @if($jewelery->videos && count($jewelery->videos) > 0)
                <div class="existing-media-gallery" style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; padding: 10px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; justify-content: center;">
                    @foreach($jewelery->videos as $vid)
                        <div class="media-item" style="position: relative; width: 120px; text-align: center;">
                            <video src="{{ (str_starts_with($vid, 'http://') || str_starts_with($vid, 'https://')) ? $vid : asset('storage/' . $vid) }}" controls style="width: 110px; height: 70px; object-fit: contain; border-radius: 6px; border: 1px solid var(--border-color);"></video>
                            <label style="display: block; font-size: 11px; margin-top: 4px; color: var(--error-color); cursor: pointer; font-weight: 700;">
                                <input type="checkbox" name="remove_videos[]" value="{{ $vid }}"> Remove
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="form-group">
                <label for="videos_input">Upload Additional Videos</label>
                <div class="file-input-wrapper" onclick="document.getElementById('videos_input').click()">
                    <i class="fa-solid fa-video" style="font-size: 24px; color: var(--primary-color); margin-bottom: 8px;"></i>
                    <h5 style="font-size: 13px; font-weight: 700; color: var(--text-color); margin-bottom: 2px;" id="videos-label-title">Choose Videos</h5>
                    <p style="font-size: 11px; color: var(--text-muted);" id="videos-label-name">Supports MP4, MOV, AVI (Max 50MB per video)</p>
                    <input type="file" id="videos_input" name="videos[]" multiple accept="video/*" style="display: none;" onchange="handleMultipleVideosChange(this)">
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: center; gap: 16px; border-top: 1px solid var(--border-color); padding-top: 24px;">
            <a href="{{ route('jewelery.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Jewelry</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function triggerImageFileSelect() {
        document.getElementById('image_file').click();
    }

    function handleImageFileChange(input) {
        const title = document.getElementById('file-label-title');
        const name = document.getElementById('file-label-name');

        if (input.files.length > 0) {
            title.textContent = "Selected Image File";
            name.textContent = input.files[0].name;
            name.style.color = "var(--primary-color)";
            name.style.fontWeight = "bold";
        } else {
            title.textContent = "Choose New File";
            name.textContent = "Supports JPEG, PNG, JPG, WEBP (Max 5MB)";
            name.style.color = "";
            name.style.fontWeight = "";
        }
    }

    function handleMultipleImagesChange(input) {
        const title = document.getElementById('images-label-title');
        const name = document.getElementById('images-label-name');

        if (input.files.length > 0) {
            title.textContent = "Selected Images";
            name.textContent = `Selected: ${input.files.length} file(s)`;
            name.style.color = "var(--primary-color)";
            name.style.fontWeight = "bold";
        } else {
            title.textContent = "Choose Images";
            name.textContent = "Supports JPEG, PNG, JPG, WEBP (Max 10MB per image)";
            name.style.color = "";
            name.style.fontWeight = "";
        }
    }

    function handleMultipleVideosChange(input) {
        const title = document.getElementById('videos-label-title');
        const name = document.getElementById('videos-label-name');

        if (input.files.length > 0) {
            title.textContent = "Selected Videos";
            name.textContent = `Selected: ${input.files.length} file(s)`;
            name.style.color = "var(--primary-color)";
            name.style.fontWeight = "bold";
        } else {
            title.textContent = "Choose Videos";
            name.textContent = "Supports MP4, MOV, AVI (Max 50MB per video)";
            name.style.color = "";
            name.style.fontWeight = "";
        }
    }
</script>
@endsection
