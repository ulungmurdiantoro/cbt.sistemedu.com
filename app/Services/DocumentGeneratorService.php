<?php

namespace App\Services;

use App\Models\ClassroomCompetencyUnit;
use App\Models\GradingScheme;
use App\Models\ParticipantResult;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;

class DocumentGeneratorService
{
    // ── Asset path helper ───────────────────────────────────────────────
    private function asset(string $key): string
    {
        return base_path(config("lsp_documents.assets.{$key}"));
    }

    // ── Date formatting — English ordinal ──────────────────────────────
    // e.g. "8th May 2026"
    private function heldOnEn(Carbon $dt): string
    {
        $day    = $dt->day;
        $suffix = match (true) {
            $day % 100 >= 11 && $day % 100 <= 13 => 'th',
            $day % 10 === 1 => 'st',
            $day % 10 === 2 => 'nd',
            $day % 10 === 3 => 'rd',
            default         => 'th',
        };
        return $day . $suffix . ' ' . $dt->format('F Y');
    }

    // ── Date formatting — Indonesian ───────────────────────────────────
    // e.g. "Jumat, 08 Mei 2026"
    private function heldOnId(Carbon $dt): string
    {
        return $dt->locale('id')->isoFormat('dddd, DD MMMM YYYY');
    }

    // ── Status mapping ──────────────────────────────────────────────────
    private function statusKompeten(string $keputusan): string
    {
        return strtoupper($keputusan) === 'LULUS' ? 'KOMPETEN' : 'TIDAK KOMPETEN';
    }

