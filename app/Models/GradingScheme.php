<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'bobot_pg',
        'bobot_esai',
        'bobot_wawancara',
        'nilai_kelulusan',
        'bobot_ujian_tulis',
        'proporsi_pg',
        'jumlah_soal_pg',
        'durasi_pg_menit',
        'jumlah_soal_esai',
        'durasi_esai_menit',
    ];

    protected function casts(): array
    {
        return [
            'bobot_pg'          => 'float',
            'bobot_esai'        => 'float',
            'bobot_wawancara'   => 'float',
            'nilai_kelulusan'   => 'float',
            'bobot_ujian_tulis' => 'float',
            'proporsi_pg'       => 'float',
            'jumlah_soal_pg'    => 'integer',
            'durasi_pg_menit'   => 'integer',
            'jumlah_soal_esai'  => 'integer',
            'durasi_esai_menit' => 'integer',
        ];
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
