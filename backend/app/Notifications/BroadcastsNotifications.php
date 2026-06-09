<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;

trait BroadcastsNotifications
{
    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        // Deliver both to DB and to Websockets broadcast channel
        return ['database', 'broadcast'];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        $data = $this->toArray($notifiable);
        
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => $data['title'] ?? 'System Notification',
            'message' => $data['message'] ?? '',
            'action_url' => $data['action_url'] ?? null,
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'created_at' => now()->toIso8601String(),
        ]);
    }
}
