<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
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

    protected function casts(): array
    {
        return [
            // Jawaban esai (HTML dari Quill) dirender v-html di halaman asesor & admin
            // serta di PDF laporan — dibersihkan saat disimpan untuk mencegah XSS.
            'answer' => SanitizedHtml::class,
        ];
    }

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
