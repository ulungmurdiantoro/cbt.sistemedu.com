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
        'faktor_wawancara',
        'nilai_kelulusan',
        'bobot_ujian_tulis',
        'proporsi_pg',
    ];

    protected function casts(): array
    {
        return [
            'bobot_pg'          => 'float',
            'bobot_esai'        => 'float',
            'bobot_wawancara'   => 'float',
            'faktor_wawancara'  => 'float',
            'nilai_kelulusan'   => 'float',
            'bobot_ujian_tulis' => 'float',
            'proporsi_pg'       => 'float',
        ];
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
