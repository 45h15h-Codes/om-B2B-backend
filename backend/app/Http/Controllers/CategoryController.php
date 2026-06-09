<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Enforce Super Admin only
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            return redirect()->route('home')->with('error', 'Unauthorized: Only Super Admin can manage categories.');
        }

        // Get options grouped by type
        $categoryTypes = [
            'shape' => 'Shape',
            'color' => 'Color',
            'clarity' => 'Clarity',
            'cut' => 'Cut',
            'polish' => 'Polish',
            'symmetry' => 'Symmetry',
            'lab' => 'Lab',
            'fluorescence_intensity' => 'Fluorescence Intensity',
            'fluorescence_color' => 'Fluorescence Color',
            'girdle_condition' => 'Girdle Condition',
            'culet_condition' => 'Culet Condition',
            'culet_size' => 'Culet Size',
            'treatment' => 'Treatment',
            'advance_shape_detail' => 'Advance Shape Detail',
            'fancy_color_intensity' => 'Fancy Color Intensity',
            'fancy_color_overtone' => 'Fancy Color Overtone',
            'fancy_color_color' => 'Fancy Color Color',
            'jewelery_type' => 'Jewelry Type',
            'metal_type' => 'Metal Type',
            'metal_karat' => 'Metal Karat',
            'gemstone_type' => 'Gemstone Type',
        ];

        $activeType = $request->input('type', 'shape');
        $options = Category::getOptionsByType($activeType);

        return view('categories.index', compact('options', 'categoryTypes', 'activeType'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Enforce Super Admin only
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            return redirect()->route('home')->with('error', 'Unauthorized: Only Super Admin can manage categories.');
        }

        $request->validate([
            'type' => 'required|string',
            'name' => 'required|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'group' => 'nullable|string|max:100'
        ]);

        $category = Category::firstOrCreate(
            ['type' => $request->type],
            ['names' => []]
        );

        $names = $category->names ?? [];
        if (!is_array($names)) {
            $names = [];
        }

        $optionKey = trim($request->name);
        if ($optionKey === '') {
            return back()->with('error', 'Invalid option name.');
        }

        foreach ($names as $item) {
            $existingName = is_array($item) ? ($item['name'] ?? '') : $item;
            if (strcasecmp(trim((string) $existingName), $optionKey) === 0) {
                return back()->with('error', 'This option already exists in this category.');
            }
        }

        $imageUrl = $this->handleFileUpload($request->file('image'), 'images/categories');
        $group = trim($request->input('group', ''));
        if ($group === '') {
            $group = null;
        }

        if ($imageUrl || $group) {
            $names[] = [
                'name' => $optionKey,
                'image' => $imageUrl,
                'group' => $group,
            ];
        } else {
            $names[] = $optionKey;
        }

        $category->names = $names;
        $category->save();

        return back()->with('success', 'New option added successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, string $id)
    {
        // Enforce Super Admin only
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            return redirect()->route('home')->with('error', 'Unauthorized: Only Super Admin can manage categories.');
        }

        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'group' => 'nullable|string'
        ]);

        $decoded = Category::decodeId($id);
        $parentId = $decoded['parentId'];
        $optionName = isset($decoded['name']) ? trim($decoded['name']) : null;

        if (!$parentId || !$optionName) {
            return back()->with('error', 'Invalid category option ID.');
        }

        $category = Category::find($parentId);
        if (!$category) {
            return back()->with('error', 'Category not found.');
        }

        $names = $category->names ?? [];
        if (!is_array($names)) {
            return back()->with('error', 'Category options are invalid.');
        }

        $foundKey = null;
        foreach ($names as $key => $item) {
            $existingName = is_array($item) ? ($item['name'] ?? '') : $item;
            if (strcasecmp(trim((string) $existingName), $optionName) === 0) {
                $foundKey = $key;
                break;
            }
        }

        if ($foundKey === null) {
            return back()->with('error', 'Option not found.');
        }

        $existingItem = $names[$foundKey];
        $existingImage = is_array($existingItem) ? ($existingItem['image'] ?? null) : null;
        $existingGroup = is_array($existingItem) ? ($existingItem['group'] ?? null) : null;

        $imageUrl = $this->handleFileUpload($request->file('image'), 'images/categories');
        if (!$imageUrl) {
            $imageUrl = $existingImage;
        }

        $group = $request->has('group') ? $request->input('group') : $existingGroup;

        // Delete old local file if replacing image
        if ($request->hasFile('image') && $existingImage && (strpos($existingImage, 'uploads/categories/') !== false || strpos($existingImage, 'images/categories/') !== false)) {
            $localPath = public_path($existingImage);
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
        }

        if ($imageUrl || $group) {
            $names[$foundKey] = [
                'name' => $optionName,
                'image' => $imageUrl,
                'group' => $group
            ];
        } else {
            $names[$foundKey] = $optionName;
        }

        $category->names = $names;
        $category->save();

        return back()->with('success', 'Option updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        // Enforce Super Admin only
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            return redirect()->route('home')->with('error', 'Unauthorized: Only Super Admin can manage categories.');
        }

        $decoded = Category::decodeId($id);
        $parentId = $decoded['parentId'];
        $optionName = isset($decoded['name']) ? trim($decoded['name']) : null;

        if (!$parentId || !$optionName) {
            return back()->with('error', 'Invalid category option ID.');
        }

        $category = Category::find($parentId);
        if (!$category) {
            return back()->with('error', 'Category not found.');
        }

        $names = $category->names ?? [];
        if (!is_array($names)) {
            return back()->with('error', 'Category options are invalid.');
        }

        // Find key case-insensitively
        $foundKey = null;
        foreach ($names as $key => $item) {
            $existingName = is_array($item) ? ($item['name'] ?? '') : $item;
            if (strcasecmp(trim((string) $existingName), $optionName) === 0) {
                $foundKey = $key;
                break;
            }
        }

        if ($foundKey === null) {
            return back()->with('error', 'Option "' . $optionName . '" not found.');
        }

        $imageUrlToDelete = is_array($names[$foundKey]) ? ($names[$foundKey]['image'] ?? null) : null;

        unset($names[$foundKey]);
        $names = array_values($names);

        $category->names = $names;
        $category->save();

        // Delete old local file if any
        if ($imageUrlToDelete && (strpos($imageUrlToDelete, 'uploads/categories/') !== false || strpos($imageUrlToDelete, 'images/categories/') !== false)) {
            $localPath = public_path($imageUrlToDelete);
            if (file_exists($localPath)) {
                @unlink($localPath);
            }
        }

        return back()->with('success', 'Option removed successfully.');
    }

    /**
     * Handle file upload with Cloudinary and local fallback.
     */
    private function handleFileUpload($file, string $directory): ?string
    {
        if (!$file) {
            return null;
        }

        $cloudinaryUrl = \App\Services\CloudinaryService::upload($file);
        if ($cloudinaryUrl) {
            return $cloudinaryUrl;
        }

        $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
        $file->move(public_path($directory), $fileName);
        return $directory . '/' . $fileName;
    }

}
