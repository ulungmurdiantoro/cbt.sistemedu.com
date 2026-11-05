<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** FR.TUK.06 — Checklist Verifikasi TUK Online, satu per peserta per sesi (lihat App\Support\TukChecklist). */
class TukVerification extends Model
{
    protected $fillable = [
        'exam_session_id',
        'student_id',
        'revision',
        'tanggal_asesmen',
        'waktu_asesmen',
        'lokasi_peserta',
        'lokasi_lainnya',
        'items',
        'kesimpulan_awal',
        'catatan_awal',
        'ketidaksesuaian',
        'kesimpulan_ulang',
        'hasil_pemantauan',
        'uraian_pemantauan',
        'tindakan_pemantauan',
        'dampak_pemantauan',
        'dampak_lainnya',
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
            'ketidaksesuaian' => 'array',
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
