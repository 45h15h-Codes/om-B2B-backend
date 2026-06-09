<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Models\ShopifyStore;
use App\Models\ShopifyProduct;
use App\Notifications\SystemAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyCrossStoreSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sys:verify-cross-store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and verify sold/available items across Shopify stores to detect listing conflicts.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Starting cross-store sync audit...");
        $conflicts = [];

        // 1. Audit Sold Diamonds
        $soldDiamonds = Diamond::where('inventory_status', 'sold')->with('shopifyProducts')->get();
        foreach ($soldDiamonds as $diamond) {
            foreach ($diamond->shopifyProducts as $mapping) {
                // If the product mapping is not marked as deleted, or has a live Shopify variant quantity > 0
                if (!$mapping->deleted_from_shopify && $mapping->shopify_status === 'active') {
                    $conflicts[] = [
                        'type' => 'Diamond',
                        'id' => $diamond->id,
                        'stock_no' => $diamond->stock_no,
                        'store_id' => $mapping->shopify_store_id,
                        'store_name' => $mapping->shopifyStore->store_name ?? 'Unknown',
                        'issue' => "Sold product is still active on Shopify store.",
                    ];
                }
            }
        }

        // 2. Audit Sold Jewelry
        $soldJewelry = Jewelery::where('inventory_status', 'sold')->with('shopifyProducts')->get();
        foreach ($soldJewelry as $jewelry) {
            foreach ($jewelry->shopifyProducts as $mapping) {
                if (!$mapping->deleted_from_shopify && $mapping->shopify_status === 'active') {
                    $conflicts[] = [
                        'type' => 'Jewelry',
                        'id' => $jewelry->id,
                        'stock_no' => $jewelry->sku,
                        'store_id' => $mapping->shopify_store_id,
                        'store_name' => $mapping->shopifyStore->store_name ?? 'Unknown',
                        'issue' => "Sold jewelry is still active on Shopify store.",
                    ];
                }
            }
        }

        // 3. Audit Available Diamonds (should be published on assigned stores)
        $availableDiamonds = Diamond::where(function($q) {
            $q->whereNull('inventory_status')->orWhere('inventory_status', 'available');
        })->with('shopifyProducts', 'storeAssignments')->get();

        foreach ($availableDiamonds as $diamond) {
            // Check assignments
            $assignedStoreIds = $diamond->storeAssignments ?? collect();
            if ($assignedStoreIds->isEmpty()) {
                // Fallback to active store if assignments table is empty
                $activeStore = ShopifyStore::where('is_active', true)->first();
                $assignedStoreIds = $activeStore ? collect([$activeStore]) : collect();
            }

            foreach ($assignedStoreIds as $store) {
                $mapping = $diamond->shopifyProducts->where('shopify_store_id', $store->id)->first();
                if (!$mapping || $mapping->deleted_from_shopify || $mapping->shopify_status !== 'active') {
                    $conflicts[] = [
                        'type' => 'Diamond',
                        'id' => $diamond->id,
                        'stock_no' => $diamond->stock_no,
                        'store_id' => $store->id,
                        'store_name' => $store->store_name,
                        'issue' => "Available product is missing or unpublished on assigned store.",
                    ];
                }
            }
        }

        // 4. Audit Available Jewelry
        $availableJewelry = Jewelery::where(function($q) {
            $q->whereNull('inventory_status')->orWhere('inventory_status', 'available');
        })->with('shopifyProducts')->get();

        foreach ($availableJewelry as $jewelry) {
            // Jewelry is mapped directly to stores in mappings table
            foreach ($jewelry->shopifyProducts as $mapping) {
                if ($mapping->deleted_from_shopify || $mapping->shopify_status !== 'active') {
                    $conflicts[] = [
                        'type' => 'Jewelry',
                        'id' => $jewelry->id,
                        'stock_no' => $jewelry->sku,
                        'store_id' => $mapping->shopify_store_id,
                        'store_name' => $mapping->shopifyStore->store_name ?? 'Unknown',
                        'issue' => "Available jewelry is unpublished/inactive on linked store.",
                    ];
                }
            }
        }

        $this->info("Cross-store audit complete. Total conflicts found: " . count($conflicts));

        if (count($conflicts) > 0) {
            $title = "System Alert - Cross-store listing conflict detected";
            $message = "Cross-store sync verification found " . count($conflicts) . " product listing conflicts. Details logged to sys:verify-cross-store.";
            
            // Log details
            foreach ($conflicts as $conflict) {
                Log::channel('shopify')->warning("Sync Conflict: {$conflict['type']} Stock: {$conflict['stock_no']} in Store: {$conflict['store_name']} (ID: {$conflict['store_id']}). Issue: {$conflict['issue']}");
            }

            // Notify Super Admins
            $superAdmins = User::where('role', 'super_admin')->get();
            $notification = new SystemAlertNotification($title, $message);
            foreach ($superAdmins as $admin) {
                $admin->notify($notification);
            }

            $this->warn($title . " - Alerts dispatched.");
        } else {
            $this->info("No conflicts detected. Synced state is consistent.");
        }

        return Command::SUCCESS;
    }
}
