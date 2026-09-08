<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsesorCv extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tempat_tanggal_lahir',
        'jenis_kelamin',
        'alamat_rumah',
        'nama_institusi',
        'alamat_institusi',
        'no_handphone',
        'photo_path',
        'pendidikan_formal',
        'pelatihan',
        'pengalaman_kerja',
        'keahlian',
        'pengalaman_profesional',
        'sertifikasi_kompetensi',
    ];

    protected function casts(): array
    {
        return [
            'pendidikan_formal'      => 'array',
            'pelatihan'              => 'array',
            'pengalaman_kerja'       => 'array',
            'keahlian'               => 'array',
            'pengalaman_profesional' => 'array',
            'sertifikasi_kompetensi' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
