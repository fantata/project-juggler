<?php

namespace App\Mail;

use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Queue\SerializesModels;

class QuestionAsked extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Issue $issue) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quick yes/no: '.Str::limit($this->issue->title, 60),
        );
    }

    public function content(): Content
    {
        // Signed links let the recipient answer without logging in — the
        // signature is the auth. They land on a confirmation page (the write
        // is a POST), and expire after two weeks to limit any replay window.
        $expiry = now()->addDays(14);

        return new Content(
            markdown: 'mail.question-asked',
            with: [
                'issue' => $this->issue,
                'asker' => $this->issue->project->name,
                'yesUrl' => URL::temporarySignedRoute('questions.answer', $expiry, ['issue' => $this->issue->id, 'answer' => 'yes']),
                'noUrl' => URL::temporarySignedRoute('questions.answer', $expiry, ['issue' => $this->issue->id, 'answer' => 'no']),
            ],
        );
    }
}
