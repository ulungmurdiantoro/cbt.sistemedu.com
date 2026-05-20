<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use App\Models\ClassroomCompetencyUnit;
use App\Models\ParticipantResult;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;

class DocumentGeneratorService
{
    private function kategori(float|null $nilai): string
    {
        if ($nilai === null) return '-';
        $t = config('lsp.kategori');
        if ($nilai < $t['remidial']) return 'Remidial (Re-Assessment)';
        if ($nilai < $t['cukup'])    return 'Cukup (Average)';
        if ($nilai < $t['bagus'])    return 'Bagus (Good)';
        return 'Bagus Sekali (Excellence)';
    }

    private function ordinalEn(int $day): string
    {
        $suffix = match(true) {
            $day % 100 >= 11 && $day % 100 <= 13 => 'th',
            $day % 10 === 1 => 'st',
            $day % 10 === 2 => 'nd',
            $day % 10 === 3 => 'rd',
            default         => 'th',
        };
        return $day . $suffix;
    }

    private function heldOnEn(Carbon $dt): string
    {
        return $this->ordinalEn($dt->day) . ' ' . $dt->format('F Y');
    }

    private function generateQrSvg(string $data, int $size = 200): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());
        $writer   = new Writer($renderer);
        return $writer->writeString($data);
    }

    private function qrTempPath(string $suffix): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lsp_qr_' . $suffix . '.svg';
    }

    private function writeQrFile(string $data, string $path): void
    {
        if (file_exists($path)) return; // cache: skip if already generated

        $svg = $this->generateQrSvg($data);
        file_put_contents($path, $svg);
    }

    /** @param 'with_kop'|'without_kop' $versi */
    public function generateSk(ParticipantResult $result, string $versi = 'with_kop'): string
    {
        $result->loadMissing(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom']);

        $template  = CertificateTemplate::first();
        $session   = $result->examSession;
        $classroom = $session?->referenceExam?->classroom;
        $student   = $result->student;
        $kategori  = $this->kategori($result->nilai_akhir);

        $tglDitetapkan = Carbon::now()->translatedFormat('d F Y');

        // QR for SK page 2
        $verifikasiUrl = config('lsp.verifikasi_url');
        $namaTtd       = $template?->nama_penandatangan    ?? config('lsp.penandatangan.nama');
        $jabatanTtd    = $template?->jabatan_penandatangan ?? config('lsp.penandatangan.jabatan');

        $qrData = implode("\n", [
            'Dokumen ini telah ditandatangani secara digital oleh:',
            $namaTtd,
            'Sebagai ' . $jabatanTtd,
            'LSP Edukasi Global Cendekia',
            '',
            'Dengan No Dokumen:',
            'No: ' . $result->sk_number,
            'Tanggal: ' . $tglDitetapkan,
            '',
            'Link: ' . $verifikasiUrl . '/sk/' . $result->sk_number,
        ]);

        $qrSkPath = null;
        if ($result->sk_number) {
            $tmpPath = $this->qrTempPath('sk_' . md5($result->sk_number));
            $this->writeQrFile($qrData, $tmpPath);
            $qrSkPath = 'file://' . $tmpPath;
        }

        $html = View::make('sk', [
            'result'        => $result,
            'template'      => $template,
            'withKop'       => $versi === 'with_kop',
            'student'       => $student,
            'classroom'     => $classroom,
            'session'       => $session,
            'kategori'      => $kategori,
            'tglDitetapkan' => $tglDitetapkan,
            'qrSkPath'      => $qrSkPath,
            'menimbang'     => config('lsp.sk_menimbang'),
            'mengingat'     => config('lsp.sk_mengingat'),
        ])->render();

        $mpdf = new Mpdf([
            'mode'         => 'utf-8',
            'format'       => 'A4',
            'orientation'  => 'P',
            'margin_top'   => 0,
            'margin_bottom'=> 0,
            'margin_left'  => 0,
            'margin_right' => 0,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /** @param 'with_kop'|'without_kop' $versi */
    public function generateSertifikat(ParticipantResult $result, string $versi = 'with_kop'): string
    {
        $result->loadMissing(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom']);

        $template  = CertificateTemplate::first();
        $student   = $result->student;
        $session   = $result->examSession;
        $classroom = $session?->referenceExam?->classroom;

        $startDt   = $session?->start_time ? Carbon::parse($session->start_time) : Carbon::now();
        $heldOn    = $this->heldOnEn($startDt);

        $certDate   = $result->finalized_at
            ? $this->heldOnEn($result->finalized_at)
            : $this->heldOnEn(Carbon::now());

        $validUntil = $result->valid_until
            ? $this->heldOnEn($result->valid_until)
            : '-';

        // Competency units
        $classroomId     = $classroom?->id;
        $competencyUnits = $classroomId
            ? ClassroomCompetencyUnit::where('classroom_id', $classroomId)->orderBy('order')->get()
            : collect();
        $hasUnitKomp = $competencyUnits->isNotEmpty();

        // Page 1 background
        $bgPage1Path = $template?->bg_sertifikat_path
            ? 'file://' . storage_path('app/public/' . $template->bg_sertifikat_path)
            : null;

        // Page 2 background: storage/app/public/templates/page2/{kode_skema}.png or fallback to page1 bg
        $kodeSkema   = $classroom?->kode_skema ?? '';
        $bgPage2Path = null;
        if ($kodeSkema) {
            $candidate = storage_path('app/public/templates/page2/' . $kodeSkema . '.png');
            if (file_exists($candidate)) {
                $bgPage2Path = 'file://' . $candidate;
            }
        }
        $bgPage2Path ??= $bgPage1Path;

        // QR for sertifikat
        $qrSertifPath = null;
        if ($result->sertifikat_number) {
            $verifikasiUrl = config('lsp.verifikasi_url');
            $namaTtd       = $template?->nama_penandatangan    ?? config('lsp.penandatangan.nama');
            $jabatanTtd    = $template?->jabatan_penandatangan ?? config('lsp.penandatangan.jabatan');

            $qrData = implode("\n", [
                'This document has been digitally signed by:',
                $namaTtd,
                'As ' . $jabatanTtd,
                'LSP Edukasi Global Cendekia',
                '',
                'By Certificate No: ' . $result->sertifikat_number,
                'Date of Certificate: ' . $certDate,
                'Certificate Holder Name: ' . $student?->name,
                '',
                'Verification Link:',
                $verifikasiUrl . '/' . $result->sertifikat_number,
            ]);

            $tmpPath = $this->qrTempPath('cert_' . md5($result->sertifikat_number));
            $this->writeQrFile($qrData, $tmpPath);
            $qrSertifPath = 'file://' . $tmpPath;
        }

        $html = View::make('sertifikat', [
            'result'          => $result,
            'template'        => $template,
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

        $mpdf = new Mpdf([
            'mode'         => 'utf-8',
            'format'       => 'A4',
            'orientation'  => 'P',
            'margin_top'   => 0,
            'margin_bottom'=> 0,
            'margin_left'  => 0,
            'margin_right' => 0,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}
