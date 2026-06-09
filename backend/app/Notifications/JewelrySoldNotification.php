<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class JewelrySoldNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $jewelry;
    public string $storeName;
    public ?string $customMessage;
    public ?string $customTitle;

    /**
     * Create a new notification instance.
     */
    public function __construct($jewelry, string $storeName, ?string $customMessage = null, ?string $customTitle = null)
    {
        $this->jewelry = $jewelry;
        $this->storeName = $storeName;
        $this->customMessage = $customMessage;
        $this->customTitle = $customTitle;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->customTitle ?: 'Jewelry Sold',
            'message' => $this->customMessage ?: "Jewelry {$this->jewelry->sku} sold from {$this->storeName}",
            'product_type' => 'jewelry',
            'product_id' => $this->jewelry->id,
            'sku' => $this->jewelry->sku,
            'action_url' => route('jewelery.show', $this->jewelry->id),
            'related_type' => 'jewelry',
            'related_id' => $this->jewelry->id,
        ];
    }
}
