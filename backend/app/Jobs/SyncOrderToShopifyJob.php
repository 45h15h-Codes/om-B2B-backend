<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ShopifyOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncOrderToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;

    /**
     * Create a new job instance.
     *
     * @param int $orderId
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @param \App\Services\ShopifyOrderService $connector
     * @return void
     */
    public function handle(ShopifyOrderService $connector)
    {
        // Prevent duplicate Draft Orders using database transaction + lockForUpdate
        $order = DB::transaction(function () {
            $ord = Order::where('id', $this->orderId)->lockForUpdate()->first();
            if (!$ord) {
                return null;
            }

            // Skip if already processed or has draft ID
            if (in_array($ord->status, ['synced', 'invoice_sent', 'paid', 'completed']) || $ord->shopify_draft_id) {
                return null;
            }

            // Mark syncing
            $ord->update(['status' => 'syncing']);
            return $ord;
        });

        if (!$order) {
            Log::info("SyncOrderToShopifyJob: Order ID {$this->orderId} is already synced or in progress. Skipping.");
            return;
        }

        $order->logs()->create([
            'action' => 'Shopify Sync Started',
            'message' => 'Shopify order synchronization started.',
        ]);

        try {
            $response = $connector->createShopifyDraftOrder($order);

            $shopifyDraftId = $response['draft_order']['id'] ?? null;
            $invoiceUrl = $response['draft_order']['invoice_url'] ?? null;

            if (!$shopifyDraftId) {
                throw new \Exception("Shopify response did not return a draft order ID.");
            }

            $order->update([
                'status' => 'synced',
                'shopify_draft_id' => (string) $shopifyDraftId,
                'invoice_url' => $invoiceUrl,
                'shopify_payload' => $response['draft_order'] ?? null,
                'shopify_response' => $response['draft_order'] ?? null,
                'error_message' => null,
            ]);

            $order->logs()->create([
                'action' => 'Shopify Sync Completed',
                'message' => "Order successfully synchronized to Shopify. Shopify Draft ID: {$shopifyDraftId}",
                'payload' => $response,
            ]);

        } catch (\Throwable $e) {
            $order->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $order->logs()->create([
                'action' => 'Sync Failed',
                'message' => "Shopify synchronization failed: " . $e->getMessage(),
            ]);

            Log::error("SyncOrderToShopifyJob Failed for Order ID {$this->orderId}: " . $e->getMessage());
            
            // Re-throw so Laravel's queue retries
            throw $e;
        }
    }
}
