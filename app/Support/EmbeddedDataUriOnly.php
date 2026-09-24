<?php

namespace App\Support;

use HTMLPurifier_URIFilter;

/**
 * Filter HTMLPurifier untuk RichText: resource tertanam (img src) hanya boleh
 * data: URI — gambar yang ditempel ke editor. URL http(s) akan diambil mPDF dari
 * server (SSRF), dan URL relatif membuat browser asesor/admin mengirim GET ke
 * aplikasi ini atas nama mereka. Link biasa (<a href>) tidak terpengaruh.
 *
 * Harus class bernama (bukan anonim) karena definisi HTMLPurifier di-cache
 * lewat serialize().
 */
class EmbeddedDataUriOnly extends HTMLPurifier_URIFilter
{
    public $name = 'EmbeddedDataUriOnly';

    public function filter(&$uri, $config, $context)
    {
        return ! $context->get('EmbeddedURI', true) || $uri->scheme === 'data';
    }
}
