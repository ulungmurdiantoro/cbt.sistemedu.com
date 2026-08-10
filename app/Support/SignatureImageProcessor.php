<?php

namespace App\Support;

class SignatureImageProcessor
{
    /**
     * Hapus latar dari gambar tanda tangan (hasil gambar di kanvas maupun
     * upload foto/scan), kembalikan PNG dengan latar transparan.
     *
     * Latar tidak selalu putih bersih — foto/scan tanda tangan fisik biasanya
     * abu-abu tidak rata (pencahayaan, tekstur kertas, noise kompresi). Karena
     * itu ambang batasnya dihitung otomatis per-gambar (metode Otsu) berdasarkan
     * distribusi terang-gelap piksel, bukan angka tetap — lalu tepi goresan
     * diberi transisi alpha halus supaya tidak bergerigi.
     */
    public static function removeBackground(string $imageData): string
    {
        $src = @imagecreatefromstring($imageData);
        if (!$src) {
            return $imageData;
        }

        $width  = imagesx($src);
        $height = imagesy($src);

        // 1) Kumpulkan histogram luminance (piksel yang sudah transparan dilewati).
        $histogram = array_fill(0, 256, 0);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $colors = imagecolorsforindex($src, imagecolorat($src, $x, $y));
                if (($colors['alpha'] ?? 0) >= 120) {
                    continue;
                }
                $lum = (int) round(0.299 * $colors['red'] + 0.587 * $colors['green'] + 0.114 * $colors['blue']);
                $histogram[$lum]++;
            }
        }
        $total = array_sum($histogram);

        if ($total === 0) {
            imagedestroy($src);
            return $imageData;
        }

        // 2) Cari ambang pemisah latar (terang) vs goresan (gelap) — metode Otsu.
        $threshold = self::otsuThreshold($histogram, $total);

        // Beri buffer supaya piksel abu-abu terang di sekitar latar ikut transparan,
        // dan lebar transisi untuk anti-alias tepi goresan.
        $cutoff     = min(252, $threshold + 25);
        $fadeRange  = 30;

        $dst = imagecreatetruecolor($width, $height);
        imagesavealpha($dst, true);
        imagealphablending($dst, false);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $colors = imagecolorsforindex($src, imagecolorat($src, $x, $y));

                if (($colors['alpha'] ?? 0) >= 120) {
                    continue; // sudah transparan
                }

                $lum = 0.299 * $colors['red'] + 0.587 * $colors['green'] + 0.114 * $colors['blue'];

                if ($lum >= $cutoff) {
                    continue; // latar — biarkan transparan
                }

                if ($lum >= $cutoff - $fadeRange) {
                    $ratio = ($cutoff - $lum) / $fadeRange; // 0 (di batas latar) .. 1 (goresan penuh)
                    $alpha = (int) round(127 * (1 - $ratio)); // 127=transparan, 0=opaque
                } else {
                    $alpha = 0; // goresan/tinta — opaque penuh
                }

                $color = imagecolorallocatealpha($dst, $colors['red'], $colors['green'], $colors['blue'], $alpha);
                imagesetpixel($dst, $x, $y, $color);
            }
        }

        ob_start();
        imagepng($dst);
        $output = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $output === false ? $imageData : $output;
    }

    /**
     * Ambang Otsu: cari nilai luminance (0-255) yang memaksimalkan varians
     * antar dua kelompok (latar terang vs goresan gelap) dari histogram.
     *
     * @param array<int,int> $histogram
     */
    private static function otsuThreshold(array $histogram, int $total): int
    {
        $sumAll = 0;
        for ($i = 0; $i < 256; $i++) {
            $sumAll += $i * $histogram[$i];
        }

        $sumB = 0;
        $weightBg = 0;
        $maxVariance = -1.0;
        $threshold = 128;

        for ($i = 0; $i < 256; $i++) {
            $weightBg += $histogram[$i];
            if ($weightBg == 0) {
                continue;
            }

            $weightFg = $total - $weightBg;
            if ($weightFg == 0) {
                break;
            }

            $sumB += $i * $histogram[$i];

            $meanBg = $sumB / $weightBg;
            $meanFg = ($sumAll - $sumB) / $weightFg;

            $varianceBetween = $weightBg * $weightFg * ($meanBg - $meanFg) ** 2;

            if ($varianceBetween > $maxVariance) {
                $maxVariance = $varianceBetween;
                $threshold = $i;
            }
        }

        return $threshold;
    }
}
