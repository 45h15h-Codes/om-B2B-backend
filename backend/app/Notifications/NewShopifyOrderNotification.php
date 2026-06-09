<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewShopifyOrderNotification extends Notification implements ShouldQueue
{
    use Queueable, BroadcastsNotifications;

    public string $orderNumber;
    public string $storeName;
    public int $orderId;
    public string $shopifyOrderId;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $orderNumber, string $storeName, int $orderId, string $shopifyOrderId)
    {
        $this->orderNumber = $orderNumber;
        $this->storeName = $storeName;
        $this->orderId = $orderId;
        $this->shopifyOrderId = $shopifyOrderId;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => 'New Shopify Order',
            'message' => "Order #{$this->orderNumber} received from {$this->storeName}",
            'order_number' => $this->orderNumber,
            'shopify_order_id' => $this->shopifyOrderId,
            'action_url' => route('orders.show', $this->orderId),
            'related_type' => 'order',
            'related_id' => $this->orderId,
        ];
    }
}
