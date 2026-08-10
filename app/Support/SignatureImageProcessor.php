<?php

namespace App\Support;

class SignatureImageProcessor
{
    /**
     * Hapus latar putih/terang dari gambar tanda tangan (hasil gambar di kanvas
     * maupun upload foto/scan), kembalikan PNG dengan latar transparan. Piksel
     * yang sudah cukup terang dianggap latar dan dibuat transparan; sisanya
     * (goresan tinta/tanda tangan) tetap dipertahankan apa adanya.
     */
    public static function removeBackground(string $imageData): string
    {
        $src = @imagecreatefromstring($imageData);
        if (!$src) {
            return $imageData;
        }

        $width  = imagesx($src);
        $height = imagesy($src);

        $dst = imagecreatetruecolor($width, $height);
        imagesavealpha($dst, true);
        imagealphablending($dst, false);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);

        $threshold = 235; // 0-255: piksel >= ini di ketiga kanal RGB dianggap latar

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($src, $x, $y);
                $colors = imagecolorsforindex($src, $rgba);

                // Sudah transparan di sumber (alpha GD: 0=opaque, 127=transparan penuh)
                if (($colors['alpha'] ?? 0) >= 120) {
                    continue;
                }

                if ($colors['red'] >= $threshold && $colors['green'] >= $threshold && $colors['blue'] >= $threshold) {
                    continue; // biarkan transparan (default $dst)
                }

                $color = imagecolorallocatealpha($dst, $colors['red'], $colors['green'], $colors['blue'], $colors['alpha'] ?? 0);
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
}
