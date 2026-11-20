<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Essay extends Model
{
    use HasFactory;

    /** Kolom yang boleh dikirim ke browser peserta — tanpa kunci jawaban. */
    public const STUDENT_COLUMNS = ['id', 'exam_id', 'essays_code', 'question', 'is_essay'];

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'exam_id',
        'essays_code',
        'question',
        'answer',
        'is_essay',
        'rubrik',
    ];

    /** Matriks rubrik per soal (FR.IA.06): [{min, max, label, deskripsi}] — panduan asesor, tidak masuk STUDENT_COLUMNS. */
    protected function casts(): array
    {
        return ['rubrik' => 'array'];
    }

    /**
     * exam
     *
     * @return void
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
