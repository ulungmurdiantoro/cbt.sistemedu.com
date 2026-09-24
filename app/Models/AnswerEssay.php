<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerEssay extends Model
{
    use HasFactory;

    /** Kolom hasil penilaian asesor — tidak dikirim ke halaman ujian peserta. */
    public const ASSESSMENT_COLUMNS = ['is_correct', 'score', 'assessed_by', 'assessed_at'];

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'answeressays_code',
        'exam_id',
        'exam_session_id',
        'essay_id',
        'student_id',
        'essay_order',
        'answer_order',
        'answer',
        'is_correct',
        'score',
        'assessed_by',
        'assessed_at',
    ];

    /**
     * question
     *
     * @return void
     */
    public function essay()
    {
        return $this->belongsTo(Essay::class);
    }
}
