<?php

namespace App\Mail;

use App\Models\ParticipantResult;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResultDistributionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ParticipantResult $result,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Hasil Asesmen Sertifikasi - ' . $this->result->examSession?->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.result-distribution', with: [
            'result'  => $this->result,
            'student' => $this->result->student,
            'session' => $this->result->examSession,
        ]);
    }

    // Notifikasi saja — SK & Sertifikat tidak dilampirkan, peserta mengunduhnya
    // sendiri lewat dashboard (lihat emails/result-distribution.blade.php).
    public function attachments(): array
    {
        return [];
    }
}
