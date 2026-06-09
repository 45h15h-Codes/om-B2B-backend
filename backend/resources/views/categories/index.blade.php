@extends('layouts.app')

@section('styles')
<style>
    .cat-container {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 30px;
    }

    /* Left categories list */
    .cat-list-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }

    .cat-list-title {
        font-size: 15px;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
    }

    .cat-group-heading {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.5px;
        margin: 20px 0 8px 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .cat-group-heading:first-of-type {
        margin-top: 5px;
    }


    .cat-type-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        border-radius: 8px;
        color: var(--text-color);
        text-decoration: none;
        font-weight: 600;
        font-size: 13.5px;
        transition: all 0.2s ease;
        margin-bottom: 6px;
    }

    .cat-type-item:hover {
        background-color: #f7fafc;
        color: var(--primary-color);
    }

    .cat-type-item.active {
        background-color: var(--primary-light);
        color: var(--primary-color);
    }

    /* Right content card */
    .cat-editor-card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }

    .editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 16px;
        margin-bottom: 24px;
    }

    .editor-title {
        font-size: 18px;
        font-weight: 700;
        color: #2d3748;
    }

    /* Options Table */
    .options-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .options-table th {
        background-color: #f8fafc;
        padding: 12px 16px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border-color);
    }

    .options-table td {
        padding: 14px 16px;
        font-size: 14px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
        font-weight: 500;
    }

    .options-table tr:last-child td {
        border-bottom: none;
    }

    /* Add Option Form */
    .add-form-wrapper {
        background-color: #f8fafc;
        border: 1px dashed var(--border-color);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .add-form-title {
        font-size: 13px;
        font-weight: 700;
        color: #4a5568;
        margin-bottom: 12px;
    }

    .add-form-fields {
        display: flex;
        gap: 12px;
    }

    .add-form-fields input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 14px;
    }

    .add-form-fields input:focus {
        outline: none;
        border-color: var(--primary-color);
    }
</style>
@endsection

