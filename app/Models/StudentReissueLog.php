<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentReissueLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_application_id',
        'old_student_id',
        'new_student_id',
        'reason',
        'reissued_by',
    ];

    public function application()
    {
        return $this->belongsTo(AssessmentApplication::class, 'assessment_application_id');
    }

    public function oldStudent()
    {
        return $this->belongsTo(Student::class, 'old_student_id');
    }

    public function newStudent()
    {
        return $this->belongsTo(Student::class, 'new_student_id');
    }

    public function reissuedBy()
    {
        return $this->belongsTo(User::class, 'reissued_by');
    }
}
