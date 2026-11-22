<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Waktu mulai Ujian Praktik/Keterampilan berwaktu seorang peserta pada satu sesi (lihat App\Support\UjianPraktik).
 * Waktu pengerjaan = started_at + durasi (jam dinding, tidak dijeda); sesudahnya ada waktu unggah
 * (lsp.praktik_waktu_unggah_menit) untuk mengunggah hasil.
 */
class StudentPraktikAttempt extends Model
{
    protected $fillable = ['student_id', 'exam_session_id', 'bagian', 'exam_id', 'started_at', 'durasi'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'durasi'     => 'integer',
            'bagian'     => 'integer',
        ];
    }

    /** Akhir waktu pengerjaan. */
    public function deadline(): Carbon
    {
        return $this->started_at->copy()->addMinutes($this->durasi);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }
}
