<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Waktu mulai Ujian Praktik berwaktu seorang peserta pada satu sesi (lihat App\Support\UjianPraktik). */
class StudentPraktikAttempt extends Model
{
    protected $fillable = ['student_id', 'exam_session_id', 'exam_id', 'started_at', 'durasi'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'durasi'     => 'integer',
        ];
    }

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
