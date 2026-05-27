<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationReceipt extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Donation $donation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for your donation',
            to: [$this->donation->donor_email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.donation-receipt',
        );
    }
}
