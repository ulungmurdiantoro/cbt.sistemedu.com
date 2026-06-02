<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'student_id',
        'nilai_pg',
        'nilai_esai',
        'nilai_wawancara',
        'nilai_akhir',
        'keputusan',
        'is_finalized',
        'finalized_at',
        'finalized_by',
        'sk_number',
        'sp_number',
        'sertifikat_number',
        'distributed_at',
        'valid_until',
        'attempt',
    ];

    protected function casts(): array
    {
        return [
            'is_finalized'   => 'boolean',
            'finalized_at'   => 'datetime',
            'distributed_at' => 'datetime',
            'valid_until'    => 'datetime',
            'nilai_pg'       => 'float',
            'nilai_esai'     => 'float',
            'nilai_wawancara'=> 'float',
            'nilai_akhir'    => 'float',
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

    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
