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
        'asesor_verified_by',
        'asesor_verified_at',
        'asesor_signature_path',
        'asesor_signature_name',
        'asesor_rekomendasi',
        'materai_status',
        'materai_order_id',
        'materai_amount',
        'materai_paid_at',
        'materai_stamped_at',
        'materai_document_path',
        'materai_failure_reason',
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
            'asesor_verified_at' => 'datetime',
            'materai_amount'     => 'float',
            'materai_paid_at'    => 'datetime',
            'materai_stamped_at' => 'datetime',
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

    public function asesorVerifier()
    {
        return $this->belongsTo(User::class, 'asesor_verified_by');
    }

    public function initialAssessment()
    {
        return $this->hasOne(InitialAssessment::class);
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

    /**
     * Bubuhkan e-meterai FR.AK.01 hanya setelah ketiga tanda tangan lengkap —
     * Asesi (signature_path), LSP/admin (admin_signature_path), dan Asesor
     * (asesor_signature_path). Dipanggil setiap salah satu dari ketiganya baru
     * diisi (savePakta, approve, finalVerify/TtdAk01), tapi hanya benar-benar
     * memicu job sekali — pada saat yang TERAKHIR dari ketiganya terpenuhi.
     */
    public function maybeTriggerAk01Stamping(): void
    {
        if ($this->materai_status !== 'none') {
            return; // sudah pernah dipicu (pending_payment/paid/stamped) atau gagal — retry ditangani terpisah
        }

        // Mode manual (MATERAI_AUTO_STAMP=false): jangan dispatch otomatis —
        // admin membubuhkan lewat tombol di halaman permohonan.
        if (!config('materai.auto_stamp', true)) {
            return;
        }

        if ($this->signature_path && $this->admin_signature_path && $this->asesor_signature_path) {
            $this->update(['materai_status' => 'pending_payment']);
            \App\Jobs\StampFrAk01Job::dispatch($this->id);
        }
    }
}
