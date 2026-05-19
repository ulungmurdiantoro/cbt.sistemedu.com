<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsesorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exam_session_id',
        'student_id',
    ];

    public function asesor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
