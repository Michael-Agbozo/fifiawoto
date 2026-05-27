<?php

namespace App\Mail;

use App\Models\BeneficiaryApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BeneficiaryApplicationConverted extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public BeneficiaryApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your support request has been approved',
            to: [$this->application->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.beneficiary-application-converted',
        );
    }
}
