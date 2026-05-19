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
        if ($nilai < 60)  return 'Remidial (Re-Assessment)';
        if ($nilai < 70)  return 'Cukup (Average)';
        if ($nilai < 80)  return 'Bagus (Good)';
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
        $qrData = implode("\n", [
            'Dokumen ini telah ditandatangani secara digital oleh:',
            $template?->nama_penandatangan ?? 'Dr. Agung Yulianto, M.Si.',
            'Sebagai ' . ($template?->jabatan_penandatangan ?? 'Ketua LSP'),
            'LSP Edukasi Global Cendekia',
            '',
            'Dengan No Dokumen:',
            'No: ' . $result->sk_number,
            'Tanggal: ' . $tglDitetapkan,
            '',
            'Link: https://verifikasi-sertifikat.lspedukia.id/sk/' . $result->sk_number,
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

        // Page 2 background: look for storage/app/public/templates/page2/{kode_skema}.png
        $bgPage2Path = null;
        $kodeSkema   = $classroom?->kode_skema ?? '';
        if ($kodeSkema) {
            $candidate = storage_path('app/public/templates/page2/' . $kodeSkema . '.png');
            if (file_exists($candidate)) {
                $bgPage2Path = 'file://' . $candidate;
            }
        }

        // QR for sertifikat
        $qrSertifPath = null;
        if ($result->sertifikat_number) {
            $certDateStr = $certDate;
            $qrData = implode("\n", [
                'This document has been digitally signed by:',
                $template?->nama_penandatangan ?? 'Dr. Agung Yulianto, M.Si',
                'As ' . ($template?->jabatan_penandatangan ?? 'Ketua LSP'),
                'LSP Edukasi Global Cendekia',
                '',
                'By Certificate No: ' . $result->sertifikat_number,
                'Date of Certificate: ' . $certDateStr,
                'Certificate Holder Name: ' . $student?->name,
                '',
                'Verification Link:',
                'https://verifikasi-sertifikat.lspedukia.id/' . $result->sertifikat_number,
            ]);

            $tmpPath = $this->qrTempPath('cert_' . md5($result->sertifikat_number));
            $this->writeQrFile($qrData, $tmpPath);
            $qrSertifPath = 'file://' . $tmpPath;
        }

        $html = View::make('sertifikat', [
            'result'          => $result,
            'template'        => $template,
            'withKop'         => $versi === 'with_kop',
            'student'         => $student,
            'classroom'       => $classroom,
            'heldOn'          => $heldOn,
            'certDate'        => $certDate,
            'validUntil'      => $validUntil,
            'competencyUnits' => $competencyUnits,
            'hasUnitKomp'     => $hasUnitKomp,
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
