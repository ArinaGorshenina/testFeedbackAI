<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactUserConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactRequest $contact) {}

    public function build(): self
    {
        return $this->subject('Мы получили ваше обращение')
            ->view('emails.contact-user')
            ->with(['contact' => $this->contact]);
    }
}
