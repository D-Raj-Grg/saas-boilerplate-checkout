<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public Invitation $invitation;

    public string $acceptUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Invitation $invitation, string $acceptUrl)
    {
        $this->invitation = $invitation;
        $this->acceptUrl = $acceptUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $organizationName = $this->invitation->organization->name ?? 'an organization';

        return new Envelope(
            subject: "You're invited to join {$organizationName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => $this->acceptUrl,
                'organization' => $this->invitation->organization,
                'inviter' => $this->invitation->inviter,
            ],
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
