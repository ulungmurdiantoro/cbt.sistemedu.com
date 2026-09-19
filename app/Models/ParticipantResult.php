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
        'manager_verified_at',
        'manager_verified_by',
        'is_finalized',
        'finalized_at',
        'finalized_by',
        'sk_number',
        'sp_number',
        'sertifikat_number',
        'distributed_at',
        'sp_distributed_at',
        'with_kan',
        'valid_until',
        'attempt',
        'fr_ak_14_signature_path',
        'fr_ak_14_signed_at',
        'materai_status',
        'materai_stamped_at',
        'materai_document_path',
        'materai_failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_finalized'         => 'boolean',
            'finalized_at'         => 'datetime',
            'manager_verified_at'  => 'datetime',
            'distributed_at' => 'datetime',
            'sp_distributed_at' => 'datetime',
            'with_kan'       => 'boolean',
            'valid_until'    => 'datetime',
            'nilai_pg'       => 'float',
            'nilai_esai'     => 'float',
            'nilai_wawancara'=> 'float',
            'nilai_akhir'    => 'float',
            'fr_ak_14_signed_at' => 'datetime',
            'materai_stamped_at' => 'datetime',
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

    /**
     * FR.AK.14 dianggap selesai (syarat unduh SK & Sertifikat): materai ON →
     * harus sudah 'stamped'; materai OFF → cukup peserta sudah menandatangani.
     */
    public function frAk14Completed(): bool
    {
        return config('materai.enabled')
            ? $this->materai_status === 'stamped'
            : $this->fr_ak_14_signed_at !== null;
    }

    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_verified_by');
    }
}
