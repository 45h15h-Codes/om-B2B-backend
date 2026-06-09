<?php

namespace App\Services;

use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\InventoryHistory;
use App\Models\FailedInventorySync;
use App\Models\User;
use App\Notifications\HoldAppliedNotification;
use App\Notifications\HoldReleasedNotification;
use App\Notifications\SyncFailedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InventoryManager
{
    protected CrossStoreInventorySyncService $syncService;

    public function __construct(CrossStoreInventorySyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Resolve the product model based on the type and ID.
     */
    public function resolveProduct(string $productType, int $productId)
    {
        $productType = strtolower($productType);
        if ($productType === 'diamond') {
            return Diamond::find($productId);
        } elseif ($productType === 'jewelry' || $productType === 'jewelery') {
            return Jewelery::find($productId);
        }
        return null;
    }

    /**
     * Get the string key for polymorphic relations.
     */
    protected function getProductType($product): string
    {
        if ($product instanceof Diamond) {
            return 'diamond';
        }
        if ($product instanceof Jewelery) {
            return 'jewelry';
        }
        return strtolower(class_basename($product));
    }

    /**
     * Apply a hold on an inventory item.
     */
    public function hold($product, ?int $adminId, ?string $reason = null, ?string $ipAddress = null)
    {
        $product->refresh();
        if ($product->inventory_status !== 'available') {
            throw ValidationException::withMessages([
                'hold' => ['This inventory item is not available to be held. Current status: ' . $product->inventory_status]
            ]);
        }

        $productType = $this->getProductType($product);

        DB::transaction(function () use ($product, $productType, $adminId, $reason) {
            // Update product state
            $product->update([
                'hold_by' => $adminId,
                'hold_reason' => $reason,
                'hold_at' => now(),
            ]);

            $inventoryService = app(\App\Services\InventoryService::class);
            $inventoryService->updateInventoryStatus($product, 'on_hold');

            // Sync Shopify
            $this->syncService->lockInventoryAcrossStores($productType, $product->id);
        });

        return $product;
    }

    /**
     * Release a hold on an inventory item.
     */
    public function release($product, ?int $adminId, ?string $remarks = null, ?string $ipAddress = null)
    {
        $productType = $this->getProductType($product);

        DB::transaction(function () use ($product, $productType, $adminId) {
            // Update product state
            $product->update([
                'hold_by' => null,
                'hold_reason' => null,
                'hold_at' => null,
            ]);

            $inventoryService = app(\App\Services\InventoryService::class);
            $inventoryService->updateInventoryStatus($product, 'available');

            // Sync Shopify
            $this->syncService->releaseInventoryAcrossStores($productType, $product->id);
        });

        return $product;
    }

    /**
     * Force synchronization of product inventory state with Shopify stores.
     */
    public function sync($product, ?int $adminId, ?string $remarks = null, ?string $ipAddress = null)
    {
        $productType = $this->getProductType($product);

        try {
            $this->syncService->syncInventoryAcrossStores($productType, $product->id);

            // Log successful sync history
            InventoryHistory::create([
                'product_type' => $productType,
                'product_id' => $product->id,
                'action' => 'sync',
                'old_value' => null,
                'new_value' => $product->inventory_status,
                'user_id' => $adminId,
                'remarks' => $remarks ?? 'Manual Shopify sync triggered',
                'ip_address' => $ipAddress,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to sync {$productType} ID {$product->id} on Shopify: " . $e->getMessage());

            // Get active mappings to log failed stores
            $mappings = $product->shopifyProducts;
            foreach ($mappings as $mapping) {
                if ($mapping->shopify_store_id) {
                    $failedSync = FailedInventorySync::create([
                        'product_type' => $productType,
                        'product_id' => $product->id,
                        'shopify_store_id' => $mapping->shopify_store_id,
                        'error_message' => $e->getMessage(),
                        'retry_count' => 0,
                        'status' => 'failed',
                    ]);

                    // Queue a retry job
                    \App\Jobs\RetrySyncJob::dispatch($failedSync->id)->delay(now()->addMinutes(5));
                }
            }

            // Immediately notify Super Admins on sync failure
            $superAdmins = User::where('role', 'super_admin')->get();
            foreach ($superAdmins as $super) {
                $super->notify(new SyncFailedNotification($product, $e->getMessage()));
            }

            throw $e;
        }

        return $product;
    }
}
