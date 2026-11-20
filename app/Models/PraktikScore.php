<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Nilai satu bagian Ujian Praktik/Keterampilan seorang peserta di satu sesi (diisi asesor) — lihat App\Support\NilaiPraktik. */
class PraktikScore extends Model
{
    protected $fillable = ['exam_session_id', 'student_id', 'bagian', 'score', 'aspek', 'rincian', 'catatan', 'asesor_id'];

    protected function casts(): array
    {
        return [
            'bagian' => 'integer',
            'score'  => 'float',
            // {no aspek: nilai 0–100} bila ujian praktik/keterampilannya dinilai per aspek
            'aspek'  => 'array',
            // {no aspek: {no butir: nilai}} untuk aspek yang dinilai per butir (mis. Lifting)
            'rincian' => 'array',
            // {no aspek: catatan asesor}
            'catatan' => 'array',
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
