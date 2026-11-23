<?php

namespace App\Mail;

use App\Models\AssessmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Pemberitahuan No. Peserta (login ujian) berubah — dikirim no-peserta:samakan-batch --kirim-email. */
class NoPesertaChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AssessmentApplication $application,
        public string $oldNumber,
        public string $newNumber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Perubahan No. Peserta Ujian — ' . $this->application->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.application.no_peserta_changed',
        );
    }
}
