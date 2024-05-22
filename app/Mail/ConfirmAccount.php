<?php

namespace Kami\Cocktail\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;

class ConfirmAccount extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $url;

    /**
     * Create a new message instance.
     */
    public function __construct(int $userId, string $hash)
    {
        $mailResetUrl = config('bar-assistant.mail_confirm_url');
        if ($mailResetUrl) {
            $this->url = str_replace('[id]', (string) $userId, $mailResetUrl);
            $this->url = str_replace('[hash]', $hash, $this->url);
        } else {
            $this->url = '';
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bar Assistant - Confirm Account',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.confirm-account',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
