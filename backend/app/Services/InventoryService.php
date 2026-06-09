<?php

namespace App\Services;

use App\Models\ShopifyInventoryReservation;
use App\Models\InventoryHistory;
use App\Models\ShopifyInventoryAudit;
use App\Models\User;
use App\Notifications\HoldAppliedNotification;
use App\Notifications\HoldReleasedNotification;
use App\Notifications\DiamondSoldNotification;
use App\Notifications\JewelrySoldNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    protected array $processingHoldIds = [];
    protected array $processingAvailableIds = [];
    protected array $processingSoldIds = [];

    /**
     * Store the Shopify order creation timestamp during webhook or recovery sync processing.
     * Used to determine if the state changes are for historical orders and filter notifications.
     *
     * @var string|null
     */
    public static $currentProcessingOrderCreatedAt = null;

    /**
     * Update the inventory status of a diamond or jewelry item safely.
     *
     * @param mixed $product
     * @param string $status
     * @param int|null $storeId
     * @param string|null $shopifyOrderId
     * @param int|null $orderId
     * @return mixed
     * @throws \Exception
     */
    public function updateInventoryStatus($product, string $status, ?int $storeId = null, ?string $shopifyOrderId = null, ?int $orderId = null)
    {
        $productClass = get_class($product);
        $productId = $product->id;

        return DB::transaction(function () use ($productClass, $productId, $status, $storeId, $shopifyOrderId, $orderId) {
            // Acquire row-level lock on the product to prevent concurrent checkouts/race conditions
            $lockedProduct = $productClass::where('id', $productId)->lockForUpdate()->first();
            if (!$lockedProduct) {
                throw new \Exception("Product not found.");
            }

            $currentStatus = $lockedProduct->inventory_status ?? 'available';

            $isSameStatus = ($currentStatus === $status) || (in_array($status, ['hold', 'on_hold']) && in_array($currentStatus, ['hold', 'on_hold']));
            if ($isSameStatus) {
                if (in_array($status, ['hold', 'on_hold'])) {
                    // Check if the hold is for the same order/reservation
                    if ($shopifyOrderId || $orderId) {
                        $sameReservation = ShopifyInventoryReservation::where('product_type', $lockedProduct->getMorphClass())
                            ->where('product_id', $productId)
                            ->where('status', 'hold')
                            ->where(function ($q) use ($shopifyOrderId, $orderId) {
                                if ($shopifyOrderId) {
                                    $q->where('shopify_order_id', $shopifyOrderId);
                                }
                                if ($orderId) {
                                    $q->orWhere('order_id', $orderId);
                                }
                            })
                            ->exists();

                        if (!$sameReservation) {
                            throw new \Exception("Product already has an active reservation for another order.");
                        }
                    }

                    Log::info("Product {$lockedProduct->getMorphClass()} ID {$productId} is already on hold.");
                    return $lockedProduct;
                }

                Log::info("Product {$lockedProduct->getMorphClass()} ID {$productId} is already {$status}.");
                return $lockedProduct;
            }

            // Enforce and execute state transitions
            if (in_array($status, ['hold', 'on_hold'])) {
                if ($currentStatus !== 'available') {
                    throw new \Exception("Invalid transition from {$currentStatus} to hold for {$lockedProduct->getMorphClass()} ID {$productId}.");
                }
                $this->transitionToHold($lockedProduct, $storeId, $shopifyOrderId, $orderId);
            } elseif ($status === 'available') {
                if ($currentStatus !== 'hold' && $currentStatus !== 'on_hold' && $currentStatus !== 'sold') {
                    throw new \Exception("Invalid transition from {$currentStatus} to available for {$lockedProduct->getMorphClass()} ID {$productId}.");
                }
                $this->transitionToAvailable($lockedProduct, $storeId, $shopifyOrderId, $orderId);
            } elseif ($status === 'sold') {
                if ($currentStatus === 'available') {
                    // Transition available -> hold -> sold
                    $this->transitionToHold($lockedProduct, $storeId, $shopifyOrderId, $orderId);
                    $lockedProduct->refresh();
                    $this->transitionToSold($lockedProduct, $storeId, $shopifyOrderId, $orderId);
                } elseif ($currentStatus === 'hold' || $currentStatus === 'on_hold') {
                    $this->transitionToSold($lockedProduct, $storeId, $shopifyOrderId, $orderId);
                } else {
                    throw new \Exception("Invalid transition from {$currentStatus} to sold for {$lockedProduct->getMorphClass()} ID {$productId}.");
                }
            } else {
                throw new \Exception("Unknown status: {$status}");
            }

            return $lockedProduct;
        });
    }

    /**
     * Transition a product to HOLD status, create a reservation, and hide it from all other stores.
     */
    protected function transitionToHold($product, ?int $storeId, ?string $shopifyOrderId, ?int $orderId)
    {
        Log::info("Transitioning {$product->getMorphClass()} ID {$product->id} to HOLD.");

        $isOutermost = empty($this->processingHoldIds);

        try {
            // Propagate hold to duplicate/matching diamonds
            if ($product instanceof \App\Models\Diamond) {
                $productId = $product->id;
                if (in_array($productId, $this->processingHoldIds)) {
                    return;
                }
                $this->processingHoldIds[] = $productId;

                $matchingDiamonds = \App\Models\Diamond::where('id', '!=', $product->id)
                    ->where('shape', $product->shape)
                    ->where('size', $product->size)
                    ->where('color', $product->color)
                    ->where('clarity', $product->clarity)
                    ->where('asking_price', $product->asking_price)
                    ->get();

                foreach ($matchingDiamonds as $matching) {
                    if (($matching->inventory_status ?? 'available') === 'available') {
                        $this->transitionToHold($matching, $storeId, $shopifyOrderId, $orderId);
                    }
                }
            }

            // Check if there is already an active reservation to prevent double hold
            $activeReservation = ShopifyInventoryReservation::where('product_type', $product->getMorphClass())
                ->where('product_id', $product->id)
                ->where('status', 'hold')
                ->exists();

            if ($activeReservation) {
                throw new \Exception("Product already has an active reservation.");
            }

            // Create reservation record
            ShopifyInventoryReservation::create([
                'product_type' => $product->getMorphClass(),
                'product_id' => $product->id,
                'shopify_store_id' => $storeId ?? \App\Models\ShopifyStore::first()->id ?? 1,
                'origin_store_id' => $storeId,
                'order_id' => $orderId,
                'shopify_order_id' => $shopifyOrderId,
                'status' => 'hold',
            ]);

            // Update status field
            $oldStatus = $product->inventory_status ?? 'available';
            $product->update([
                'inventory_status' => 'on_hold',
                'hold_at' => now(),
                'hold_by' => auth()->id() ?? $product->user_id ?? null,
                'hold_reason' => 'Shopify order checkout' . ($shopifyOrderId ? ' (Shopify Order ID: ' . $shopifyOrderId . ')' : '')
            ]);

            $this->recordStateChange($product, $oldStatus, 'on_hold', $storeId, $shopifyOrderId, $orderId);

            // Dispatch unpublish jobs to all stores synced with this product
            $mappings = $product->shopifyProducts;
            foreach ($mappings as $mapping) {
                if ($mapping->shopify_product_id) {
                    \App\Jobs\UnpublishProductFromStoreJob::dispatch($mapping->shopify_product_id, $mapping->shopify_store_id);
                }
            }
        } finally {
            if ($isOutermost) {
                $this->processingHoldIds = [];
            }
        }
    }

    /**
     * Transition a product back to AVAILABLE, release reservations, and republish to all linked stores.
     */
    protected function transitionToAvailable($product, ?int $storeId = null, ?string $shopifyOrderId = null, ?int $orderId = null)
    {
        Log::info("Transitioning {$product->getMorphClass()} ID {$product->id} to AVAILABLE.");

        $isOutermost = empty($this->processingAvailableIds);

        try {
            // Propagate release to duplicate/matching diamonds
            if ($product instanceof \App\Models\Diamond) {
                $productId = $product->id;
                if (in_array($productId, $this->processingAvailableIds)) {
                    return;
                }
                $this->processingAvailableIds[] = $productId;

                $matchingDiamonds = \App\Models\Diamond::where('id', '!=', $product->id)
                    ->where('shape', $product->shape)
                    ->where('size', $product->size)
                    ->where('color', $product->color)
                    ->where('clarity', $product->clarity)
                    ->where('asking_price', $product->asking_price)
                    ->get();

                foreach ($matchingDiamonds as $matching) {
                    if ($matching->inventory_status === 'hold' || $matching->inventory_status === 'on_hold' || $matching->inventory_status === 'sold') {
                        $this->transitionToAvailable($matching, $storeId, $shopifyOrderId, $orderId);
                    }
                }
            }

            // Mark reservations as released
            ShopifyInventoryReservation::where('product_type', $product->getMorphClass())
                ->where('product_id', $product->id)
                ->where('status', 'hold')
                ->update(['status' => 'released']);

            // Update status field
            $oldStatus = $product->inventory_status ?? 'on_hold';
            $product->update([
                'inventory_status' => 'available',
                'hold_at' => null,
                'hold_by' => null,
                'hold_reason' => null,
                'order_id' => null,
                'shopify_order_id' => null,
                'sold_store_id' => null,
                'sold_at' => null,
                'sold_by_store_id' => null,
                'sold_by_store_name' => null,
                'sold_by_user_id' => null,
                'sold_order_number' => null,
                'sold_order_date' => null,
            ]);

            $this->recordStateChange($product, $oldStatus, 'available', $storeId, $shopifyOrderId, $orderId);

            // Dispatch publish jobs to all linked stores
            $mappings = $product->shopifyProducts;
            foreach ($mappings as $mapping) {
                \App\Jobs\PublishProductToStoreJob::dispatch($product->getMorphClass(), $product->id, $mapping->shopify_store_id);
            }
        } finally {
            if ($isOutermost) {
                $this->processingAvailableIds = [];
            }
        }
    }

    /**
     * Transition a product to SOLD, complete active reservations, and permanently delete/draft from all Shopify stores.
     */
    protected function transitionToSold($product, ?int $storeId = null, ?string $shopifyOrderId = null, ?int $orderId = null)
    {
        Log::info("Transitioning {$product->getMorphClass()} ID {$product->id} to SOLD.");

        $isOutermost = empty($this->processingSoldIds);

        try {
            // Propagate sold to duplicate/matching diamonds
            if ($product instanceof \App\Models\Diamond) {
                $productId = $product->id;
                if (in_array($productId, $this->processingSoldIds)) {
                    return;
                }
                $this->processingSoldIds[] = $productId;

                $matchingDiamonds = \App\Models\Diamond::where('id', '!=', $product->id)
                    ->where('shape', $product->shape)
                    ->where('size', $product->size)
                    ->where('color', $product->color)
                    ->where('clarity', $product->clarity)
                    ->where('asking_price', $product->asking_price)
                    ->get();

                foreach ($matchingDiamonds as $matching) {
                    $this->transitionToSold($matching, $storeId, $shopifyOrderId, $orderId);
                }
            }

            // Mark reservations as completed
            ShopifyInventoryReservation::where('product_type', $product->getMorphClass())
                ->where('product_id', $product->id)
                ->where('status', 'hold')
                ->update(['status' => 'completed']);

            // Update status field
            $oldStatus = $product->inventory_status ?? 'on_hold';

            $soldStoreId = $storeId;
            $soldStoreName = null;
            $soldOrderNo = null;
            $soldOrderDate = null;
            $soldUserId = auth()->id() ?? $product->user_id ?? null;

            if ($soldStoreId) {
                $storeObj = \App\Models\ShopifyStore::find($soldStoreId);
                if ($storeObj) {
                    $soldStoreName = $storeObj->store_name;
                }
            }

            if ($shopifyOrderId) {
                $shopOrder = \App\Models\ShopifyOrder::where('shopify_order_id', $shopifyOrderId)->first();
                if ($shopOrder) {
                    if (!$soldStoreId) {
                        $soldStoreId = $shopOrder->shopify_store_id;
                        $storeObj = \App\Models\ShopifyStore::find($soldStoreId);
                        if ($storeObj) {
                            $soldStoreName = $storeObj->store_name;
                        }
                    }
                    $soldOrderNo = $shopOrder->order_number;
                    $soldOrderDate = $shopOrder->created_at;
                }
            }

            if ($orderId && !$soldOrderNo) {
                $localOrder = \App\Models\Order::find($orderId);
                if ($localOrder) {
                    if (!$soldStoreId) {
                        $soldStoreId = $localOrder->shopify_store_id;
                        $storeObj = \App\Models\ShopifyStore::find($soldStoreId);
                        if ($storeObj) {
                            $soldStoreName = $storeObj->store_name;
                        }
                    }
                    $soldOrderNo = $localOrder->shopify_order_number ?? $localOrder->uuid;
                    $soldOrderDate = $localOrder->created_at;
                    if (!$soldUserId) {
                        $soldUserId = $localOrder->created_by;
                    }
                }
            }

            if (!$soldStoreId) {
                $reservation = \App\Models\ShopifyInventoryReservation::where('product_type', $product->getMorphClass())
                    ->where('product_id', $product->id)
                    ->latest()
                    ->first();
                if ($reservation) {
                    $soldStoreId = $reservation->shopify_store_id;
                    $storeObj = \App\Models\ShopifyStore::find($soldStoreId);
                    if ($storeObj) {
                        $soldStoreName = $storeObj->store_name;
                    }
                }
            }

            if (!$soldStoreId) {
                $mapping = $product->shopifyProducts()->first();
                if ($mapping) {
                    $soldStoreId = $mapping->shopify_store_id;
                    $storeObj = \App\Models\ShopifyStore::find($soldStoreId);
                    if ($storeObj) {
                        $soldStoreName = $storeObj->store_name;
                    }
                }
            }

            if (!$soldStoreId) {
                $activeStore = \App\Models\ShopifyStore::where('is_active', true)->first();
                if ($activeStore) {
                    $soldStoreId = $activeStore->id;
                    $soldStoreName = $activeStore->store_name;
                }
            }

            if (!$soldOrderDate) {
                $soldOrderDate = now();
            }

            $product->update([
                'inventory_status' => 'sold',
                'order_id' => $orderId,
                'shopify_order_id' => $shopifyOrderId,
                'sold_store_id' => $soldStoreId,
                'sold_at' => now(),
                'sold_by_store_id' => $soldStoreId,
                'sold_by_store_name' => $soldStoreName,
                'sold_by_user_id' => $soldUserId,
                'sold_order_number' => $soldOrderNo,
                'sold_order_date' => $soldOrderDate,
            ]);

            $this->recordStateChange($product, $oldStatus, 'sold', $soldStoreId, $shopifyOrderId, $orderId);

            // Dispatch delete/draft jobs to all stores
            $mappings = $product->shopifyProducts;
            foreach ($mappings as $mapping) {
                if ($mapping->shopify_product_id) {
                    if ($mapping->shopify_store_id != $soldStoreId) {
                        \App\Models\ShopifyInventoryAudit::create([
                            'shopify_store_id' => $mapping->shopify_store_id,
                            'diamond_id' => $product instanceof \App\Models\Diamond ? $product->id : null,
                            'jewelry_id' => $product instanceof \App\Models\Jewelery ? $product->id : null,
                            'stock_no' => $product->stock_no ?? $product->sku ?? 'N/A',
                            'action' => 'auto_draft',
                            'shopify_product_id' => $mapping->shopify_product_id,
                            'shopify_variant_id' => $mapping->shopify_variant_id,
                            'previous_quantity' => 1,
                            'new_quantity' => 0,
                            'api_response' => [
                                'sold_store_id' => $soldStoreId,
                                'sold_store_name' => $soldStoreName,
                                'sold_order_number' => $soldOrderNo,
                                'sold_order_id' => $shopifyOrderId,
                                'sold_at' => now()->toDateTimeString(),
                            ],
                        ]);
                    }
                    \App\Jobs\DeleteProductFromStoreJob::dispatch($mapping->shopify_product_id, $mapping->shopify_store_id);
                }
            }
        } finally {
            if ($isOutermost) {
                $this->processingSoldIds = [];
            }
        }
    }

    /**
     * Create activity log, audit record, and database notification on state change.
     */
    protected function recordStateChange($product, string $oldStatus, string $newStatus, ?int $storeId = null, ?string $shopifyOrderId = null, ?int $orderId = null)
    {
        $productType = strtolower(class_basename($product));
        if ($productType === 'jewelery') {
            $productType = 'jewelry';
        }

        // 1. Create activity log (InventoryHistory)
        InventoryHistory::create([
            'product_type' => $productType,
            'product_id' => $product->id,
            'action' => $newStatus === 'on_hold' ? 'hold' : ($newStatus === 'available' ? 'release' : 'sold'),
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
            'user_id' => auth()->id() ?? ($product->user_id ?? null),
            'remarks' => $newStatus === 'on_hold' ? ($product->hold_reason ?? 'Hold applied via order checkout') : ($newStatus === 'available' ? 'Hold released' : 'Sold'),
            'ip_address' => request() ? request()->ip() : null,
        ]);

        // 2. Create audit record (ShopifyInventoryAudit)
        $auditStoreId = $storeId;
        if (!$auditStoreId) {
            $reservation = ShopifyInventoryReservation::where('product_type', $product->getMorphClass())
                ->where('product_id', $product->id)
                ->latest()
                ->first();
            if ($reservation) {
                $auditStoreId = $reservation->shopify_store_id;
            } else {
                $mapping = $product->shopifyProducts()->first();
                $auditStoreId = $mapping ? $mapping->shopify_store_id : null;
            }
        }

        if (!$auditStoreId) {
            $activeStore = \App\Models\ShopifyStore::where('is_active', true)->first();
            $auditStoreId = $activeStore ? $activeStore->id : 1;
        }

        $mapping = $product->shopifyProducts()->where('shopify_store_id', $auditStoreId)->first();

        ShopifyInventoryAudit::create([
            'shopify_store_id' => $auditStoreId,
            'diamond_id' => $product instanceof \App\Models\Diamond ? $product->id : null,
            'jewelry_id' => $product instanceof \App\Models\Jewelery ? $product->id : null,
            'stock_no' => $product->stock_no ?? $product->sku ?? 'N/A',
            'action' => $newStatus === 'on_hold' ? 'lock_set_zero' : ($newStatus === 'sold' ? 'sold_set_zero' : 'release_set_one'),
            'shopify_product_id' => $mapping ? $mapping->shopify_product_id : null,
            'shopify_variant_id' => $mapping ? $mapping->shopify_variant_id : null,
            'previous_quantity' => $oldStatus === 'available' ? 1 : 0,
            'new_quantity' => $newStatus === 'available' ? 1 : 0,
        ]);

        // 3. Create database notification (using queued notifications)
        $isFresh = true;
        $orderCreatedAt = self::$currentProcessingOrderCreatedAt;

        if (!$orderCreatedAt && $shopifyOrderId) {
            // Fallback: look up in database if not set in class property
            $shopifyOrder = \App\Models\ShopifyOrder::where('shopify_order_id', (string) $shopifyOrderId)->first();
            if ($shopifyOrder) {
                $orderPayload = $shopifyOrder->order_json;
                if (is_string($orderPayload)) {
                    $orderPayload = json_decode($orderPayload, true);
                }
                if (is_array($orderPayload) && isset($orderPayload['created_at'])) {
                    $orderCreatedAt = $orderPayload['created_at'];
                }
            }
        }

        if ($orderCreatedAt) {
            $createdAt = \Illuminate\Support\Carbon::parse($orderCreatedAt);
            if ($createdAt->lt(now()->subMinutes(15))) {
                $isFresh = false;
            }
        }

        if (!$isFresh) {
            Log::info("Suppressing notification for historical order transition (Order ID: {$shopifyOrderId}).");
            return;
        }

        $type = $newStatus === 'on_hold' ? 'hold' : ($newStatus === 'available' ? 'release' : 'sold');
        $cacheKey = "notification_sent:{$product->id}:{$type}";
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            Log::info("Notification already sent for product {$product->id} type {$type}. Skipping.");
            return;
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, 300);

        $allAdmins = User::all();
        $notification = null;

        if ($newStatus === 'on_hold') {
            $reason = $product->hold_reason ?? 'Shopify order checkout';
            $adminName = auth()->user()->name ?? 'System';
            $notification = new HoldAppliedNotification($product, $reason, $adminName);
        } elseif ($newStatus === 'available') {
            $remarks = 'Status set to available';
            $adminName = auth()->user()->name ?? 'System';
            $notification = new HoldReleasedNotification($product, $remarks, $adminName);
        }

        if ($newStatus === 'sold') {
            $store = $auditStoreId ? \App\Models\ShopifyStore::find($auditStoreId) : null;
            $storeName = $store ? $store->store_name : 'Shopify Store';
            $orderNo = $product->sold_order_number ?? $shopifyOrderId ?? 'N/A';
            $soldTime = $product->sold_at ? (\Illuminate\Support\Carbon::parse($product->sold_at)->toDateTimeString()) : now()->toDateTimeString();

            $storesGroupedByUser = \App\Models\ShopifyStore::all()->groupBy('user_id');
            $mappings = $product->shopifyProducts()->with('shopifyStore')->get();

            foreach ($allAdmins as $user) {
                $isSuperAdmin = $user->role === 'super_admin';
                
                $userStores = $storesGroupedByUser->get($user->id) ?? collect();
                $userStoreIds = $userStores->pluck('id')->toArray();
                $isSellingStoreAdmin = in_array($auditStoreId, $userStoreIds);
                
                $otherAffectedStoreNames = [];
                foreach ($mappings as $m) {
                    if ($m->shopify_store_id != $auditStoreId && in_array($m->shopify_store_id, $userStoreIds)) {
                        $otherAffectedStoreNames[] = $m->shopifyStore ? $m->shopifyStore->store_name : 'their store';
                    }
                }

                if ($isSuperAdmin) {
                    $msg = $product instanceof \App\Models\Diamond 
                        ? "Diamond {$product->stock_no} sold successfully on {$storeName}."
                        : "Jewelry {$product->sku} sold successfully on {$storeName}.";
                    $notification = $product instanceof \App\Models\Diamond
                        ? new DiamondSoldNotification($product, $storeName, $msg, 'Diamond Sold')
                        : new JewelrySoldNotification($product, $storeName, $msg, 'Jewelry Sold');
                    
                    $user->notify($notification);
                    
                    foreach ($mappings as $m) {
                        if ($m->shopify_store_id != $auditStoreId) {
                            $affectedStoreName = $m->shopifyStore ? $m->shopifyStore->store_name : 'other store';
                            $draftMsg = $product instanceof \App\Models\Diamond
                                ? "Diamond {$product->stock_no} sold on {$storeName} (Order #{$orderNo}) at {$soldTime} and was automatically unpublished from {$affectedStoreName}."
                                : "Jewelry {$product->sku} sold on {$storeName} (Order #{$orderNo}) at {$soldTime} and was automatically unpublished from {$affectedStoreName}.";
                            $draftNotification = $product instanceof \App\Models\Diamond
                                ? new DiamondSoldNotification($product, $affectedStoreName, $draftMsg, 'Diamond Auto-Drafted')
                                : new JewelrySoldNotification($product, $affectedStoreName, $draftMsg, 'Jewelry Auto-Drafted');
                            $user->notify($draftNotification);
                        }
                    }
                } elseif ($isSellingStoreAdmin) {
                    $msg = $product instanceof \App\Models\Diamond 
                        ? "Diamond {$product->stock_no} sold successfully."
                        : "Jewelry {$product->sku} sold successfully.";
                    $notification = $product instanceof \App\Models\Diamond
                        ? new DiamondSoldNotification($product, $storeName, $msg, 'Product Sold Successfully')
                        : new JewelrySoldNotification($product, $storeName, $msg, 'Product Sold Successfully');
                    
                    $user->notify($notification);
                } elseif (!empty($otherAffectedStoreNames)) {
                    foreach ($otherAffectedStoreNames as $affectedName) {
                        $msg = $product instanceof \App\Models\Diamond
                            ? "Diamond {$product->stock_no} sold on {$storeName} (Order #{$orderNo}) at {$soldTime} and was automatically unpublished from {$affectedName}."
                            : "Jewelry {$product->sku} sold on {$storeName} (Order #{$orderNo}) at {$soldTime} and was automatically unpublished from {$affectedName}.";
                        $notification = $product instanceof \App\Models\Diamond
                            ? new DiamondSoldNotification($product, $affectedName, $msg, 'Product Auto-Drafted')
                            : new JewelrySoldNotification($product, $affectedName, $msg, 'Product Auto-Drafted');
                        
                        $user->notify($notification);
                    }
                }
            }
        } else {
            if ($notification) {
                foreach ($allAdmins as $user) {
                    $user->notify($notification);
                }
            }
        }
    }
}

