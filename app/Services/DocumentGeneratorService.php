<?php

namespace App\Services;

use App\Models\ClassroomCompetencyUnit;
use App\Models\ParticipantResult;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;

class DocumentGeneratorService
{
    // ── Asset paths (fixed, dari resources/lsp-assets/) ────────────────
    private function asset(string $key): string
    {
        return base_path(config("lsp_documents.assets.{$key}"));
    }

    // ── Kategori nilai ──────────────────────────────────────────────────
    private function kategori(?float $nilai): string
    {
        if ($nilai === null) return '-';
        $t = config('lsp.kategori');
        if ($nilai < $t['remidial']) return 'Remidial (Re-Assessment)';
        if ($nilai < $t['cukup'])    return 'Cukup (Average)';
        if ($nilai < $t['bagus'])    return 'Bagus (Good)';
        return 'Bagus Sekali (Excellence)';
    }

    // ── Status KOMPETEN mapping ─────────────────────────────────────────
    private function statusKompeten(string $keputusan): string
    {
        return strtoupper($keputusan) === 'LULUS' ? 'KOMPETEN' : 'TIDAK KOMPETEN';
    }

    // ── heldOn English ─────────────────────────────────────────────────
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

    // ── QR SVG → temp file dengan cache ────────────────────────────────
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

    // ── Cache helper ────────────────────────────────────────────────────
    private function cachedPdf(string $path, \Closure $generate): string
    {
        $disk = Storage::disk('local');
        if ($disk->exists($path)) return $disk->get($path);
        $bytes = $generate();
        $disk->put($path, $bytes);
        return $bytes;
    }

    // ═══════════════════════════════════════════════════════════════════
    // PUBLIC API
    // ═══════════════════════════════════════════════════════════════════

