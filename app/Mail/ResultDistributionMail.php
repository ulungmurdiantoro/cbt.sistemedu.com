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

class ResultDistributionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ParticipantResult $result,
        public readonly string $versi = 'with_kop',
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

    public function attachments(): array
    {
        $generator   = app(DocumentGeneratorService::class);
        $result      = $this->result;
        $attachments = [];

        $skPdf = $generator->skPdf($result, $this->versi);
        $attachments[] = Attachment::fromData(fn() => $skPdf, 'SK_' . $result->student?->no_participant . '.pdf')
            ->withMime('application/pdf');

        if ($result->keputusan === 'LULUS' && $result->sertifikat_number) {
            $sertPdf = $generator->sertifikatPdf($result, $this->versi);
            $attachments[] = Attachment::fromData(fn() => $sertPdf, 'Sertifikat_' . $result->student?->no_participant . '.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
