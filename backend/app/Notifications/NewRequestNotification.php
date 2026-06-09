<?php

namespace App\Notifications;

use App\Models\InventoryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewRequestNotification extends Notification implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Queueable;

    public InventoryRequest $request;

    /**
     * Create a new notification instance.
     */
    public function __construct(InventoryRequest $request)
    {
        $this->request = $request;
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
        $user = $this->request->user ? $this->request->user->name : 'Admin';
        $isSuperAdmin = $notifiable->role === 'super_admin';
        $route = $isSuperAdmin ? route('all-requests') : route('my-requests');
        return [
            'title' => 'New Request Created',
            'message' => "New request for '{$this->request->request_type}' submitted by {$user}.",
            'request_id' => $this->request->id,
            'action_url' => $route,
            'related_type' => 'request',
            'related_id' => $this->request->id,
        ];
    }
}
