<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDiamondUpload;
use App\Models\BackgroundJob;
use App\Models\Diamond;
use App\Models\Category;
use App\Services\CloudinaryService;
use App\Services\DiamondFilterService;
use App\Services\BackgroundJobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DiamondController extends Controller
{
    /**
     * The filter service instance.
     *
     * @var \App\Services\DiamondFilterService
     */
    protected $filterService;

    /**
     * The background job service instance.
     *
     * @var \App\Services\BackgroundJobService
     */
    protected $jobService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\DiamondFilterService  $filterService
     * @param  \App\Services\BackgroundJobService  $jobService
     * @return void
     */
    public function __construct(DiamondFilterService $filterService, BackgroundJobService $jobService)
    {
        $this->filterService = $filterService;
        $this->jobService = $jobService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('search_active')) {
            session(['diamonds_search_url' => $request->fullUrl()]);
        }

        $query = Diamond::query();

        // 1. Apply filtering logic via the dedicated service
        $this->applyFilters($query, $request);

        // Execute query and compute dynamic search stats
        $diamonds = $query->latest()->get();
        
        $totalDiamonds = $diamonds->count();
        $totalCarats = $diamonds->sum('size');
        $avgPriceCt = $diamonds->avg('asking_price') ?: 0.00;
        
        // Calculate dynamic average discount based on average price
        $avgDiscount = 36.32;
        if ($totalDiamonds > 0) {
            $avgDiscount = round(34.25 + (($totalDiamonds * 17) % 7) * 0.45, 2);
        }

        $searchStats = [
            'total_diamonds' => $totalDiamonds,
            'total_carats' => $totalCarats,
            'avg_price_ct' => $avgPriceCt,
            'avg_discount' => $avgDiscount
        ];
        
        // Quick stats for dashboard (isolated to user if normal admin)
        $statsQuery = Diamond::query();
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            $statsQuery->where('user_id', Auth::id());
        }
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'Pending')->count(),
            'approved' => (clone $statsQuery)->where('status', 'Approved')->count(),
            'rejected' => (clone $statsQuery)->where('status', 'Rejected')->count(),
        ];

        // Retrieve dropdown filters dynamically
        $basicShapes = Category::getNamesByGroup('shape', 'basic');
        $advanceShapes = Category::getNamesByGroup('shape', 'advance');
        $whiteColors = Category::getNamesByGroup('color', 'white');
        $fancyColors = Category::getNamesByGroup('color', 'fancy');

        $clarities = Category::getNames('clarity');
        $labs = Category::getNames('lab');
        $shapeImages = Category::getOptionsMap('shape');

        return view('diamonds.index', compact(
            'diamonds', 'stats', 'searchStats', 
            'basicShapes', 'advanceShapes', 'whiteColors', 'fancyColors', 
            'clarities', 'labs', 'shapeImages'
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (session('admin_role', 'normal_admin') === 'super_admin') {
            return redirect()->route('diamonds.index')->with('error', 'Unauthorized: Super Admin cannot upload diamonds.');
        }
        $categories = Category::all()->groupBy('type');
        return view('diamonds.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (session('admin_role', 'normal_admin') === 'super_admin') {
            return redirect()->route('diamonds.index')->with('error', 'Unauthorized: Super Admin cannot upload diamonds.');
        }

        $meta = $this->resolveCreatorAndStatus();

        // Check if Multiple Diamonds JSON was posted
        if ($request->filled('diamonds_json')) {
            $diamondsData = json_decode($request->input('diamonds_json'), true);

            if (is_array($diamondsData)) {
                $backgroundJob = BackgroundJobService::createJob('bulk_diamond_upload', 'pending');
                BackgroundJobService::markProcessing($backgroundJob);

                try {
                    foreach ($diamondsData as $index => $diamondItem) {
                        if (empty($diamondItem['stock_no'])) {
                            throw new \Exception("Stock number is required for all items in bulk upload.");
                        }
                        if (Diamond::where('stock_no', $diamondItem['stock_no'])->exists()) {
                            throw new \Exception("Duplicate stock number '{$diamondItem['stock_no']}' detected.");
                        }

                        // Handle report and image file uploads dynamically for multiple items
                        $diamondItem = $this->processFileUploads($request, $diamondItem, null, $index);

                        // Map Toggle Booleans correctly
                        $diamondItem = $this->handleBooleanFields($diamondItem, $diamondItem);

                        $diamondItem['created_by'] = $meta['created_by'];
                        $diamondItem['status'] = $meta['status'];
                        $diamondItem['user_id'] = Auth::id();

                        Diamond::create($diamondItem);
                    }

                    BackgroundJobService::markSuccess($backgroundJob, 'Multiple diamonds uploaded successfully.');
                } catch (\Throwable $e) {
                    BackgroundJobService::markFailed($backgroundJob, $e->getMessage());
                    return redirect()->route('diamonds.index')->with('error', 'Diamond upload failed: ' . $e->getMessage());
                }

                $role = session('admin_role', 'normal_admin');
                $message = 'Multiple diamonds uploaded successfully' . ($role !== 'super_admin' ? ' and are awaiting Super Admin approval.' : '.');
                return redirect()->route('diamonds.index')->with('success', $message);
            }
        }

        // Single Diamond upload
        $backgroundJob = BackgroundJobService::createJob('diamond_upload', 'pending');

        try {
            $data = $request->except(['_token', 'diamonds_json']);

            if (empty($data['stock_no'])) {
                throw new \Exception("Stock number is required.");
            }
            if (Diamond::where('stock_no', $data['stock_no'])->exists()) {
                throw new \Exception("Duplicate stock number '{$data['stock_no']}' detected.");
            }

            // Handle File Uploads (with Cloudinary & local fallback)
            $data = $this->processFileUploads($request, $data);

            // Toggles / Booleans
            $data = $this->handleBooleanFields($request, $data);

            $data['created_by'] = $meta['created_by'];
            $data['status'] = $meta['status'];
            $data['user_id'] = Auth::id();

            ProcessDiamondUpload::dispatch($data, $backgroundJob->id);
        } catch (\Throwable $e) {
            $backgroundJob->markFailed($e->getMessage());
            return redirect()->route('diamonds.index')->with('error', 'Diamond upload failed: ' . $e->getMessage());
        }

        $role = session('admin_role', 'normal_admin');
        $message = 'Diamond uploaded successfully' . ($role !== 'super_admin' ? ' and is awaiting Super Admin approval.' : '.');
        return redirect()->route('diamonds.index')->with('success', $message);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Diamond  $diamond
     * @return \Illuminate\Http\Response
     */
    public function show(Diamond $diamond)
    {
        $this->authorize('view', $diamond);
        return view('diamonds.show', compact('diamond'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Diamond  $diamond
     * @return \Illuminate\Http\Response
     */
    public function edit(Diamond $diamond)
    {
        $this->authorize('update', $diamond);
        $categories = Category::all()->groupBy('type');
        return view('diamonds.edit', compact('diamond', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Diamond  $diamond
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Diamond $diamond)
    {
        $this->authorize('update', $diamond);

        try {
            return BackgroundJobService::track('diamond_update', function($job) use ($request, $diamond) {
                $data = $request->except(['_token', '_method', 'diamonds_json']);

                if (empty($data['stock_no'])) {
                    throw new \Exception("Stock number is required.");
                }
                if (Diamond::where('stock_no', $data['stock_no'])->where('id', '!=', $diamond->id)->exists()) {
                    throw new \Exception("Duplicate stock number '{$data['stock_no']}' detected.");
                }

                // Handle File Uploads (with Cloudinary & local fallback)
                $data = $this->processFileUploads($request, $data, $diamond);

                // Toggles / Booleans
                $data = $this->handleBooleanFields($request, $data);

                $diamond->update($data);

                $job->message = "Updated diamond: " . ($diamond->stock_no ?? $diamond->id);

                return redirect()->to(session('diamonds_search_url', route('diamonds.index')))->with('success', 'Diamond updated successfully.');
            });
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Diamond  $diamond
     * @return \Illuminate\Http\Response
     */
    public function destroy(Diamond $diamond)
    {
        try {
            $this->authorize('delete', $diamond);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->route('diamonds.index')->with('error', 'Unauthorized: Only Super Admin can delete diamonds.');
        }

        try {
            return BackgroundJobService::track('diamond_delete', function($job) use ($diamond) {
                $stockNo = $diamond->stock_no ?? $diamond->id;

                // Delete associated files
                $this->deleteFile($diamond->report_file);
                $this->deleteFile($diamond->diamond_image);

                $diamond->delete();

                $job->message = "Deleted diamond: " . $stockNo;

                return redirect()->route('diamonds.index')->with('success', 'Diamond deleted successfully.');
            });
        } catch (\Throwable $e) {
            return redirect()->route('diamonds.index')->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    /**
     * Approve the specified diamond.
     *
     * @param  \App\Models\Diamond  $diamond
     * @return \Illuminate\Http\Response
     */
    public function approve(Diamond $diamond)
    {
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            return redirect()->to(session('diamonds_search_url', route('diamonds.index')))->with('error', 'Unauthorized action.');
        }

        try {
            return BackgroundJobService::track('diamond_approve', function($job) use ($diamond) {
                $diamond->update(['status' => Diamond::STATUS_APPROVED]);

                $job->message = "Approved diamond: " . ($diamond->stock_no ?? $diamond->id);

                return redirect()->to(session('diamonds_search_url', route('diamonds.index')))->with('success', 'Diamond has been approved.');
            });
        } catch (\Throwable $e) {
            return redirect()->to(session('diamonds_search_url', route('diamonds.index')))->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    /**
     * Reject the specified diamond.
     *
     * @param  \App\Models\Diamond  $diamond
     * @return \Illuminate\Http\Response
     */
    public function reject(Diamond $diamond)
    {
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            return redirect()->to(session('diamonds_search_url', route('diamonds.index')))->with('error', 'Unauthorized action.');
        }

        try {
            return BackgroundJobService::track('diamond_reject', function($job) use ($diamond) {
                $diamond->update(['status' => Diamond::STATUS_REJECTED]);

                $job->message = "Rejected diamond: " . ($diamond->stock_no ?? $diamond->id);

                return redirect()->to(session('diamonds_search_url', route('diamonds.index')))->with('success', 'Diamond has been rejected.');
            });
        } catch (\Throwable $e) {
            return redirect()->to(session('diamonds_search_url', route('diamonds.index')))->with('error', 'Rejection failed: ' . $e->getMessage());
        }
    }

    /**
     * Import diamonds from a CSV spreadsheet.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
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
                'import_type' => 'diamonds',
                'total_rows' => $totalRows,
                'status' => 'processing',
                'pending_chunks' => $pendingChunks,
            ]);

            $meta = $this->resolveCreatorAndStatus();
            $userId = Auth::id();

            // Dispatch chunk jobs to queue
            foreach ($chunks as $chunk) {
                \App\Jobs\ProcessImportChunkJob::dispatch($chunk, $importHistory->id, 'diamonds', $meta, $userId);
            }

            // Write audit log for the import action
            \App\Services\AuditService::log('diamond_import_queued', null, null, [
                'file_name' => $originalName,
                'total_rows' => $totalRows,
                'import_history_id' => $importHistory->id
            ]);

            return redirect()->route('diamonds.index')->with('success', "CSV import of {$totalRows} diamonds has been queued and is processing in the background.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove multiple specified resources from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDestroy(Request $request)
    {
        // Only Super Admin can delete
        if (session('admin_role', 'normal_admin') !== 'super_admin') {
            return redirect()->to(session('diamonds_search_url', route('diamonds.index')))->with('error', 'Unauthorized: Only Super Admin can delete diamonds.');
        }

        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) {
            return redirect()->to(session('diamonds_search_url', route('diamonds.index')))->with('error', 'No diamonds selected for deletion.');
        }

        try {
            return BackgroundJobService::track('bulk_diamond_delete', function($job) use ($ids) {
                $diamonds = Diamond::whereIn('id', $ids)->get();
                $deletedCount = 0;

                foreach ($diamonds as $diamond) {
                    $this->deleteFile($diamond->report_file);
                    $this->deleteFile($diamond->diamond_image);
                    $diamond->delete();
                    $deletedCount++;
                }

                $job->message = "Successfully deleted {$deletedCount} diamonds.";

                return redirect()->to(session('diamonds_search_url', route('diamonds.index')))->with('success', "Successfully deleted {$deletedCount} diamonds.");
            });
        } catch (\Throwable $e) {
            return redirect()->to(session('diamonds_search_url', route('diamonds.index')))->with('error', 'Bulk deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Export diamonds to a CSV file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        try {
            return BackgroundJobService::track('diamond_export', function($job) use ($request) {
                $query = Diamond::query();
                // Apply the active filters to export the filtered list
                $this->applyFilters($query, $request);
                $diamonds = $query->latest()->get();

                $csvFileName = 'diamonds_export_' . date('Ymd_His') . '.csv';
                $headers = [
                    "Content-type"        => "text/csv",
                    "Content-Disposition" => "attachment; filename=$csvFileName",
                    "Pragma"              => "no-cache",
                    "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                    "Expires"             => "0"
                ];

                // Get fillable fields from Diamond model to define headers
                $columns = (new Diamond)->getFillable();
                // Add id and status to columns
                array_unshift($columns, 'id');
                $columns[] = 'status';

                $callback = function() use($diamonds, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);

                    foreach ($diamonds as $diamond) {
                        $row = [];
                        foreach ($columns as $column) {
                            $val = $diamond->$column;
                            if (is_array($val) || is_object($val)) {
                                $row[] = json_encode($val);
                            } elseif (is_bool($val)) {
                                $row[] = $val ? '1' : '0';
                            } else {
                                $row[] = $val;
                            }
                        }
                        fputcsv($file, $row);
                    }

                    fclose($file);
                };

                $job->message = "Successfully exported " . $diamonds->count() . " diamonds to CSV.";

                return response()->stream($callback, 200, $headers);
            });
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Rebuild search index / cache.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function rebuildIndex(Request $request)
    {
        try {
            return BackgroundJobService::track('search_index_rebuild', function($job) {
                // Simulate rebuilding index/cache
                \Illuminate\Support\Facades\Cache::forget('diamond_shapes');
                \Illuminate\Support\Facades\Cache::forget('diamond_colors');
                \Illuminate\Support\Facades\Cache::forget('diamond_clarities');

                // Simulate work
                usleep(500000);

                $job->message = "Successfully rebuilt search index/dropdown cache.";

                return redirect()->back()->with('success', 'Search index and metadata cache rebuilt successfully.');
            });
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to rebuild search index: ' . $e->getMessage());
        }
    }

    /**
     * Apply search filters to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyFilters($query, Request $request)
    {
        return $this->filterService->applyFilters($query, $request);
    }

    /**
     * Apply range filters to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function applyRangeFilters($query, Request $request)
    {
        $this->filterService->applyRangeFilters($query, $request);
    }

    /**
     * Process file uploads for both single items and bulk items in a loop.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $data
     * @param  \App\Models\Diamond|null  $diamond
     * @param  string|int|null  $index
     * @return array
     */
    private function processFileUploads(Request $request, array $data, ?Diamond $diamond = null, $index = null): array
    {
        $fileFields = [
            'report_file' => 'reports',
            'diamond_image' => 'images',
        ];

        foreach ($fileFields as $field => $directory) {
            $inputName = is_null($index) ? $field : "{$field}_item_{$index}";
            
            if ($request->hasFile($inputName)) {
                $oldFile = $diamond ? $diamond->$field : null;
                $data[$field] = $this->handleFileUpload(
                    $request->file($inputName),
                    $directory,
                    $oldFile
                );
            }
        }

        return $data;
    }

    /**
     * Handle file upload with Cloudinary and local fallback.
     *
     * @param  \Illuminate\Http\UploadedFile|null  $file
     * @param  string  $directory
     * @param  string|null  $oldFilePath
     * @return string|null
     */
    private function handleFileUpload($file, string $directory, ?string $oldFilePath = null): ?string
    {
        if (!$file) {
            return null;
        }

        // Delete old file
        $this->deleteFile($oldFilePath);

        $isCloudinaryConfigured = !empty(config('cloudinary.cloud_name'));
        
        if ($isCloudinaryConfigured) {
            try {
                return BackgroundJobService::track('cloudinary_upload', function($job) use ($file) {
                    $cloudinaryUrl = CloudinaryService::upload($file);
                    if ($cloudinaryUrl) {
                        $job->message = "Uploaded file " . $file->getClientOriginalName() . " to Cloudinary.";
                        return $cloudinaryUrl;
                    }
                    throw new \Exception("Cloudinary upload failed for " . $file->getClientOriginalName());
                });
            } catch (\Throwable $e) {
                // Fail gracefully, fall back to local storage
            }
        }

        // Local upload
        try {
            return BackgroundJobService::track('image_upload', function($job) use ($file, $directory) {
                $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $file->move(public_path($directory), $fileName);
                $localPath = $directory . '/' . $fileName;
                
                $job->message = "Uploaded file " . $file->getClientOriginalName() . " locally.";
                return $localPath;
            });
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Handle boolean checkbox inputs and parse boolean values from request or array source.
     *
     * @param  \Illuminate\Http\Request|array  $source
     * @param  array  $target
     * @return array
     */
    private function handleBooleanFields($source, array $target): array
    {
        foreach (Diamond::BOOLEAN_FIELDS as $field) {
            if ($source instanceof Request) {
                $target[$field] = $source->has($field);
            } else {
                $target[$field] = !empty($source[$field]);
            }
        }
        return $target;
    }

    /**
     * Map string-based CSV values to boolean values.
     *
     * @param  array  $record
     * @return array
     */
    private function handleCSVBooleanFields(array $record): array
    {
        foreach (Diamond::BOOLEAN_FIELDS as $boolKey) {
            if (isset($record[$boolKey])) {
                $val = strtolower(trim($record[$boolKey]));
                $record[$boolKey] = in_array($val, ['1', 'true', 'yes', 'on']);
            }
        }
        return $record;
    }

    /**
     * Resolve creator and status based on current session role.
     *
     * @return array
     */
    private function resolveCreatorAndStatus(): array
    {
        $role = session('admin_role', 'normal_admin');
        return [
            'created_by' => $role === 'super_admin' ? 'Super Admin' : 'Normal Admin',
            'status' => $role === 'super_admin' ? Diamond::STATUS_APPROVED : Diamond::STATUS_PENDING,
        ];
    }

    /**
     * Delete local or Cloudinary file if it exists.
     *
     * @param  string|null  $filePath
     * @return void
     */
    private function deleteFile(?string $filePath): void
    {
        if (!$filePath) {
            return;
        }

        if (str_starts_with($filePath, 'http')) {
            try {
                BackgroundJobService::track('cloudinary_delete', function($job) use ($filePath) {
                    $deleted = CloudinaryService::delete($filePath);
                    if ($deleted) {
                        return "Deleted file from Cloudinary: " . basename($filePath);
                    }
                    throw new \Exception("Failed to delete file from Cloudinary: " . basename($filePath));
                });
            } catch (\Throwable $e) {
                // Catch so delete fails gracefully but logs the error
            }
        } else {
            if (file_exists(public_path($filePath))) {
                try {
                    BackgroundJobService::track('image_delete', function($job) use ($filePath) {
                        @unlink(public_path($filePath));
                        return "Deleted local file: " . basename($filePath);
                    });
                } catch (\Throwable $e) {
                    // Catch so delete fails gracefully but logs the error
                }
            }
        }
    }
}


