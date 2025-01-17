<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'answers_code',
        'exam_id',
        'exam_session_id',
        'question_id',
        'student_id',
        'question_order',
        'answer_order',
        'answer',
        'is_correct',
    ];

    /**
     * question
     *
     * @return void
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function answers()
    {
        return $this->belongsTo(Grade::class, 'student_id', 'student_id');
    }
}
