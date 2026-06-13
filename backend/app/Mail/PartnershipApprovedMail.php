<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PartnershipApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public string $email;
    public string $setupPasswordUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $name, string $email, string $setupPasswordUrl)
    {
        $this->name = $name;
        $this->email = $email;
        $this->setupPasswordUrl = $setupPasswordUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your OM Gems Partner Account Has Been Approved')
                    ->view('emails.partnership_approved');
    }
}
