<?php

namespace App\Services;

use App\Models\ClassroomCompetencyUnit;
use App\Models\GradingScheme;
use App\Models\ParticipantResult;
use BaconQrCode\Common\ErrorCorrectionLevel;
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
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lsp_qr_' . $suffix . '.png';
    }

    private function writeQrFile(string $data, string $path, ?string $logoPath = null, ?ErrorCorrectionLevel $ecLevel = null): void
    {
        if (file_exists($path)) return;

        $level  = $ecLevel ?? ErrorCorrectionLevel::M();
        $qrCode = \BaconQrCode\Encoder\Encoder::encode($data, $level);
        $matrix = $qrCode->getMatrix();
        $size   = $matrix->getWidth();

        $scale  = 10;
        $quiet  = 4 * $scale;
        $dim    = $size * $scale + $quiet * 2;

        $img = imagecreatetruecolor($dim, $dim);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $img,
                        $quiet + $x * $scale,
                        $quiet + $y * $scale,
                        $quiet + ($x + 1) * $scale - 1,
                        $quiet + ($y + 1) * $scale - 1,
                        $black
                    );
                }
            }
        }

        if ($logoPath && file_exists($logoPath)) {
            [$lw, $lh] = getimagesize($logoPath);
            $logoW  = (int) round($dim * 0.50);
            $logoH  = (int) round($logoW * $lh / $lw);
            $logoX  = (int) round(($dim - $logoW) / 2);
            $logoY  = (int) round(($dim - $logoH) / 2);
            imagefilledrectangle($img, $logoX, $logoY, $logoX + $logoW, $logoY + $logoH, $white);
            $logo = imagecreatefromstring(file_get_contents($logoPath));
            if ($logo) {
                imagecopyresampled($img, $logo, $logoX, $logoY, 0, 0, $logoW, $logoH, $lw, $lh);
                imagedestroy($logo);
            }
        }

        imagepng($img, $path);
        imagedestroy($img);
    }

    // ── mPDF factory — untuk SK & SP (font default system) ─────────────
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

    // ── mPDF factory — untuk SK (Cambria, margin standar dokumen) ────
    private function makeMpdfSk(): Mpdf
    {
        return new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_top'    => 10,
            'margin_bottom' => 10,
            'margin_left'   => 17,
            'margin_right'  => 17,
            'fontDir'       => [
                base_path('vendor/mpdf/mpdf/ttfonts'),
                resource_path('fonts'),
            ],
            'fontdata'      => [
                'cambria' => [
                    'R'  => 'Cambria.ttf',
                    'B'  => 'Cambria Bold.ttf',
                    'I'  => 'Cambria Italic.ttf',
                    'BI' => 'Cambria Bold Italic.ttf',
                ],
            ],
            'default_font'  => 'cambria',
        ]);
    }

    // ── mPDF factory — untuk SP (Cambria, margin sesuai referensi dokumen) ──
    private function makeMpdfSp(): Mpdf
    {
        return new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_top'    => 10,   // HEADER_MARGIN_TOP    = 1cm
            'margin_bottom' => 35,   // CONTENT_MARGIN_BOTTOM = 3.54cm
            'margin_left'   => 17,   // HEADER_MARGIN_LEFT   = 1.72cm
            'margin_right'  => 17,   // HEADER_MARGIN_RIGHT  = 1.72cm
            'fontDir'       => [
                base_path('vendor/mpdf/mpdf/ttfonts'),
                resource_path('fonts'),
            ],
            'fontdata'      => [
                'cambria' => [
                    'R'  => 'Cambria.ttf',
                    'B'  => 'Cambria Bold.ttf',
                    'I'  => 'Cambria Italic.ttf',
                    'BI' => 'Cambria Bold Italic.ttf',
                ],
            ],
            'default_font'  => 'cambria',
        ]);
    }

    // ── mPDF factory — untuk Sertifikat (pakai font Radley) ─────────────
    private function makeMpdfSertifikat(): Mpdf
    {
        return new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_top'    => 0,
            'margin_bottom' => 0,
            'margin_left'   => 0,
            'margin_right'  => 0,
            'fontDir'       => [
                base_path('vendor/mpdf/mpdf/ttfonts'),
                resource_path('fonts'),
            ],
            'fontdata'      => [
                'radley' => [
                    'R' => 'Radley-Regular.ttf',
                    'I' => 'Radley-Italic.ttf',
                ],
            ],
            'default_font'  => 'radley',
        ]);
    }

    private function setPageBackground(Mpdf $mpdf, string $path): void
    {
        if (!file_exists($path)) return;

        $mpdf->SetDefaultBodyCSS('background', 'url("' . str_replace('\\', '/', $path) . '")');
        $mpdf->SetDefaultBodyCSS('background-image-resize', 6);
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

    public function generateSertifikat(ParticipantResult $result, bool $withKan = false): string
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
            $tmpPath = $this->qrTempPath('cert_logo3_' . md5($result->sertifikat_number));
            $this->writeQrFile($qrData, $tmpPath, $this->asset('logo_edukia'), ErrorCorrectionLevel::H());
            $qrSertifPath = 'file://' . $tmpPath;
        }

        $viewData = [
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
        ];

        $bgSertifikat = $this->asset('bg_sertifikat');
        $logoFile     = $this->asset('logo_edukia');

        [$imgW, $imgH] = getimagesize($logoFile);
        $exif = @exif_read_data($logoFile) ?: [];
        $dpi  = 96;
        if (!empty($exif['XResolution'])) {
            $parts = explode('/', (string) $exif['XResolution']);
            $dpi   = count($parts) === 2 && (float)$parts[1] > 0
                ? (float)$parts[0] / (float)$parts[1]
                : (float)$parts[0];
        }
        $qrLogoW = round($imgW * 25.4 / $dpi * 0.02, 2);
        $qrLogoH = round($imgH * 25.4 / $dpi * 0.02, 2);

        $kanFile = $this->asset('logo_kan');

        if ($withKan && file_exists($kanFile)) {
            $headerLogoY = 16;
            $hH          = 16;
            $edukiaW     = round($hH * ($imgW / $imgH), 2);
            [$kW, $kH]   = getimagesize($kanFile);
            $kanW        = round($hH * ($kW / $kH), 2);
            $edukiaX     = 31 + 8;
            $kanX        = 31 + 171 - $kanW - 8;
        } else {
            $headerLogoY = 10;
            $hH          = 22;
            $edukiaW     = round($hH * ($imgW / $imgH), 2);
            $edukiaX     = 31 + (171 - $edukiaW) / 2;
        }

        $frontHtml = View::make('documents.sertifikat', $viewData + ['renderPage' => 'front'])->render();
        $backHtml  = View::make('documents.sertifikat', $viewData + ['renderPage' => 'back'])->render();

        $mpdf = $this->makeMpdfSertifikat();

        $placeHeader = function () use ($mpdf, $withKan, $logoFile, $kanFile, $edukiaX, $edukiaW, $hH, $headerLogoY, &$kanX, &$kanW) {
            if (file_exists($logoFile)) {
                $mpdf->Image($logoFile, $edukiaX, $headerLogoY, $edukiaW, $hH);
            }
            if ($withKan && file_exists($kanFile)) {
                $mpdf->Image($kanFile, $kanX, $headerLogoY, $kanW, $hH);
            }
        };

        $placeQr = function () use ($mpdf, $qrSertifPath) {
            if (!$qrSertifPath) return;
            $qrFile = str_replace('file://', '', $qrSertifPath);
            $qrX    = 118; $qrY = 217; $qrW = 31.5; $qrH = 31.5;
            if (file_exists($qrFile)) {
                $mpdf->Image($qrFile, $qrX, $qrY, $qrW, $qrH);
            }
        };

        // ── Halaman 1 ───────────────────────────────────────────────────
        $this->setPageBackground($mpdf, $bgSertifikat);
        $mpdf->WriteHTML($frontHtml);
        $placeHeader();
        $placeQr();

        // ── Halaman 2 ───────────────────────────────────────────────────
        $this->setPageBackground($mpdf, $bgSertifikat);
        $mpdf->AddPage();
        $mpdf->WriteHTML($backHtml);
        $placeHeader();
        $placeQr();

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public function sertifikatPdf(ParticipantResult $result, bool $withKan = false): string
    {
        if (!$result->sertifikat_number) return $this->generateSertifikat($result, $withKan);

        $bgDepan    = $withKan ? $this->asset('bg_sertif_depan_kan') : $this->asset('bg_sertif_depan_tanpa_kan');
        $bgBelakang = $withKan ? $this->asset('bg_sertif_kan') : $this->asset('bg_sertif_tanpa_kan');
        $templateVersion = implode('|', [
            filemtime(resource_path('views/documents/sertifikat.blade.php')) ?: '',
            filemtime(__FILE__) ?: '',
            file_exists($bgDepan) ? filemtime($bgDepan) : '',
            file_exists($bgBelakang) ? filemtime($bgBelakang) : '',
        ]);

        $cacheKey = 'documents/sertifikat/' . md5($result->sertifikat_number . '|' . ($withKan ? 'kan' : 'tanpa') . '|' . $templateVersion) . '.pdf';

        return $this->cachedPdf(
            $cacheKey,
            fn() => $this->generateSertifikat($result, $withKan)
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    // SK — Surat Keputusan
    // ═══════════════════════════════════════════════════════════════════

    public function generateSk(ParticipantResult $result, bool $withKan = false): string
    {
        $result->loadMissing(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom']);

        $session   = $result->examSession;
        $classroom = $session?->referenceExam?->classroom;
        $student   = $result->student;
        $lsp       = config('lsp_documents.lsp');
        $pnds      = config('lsp_documents.penandatangan');
        $skConf    = config('lsp_documents.sk');

        $startDt       = $session?->start_time ? Carbon::parse($session->start_time) : Carbon::now();
        $finalizedDt   = $result->finalized_at ? Carbon::parse($result->finalized_at) : Carbon::now();

        $hariTanggalUjian = $this->heldOnId($startDt);
        $tglDitetapkan    = $finalizedDt->locale('id')->isoFormat('DD MMMM YYYY');
        $certDate         = $this->heldOnId($finalizedDt);

        $namaSkema = $classroom?->title ?? '-';
        $nilaiAkhir = (float) ($result->nilai_akhir ?? 0);
        $nilaiKelulusan = 60;

        if ($nilaiAkhir < $nilaiKelulusan)      $kategoriLabel = strip_tags($skConf['kategori'][0]['label'] ?? 'Remidial');
        elseif ($nilaiAkhir <= 70)               $kategoriLabel = strip_tags($skConf['kategori'][1]['label'] ?? 'Cukup');
        elseif ($nilaiAkhir <= 80)               $kategoriLabel = strip_tags($skConf['kategori'][2]['label'] ?? 'Bagus');
        else                                     $kategoriLabel = strip_tags($skConf['kategori'][3]['label'] ?? 'Bagus Sekali');

        $statusKompeten = strtoupper($result->keputusan ?? '') === 'LULUS' ? 'Kompeten' : 'Tidak Kompeten';

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
            $tmpPath = $this->qrTempPath('sk_logo_final_' . md5($result->sk_number));
            $this->writeQrFile($qrData, $tmpPath, $this->asset('logo_edukia'), ErrorCorrectionLevel::H());
            $qrSkPath = 'file://' . $tmpPath;
        }

        // Logo size untuk overlay QR — rumus sama dengan sertifikat
        $logoFile = $this->asset('logo_edukia');
        [$imgW, $imgH] = getimagesize($logoFile);
        $exif = @exif_read_data($logoFile) ?: [];
        $dpi  = 96;
        if (!empty($exif['XResolution'])) {
            $parts = explode('/', (string) $exif['XResolution']);
            $dpi   = count($parts) === 2 && (float)$parts[1] > 0
                ? (float)$parts[0] / (float)$parts[1]
                : (float)$parts[0];
        }
        $qrLogoW = round($imgW * 25.4 / $dpi * 0.02, 2);
        $qrLogoH = round($imgH * 25.4 / $dpi * 0.02, 2);

        $html = View::make('documents.sk', [
            'result'           => $result,
            'student'          => $student,
            'classroom'        => $classroom,
            'session'          => $session,
            'competencyUnits'  => $competencyUnits,
            'certNumber'       => $result->sk_number ?? '-',
            'hariTanggalUjian' => $hariTanggalUjian,
            'tglDitetapkan'    => $tglDitetapkan,
            'namaSkema'        => $namaSkema,
            'statusKompeten'   => $statusKompeten,
            'kategoriLabel'    => $kategoriLabel,
            'nilaiAkhir'       => $nilaiAkhir,
            'skConf'           => $skConf,
            'qrSkPath'         => $qrSkPath,
            'qrLogoW'          => $qrLogoW,
            'qrLogoH'          => $qrLogoH,
            'logoEdukiaPath'   => $logoFile,
            'logoKanPath'      => $this->asset('logo_kan'),
            'withKan'          => $withKan,
            'lsp'              => $lsp,
            'penandatangan'    => $pnds,
        ])->render();

        $mpdf = $this->makeMpdfSk();
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public function skPdf(ParticipantResult $result, bool $withKan = false): string
    {
        if (!$result->sk_number) return $this->generateSk($result, $withKan);
        $hash = substr(md5(
            md5_file(resource_path('views/documents/sk.blade.php')) .
            md5_file(__FILE__)
        ), 0, 8);
        $suffix = $withKan ? '_kan' : '';
        return $this->cachedPdf(
            'documents/sk/' . md5($result->sk_number) . '_' . $hash . $suffix . '.pdf',
            fn() => $this->generateSk($result, $withKan)
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
            'tglSurat'       => Carbon::now()->locale('id')->isoFormat('DD MMMM YYYY'),
            'heldOn'         => $this->heldOnId($startDt),
            'statusKompeten' => $this->statusKompeten($result->keputusan ?? ''),
            'lsp'            => $lsp,
            'sp'             => $sp,
            'penandatangan'  => config('lsp_documents.penandatangan'),
            'logoEdukiaPath' => $this->asset('logo_edukia'),
            'ttdPath'        => $this->asset('ttd'),
        ])->render();

        $mpdf = $this->makeMpdfSp();
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public function spPdf(ParticipantResult $result): string
    {
        if (!$result->sp_number) return $this->generateSp($result);
        $hash = substr(md5(
            md5_file(resource_path('views/documents/sp.blade.php')) .
            md5_file(__FILE__)
        ), 0, 8);
        return $this->cachedPdf(
            'documents/sp/' . md5($result->sp_number) . '_' . $hash . '.pdf',
            fn() => $this->generateSp($result)
        );
    }
}
