<?php

namespace App\Mail;

use App\Models\ApplicationDocument;
use App\Models\AssessmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AssessmentApplication $application,
        public ApplicationDocument $document
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dokumen Permohonan Ditolak — ' . $this->application->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.application.document_rejected',
        );
    }
}
