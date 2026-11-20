<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Nilai satu bagian Ujian Praktik seorang peserta di satu sesi (diisi asesor) — lihat App\Support\NilaiPraktik. */
class PraktikScore extends Model
{
    protected $fillable = ['exam_session_id', 'student_id', 'bagian', 'score', 'asesor_id'];

    protected function casts(): array
    {
        return [
            'bagian' => 'integer',
            'score'  => 'float',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }
}
