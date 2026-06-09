<?php

namespace App\Services;

use App\Models\Diamond;
use App\Models\InventoryHistory;
use App\Models\Order;
use App\Models\ShopifyProduct;
use App\Models\ShopifyInventoryReservation;
use App\Jobs\LockInventoryAcrossStoresJob;
use App\Jobs\ReleaseInventoryAcrossStoresJob;
use App\Jobs\DeleteProductFromStoreJob;
use App\Models\User;
use App\Notifications\HoldAppliedNotification;
use App\Notifications\HoldReleasedNotification;
use App\Notifications\DiamondSoldNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GlobalDiamondLockService
{
    /**
     * Lock a diamond for an order.
     */
    public function lockDiamond(int $diamondId, int $storeId, string $shopifyOrderId, ?int $orderId = null): bool
    {
        return DB::transaction(function () use ($diamondId, $storeId, $shopifyOrderId, $orderId) {
            $diamond = Diamond::lockForUpdate()->find($diamondId);
            
            if (!$diamond || $diamond->inventory_status !== 'available') {
                Log::warning("lockDiamond: Diamond ID {$diamondId} is not available. Current status: " . ($diamond ? $diamond->inventory_status : 'not found'));
                return false;
            }

            $oldStatus = $diamond->inventory_status;
            $newStatus = 'on_hold';

            $diamond->update([
                'inventory_status' => $newStatus,
                'hold_reason' => 'Shopify Order',
                'hold_at' => now(),
                'hold_shopify_store_id' => $storeId,
            ]);

            // Create InventoryHistory log
            InventoryHistory::create([
                'product_type' => 'diamond',
                'product_id' => $diamondId,
                'action' => 'available -> on_hold',
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'remarks' => "Locked for Shopify Order #{$shopifyOrderId} from store ID {$storeId}. Local Order ID: {$orderId}.",
            ]);

            // Log reservation
            ShopifyInventoryReservation::create([
                'product_type' => 'diamond',
                'product_id' => $diamondId,
                'shopify_store_id' => $storeId,
                'origin_store_id' => $storeId,
                'order_id' => $orderId,
                'shopify_order_id' => $shopifyOrderId,
                'status' => 'hold',
            ]);

            // Dispatch cross-store lock job
            LockInventoryAcrossStoresJob::dispatch('diamond', $diamondId, $storeId, $shopifyOrderId);

            // Dispatch database notifications
            $this->dispatchNotifications($diamond, $newStatus, $storeId, $shopifyOrderId);

            // Propagate hold status to matching diamonds locally in database
            $matchingDiamonds = Diamond::where('id', '!=', $diamond->id)
                ->where('shape', $diamond->shape)
                ->where('size', $diamond->size)
                ->where('color', $diamond->color)
                ->where('clarity', $diamond->clarity)
                ->where('asking_price', $diamond->asking_price)
                ->where('inventory_status', 'available')
                ->get();

            foreach ($matchingDiamonds as $matching) {
                $matching->update([
                    'inventory_status' => $newStatus,
                    'hold_reason' => 'Shopify Order (Matching Hold)',
                    'hold_at' => now(),
                    'hold_shopify_store_id' => $storeId,
                ]);

                InventoryHistory::create([
                    'product_type' => 'diamond',
                    'product_id' => $matching->id,
                    'action' => 'available -> on_hold',
                    'old_value' => 'available',
                    'new_value' => $newStatus,
                    'remarks' => "Locked due to matching Shopify Order #{$shopifyOrderId} on diamond stock {$diamond->stock_no}.",
                ]);

                // Create placeholder reservation for matching diamond so release/sold hooks identify it
                ShopifyInventoryReservation::create([
                    'product_type' => 'diamond',
                    'product_id' => $matching->id,
                    'shopify_store_id' => $storeId,
                    'origin_store_id' => $storeId,
                    'order_id' => $orderId,
                    'shopify_order_id' => $shopifyOrderId,
                    'status' => 'hold',
                ]);

                // Dispatch cross-store lock job for matching diamond too to ensure all its mappings get drafted
                LockInventoryAcrossStoresJob::dispatch('diamond', $matching->id, $storeId, $shopifyOrderId);
                
                $this->dispatchNotifications($matching, $newStatus, $storeId, $shopifyOrderId);
            }

            return true;
        });
    }

    /**
     * Release a hold on a diamond.
     */
    public function releaseDiamond(int $diamondId, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($diamondId, $reason) {
            $diamond = Diamond::lockForUpdate()->find($diamondId);

            if (!$diamond) {
                return false;
            }

            if ($diamond->inventory_status === 'sold') {
                Log::info("releaseDiamond: Diamond ID {$diamondId} is already marked as sold. Release ignored.");
                InventoryHistory::create([
                    'product_type' => 'diamond',
                    'product_id' => $diamondId,
                    'action' => 'release_ignored',
                    'remarks' => "Refund/cancel webhook received after diamond was already sold. Reason: {$reason}",
                ]);
                return false;
            }

            if ($diamond->inventory_status !== 'on_hold') {
                return false;
            }

            $oldStatus = $diamond->inventory_status;
            $newStatus = 'available';
            $oldStoreId = $diamond->hold_shopify_store_id;

            $diamond->update([
                'inventory_status' => $newStatus,
                'hold_reason' => null,
                'hold_at' => null,
                'hold_shopify_store_id' => null,
            ]);

            InventoryHistory::create([
                'product_type' => 'diamond',
                'product_id' => $diamondId,
                'action' => 'on_hold -> available',
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'remarks' => $reason ?: "Inventory released from hold.",
            ]);

            // Dispatch release job
            ReleaseInventoryAcrossStoresJob::dispatch('diamond', $diamondId);

            // Dispatch database notifications
            $this->dispatchNotifications($diamond, $newStatus, $oldStoreId, null);

            // Find matching diamonds that were locked due to this hold or matching criteria
            $matchingDiamonds = Diamond::where('id', '!=', $diamond->id)
                ->where('shape', $diamond->shape)
                ->where('size', $diamond->size)
                ->where('color', $diamond->color)
                ->where('clarity', $diamond->clarity)
                ->where('asking_price', $diamond->asking_price)
                ->where('inventory_status', 'on_hold')
                ->get();

            foreach ($matchingDiamonds as $matching) {
                $matching->update([
                    'inventory_status' => $newStatus,
                    'hold_reason' => null,
                    'hold_at' => null,
                    'hold_shopify_store_id' => null,
                ]);

                InventoryHistory::create([
                    'product_type' => 'diamond',
                    'product_id' => $matching->id,
                    'action' => 'on_hold -> available',
                    'old_value' => 'on_hold',
                    'new_value' => $newStatus,
                    'remarks' => $reason ?: "Inventory released due to matching diamond release.",
                ]);

                // Dispatch release job for matching diamond too to republish its listings
                ReleaseInventoryAcrossStoresJob::dispatch('diamond', $matching->id);
                $this->dispatchNotifications($matching, $newStatus, $oldStoreId, null);
            }

            // Update reservations to released for both original and matching diamonds
            $matchingIds = $matchingDiamonds->pluck('id')->toArray();
            ShopifyInventoryReservation::where('product_type', 'diamond')
                ->whereIn('product_id', array_merge([$diamondId], $matchingIds))
                ->where('status', 'hold')
                ->update(['status' => 'released']);

            return true;
        });
    }

    /**
     * Mark a diamond as permanently sold.
     */
    public function markSold(int $diamondId, int $storeId, string $shopifyOrderId, ?int $orderId = null): bool
    {
        return DB::transaction(function () use ($diamondId, $storeId, $shopifyOrderId, $orderId) {
            $diamond = Diamond::lockForUpdate()->find($diamondId);

            if (!$diamond) {
                return false;
            }

            if ($diamond->inventory_status === 'sold') {
                // Already sold, skip to maintain idempotency
                return true;
            }

            $oldStatus = $diamond->inventory_status;
            $newStatus = 'sold';

            $diamond->update([
                'inventory_status' => $newStatus,
                'sold_store_id' => $storeId,
                'sold_at' => now(),
                'hold_reason' => null,
                'hold_at' => null,
                'hold_shopify_store_id' => null,
            ]);

            InventoryHistory::create([
                'product_type' => 'diamond',
                'product_id' => $diamondId,
                'action' => 'on_hold -> sold',
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'remarks' => "Marked sold via Shopify order #{$shopifyOrderId} on store ID {$storeId}.",
            ]);

            // Dispatch unpublish/draft jobs for all mapped Shopify products
            $shopifyProducts = ShopifyProduct::where('product_type', 'diamond')
                ->where('product_id', $diamondId)
                ->get();

            foreach ($shopifyProducts as $product) {
                if ($product->shopify_product_id && $product->shopify_store_id) {
                    DeleteProductFromStoreJob::dispatch($product->shopify_product_id, $product->shopify_store_id);
                }
            }

            // Dispatch database notifications
            $this->dispatchNotifications($diamond, $newStatus, $storeId, $shopifyOrderId);

            // Find matching diamonds that are on hold (or matching criteria)
            $matchingDiamonds = Diamond::where('id', '!=', $diamond->id)
                ->where('shape', $diamond->shape)
                ->where('size', $diamond->size)
                ->where('color', $diamond->color)
                ->where('clarity', $diamond->clarity)
                ->where('asking_price', $diamond->asking_price)
                ->where('inventory_status', '!=', 'sold')
                ->get();

            foreach ($matchingDiamonds as $matching) {
                $matching->update([
                    'inventory_status' => $newStatus,
                    'sold_store_id' => $storeId,
                    'sold_at' => now(),
                    'hold_reason' => null,
                    'hold_at' => null,
                    'hold_shopify_store_id' => null,
                ]);

                InventoryHistory::create([
                    'product_type' => 'diamond',
                    'product_id' => $matching->id,
                    'action' => 'on_hold -> sold',
                    'old_value' => $matching->inventory_status,
                    'new_value' => $newStatus,
                    'remarks' => "Marked sold due to matching diamond sale on store ID {$storeId}.",
                ]);

                // Dispatch unpublish/draft jobs for all mapped Shopify products of matching diamond
                $matchingProducts = ShopifyProduct::where('product_type', 'diamond')
                    ->where('product_id', $matching->id)
                    ->get();

                foreach ($matchingProducts as $mProduct) {
                    if ($mProduct->shopify_product_id && $mProduct->shopify_store_id) {
                        DeleteProductFromStoreJob::dispatch($mProduct->shopify_product_id, $mProduct->shopify_store_id);
                    }
                }

                $this->dispatchNotifications($matching, $newStatus, $storeId, $shopifyOrderId);
            }

            // Update reservations to completed for both original and matching diamonds
            $matchingIds = $matchingDiamonds->pluck('id')->toArray();
            ShopifyInventoryReservation::where('product_type', 'diamond')
                ->whereIn('product_id', array_merge([$diamondId], $matchingIds))
                ->update(['status' => 'completed']);

            return true;
        });
    }

    /**
     * Synchronize a diamond's published state to all assigned stores.
     */
    public function syncAllStores(int $diamondId): void
    {
        $diamond = Diamond::find($diamondId);
        if (!$diamond) {
            return;
        }

        $assignments = $diamond->storeAssignments()->where('is_published', true)->get();
        foreach ($assignments as $assignment) {
            \App\Jobs\PublishDiamondToShopifyJob::dispatch($diamondId, $assignment->shopify_store_id);
        }
    }

    /**
     * Dispatch database notifications for state changes.
     */
    protected function dispatchNotifications(Diamond $diamond, string $newStatus, ?int $storeId = null, ?string $shopifyOrderId = null)
    {
        // 1. Check if historical order (older than 15 minutes)
        $isFresh = true;
        $orderCreatedAt = \App\Services\InventoryService::$currentProcessingOrderCreatedAt;

        if (!$orderCreatedAt && $shopifyOrderId) {
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
        $cacheKey = "notification_sent:{$diamond->id}:{$type}";
        if (Cache::has($cacheKey)) {
            Log::info("Notification already sent for diamond {$diamond->id} type {$type}. Skipping.");
            return;
        }
        Cache::put($cacheKey, true, 300);

        $allAdmins = User::all();
        $notification = null;

        if ($newStatus === 'on_hold') {
            $reason = $diamond->hold_reason ?? 'Shopify order checkout';
            $adminName = auth()->user()->name ?? 'System';
            $notification = new HoldAppliedNotification($diamond, $reason, $adminName);
        } elseif ($newStatus === 'available') {
            $remarks = 'Status set to available';
            $adminName = auth()->user()->name ?? 'System';
            $notification = new HoldReleasedNotification($diamond, $remarks, $adminName);
        }

        if ($newStatus === 'sold') {
            $store = $storeId ? \App\Models\ShopifyStore::find($storeId) : null;
            $storeName = $store ? $store->store_name : 'Shopify Store';
            $orderNo = $shopifyOrderId ?? 'N/A';
            $soldTime = now()->toDateTimeString();

            $storesGroupedByUser = \App\Models\ShopifyStore::all()->groupBy('user_id');
            $mappings = $diamond->shopifyProducts()->with('shopifyStore')->get();

            foreach ($allAdmins as $user) {
                $isSuperAdmin = $user->role === 'super_admin';
                $userStores = $storesGroupedByUser->get($user->id) ?? collect();
                $userStoreIds = $userStores->pluck('id')->toArray();
                $isSellingStoreAdmin = in_array($storeId, $userStoreIds);
                
                $otherAffectedStoreNames = [];
                foreach ($mappings as $m) {
                    if ($m->shopify_store_id != $storeId && in_array($m->shopify_store_id, $userStoreIds)) {
                        $otherAffectedStoreNames[] = $m->shopifyStore ? $m->shopifyStore->store_name : 'their store';
                    }
                }

                if ($isSuperAdmin) {
                    $msg = "Diamond {$diamond->stock_no} sold successfully on {$storeName}.";
                    $notificationInstance = new DiamondSoldNotification($diamond, $storeName, $msg, 'Diamond Sold');
                    $user->notify($notificationInstance);
                    
                    foreach ($mappings as $m) {
                        if ($m->shopify_store_id != $storeId) {
                            $affectedStoreName = $m->shopifyStore ? $m->shopifyStore->store_name : 'other store';
                            $draftMsg = "Diamond {$diamond->stock_no} sold on {$storeName} (Order #{$orderNo}) at {$soldTime} and was automatically unpublished from {$affectedStoreName}.";
                            $draftNotification = new DiamondSoldNotification($diamond, $affectedStoreName, $draftMsg, 'Diamond Auto-Drafted');
                            $user->notify($draftNotification);
                        }
                    }
                } elseif ($isSellingStoreAdmin) {
                    $msg = "Diamond {$diamond->stock_no} sold successfully.";
                    $notificationInstance = new DiamondSoldNotification($diamond, $storeName, $msg, 'Product Sold Successfully');
                    $user->notify($notificationInstance);
                } elseif (!empty($otherAffectedStoreNames)) {
                    foreach ($otherAffectedStoreNames as $affectedName) {
                        $msg = "Diamond {$diamond->stock_no} sold on {$storeName} (Order #{$orderNo}) at {$soldTime} and was automatically unpublished from {$affectedName}.";
                        $notificationInstance = new DiamondSoldNotification($diamond, $affectedName, $msg, 'Product Auto-Drafted');
                        $user->notify($notificationInstance);
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
