<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'grades_code',
        'exam_id',
        'exam_session_id',
        'student_id',
        'duration',
        'start_time',
        'end_time',
        'total_correct',
        'grade',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function exam_session()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function questions()
    {
        return $this->belongsTo(Question::class, 'exam_id');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'exam_session_id');
    }

    public function answersEssay()
    {
        return $this->hasMany(AnswerEssay::class, 'exam_session_id');
    }
}
