<?php

namespace App\Jobs;

use App\Services\InventoryManager;
use App\Models\InventoryHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkAssignAdminJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $productType;
    public array $productIds;
    public int $assignedAdminId;
    public int $userId;
    public ?string $ipAddress;

    /**
     * Create a new job instance.
     */
    public function __construct(string $productType, array $productIds, int $assignedAdminId, int $userId, ?string $ipAddress = null)
    {
        $this->productType = $productType;
        $this->productIds = $productIds;
        $this->assignedAdminId = $assignedAdminId;
        $this->userId = $userId;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Execute the job.
     */
    public function handle(InventoryManager $inventoryManager)
    {
        foreach ($this->productIds as $id) {
            $product = $inventoryManager->resolveProduct($this->productType, $id);
            if ($product) {
                $oldAssigned = $product->assigned_admin_id;
                $product->update(['assigned_admin_id' => $this->assignedAdminId]);

                // Log audit history
                InventoryHistory::create([
                    'product_type' => $this->productType,
                    'product_id' => $id,
                    'action' => 'assign_admin',
                    'old_value' => $oldAssigned ? "User ID: {$oldAssigned}" : 'None',
                    'new_value' => "User ID: {$this->assignedAdminId}",
                    'user_id' => $this->userId,
                    'remarks' => 'Bulk assigned admin',
                    'ip_address' => $this->ipAddress,
                ]);
            }
        }
    }
}
