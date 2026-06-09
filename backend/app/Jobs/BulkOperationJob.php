<?php

namespace App\Jobs;

use App\Services\InventoryManager;
use App\Models\BackgroundJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BulkOperationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $action;
    public string $productType;
    public array $productIds;
    public int $adminId;
    public array $extraParams;
    public ?string $ipAddress;
    public int $backgroundJobId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $action, string $productType, array $productIds, int $adminId, array $extraParams = [], ?string $ipAddress = null, int $backgroundJobId)
    {
        $this->action = $action;
        $this->productType = $productType;
        $this->productIds = $productIds;
        $this->adminId = $adminId;
        $this->extraParams = $extraParams;
        $this->ipAddress = $ipAddress;
        $this->backgroundJobId = $backgroundJobId;
    }

    /**
     * Execute the job.
     */
    public function handle(InventoryManager $inventoryManager)
    {
        $jobRecord = BackgroundJob::find($this->backgroundJobId);
        if ($jobRecord) {
            $jobRecord->markProcessing();
        }

        $total = count($this->productIds);
        $processed = 0;
        $errors = [];

        foreach ($this->productIds as $id) {
            try {
                $product = $inventoryManager->resolveProduct($this->productType, $id);
                if ($product) {
                    if ($this->action === 'hold') {
                        $inventoryManager->hold($product, $this->adminId, $this->extraParams['reason'] ?? 'Bulk hold', $this->ipAddress);
                    } elseif ($this->action === 'release') {
                        $inventoryManager->release($product, $this->adminId, $this->extraParams['remarks'] ?? 'Bulk release', $this->ipAddress);
                    } elseif ($this->action === 'sync') {
                        $inventoryManager->sync($product, $this->adminId, 'Bulk sync triggered', $this->ipAddress);
                    } elseif ($this->action === 'assign') {
                        $assignedAdminId = $this->extraParams['assigned_admin_id'];
                        $oldAdminId = $product->assigned_admin_id;
                        $product->update(['assigned_admin_id' => $assignedAdminId]);
                        
                        // Log history
                        \App\Models\InventoryHistory::create([
                            'product_type' => get_class($product),
                            'product_id' => $product->id,
                            'action' => 'update',
                            'old_value' => 'assigned_admin_id: ' . $oldAdminId,
                            'new_value' => 'assigned_admin_id: ' . $assignedAdminId,
                            'user_id' => $this->adminId,
                            'remarks' => 'Bulk assigned admin',
                            'ip_address' => $this->ipAddress,
                        ]);
                    }
                } else {
                    $errors[] = "ID {$id}: Product not found";
                }
            } catch (\Throwable $e) {
                $errors[] = "ID {$id}: " . $e->getMessage();
                Log::error("Bulk Operation Job Error for ID {$id}: " . $e->getMessage());
            }

            $processed++;
            if ($jobRecord) {
                $percent = round(($processed / $total) * 100);
                $jobRecord->update([
                    'message' => json_encode([
                        'processed' => $processed,
                        'total' => $total,
                        'percent' => $percent,
                        'errors' => $errors
                    ])
                ]);
            }
        }

        if ($jobRecord) {
            if (count($errors) === $total) {
                $jobRecord->markFailed("All items failed. Errors: " . implode(', ', $errors));
            } else {
                $successCount = $total - count($errors);
                $jobRecord->markSuccess("Successfully processed {$successCount} of {$total} items. Errors: " . count($errors));
            }
        }
    }
}
