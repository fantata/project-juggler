<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MorningSweep extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Collection $awaitingYou,
        public Collection $assigned,
        public ?string $summary = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your morning sweep — '.now()->format('D j M'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.morning-sweep');
    }
}
