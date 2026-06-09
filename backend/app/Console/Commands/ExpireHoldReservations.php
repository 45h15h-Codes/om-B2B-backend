<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Diamond;
use App\Models\Jewelery;
use App\Services\InventoryManager;
use Illuminate\Support\Facades\Log;

class ExpireHoldReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sys:expire-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan for and auto-release inventory reservations on hold for more than 30 minutes.';

    /**
     * Execute the console command.
     *
     * @param  \App\Services\InventoryManager  $inventoryManager
     * @return int
     */
    public function handle(InventoryManager $inventoryManager)
    {
        $this->info('Starting hold reservations expiry scan...');
        $cutoffTime = now()->subMinutes(30);

        // 1. Scan Diamonds
        $expiredDiamonds = Diamond::where('inventory_status', 'on_hold')
            ->where('hold_at', '<', $cutoffTime)
            ->get();

        $this->info('Found ' . $expiredDiamonds->count() . ' expired diamond holds.');

        foreach ($expiredDiamonds as $diamond) {
            try {
                $inventoryManager->release($diamond, null, 'Auto-expired hold (exceeded 30 minutes)', '127.0.0.1');
                
                // Audit Log
                app(\App\Services\AuditService::class)->log(
                    'auto_release_hold',
                    get_class($diamond),
                    $diamond->id,
                    ['stock_no' => $diamond->stock_no, 'reason' => 'Auto-expired (> 30 minutes)']
                );

                $this->info("Released Diamond stock #{$diamond->stock_no}");
            } catch (\Throwable $e) {
                $this->error("Failed to release Diamond ID {$diamond->id}: " . $e->getMessage());
                Log::error("Failed to auto-expire hold for Diamond ID {$diamond->id}: " . $e->getMessage());
            }
        }

        // 2. Scan Jewelry
        $expiredJewelry = Jewelery::where('inventory_status', 'on_hold')
            ->where('hold_at', '<', $cutoffTime)
            ->get();

        $this->info('Found ' . $expiredJewelry->count() . ' expired jewelry holds.');

        foreach ($expiredJewelry as $jewelry) {
            try {
                $inventoryManager->release($jewelry, null, 'Auto-expired hold (exceeded 30 minutes)', '127.0.0.1');

                // Audit Log
                app(\App\Services\AuditService::class)->log(
                    'auto_release_hold',
                    get_class($jewelry),
                    $jewelry->id,
                    ['sku' => $jewelry->sku, 'reason' => 'Auto-expired (> 30 minutes)']
                );

                $this->info("Released Jewelry SKU {$jewelry->sku}");
            } catch (\Throwable $e) {
                $this->error("Failed to release Jewelry ID {$jewelry->id}: " . $e->getMessage());
                Log::error("Failed to auto-expire hold for Jewelry ID {$jewelry->id}: " . $e->getMessage());
            }
        }

        $this->info('Hold reservations expiry scan completed.');
        return Command::SUCCESS;
    }
}
