<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\GlobalDiamondLockService;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:expire-unpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically release diamond holds for unpaid orders older than 72 hours.';

    /**
     * Execute the console command.
     */
    public function handle(GlobalDiamondLockService $lockService)
    {
        $this->info('Starting unpaid orders hold expiration scan...');
        
        $cutoff = now()->subHours(72);

        // Find orders that are pending, have a diamond, and are older than 72 hours
        $expiredOrders = Order::where('status', 'pending')
            ->whereNotNull('diamond_id')
            ->where('created_at', '<', $cutoff)
            ->get();

        $this->info("Found {$expiredOrders->count()} expired unpaid orders.");

        foreach ($expiredOrders as $order) {
            $diamond = $order->diamond;
            if ($diamond) {
                $this->info("Expiring hold on Diamond ID {$diamond->id} for Order ID {$order->id}");
                
                try {
                    $reason = "Payment not received within 72 hours; inventory automatically released.";
                    $lockService->releaseDiamond($diamond->id, $reason);

                    // Update order status so we don't process it again
                    $order->update([
                        'status' => 'cancelled',
                        'error_message' => $reason
                    ]);

                    $order->logs()->create([
                        'action' => 'Hold Expired',
                        'message' => $reason,
                    ]);

                    $this->info("Released Diamond stock #{$diamond->stock_no} successfully.");
                } catch (\Throwable $e) {
                    $this->error("Failed to release Diamond ID {$diamond->id}: " . $e->getMessage());
                    Log::error("Failed to expire unpaid order ID {$order->id}: " . $e->getMessage());
                }
            } else {
                // If the diamond was deleted or not found
                $order->update([
                    'status' => 'cancelled',
                    'error_message' => "Payment not received within 72 hours; diamond not found."
                ]);
            }
        }

        $this->info('Unpaid orders hold expiration scan completed.');
        return Command::SUCCESS;
    }
}