    /**
     * SP — Surat Pemberitahuan (2 halaman, tanpa varian KAN).
     */
    public function generateSp(ParticipantResult $result): string
    {
        $result->loadMissing(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom']);

        $session   = $result->examSession;
        $classroom = $session?->referenceExam?->classroom;
        $student   = $result->student;
        $lsp       = config('lsp_documents.lsp');
        $sp        = config('lsp_documents.sp');

        $startDt = $session?->start_time ? Carbon::parse($session->start_time) : Carbon::now();

        $html = View::make('documents.sp', [
            'result'         => $result,
            'student'        => $student,
            'classroom'      => $classroom,
            'session'        => $session,
            'skema'          => $classroom?->title ?? '-',
            'heldOn'         => $startDt->translatedFormat('d F Y'),
            'noSp'           => $result->sp_number ?? '-',
            'tglSurat'       => now()->translatedFormat('d F Y'),
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

    /**
     * SP dengan cache (pakai sp_number sebagai key).
     */
    public function spPdf(ParticipantResult $result): string
    {
        if (!$result->sp_number) return $this->generateSp($result);
        return $this->cachedPdf(
            'documents/sp/' . md5($result->sp_number) . '.pdf',
            fn() => $this->generateSp($result)
        );
    }

    /**
     * SK — 3 halaman.
     * @param bool $kan true = dengan logo KAN di kop
     */
    public function generateSk(ParticipantResult $result, bool $kan = false): string
    {
        $result->loadMissing(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom']);

        $lsp       = config('lsp_documents.lsp');
        $skConf    = config('lsp_documents.sk');
        $session   = $result->examSession;
        $classroom = $session?->referenceExam?->classroom;
        $student   = $result->student;

        // QR
        $qrSkPath = null;
        if ($result->sk_number) {
            $verifikasiUrl = $lsp['verifikasi_url'];
            $pnds          = config('lsp_documents.penandatangan');
            $tglStr        = now()->translatedFormat('d F Y');
            $qrData        = implode("\n", [
                'Dokumen ini telah ditandatangani secara digital oleh:',
                $pnds['nama'],
                'Sebagai ' . $pnds['jabatan'],
                'LSP Edukasi Global Cendekia',
                '',
                'No: ' . $result->sk_number,
                'Tanggal: ' . $tglStr,
                '',
                'Link: ' . $verifikasiUrl . '/sk/' . $result->sk_number,
            ]);
            $tmpPath = $this->qrTempPath('sk_' . md5($result->sk_number));
            $this->writeQrFile($qrData, $tmpPath);
            $qrSkPath = 'file://' . $tmpPath;
        }

        $html = View::make('documents.sk', [
            'result'         => $result,
            'student'        => $student,
            'classroom'      => $classroom,
            'session'        => $session,
            'kan'            => $kan,
            'kategori'       => $this->kategori($result->nilai_akhir),
            'kategoriList'   => $skConf['kategori'],
            'menimbang'      => $skConf['menimbang'],
            'mengingat'      => $skConf['mengingat'],
            'penutupHal2'    => $skConf['penutup_hal2'],
            'catatanQr'      => $skConf['catatan_qr'],
            'tglDitetapkan'  => now()->translatedFormat('d F Y'),
            'lsp'            => $lsp,
            'penandatangan'  => config('lsp_documents.penandatangan'),
            'logoEdukiaPath' => $this->asset('logo_edukia'),
            'logoKanPath'    => $this->asset('logo_kan'),
            'qrSkPath'       => $qrSkPath,
        ])->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * SK dengan cache.
     */
    public function skPdf(ParticipantResult $result, bool $kan = false): string
    {
        if (!$result->sk_number) return $this->generateSk($result, $kan);
        return $this->cachedPdf(
            'documents/sk/' . md5($result->sk_number . '|' . ($kan ? 'kan' : 'nokan')) . '.pdf',
            fn() => $this->generateSk($result, $kan)
        );
    }

    /**
     * Sertifikat — 2 halaman.
     * @param bool $kan true = background hal.2 versi KAN
     */
    public function generateSertifikat(ParticipantResult $result, bool $kan = false): string
    {
        $result->loadMissing(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom']);

        $session   = $result->examSession;
        $classroom = $session?->referenceExam?->classroom;
        $student   = $result->student;
        $lsp       = config('lsp_documents.lsp');

        $startDt    = $session?->start_time ? Carbon::parse($session->start_time) : Carbon::now();
        $heldOn     = $this->heldOnEn($startDt);
        $certDate   = $result->finalized_at ? $this->heldOnEn($result->finalized_at) : $this->heldOnEn(Carbon::now());
        $validUntil = $result->valid_until  ? $this->heldOnEn($result->valid_until)  : '-';

        // Background pages
        $bgPage1Path = 'file://' . $this->asset('bg_sertif_depan');
        $bgPage2Path = $kan
            ? 'file://' . $this->asset('bg_sertif_kan')
            : 'file://' . $this->asset('bg_sertif_tanpa_kan');

        // Unit kompetensi dari DB
        $classroomId     = $classroom?->id;
        $competencyUnits = $classroomId
            ? ClassroomCompetencyUnit::where('classroom_id', $classroomId)->orderBy('order')->get()
            : collect();
        $hasUnitKomp = $competencyUnits->isNotEmpty();

        // QR
        $qrSertifPath = null;
        if ($result->sertifikat_number) {
            $pnds   = config('lsp_documents.penandatangan');
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
            'heldOn'          => $heldOn,
            'certDate'        => $certDate,
            'validUntil'      => $validUntil,
            'competencyUnits' => $competencyUnits,
            'hasUnitKomp'     => $hasUnitKomp,
            'bgPage1Path'     => $bgPage1Path,
            'bgPage2Path'     => $bgPage2Path,
            'qrSertifPath'    => $qrSertifPath,
        ])->render();

        $mpdf = $this->makeMpdf();
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * Sertifikat dengan cache.
     */
    public function sertifikatPdf(ParticipantResult $result, bool $kan = false): string
    {
        if (!$result->sertifikat_number) return $this->generateSertifikat($result, $kan);
        return $this->cachedPdf(
            'documents/sertifikat/' . md5($result->sertifikat_number . '|' . ($kan ? 'kan' : 'nokan')) . '.pdf',
            fn() => $this->generateSertifikat($result, $kan)
        );
    }
}
