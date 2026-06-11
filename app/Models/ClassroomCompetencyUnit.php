<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassroomCompetencyUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'kode_unit',
        'kode_unit_asli',
        'judul_unit',
        'judul_unit_en',
        'order',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
