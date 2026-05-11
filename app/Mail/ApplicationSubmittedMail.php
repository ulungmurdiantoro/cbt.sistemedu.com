<?php

namespace App\Mail;

use App\Models\AssessmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AssessmentApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Permohonan Sertifikasi Disubmit — ' . $this->application->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.application.submitted',
        );
    }
}
