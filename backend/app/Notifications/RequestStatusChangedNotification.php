<?php

namespace App\Notifications;

use App\Models\InventoryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestStatusChangedNotification extends Notification implements \Illuminate\Contracts\Queue\ShouldQueue
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
        $status = $this->request->status;
        $action = $this->request->request_type;
        $route = route('my-requests');
        return [
            'title' => "Request {$status}",
            'message' => "Your request for '{$action}' was {$status}.",
            'request_id' => $this->request->id,
            'action_url' => $route,
            'related_type' => 'request',
            'related_id' => $this->request->id,
        ];
    }
}
