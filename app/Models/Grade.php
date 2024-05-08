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

    /**
     * exam
     *
     * @return void
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * exam_session
     *
     * @return void
     */
    public function exam_session()
    {
        return $this->belongsTo(ExamSession::class);
    }

    /**
     * student
     *
     * @return void
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * exam
     *
     * @return void
     */
    public function questions()
    {
        return $this->belongsTo(Question::class, 'exam_id');
    }

    /**
     * exam
     *
     * @return void
     */
    // public function answers()
    // {
    //     return $this->hasManyThrough(Answer::class, Question::class, 
    //     'exam_id', 
    //     'question_id', 
    //     'exam_id', 
    //     'id');
    // }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'exam_session_id');
    }
}