@section('content')
<div class="cat-container">
    
    <!-- Left Category types list -->
    <div class="cat-list-card">
        <div class="cat-list-title">Category Dropdowns</div>
        
        @php
            $diamondKeys = [
                'shape', 'color', 'clarity', 'cut', 'polish', 'symmetry', 'lab',
                'fluorescence_intensity', 'fluorescence_color', 'girdle_condition',
                'culet_condition', 'culet_size', 'treatment', 'advance_shape_detail',
                'fancy_color_intensity', 'fancy_color_overtone', 'fancy_color_color'
            ];
            $jewelryKeys = [
                'jewelery_type', 'metal_type', 'metal_karat', 'gemstone_type'
            ];
        @endphp

        <div style="display: flex; flex-direction: column;">
            <div class="cat-group-heading">
                <i class="fa-solid fa-gem"></i> Diamond Dropdowns
            </div>
            @foreach($diamondKeys as $key)
                @if(isset($categoryTypes[$key]))
                    <a href="{{ route('categories.index', ['type' => $key]) }}" class="cat-type-item {{ $activeType === $key ? 'active' : '' }}">
                        <span>{{ $categoryTypes[$key] }}</span>
                        <i class="fa-solid fa-chevron-right" style="font-size: 10px; opacity: 0.7;"></i>
                    </a>
                @endif
            @endforeach

            <div class="cat-group-heading">
                <i class="fa-solid fa-ring"></i> Jewelry Dropdowns
            </div>
            @foreach($jewelryKeys as $key)
                @if(isset($categoryTypes[$key]))
                    <a href="{{ route('categories.index', ['type' => $key]) }}" class="cat-type-item {{ $activeType === $key ? 'active' : '' }}">
                        <span>{{ $categoryTypes[$key] }}</span>
                        <i class="fa-solid fa-chevron-right" style="font-size: 10px; opacity: 0.7;"></i>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Right Category Option Editor -->
    <div class="cat-editor-card">
        <div class="editor-header">
            <div class="editor-title">Manage: {{ $categoryTypes[$activeType] }} Options</div>
            <span style="font-size: 13px; font-weight: 600; color: var(--text-muted);">
                {{ $options->count() }} Option(s)
            </span>
        </div>

        <!-- Add Option form -->
        <div class="add-form-wrapper">
            <div class="add-form-title">Add New Option to {{ $categoryTypes[$activeType] }}</div>
            <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" value="{{ $activeType }}">
                <div class="add-form-fields" style="align-items: center; display: flex; flex-wrap: wrap; gap: 12px;">
                    <input type="text" name="name" placeholder="Enter option name (e.g. Trillion, D, Excellent, etc.)" required style="min-width: 250px;">
                    
                    @if($activeType === 'shape' || $activeType === 'color')
                        <div style="display: flex; align-items: center; gap: 8px; border: 1px solid var(--border-color); background-color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px;">
                            <i class="fa-solid fa-layer-group" style="color: var(--text-muted);"></i>
                            <span style="font-weight: 600; color: var(--text-muted);">Type/Group:</span>
                            <select name="group" style="border: none; outline: none; background: transparent; font-weight: 600; color: var(--text-color); cursor: pointer;">
                                @if($activeType === 'shape')
                                    <option value="basic" selected>Basic</option>
                                    <option value="advance">Advance</option>
                                @else
                                    <option value="white" selected>White</option>
                                    <option value="fancy">Fancy</option>
                                @endif
                            </select>
                        </div>
                    @endif

                    <div style="display: flex; align-items: center; gap: 8px; border: 1px solid var(--border-color); background-color: #fff; padding: 8px 12px; border-radius: 6px; font-size: 13px;">
                        <i class="fa-solid fa-image" style="color: var(--text-muted);"></i>
                        <span style="font-weight: 600; color: var(--text-muted);">Icon / Image (Optional):</span>
                        <input type="file" name="image" accept="image/*" style="border: none; outline: none; padding: 0; max-width: 200px;">
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px;"><i class="fa-solid fa-plus"></i> Add Option</button>
                </div>
            </form>
        </div>

        <!-- Option List Table -->
        @if($options->count() > 0)
            <table class="options-table">
                <thead>
                    <tr>
                        <th style="width: 120px;">Icon / Image</th>
                        <th>Option Value</th>
                        @if($activeType === 'shape' || $activeType === 'color')
                            <th style="width: 160px;">Type/Group</th>
                        @endif
                        <th>Created At</th>
                        <th style="width: 220px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($options as $option)
                        <tr>
                            <td>
                                @if(!empty($option->image))
                                    <img src="{{ asset($option->image) }}" alt="{{ $option->name }}" style="width: 28px; height: 28px; object-fit: contain; border-radius: 4px; border: 1px solid var(--border-color); padding: 1px; background-color: #f7fafc;">
                                @else
                                    <span style="font-size: 11px; color: var(--text-muted); font-style: italic;">No Icon</span>
                                @endif
                            </td>
                            <td style="font-weight: 700; color: var(--primary-color);">{{ $option->name }}</td>
                            @if($activeType === 'shape' || $activeType === 'color')
                                <td>
                                    <form action="{{ route('categories.update', $option->id) }}" method="POST" style="margin: 0; display: inline-block;">
                                        @csrf
                                        @method('PUT')
                                        <select name="group" onchange="this.form.submit()" style="padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-color); font-size: 13px; font-weight: 600; cursor: pointer; color: var(--text-color); background-color: #ffffff; outline: none; transition: all 0.2s ease;">
                                            @if($activeType === 'shape')
                                                <option value="basic" {{ ($option->group ?? 'basic') === 'basic' ? 'selected' : '' }}>Basic</option>
                                                <option value="advance" {{ ($option->group ?? '') === 'advance' ? 'selected' : '' }}>Advance</option>
                                            @else
                                                <option value="white" {{ ($option->group ?? 'white') === 'white' ? 'selected' : '' }}>White</option>
                                                <option value="fancy" {{ ($option->group ?? '') === 'fancy' ? 'selected' : '' }}>Fancy</option>
                                            @endif
                                        </select>
                                    </form>
                                </td>
                            @endif
                            <td>{{ $option->created_at->format('M d, Y H:i') }}</td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px; align-items: center; justify-content: flex-end; width: 100%;">
                                    <!-- Inline update image form -->
                                    <form action="{{ route('categories.update', $option->id) }}" method="POST" enctype="multipart/form-data" style="margin: 0;">
                                        @csrf
                                        @method('PUT')
                                        <label class="btn btn-secondary" style="padding: 6px 12px; cursor: pointer; border-color: #cbd5e0; background-color: #f7fafc; color: var(--text-color); margin: 0; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;" title="Upload or update image icon">
                                            <i class="fa-solid fa-image"></i> Upload Icon
                                            <input type="file" name="image" accept="image/*" style="display: none;" onchange="this.form.submit()">
                                        </label>
                                    </form>

                                    <!-- Delete form -->
                                    <form action="{{ route('categories.destroy', $option->id) }}" method="POST" class="confirm-delete-form" data-username="{{ $option->name }}" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; color: var(--error-color); border-color: #fed7d7; background-color: #fff5f5; margin: 0;" title="Remove option">
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
            <div style="padding: 40px; text-align: center; color: var(--text-muted); font-weight: 500;">
                <i class="fa-solid fa-folder-open" style="font-size: 36px; color: #cbd5e0; margin-bottom: 12px; display: block;"></i>
                No options seeded or added yet for this category.
            </div>
        @endif
    </div>

</div>
@endsection
