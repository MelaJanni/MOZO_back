<?php

namespace App\Mail;

use App\Models\Staff;
use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $staff;
    public $business;
    public $invitationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Staff $staff, Business $business, string $invitationUrl)
    {
        $this->staff = $staff;
        $this->business = $business;
        $this->invitationUrl = $invitationUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "¡Invitación para trabajar en {$this->business->name}!",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.staff-invitation',
            text: 'emails.staff-invitation-text',
            with: [
                'staffName' => $this->staff->name,
                'businessName' => $this->business->name,
                'position' => $this->staff->position,
                'invitationUrl' => $this->invitationUrl,
                'businessAddress' => $this->business->address,
                'businessPhone' => $this->business->phone,
                'invitationToken' => $this->staff->invitation_token,
                'expirationDate' => $this->staff->invitation_sent_at->addHours(24)->format('d/m/Y H:i'),
            ]
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