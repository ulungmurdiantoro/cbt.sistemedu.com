<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_sessions_code',
        'exam_id_pg',
        'exam_id_esai',
        'has_wawancara',
        'verifikasi_tuk',
        'title',
        'start_time',
        'end_time',
        'remidi_start_at',
        'remidi_end_at',
        'konteks_asesmen',
        'tempat_ujian',
        'kode_batch',
        'keputusan_number',
        'keputusan_issued_at',
        'keputusan_issued_by',
    ];

    protected function casts(): array
    {
        return [
            'has_wawancara'       => 'boolean',
            'verifikasi_tuk'      => 'boolean',
            'keputusan_issued_at' => 'datetime',
        ];
    }

    public function exam_groups()
    {
        return $this->hasMany(ExamGroup::class);
    }

    public function examPg()
    {
        return $this->belongsTo(Exam::class, 'exam_id_pg');
    }

    public function examEsai()
    {
        return $this->belongsTo(Exam::class, 'exam_id_esai');
    }

    /** Kembalikan exam pertama yang tidak null (untuk mendapat classroom_id) */
    public function getReferenceExamAttribute(): ?Exam
    {
        return $this->examPg ?? $this->examEsai;
    }

    /**
     * ID peserta yang terdaftar di sesi ini. Akun nonaktif (akun lama hasil
     * re-issue / merge duplikat) tidak ikut.
     */
    public static function activeStudentIds(int $examSessionId): \Illuminate\Support\Collection
    {
        $ids = ExamGroup::where('exam_session_id', $examSessionId)->pluck('student_id')->unique();

        return Student::whereIn('id', $ids)->where('is_active', true)->pluck('id');
    }

    public function assessmentApplications()
    {
        return $this->hasMany(AssessmentApplication::class);
    }

    public function participantResults()
    {
        return $this->hasMany(ParticipantResult::class);
    }

    public function scopeActive($query)
    {
        return $query->where('end_time', '>', now());
    }

    public function keputusanIssuer()
    {
        return $this->belongsTo(User::class, 'keputusan_issued_by');
    }
}
