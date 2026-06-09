<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HoldReleasedNotification extends Notification implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Queueable;

    public $product;
    public string $remarks;
    public string $adminName;

    /**
     * Create a new notification instance.
     */
    public function __construct($product, string $remarks, string $adminName)
    {
        $this->product = $product;
        $this->remarks = $remarks;
        $this->adminName = $adminName;
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
        $sku = $this->product->sku ?? $this->product->stock_no ?? 'N/A';
        $type = $this->product instanceof \App\Models\Diamond ? 'Diamond' : 'Jewelry';
        $route = $type === 'Diamond' ? route('diamonds.show', $this->product->id) : route('jewelery.show', $this->product->id);
        return [
            'title' => 'Hold Released',
            'message' => "{$type} {$sku} hold has been released by {$this->adminName}." . ($this->remarks ? " (Remarks: {$this->remarks})" : ""),
            'product_type' => strtolower($type),
            'product_id' => $this->product->id,
            'action_url' => $route,
            'related_type' => strtolower($type),
            'related_id' => $this->product->id,
        ];
    }
}
