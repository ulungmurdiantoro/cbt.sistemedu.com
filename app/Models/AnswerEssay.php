<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerEssay extends Model
{
    use HasFactory;

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
