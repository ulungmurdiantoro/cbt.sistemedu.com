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
    ];

    protected function casts(): array
    {
        return [
            'bobot_pg'         => 'float',
            'bobot_esai'       => 'float',
            'bobot_wawancara'  => 'float',
            'nilai_kelulusan'  => 'float',
        ];
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
