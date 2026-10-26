<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_sessions_code',
        'exam_id_pg',
        'exam_id_esai',
        'has_wawancara',
        'verifikasi_tuk',
        'title',
        'start_time',
        'end_time',
        'remidi_start_at',
        'remidi_end_at',
        'konteks_asesmen',
        'tempat_ujian',
        'kode_batch',
        'keputusan_number',
        'keputusan_issued_at',
        'keputusan_issued_by',
        'pengambil_keputusan_id',
    ];

    protected function casts(): array
    {
        return [
            'has_wawancara'       => 'boolean',
            'verifikasi_tuk'      => 'boolean',
            'keputusan_issued_at' => 'datetime',
        ];
    }

    public function exam_groups()
    {
        return $this->hasMany(ExamGroup::class);
    }

    public function examPg()
    {
        return $this->belongsTo(Exam::class, 'exam_id_pg');
    }

    public function examEsai()
    {
        return $this->belongsTo(Exam::class, 'exam_id_esai');
    }

    /** Kembalikan exam pertama yang tidak null (untuk mendapat classroom_id) */
    public function getReferenceExamAttribute(): ?Exam
    {
        return $this->examPg ?? $this->examEsai;
    }

    /**
     * ID peserta yang terdaftar di sesi ini. Akun nonaktif (akun lama hasil
     * re-issue / merge duplikat) tidak ikut.
     */
    public static function activeStudentIds(int $examSessionId): \Illuminate\Support\Collection
    {
        $ids = ExamGroup::where('exam_session_id', $examSessionId)->pluck('student_id')->unique();

        return Student::whereIn('id', $ids)->where('is_active', true)->pluck('id');
    }

    /**
     * withCount jumlah peserta = sumber yang sama dengan activeStudentIds() dan isi Rekap Hasil /
     * Tinjau Sertifikasi. Jangan hitung participant_results: barisnya baru dibuat saat halaman
     * itu dibuka dan tertinggal saat peserta dipindah/dikeluarkan dari sesi.
     * Satu peserta punya 2 baris exam_groups (PG + Esai), jadi hitung student_id unik.
     */
    public function scopeWithActiveStudentsCount($query, string $as = 'students_count')
    {
        return $query->withCount(["exam_groups as {$as}" => function ($q) {
            $q->join('students', 'students.id', '=', 'exam_groups.student_id')
                ->where('students.is_active', true)
                ->select(\Illuminate\Support\Facades\DB::raw('count(distinct exam_groups.student_id)'));
        }]);
    }

    public function assessmentApplications()
    {
        return $this->hasMany(AssessmentApplication::class);
    }

    public function participantResults()
    {
        return $this->hasMany(ParticipantResult::class);
    }

    public function scopeActive($query)
    {
        return $query->where('end_time', '>', now());
    }

    public function keputusanIssuer()
    {
        return $this->belongsTo(User::class, 'keputusan_issued_by');
    }

    /** Pengambil Keputusan yang ditunjuk admin untuk sesi ini (satu per sesi). */
    public function pengambilKeputusan()
    {
        return $this->belongsTo(User::class, 'pengambil_keputusan_id');
    }
}
