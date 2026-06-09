<?php

namespace App\Jobs;

use App\Models\ImportHistory;
use App\Models\Diamond;
use App\Models\Jewelery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessImportChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $rows;
    public int $importHistoryId;
    public string $importType;
    public array $meta;
    public int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $rows, int $importHistoryId, string $importType, array $meta, int $userId)
    {
        $this->rows = $rows;
        $this->importHistoryId = $importHistoryId;
        $this->importType = $importType;
        $this->meta = $meta;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $importHistory = ImportHistory::find($this->importHistoryId);
        if (!$importHistory) {
            return;
        }

        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($this->rows as $record) {
            try {
                if ($this->importType === 'diamonds') {
                    $this->processDiamondRow($record);
                } else {
                    $this->processJewelryRow($record);
                }
                $successful++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $record,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Atomically update counters and error logs
        DB::transaction(function () use ($importHistory, $successful, $failed, $errors) {
            $importHistory->increment('successful_rows', $successful);
            $importHistory->increment('failed_rows', $failed);

            if (!empty($errors)) {
                $currentErrors = $importHistory->error_log ?? [];
                $importHistory->error_log = array_merge($currentErrors, $errors);
                $importHistory->save();
            }

            $importHistory->decrement('pending_chunks');
            $importHistory->refresh();

            if ($importHistory->pending_chunks <= 0) {
                $finalStatus = $importHistory->failed_rows > 0 ? 'completed_with_errors' : 'completed';
                if ($importHistory->successful_rows === 0 && $importHistory->failed_rows > 0) {
                    $finalStatus = 'failed';
                }
                $importHistory->status = $finalStatus;
                $importHistory->save();

                // Send a database notification to the user
                $user = \App\Models\User::find($this->userId);
                if ($user) {
                    $user->notify(new \App\Notifications\SystemAlertNotification(
                        "CSV Import Completed",
                        "Your import of {$importHistory->file_name} has finished. " .
                        "Successful: {$importHistory->successful_rows}, Failed: {$importHistory->failed_rows}."
                    ));
                }
            }
        });
    }

    private function processDiamondRow(array $record)
    {
        // Trim row values
        $record = array_map('trim', $record);

        // Map common aliases
        $aliasMap = [
            'stock' => 'stock_no',
            'stock_number' => 'stock_no',
            'sku' => 'stock_no',
            'carat' => 'size',
            'carats' => 'size',
            'weight' => 'size',
            'carat_weight' => 'size',
            'price' => 'asking_price',
            'asking' => 'asking_price',
            'asking_price' => 'asking_price',
            'cash' => 'cash_price',
            'cash_price' => 'cash_price',
        ];
        foreach ($aliasMap as $alias => $target) {
            if (isset($record[$alias]) && $record[$alias] !== '' && (empty($record[$target]) || $record[$target] === '')) {
                $record[$target] = $record[$alias];
            }
        }

        $stockNo = isset($record['stock_no']) ? trim($record['stock_no']) : '';
        if (empty($stockNo)) {
            throw new \Exception("Stock number is empty or missing.");
        }

        $record['stock_no'] = $stockNo;
        $record['created_by'] = $this->meta['created_by'];
        $record['status'] = $this->meta['status'];

        // Handle boolean conversions
        foreach (Diamond::BOOLEAN_FIELDS as $boolKey) {
            if (isset($record[$boolKey])) {
                $val = strtolower(trim($record[$boolKey]));
                $record[$boolKey] = in_array($val, ['1', 'true', 'yes', 'on']);
            }
        }

        // Filter fillable data
        $fillableKeys = (new Diamond)->getFillable();
        $fillableData = array_intersect_key($record, array_flip($fillableKeys));

        $fillableData['user_id'] = $this->userId;
        $fillableData['assigned_admin_id'] = $this->userId;

        // Check if diamond with this stock number already exists
        $existingDiamond = Diamond::where('stock_no', $record['stock_no'])->first();
        if ($existingDiamond) {
            $fillableData['user_id'] = $existingDiamond->user_id;
            $existingDiamond->update($fillableData);
        } else {
            Diamond::create($fillableData);
        }
    }

    private function processJewelryRow(array $record)
    {
        // Trim row values
        $record = array_map('trim', $record);

        // Map common aliases
        if (isset($record['stock']) && $record['stock'] !== '') {
            $record['sku'] = $record['stock'];
        }
        if (isset($record['stock_no']) && $record['stock_no'] !== '') {
            $record['sku'] = $record['stock_no'];
        }
        if (isset($record['title']) && $record['title'] !== '') {
            $record['name'] = $record['title'];
        }
        if (isset($record['image']) && $record['image'] !== '') {
            $record['image_url'] = $record['image'];
        }

        $sku = isset($record['sku']) ? trim($record['sku']) : '';
        if (empty($sku)) {
            throw new \Exception("SKU is empty or missing.");
        }

        $record['sku'] = $sku;
        $record['created_by'] = $this->meta['created_by'];
        $record['status'] = $this->meta['status'];

        // Handle boolean conversions
        foreach (['is_available', 'is_available_for_memo', 'treatment_yes_no', 'is_unpublished', 'is_shareable', 'is_own_stock'] as $boolKey) {
            if (isset($record[$boolKey])) {
                $val = strtolower(trim($record[$boolKey]));
                $record[$boolKey] = in_array($val, ['1', 'true', 'yes', 'on']);
            }
        }

        // Filter fillable data
        $fillableKeys = array_merge(Jewelery::PHYSICAL_COLUMNS, Jewelery::VIRTUAL_FIELDS);
        $fillableData = array_intersect_key($record, array_flip($fillableKeys));

        $fillableData['user_id'] = $this->userId;
        $fillableData['assigned_admin_id'] = $this->userId;

        // Check if jewelry item with this SKU already exists
        $existingJewelry = Jewelery::where('sku', $record['sku'])->first();
        if ($existingJewelry) {
            $fillableData['user_id'] = $existingJewelry->user_id;
            $existingJewelry->update($fillableData);
        } else {
            Jewelery::create($fillableData);
        }
    }
}
