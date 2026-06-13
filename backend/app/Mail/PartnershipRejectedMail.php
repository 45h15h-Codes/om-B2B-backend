<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartnershipRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public ?string $notes;

    /**
     * Create a new message instance.
     */
    public function __construct(string $name, ?string $notes = null)
    {
        $this->name = $name;
        $this->notes = $notes;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('OM Gems Partnership Request Update')
                    ->view('emails.partnership_rejected');
    }
}
