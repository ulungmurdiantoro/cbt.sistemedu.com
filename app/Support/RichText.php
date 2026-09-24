<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Membersihkan HTML dari editor Quill peserta (jawaban esai) sebelum disimpan.
 *
 * Jawaban ini ditampilkan dengan v-html di halaman asesor & admin serta di PDF
 * laporan (mPDF), jadi script, event handler (onerror=...), iframe, javascript: URL,
 * dan gambar eksternal harus dibuang. Format dasar Quill (tebal, miring, daftar,
 * judul, kutipan, kode, rata teks lewat class ql-*, gambar tempel base64) tetap utuh.
 */
class RichText
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        // Teks polos (mis. path file Essay Migas) tidak perlu diproses.
        if (! str_contains($html, '<') && ! str_contains($html, '&')) {
            return $html;
        }

        return self::purifier()->purify($html);
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();

        $cacheDir = storage_path('framework/cache/htmlpurifier');
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $config->set('Cache.SerializerPath', $cacheDir);

        $config->set('HTML.Allowed', implode(',', [
            'p[class]', 'br', 'span[class]', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup',
            'h1[class]', 'h2[class]', 'h3[class]', 'blockquote', 'pre[class]', 'code',
            'ol[class]', 'ul[class]', 'li[class|data-list]',
            'a[href|target|rel]', 'img[src|alt|width|height]',
        ]));
        // data: hanya untuk gambar tempel (HTMLPurifier membatasi ke image/png, jpeg, gif).
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'data' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.TargetNoopener', true);

        // Definisi kustom harus diberi ID + revisi agar cache-nya tidak tertukar.
        // Naikkan revisi setiap kali definisi di bawah diubah.
        $config->set('HTML.DefinitionID', 'cbt-answer-html');
        $config->set('HTML.DefinitionRev', 1);
        $config->set('URI.DefinitionID', 'cbt-answer-uri');
        $config->set('URI.DefinitionRev', 1);

        if ($def = $config->maybeGetRawHTMLDefinition()) {
            // Quill 2 menandai jenis daftar lewat data-list (<ol><li data-list="bullet">).
            $def->addAttribute('li', 'data-list', 'Enum#bullet,ordered,checked,unchecked');
        }

        if ($uri = $config->maybeGetRawURIDefinition()) {
            $uri->addFilter(new EmbeddedDataUriOnly(), $config);
        }

        return self::$purifier = new HTMLPurifier($config);
    }
}
