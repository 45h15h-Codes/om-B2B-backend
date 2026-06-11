<?php

namespace App\Http\Controllers;

use App\Models\Jewelery;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JeweleryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Jewelery::query();

        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            $query->where('assigned_admin_id', Auth::id());
        }

        if ($request->filled('inventory_status')) {
            $query->where('inventory_status', $request->inventory_status);
        }

        // Keyword filter
        if ($request->filled('keyword')) {
            $kw = $request->keyword;
            $query->where(function($q) use ($kw) {
                $q->where('sku', 'like', "%{$kw}%")
                  ->orWhere('name', 'like', "%{$kw}%");
            });
        }

        // Type filter (from icons or sidebar checkboxes)
        if ($request->filled('types') && is_array($request->types)) {
            $query->whereIn('type', $request->types);
        } elseif ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Country/Location filter
        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        $items = $query->latest()->get();
        $categories = \App\Models\Category::all()->groupBy('type');

        return view('jewelery.index', compact('items', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (session('admin_role', 'normal_admin') === 'super_admin') {
            return redirect()->route('jewelery.index')->with('error', 'Unauthorized: Super Admin cannot upload jewelry.');
        }

        $request->validate([
            'sku' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'price' => 'required|numeric|min:0',
            'location' => 'required|string',
            'image_file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120'
        ]);

        $data = $request->except(['_token', 'image_file']);
        $role = session('admin_role', 'normal_admin');
        $data['created_by'] = $role === 'super_admin' ? 'Super Admin' : 'Normal Admin';
        $data['status'] = $role === 'super_admin' ? 'Approved' : 'Pending';
        $data['user_id'] = Auth::id();

        // Checkbox conversions to booleans
        $data['is_available'] = $request->has('is_available');
        $data['is_available_for_memo'] = $request->has('is_available_for_memo');
        $data['treatment_yes_no'] = $request->has('treatment_yes_no');
        $data['is_unpublished'] = $request->has('is_unpublished');
        $data['is_shareable'] = $request->has('is_shareable');
        $data['is_own_stock'] = $request->has('is_own_stock');

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            // Upload to Cloudinary using CloudinaryService
            $cloudinaryUrl = CloudinaryService::upload($file);
            if ($cloudinaryUrl) {
                $data['image_url'] = $cloudinaryUrl;
            } else {
                // Local fallback storage
                $fileName = time() . '_' . $file->getClientOriginalName();
                \Illuminate\Support\Facades\Storage::disk('public_uploads')->putFileAs('images/jewelery', $file, $fileName);
                $data['image_url'] = 'images/jewelery/' . $fileName;
            }
        }

        Jewelery::create($data);

        $message = 'Jewelery item uploaded successfully' . ($role !== 'super_admin' ? ' and is awaiting Super Admin approval.' : '.');
        return redirect()->route('jewelery.index')->with('success', $message);
    }

    /**
     * Import jeweleries from a CSV spreadsheet.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        if (session('admin_role', 'normal_admin') === 'super_admin') {
            return redirect()->route('jewelery.index')->with('error', 'Unauthorized: Super Admin cannot upload jewelry.');
        }

        $request->validate([
            'import_file' => 'required|file'
        ]);

        try {
            $file = $request->file('import_file');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . $originalName;

            // Ensure storage directory exists
            $fileDir = storage_path('app/imports');
            if (!file_exists($fileDir)) {
                mkdir($fileDir, 0777, true);
            }

            $file->move($fileDir, $fileName);
            $filePath = 'imports/' . $fileName;
            $absolutePath = $fileDir . '/' . $fileName;

            // Detect delimiter
            $firstLine = fgets(fopen($absolutePath, 'r'));
            $delimiter = ',';
            if ($firstLine !== false) {
                $commaCount = substr_count($firstLine, ',');
                $semicolonCount = substr_count($firstLine, ';');
                $tabCount = substr_count($firstLine, "\t");
                if ($semicolonCount > $commaCount && $semicolonCount > $tabCount) {
                    $delimiter = ';';
                } elseif ($tabCount > $commaCount && $tabCount > $semicolonCount) {
                    $delimiter = "\t";
                }
            }

            if (($handle = fopen($absolutePath, 'r')) === false) {
                return back()->with('error', 'Unable to open the uploaded CSV file.');
            }

            $headers = fgetcsv($handle, 0, $delimiter);
            if (!$headers) {
                fclose($handle);
                @unlink($absolutePath);
                return back()->with('error', 'The uploaded file is empty or missing headers.');
            }

            // Strip UTF-8 BOM if present on the first header
            if (isset($headers[0])) {
                $headers[0] = preg_replace('/^[\xEF\xBB\xBF\xFE\xFF\xFF\xFE]+/', '', $headers[0]);
            }

            // Normalize headers: lowercased, snake_cased
            $headers = array_map(function($h) {
                return strtolower(trim(str_replace([' ', '-', '#'], ['_', '_', 'no'], $h)));
            }, $headers);

            $totalRows = 0;
            $allRows = [];

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $headerCount = count($headers);
                $rowCount = count($row);
                if ($rowCount < $headerCount) {
                    $row = array_pad($row, $headerCount, '');
                } elseif ($rowCount > $headerCount) {
                    $row = array_slice($row, 0, $headerCount);
                }

                // Trim row values
                $row = array_map('trim', $row);
                $allRows[] = array_combine($headers, $row);
                $totalRows++;
            }
            fclose($handle);

            // Protection against 100,000+ record uploads
            if ($totalRows > 100000) {
                @unlink($absolutePath);
                return back()->with('error', 'Import failed: CSV file exceeds the limit of 100,000 records.');
            }

            if ($totalRows === 0) {
                @unlink($absolutePath);
                return back()->with('error', 'The uploaded CSV file contains no data rows.');
            }

            $chunkSize = 250;
            $chunks = array_chunk($allRows, $chunkSize);
            $pendingChunks = count($chunks);

            // Create ImportHistory record
            $importHistory = \App\Models\ImportHistory::create([
                'user_id' => Auth::id(),
                'file_name' => $originalName,
                'file_path' => $filePath,
                'import_type' => 'jewelry',
                'total_rows' => $totalRows,
                'status' => 'processing',
                'pending_chunks' => $pendingChunks,
            ]);

            $role = session('admin_role', 'normal_admin');
            $meta = [
                'created_by' => $role === 'super_admin' ? 'Super Admin' : 'Normal Admin',
                'status' => $role === 'super_admin' ? 'Approved' : 'Pending',
            ];
            $userId = Auth::id();

            // Dispatch chunk jobs to queue
            foreach ($chunks as $chunk) {
                \App\Jobs\ProcessImportChunkJob::dispatch($chunk, $importHistory->id, 'jewelry', $meta, $userId);
            }

            // Write audit log for the import action
            \App\Services\AuditService::log('jewelry_import_queued', null, null, [
                'file_name' => $originalName,
                'total_rows' => $totalRows,
                'import_history_id' => $importHistory->id
            ]);

            return redirect()->route('jewelery.index')->with('success', "CSV import of {$totalRows} jewelry items has been queued and is processing in the background.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Jewelery $jewelery)
    {
        $this->authorize('view', $jewelery);
        return view('jewelery.show', compact('jewelery'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Jewelery $jewelery)
    {
        $this->authorize('update', $jewelery);
        $categories = \App\Models\Category::all()->groupBy('type');
        return view('jewelery.edit', compact('jewelery', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Jewelery $jewelery)
    {
        $this->authorize('update', $jewelery);

        $request->validate([
            'sku' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'price' => 'required|numeric|min:0',
            'location' => 'required|string',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120'
        ]);

        $data = $request->except(['_token', '_method', 'image_file']);

        // Checkbox conversions to booleans
        $data['is_available'] = $request->has('is_available');
        $data['is_available_for_memo'] = $request->has('is_available_for_memo');
        $data['treatment_yes_no'] = $request->has('treatment_yes_no');
        $data['is_unpublished'] = $request->has('is_unpublished');
        $data['is_shareable'] = $request->has('is_shareable');
        $data['is_own_stock'] = $request->has('is_own_stock');

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            
            // Delete old file if exists
            $this->deleteFile($jewelery->image_url);

            // Upload to Cloudinary using CloudinaryService
            $cloudinaryUrl = CloudinaryService::upload($file);
            if ($cloudinaryUrl) {
                $data['image_url'] = $cloudinaryUrl;
            } else {
                // Local fallback storage
                $fileName = time() . '_' . $file->getClientOriginalName();
                \Illuminate\Support\Facades\Storage::disk('public_uploads')->putFileAs('images/jewelery', $file, $fileName);
                $data['image_url'] = 'images/jewelery/' . $fileName;
            }
        }

        // Set status to pending if normal admin updates, or approved if super admin
        $role = session('admin_role', 'normal_admin');
        if ($role !== 'super_admin') {
            $data['status'] = 'Pending';
        }

        $jewelery->update($data);

        $message = 'Jewelery item updated successfully' . ($role !== 'super_admin' ? ' and is awaiting Super Admin approval.' : '.');
        return redirect()->route('jewelery.index')->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Jewelery $jewelery)
    {
        try {
            $this->authorize('delete', $jewelery);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->route('jewelery.index')->with('error', 'Unauthorized: Only Super Admin can delete jewelry.');
        }

        try {
            $this->deleteFile($jewelery->image_url);
            $jewelery->delete();
            return redirect()->route('jewelery.index')->with('success', 'Jewelery item deleted successfully.');
        } catch (\Throwable $e) {
            return redirect()->route('jewelery.index')->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    /**
     * Approve the specified jewelry item.
     */
    public function approve(Jewelery $jewelery)
    {
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            return redirect()->route('jewelery.index')->with('error', 'Unauthorized action.');
        }

        $jewelery->update(['status' => 'Approved']);
        return redirect()->route('jewelery.index')->with('success', 'Jewelery item has been approved.');
    }

    /**
     * Reject the specified jewelry item.
     */
    public function reject(Jewelery $jewelery)
    {
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            return redirect()->route('jewelery.index')->with('error', 'Unauthorized action.');
        }

        $jewelery->update(['status' => 'Rejected']);
        return redirect()->route('jewelery.index')->with('success', 'Jewelery item has been rejected.');
    }

    /**
     * Delete local or Cloudinary file if it exists.
     */
    private function deleteFile(?string $filePath): void
    {
        if (!$filePath) {
            return;
        }

        if (str_starts_with($filePath, 'http')) {
            try {
                CloudinaryService::delete($filePath);
            } catch (\Throwable $e) {
                // Fail gracefully
            }
        } else {
            $fullPath = public_path($filePath);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }
}
