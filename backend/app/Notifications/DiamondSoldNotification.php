<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DiamondSoldNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $diamond;
    public string $storeName;
    public ?string $customMessage;
    public ?string $customTitle;

    /**
     * Create a new notification instance.
     */
    public function __construct($diamond, string $storeName, ?string $customMessage = null, ?string $customTitle = null)
    {
        $this->diamond = $diamond;
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
            'title' => $this->customTitle ?: 'Diamond Sold',
            'message' => $this->customMessage ?: "Diamond {$this->diamond->stock_no} sold from {$this->storeName}",
            'product_type' => 'diamond',
            'product_id' => $this->diamond->id,
            'stock_no' => $this->diamond->stock_no,
            'action_url' => route('diamonds.show', $this->diamond->id),
            'related_type' => 'diamond',
            'related_id' => $this->diamond->id,
        ];
    }
}
