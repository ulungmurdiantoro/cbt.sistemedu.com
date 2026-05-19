<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    use HasFactory;

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'participant_id',
        'no_participant',
        'classroom_id',
        'name',
        'position',
        'institution',
        'gender',
        'is_active',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function assessmentApplication()
    {
        return $this->hasOne(AssessmentApplication::class);
    }

    public function examGroups()
    {
        return $this->hasMany(ExamGroup::class);
    }

    public function participantResults()
    {
        return $this->hasMany(ParticipantResult::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'student_id', 'student_id');
    }

    public function answersEssay()
    {
        return $this->hasMany(AnswerEssay::class, 'student_id', 'student_id');
    }
}