<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'participant_id',
        'classroom_id',
        'exam_session_id',
        'student_id',
        'exam_group_id',
        'konteks_asesmen',
        'tempat_ujian',
        'kode_batch',
        'tujuan_asesmen',
        'snapshot_pribadi',
        'snapshot_pekerjaan',
        'signature_form_path',
        'signature_path',
        'pakta_signed_at',
        'status',
        'admin_notes',
        'submitted_at',
        'approved_at',
        'approved_by',
        'admin_signature_path',
        'admin_signature_name',
    ];

    protected function casts(): array
    {
        return [
            'status'             => ApplicationStatus::class,
            'snapshot_pribadi'   => 'array',
            'snapshot_pekerjaan' => 'array',
            'submitted_at'       => 'datetime',
            'approved_at'        => 'datetime',
            'pakta_signed_at'    => 'datetime',
        ];
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function examGroup()
    {
        return $this->belongsTo(ExamGroup::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function reissueLogs()
    {
        return $this->hasMany(StudentReissueLog::class);
    }

    public function isDraft(): bool
    {
        return $this->status === ApplicationStatus::Draft;
    }

    public function isSubmitted(): bool
    {
        return $this->status === ApplicationStatus::Submitted;
    }

    public function isApproved(): bool
    {
        return $this->status === ApplicationStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === ApplicationStatus::Rejected;
    }
}