    // ── QR SVG temp file ────────────────────────────────────────────────
    private function qrTempPath(string $suffix): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lsp_qr_' . $suffix . '.svg';
    }

    private function writeQrFile(string $data, string $path): void
    {
        if (file_exists($path)) return;
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
        $writer   = new Writer($renderer);
        file_put_contents($path, $writer->writeString($data));
    }

    // ── mPDF factory ────────────────────────────────────────────────────
    private function makeMpdf(): Mpdf
    {
        return new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_top'    => 0,
            'margin_bottom' => 0,
            'margin_left'   => 0,
            'margin_right'  => 0,
        ]);
    }

    // ── PDF cache helper ────────────────────────────────────────────────
    private function cachedPdf(string $path, Closure $generate): string
    {
        $disk = Storage::disk('local');
        if ($disk->exists($path)) return $disk->get($path);
        $bytes = $generate();
        $disk->put($path, $bytes);
        return $bytes;
    }

    // ═══════════════════════════════════════════════════════════════════
    // SERTIFIKAT
    // ═══════════════════════════════════════════════════════════════════

    public function generateSertifikat(ParticipantResult $result): string
    {
        $result->loadMissing(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom']);

        $session   = $result->examSession;
        $classroom = $session?->referenceExam?->classroom;
        $student   = $result->student;
        $lsp       = config('lsp_documents.lsp');
        $pnds      = config('lsp_documents.penandatangan');

        $startDt    = $session?->start_time ? Carbon::parse($session->start_time) : Carbon::now();
        $heldOn     = $this->heldOnEn($startDt);
        $certDate   = $result->finalized_at ? $this->heldOnEn(Carbon::parse($result->finalized_at)) : $this->heldOnEn(Carbon::now());
        $validUntil = $result->valid_until  ? $this->heldOnEn(Carbon::parse($result->valid_until))  : '-';

        $classroomId     = $classroom?->id;
        $competencyUnits = $classroomId
            ? ClassroomCompetencyUnit::where('classroom_id', $classroomId)->orderBy('order')->get()
            : collect();

        // QR code
        $qrSertifPath = null;
        if ($result->sertifikat_number) {
            $qrData = implode("\n", [
                'This document has been digitally signed by:',
                $pnds['nama'],
                'As ' . $pnds['jabatan'],
                'LSP Edukasi Global Cendekia',
                '',
                'By Certificate No: ' . $result->sertifikat_number,
                'Date of Certificate: ' . $certDate,
                'Certificate Holder Name: ' . ($student?->name ?? '-'),
                '',
                'Verification Link:',
                $lsp['verifikasi_url'] . '/' . $result->sertifikat_number,
            ]);
            $tmpPath = $this->qrTempPath('cert_' . md5($result->sertifikat_number));
            $this->writeQrFile($qrData, $tmpPath);
            $qrSertifPath = 'file://' . $tmpPath;
        }

        $html = View::make('documents.sertifikat', [
            'result'          => $result,
            'student'         => $student,
            'classroom'       => $classroom,
            'session'         => $session,
            'competencyUnits' => $competencyUnits,
            'certNumber'      => $result->sertifikat_number,
            'heldOn'          => $heldOn,
            'certDate'        => $certDate,
            'validUntil'      => $validUntil,
            'qrSertifPath'    => $qrSertifPath,
            'logoEdukiaPath'  => $this->asset('logo_edukia'),
            'logoKanPath'     => $this->asset('logo_kan'),
            'lsp'             => $lsp,
            'penandatangan'   => $pnds,
        ])->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public function sertifikatPdf(ParticipantResult $result): string
    {
        if (!$result->sertifikat_number) return $this->generateSertifikat($result);
        return $this->cachedPdf(
            'documents/sertifikat/' . md5($result->sertifikat_number) . '.pdf',
            fn() => $this->generateSertifikat($result)
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // SK — sama layoutnya dengan sertifikat, pakai sk_number
    // ═══════════════════════════════════════════════════════════════════

    public function generateSk(ParticipantResult $result): string
    {
        $result->loadMissing(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom']);

        $session   = $result->examSession;
        $classroom = $session?->referenceExam?->classroom;
        $student   = $result->student;
        $lsp       = config('lsp_documents.lsp');
        $pnds      = config('lsp_documents.penandatangan');

        $startDt    = $session?->start_time ? Carbon::parse($session->start_time) : Carbon::now();
        $heldOn     = $this->heldOnEn($startDt);
        $certDate   = $result->finalized_at ? $this->heldOnEn(Carbon::parse($result->finalized_at)) : $this->heldOnEn(Carbon::now());
        $validUntil = $result->valid_until  ? $this->heldOnEn(Carbon::parse($result->valid_until))  : '-';

        $classroomId     = $classroom?->id;
        $competencyUnits = $classroomId
            ? ClassroomCompetencyUnit::where('classroom_id', $classroomId)->orderBy('order')->get()
            : collect();

        // QR code
        $qrSkPath = null;
        if ($result->sk_number) {
            $qrData = implode("\n", [
                'Dokumen ini telah ditandatangani secara digital oleh:',
                $pnds['nama'],
                'Sebagai ' . $pnds['jabatan'],
                'LSP Edukasi Global Cendekia',
                '',
                'No: ' . $result->sk_number,
                'Tanggal: ' . $certDate,
                '',
                'Link: ' . $lsp['verifikasi_url'] . '/sk/' . $result->sk_number,
            ]);
            $tmpPath = $this->qrTempPath('sk_' . md5($result->sk_number));
            $this->writeQrFile($qrData, $tmpPath);
            $qrSkPath = 'file://' . $tmpPath;
        }

        $html = View::make('documents.sk', [
            'result'          => $result,
            'student'         => $student,
            'classroom'       => $classroom,
            'session'         => $session,
            'competencyUnits' => $competencyUnits,
            'certNumber'      => $result->sk_number,
            'heldOn'          => $heldOn,
            'certDate'        => $certDate,
            'validUntil'      => $validUntil,
            'qrSkPath'        => $qrSkPath,
            'logoEdukiaPath'  => $this->asset('logo_edukia'),
            'logoKanPath'     => $this->asset('logo_kan'),
            'lsp'             => $lsp,
            'penandatangan'   => $pnds,
        ])->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public function skPdf(ParticipantResult $result): string
    {
        if (!$result->sk_number) return $this->generateSk($result);
        return $this->cachedPdf(
            'documents/sk/' . md5($result->sk_number) . '.pdf',
            fn() => $this->generateSk($result)
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // SP — Surat Pemberitahuan (2 halaman, tanpa sidebar)
    // ═══════════════════════════════════════════════════════════════════

    public function generateSp(ParticipantResult $result): string
    {
        $result->loadMissing(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom']);

        $session   = $result->examSession;
        $classroom = $session?->referenceExam?->classroom;
        $student   = $result->student;
        $lsp       = config('lsp_documents.lsp');
        $sp        = config('lsp_documents.sp');

        $classroomId = $classroom?->id;
        $scheme      = $classroomId
            ? GradingScheme::where('classroom_id', $classroomId)->first()
            : null;

        $startDt = $session?->start_time ? Carbon::parse($session->start_time) : Carbon::now();

        $html = View::make('documents.sp', [
            'result'         => $result,
            'student'        => $student,
            'classroom'      => $classroom,
            'session'        => $session,
            'scheme'         => $scheme,
            'spNumber'       => $result->sp_number ?? '-',
            'tglSurat'       => $this->heldOnId(Carbon::now()),
            'heldOn'         => $this->heldOnId($startDt),
            'statusKompeten' => $this->statusKompeten($result->keputusan ?? ''),
            'lsp'            => $lsp,
            'sp'             => $sp,
            'penandatangan'  => config('lsp_documents.penandatangan'),
            'logoEdukiaPath' => $this->asset('logo_edukia'),
            'ttdPath'        => $this->asset('ttd'),
        ])->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public function spPdf(ParticipantResult $result): string
    {
        if (!$result->sp_number) return $this->generateSp($result);
        return $this->cachedPdf(
            'documents/sp/' . md5($result->sp_number) . '.pdf',
            fn() => $this->generateSp($result)
        );
    }
}
