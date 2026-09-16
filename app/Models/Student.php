<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    use HasFactory;

    /**
     * Kode skema (classrooms.classrooms_code) yang dikecualikan dari wajib
     * upload tugas sebelum ujian: Lifting Engineer for Medium Lifting (LEM),
     * Lifting Engineer for Heavy & Critical Lifting (LHC), 2D Lifting
     * Designer (LDT), 3D Lifting Designer (DLD).
     */
    private const TUGAS_EXEMPT_CLASSROOM_CODES = ['LEM', 'LHC', 'LDT', 'DLD'];

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

    public function tasks()
    {
        return $this->hasMany(StudentTask::class);
    }

    /**
     * Apakah peserta ini wajib mengunggah tugas sebelum mengerjakan ujian.
     */
    public function requiresTugas(): bool
    {
        return ! in_array($this->classroom?->classrooms_code, self::TUGAS_EXEMPT_CLASSROOM_CODES, true);
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