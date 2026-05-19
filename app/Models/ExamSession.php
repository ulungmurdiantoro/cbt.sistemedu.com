<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_sessions_code',
        'exam_id',
        'exam_id_pg',
        'exam_id_esai',
        'has_wawancara',
        'title',
        'start_time',
        'end_time',
        'remidi_start_at',
        'remidi_end_at',
        'konteks_asesmen',
        'tempat_ujian',
        'kode_batch',
    ];

    protected function casts(): array
    {
        return [
            'has_wawancara' => 'boolean',
        ];
    }

    public function exam_groups()
    {
        return $this->hasMany(ExamGroup::class);
    }

    /** Legacy — dipertahankan agar kode lama tidak pecah */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
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
}
