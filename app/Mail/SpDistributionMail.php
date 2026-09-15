<?php

namespace App\Mail;

use App\Models\ParticipantResult;
use App\Services\DocumentGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tahap 1 distribusi hasil — kirim SP (Surat Pernyataan) saja, supaya peserta
 * bisa memeriksa & mengajukan revisi (mis. typo nama) SEBELUM SK/Sertifikat
 * resmi diterbitkan lewat ResultDistributionMail (tahap 2).
 */
class SpDistributionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ParticipantResult $result,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Surat Pernyataan Hasil Asesmen - ' . $this->result->examSession?->title);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sp-distribution', with: [
            'result'  => $this->result,
            'student' => $this->result->student,
            'session' => $this->result->examSession,
        ]);
    }

    public function attachments(): array
    {
        $spPdf = app(DocumentGeneratorService::class)->spPdf($this->result);

        return [
            Attachment::fromData(fn () => $spPdf, 'SP_' . $this->result->student?->no_participant . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
