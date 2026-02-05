<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'exams_code',
        'title',
        'type',
        'classroom_id',
        'duration',
        'description',
        'random_question',
        'random_answer',
        'show_answer',
    ];

    /**
     * lesson
     *
     * @return void
     */
    // public function lesson()
    // {
    //     return $this->belongsTo(Lesson::class);
    // }

    /**
     * classroom
     *
     * @return void
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * questions
     *
     * @return void
     */
    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('id', 'DESC');
    }

    /**
     * questions
     *
     * @return void
     */
    public function answers()
    {
        return $this->hasManyThrough(Answer::class, Question::class)->orderBy('question_id', 'DESC');
    }

    /**
     * essays
     *
     * @return void
     */
    public function essays()
    {
        return $this->hasMany(Essay::class)->orderBy('id', 'DESC');
    }
    
    /**
     * essays
     *
     * @return void
     */
    public function migas_essays()
    {
        return $this->hasMany(Essay::class)->orderBy('id', 'DESC');
    }

    /**
     * questions
     *
     * @return void
     */
    public function essaysanswers()
    {
        return $this->hasManyThrough(AnswerEssay::class, Essay::class)->orderBy('essay_id', 'DESC');
    }
}
