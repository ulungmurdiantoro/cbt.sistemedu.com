<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'kop_path',
        'kop_logo2_path',
        'ttd_path',
        'bg_sertifikat_path',
        'sk_body',
        'nama_penandatangan',
        'jabatan_penandatangan',
        'kota',
    ];
}
