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

        // Find all orders that are pending and are older than 72 hours
        $expiredOrders = Order::where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->get();

        $this->info("Found {$expiredOrders->count()} expired unpaid orders.");

        foreach ($expiredOrders as $order) {
            try {
                $reason = "Payment not received within 72 hours; order automatically cancelled.";

                if ($order->diamond_id) {
                    $diamond = $order->diamond;
                    if ($diamond) {
                        $this->info("Expiring hold on Diamond ID {$diamond->id} for Order ID {$order->id}");
                        try {
                            $lockService->releaseDiamond($diamond->id, "Payment not received within 72 hours; inventory automatically released.");
                            $reason = "Payment not received within 72 hours; inventory automatically released.";
                            $this->info("Released Diamond stock #{$diamond->stock_no} successfully.");
                        } catch (\Throwable $e) {
                            $this->error("Failed to release Diamond ID {$diamond->id}: " . $e->getMessage());
                            Log::error("Failed to release Diamond ID {$diamond->id} on order expiration: " . $e->getMessage());
                            // Fallback reasoning if the lock service itself fails but we still want to record the attempt
                            $reason = "Payment not received within 72 hours; automatic release failed: " . $e->getMessage();
                        }
                    } else {
                        Log::warning("ExpireUnpaidOrders: Order ID {$order->id} references Diamond ID {$order->diamond_id} which does not exist.");
                        $reason = "Payment not received within 72 hours; diamond not found.";
                    }
                } else {
                    $this->info("Cancelling unpaid jewelry order ID {$order->id}");
                }

                // Update order status so we don't process it again
                $order->update([
                    'status' => 'cancelled',
                    'error_message' => $reason
                ]);

                $order->logs()->create([
                    'action' => 'Hold Expired',
                    'message' => $reason,
                ]);
            } catch (\Throwable $e) {
                $this->error("Failed to expire unpaid order ID {$order->id}: " . $e->getMessage());
                Log::error("Failed to expire unpaid order ID {$order->id}: " . $e->getMessage());
            }
        }

        $this->info('Unpaid orders hold expiration scan completed.');
        return Command::SUCCESS;
    }
}
