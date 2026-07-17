<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactOwnerNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactRequest $contact) {}

    public function build(): self
    {
        return $this->subject('Новое обращение с сайта — ' . ($this->contact->category ?? 'без категории'))
            ->view('emails.contact-owner')
            ->with(['contact' => $this->contact]);
    }
}
