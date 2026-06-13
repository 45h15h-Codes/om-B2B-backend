<?php

namespace App\Notifications;

use App\Models\PartnershipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewPartnershipRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public PartnershipRequest $partnershipRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(PartnershipRequest $partnershipRequest)
    {
        $this->partnershipRequest = $partnershipRequest;
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
            'title' => 'New Partnership Request',
            'message' => "New partnership request submitted by {$this->partnershipRequest->full_name} ({$this->partnershipRequest->business_name}).",
            'request_id' => $this->partnershipRequest->id,
            'action_url' => route('partnership-requests.show', $this->partnershipRequest->id),
            'related_type' => 'partnership_request',
            'related_id' => $this->partnershipRequest->id,
        ];
    }
}
