<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'student_id',
        'asesor_id',
        'gaya_wawancara',
        'penguasaan_materi',
        'kemampuan_hadapi_pertanyaan',
        'hasil_worksheet',
        'total_nilai',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'gaya_wawancara'              => 'decimal:2',
            'penguasaan_materi'           => 'decimal:2',
            'kemampuan_hadapi_pertanyaan' => 'decimal:2',
            'hasil_worksheet'             => 'decimal:2',
            'total_nilai'                 => 'decimal:2',
        ];
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }
}
