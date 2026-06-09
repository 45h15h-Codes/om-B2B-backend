<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SyncFailedNotification extends Notification implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Queueable, BroadcastsNotifications;

    public $product;
    public string $errorMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct($product, string $errorMessage)
    {
        $this->product = $product;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $sku = $this->product->sku ?? $this->product->stock_no ?? 'N/A';
        $type = $this->product instanceof \App\Models\Diamond ? 'diamond' : 'jewelry';
        $route = $type === 'diamond' ? route('diamonds.show', $this->product->id) : route('jewelery.show', $this->product->id);
        return [
            'title' => 'Shopify Sync Failed',
            'message' => "Inventory sync failed for {$sku}. Error: {$this->errorMessage}",
            'product_type' => $type,
            'product_id' => $this->product->id,
            'action_url' => $route,
            'related_type' => $type,
            'related_id' => $this->product->id,
        ];
    }
}
