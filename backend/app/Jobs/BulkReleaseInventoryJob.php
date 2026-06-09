<?php

namespace App\Jobs;

use App\Services\InventoryManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkReleaseInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $productType;
    public array $productIds;
    public int $adminId;
    public ?string $remarks;
    public ?string $ipAddress;

    /**
     * Create a new job instance.
     */
    public function __construct(string $productType, array $productIds, int $adminId, ?string $remarks = null, ?string $ipAddress = null)
    {
        $this->productType = $productType;
        $this->productIds = $productIds;
        $this->adminId = $adminId;
        $this->remarks = $remarks;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Execute the job.
     */
    public function handle(InventoryManager $inventoryManager)
    {
        foreach ($this->productIds as $id) {
            $product = $inventoryManager->resolveProduct($this->productType, $id);
            if ($product && $product->inventory_status === 'on_hold') {
                try {
                    $inventoryManager->release($product, $this->adminId, $this->remarks, $this->ipAddress);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Bulk Release Error for product ID {$id}: " . $e->getMessage());
                }
            }
        }
    }
}
