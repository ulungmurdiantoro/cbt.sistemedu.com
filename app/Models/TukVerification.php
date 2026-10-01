<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** FR.TUK.06 — Checklist Verifikasi TUK Online, satu per peserta per sesi (lihat App\Support\TukChecklist). */
class TukVerification extends Model
{
    protected $fillable = [
        'exam_session_id',
        'student_id',
        'tanggal_asesmen',
        'waktu_asesmen',
        'lokasi_peserta',
        'items',
        'kesimpulan_awal',
        'catatan_awal',
        'hasil_pemantauan',
        'uraian_pemantauan',
        'kesimpulan_akhir',
        'pengawas_id',
        'pengawas_name',
        'pengawas_signature_path',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'items'           => 'array',
            'tanggal_asesmen' => 'date:Y-m-d',
            'verified_at'     => 'datetime',
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

    public function pengawas()
    {
        return $this->belongsTo(User::class, 'pengawas_id');
    }
}
