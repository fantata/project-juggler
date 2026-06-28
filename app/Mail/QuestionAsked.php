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
        // Signed links let the recipient answer in one click, no login. The
        // signature is the auth — it ties the link to this issue + answer.
        return new Content(
            markdown: 'mail.question-asked',
            with: [
                'issue' => $this->issue,
                'asker' => $this->issue->project->name,
                'yesUrl' => URL::signedRoute('questions.answer', ['issue' => $this->issue->id, 'answer' => 'yes']),
                'noUrl' => URL::signedRoute('questions.answer', ['issue' => $this->issue->id, 'answer' => 'no']),
            ],
        );
    }
}
