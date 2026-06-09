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
        // If database is empty, seed 10 beautiful mock jewelry items for testing & visual accuracy
        if (Jewelery::count() === 0) {
            $mocks = [
                [
                    'sku' => 'R3-236598',
                    'name' => 'Diamond Ring 14 KT Yellow Gold Jewellery - Whirl Diamond Ring',
                    'type' => 'Ring',
                    'price' => 350.00,
                    'location' => 'London, United Kingdom',
                    'image_url' => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?w=500&auto=format&fit=crop',
                    'created_by' => 'OM Gems'
                ],
                [
                    'sku' => 'W1-984433',
                    'name' => 'Maxima Max Pro Turbo Bluetooth Call Smartwatch',
                    'type' => 'Watch',
                    'price' => 150.00,
                    'location' => 'London, United Kingdom',
                    'image_url' => 'https://images.unsplash.com/photo-1522312346375-d1a52e2b99b3?w=500&auto=format&fit=crop',
                    'created_by' => 'OM Gems'
                ],
                [
                    'sku' => 'E2-334411',
                    'name' => 'Diamond Cluster Stud Earrings 18 KT White Gold',
                    'type' => 'Earings',
                    'price' => 450.00,
                    'location' => 'London, United Kingdom',
                    'image_url' => 'https://images.unsplash.com/photo-1635767798638-3e25273a8236?w=500&auto=format&fit=crop',
                    'created_by' => 'OM Gems'
                ],
                [
                    'sku' => 'N1-229988',
                    'name' => 'Classic Diamond Solitaire Pendant Necklace',
                    'type' => 'Necklace',
                    'price' => 600.00,
                    'location' => 'London, United Kingdom',
                    'image_url' => 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=500&auto=format&fit=crop',
                    'created_by' => 'OM Gems'
                ],
                [
                    'sku' => 'W2-556633',
                    'name' => 'Chronograph Sport Quartz Watch Men',
                    'type' => 'Watch',
                    'price' => 250.00,
                    'location' => 'London, United Kingdom',
                    'image_url' => 'https://images.unsplash.com/photo-1547996160-81dfa63595aa?w=500&auto=format&fit=crop',
                    'created_by' => 'OM Gems'
                ],
                [
                    'sku' => 'P1-778844',
                    'name' => 'Ruby Heart Gemstone Diamond Pendant',
                    'type' => 'Pendent',
                    'price' => 320.00,
                    'location' => 'London, United Kingdom',
                    'image_url' => 'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?w=500&auto=format&fit=crop',
                    'created_by' => 'OM Gems'
                ],
                [
                    'sku' => 'E3-889911',
                    'name' => 'Hoop Diamond Earrings 14 KT Rose Gold',
                    'type' => 'Earings',
                    'price' => 550.00,
                    'location' => 'London, United Kingdom',
                    'image_url' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=500&auto=format&fit=crop',
                    'created_by' => 'OM Gems'
                ],
                [
                    'sku' => 'N2-112233',
                    'name' => 'Emerald and Diamond Halo Pendant Necklace',
                    'type' => 'Necklace',
                    'price' => 850.00,
                    'location' => 'London, United Kingdom',
                    'image_url' => 'https://images.unsplash.com/photo-1602751584552-8ba73aad10e1?w=500&auto=format&fit=crop',
                    'created_by' => 'OM Gems'
                ],
                [
                    'sku' => 'W3-445566',
                    'name' => 'Luxury Diamond Analog Dress Watch',
                    'type' => 'Watch',
                    'price' => 1200.00,
                    'location' => 'London, United Kingdom',
                    'image_url' => 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?w=500&auto=format&fit=crop',
                    'created_by' => 'OM Gems'
                ],
                [
                    'sku' => 'R4-771122',
                    'name' => 'Platinum Oval Cut Diamond Engagement Ring',
                    'type' => 'Ring',
                    'price' => 1500.00,
                    'location' => 'London, United Kingdom',
                    'image_url' => 'https://images.unsplash.com/photo-1603561591411-07134e71a2a9?w=500&auto=format&fit=crop',
                    'created_by' => 'OM Gems'
                ]
            ];

            foreach ($mocks as $mock) {
                $mock['user_id'] = Auth::id();
                $mock['assigned_admin_id'] = Auth::id();
                Jewelery::create($mock);
            }
        }

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
                // Ensure target dir exists
                if (!file_exists(public_path('images/jewelery'))) {
                    mkdir(public_path('images/jewelery'), 0777, true);
                }
                $file->move(public_path('images/jewelery'), $fileName);
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
                // Ensure target dir exists
                if (!file_exists(public_path('images/jewelery'))) {
                    mkdir(public_path('images/jewelery'), 0777, true);
                }
                $file->move(public_path('images/jewelery'), $fileName);
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
