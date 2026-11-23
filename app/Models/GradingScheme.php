<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'bobot_pg',
        'bobot_esai',
        'bobot_wawancara',
        'bobot_praktik_1',
        'bobot_praktik_2',
        'faktor_wawancara',
        'nilai_kelulusan',
        'bobot_ujian_tulis',
        'proporsi_pg',
    ];

    protected function casts(): array
    {
        return [
            'bobot_pg'          => 'float',
            'bobot_esai'        => 'float',
            'bobot_wawancara'   => 'float',
            'bobot_praktik_1'   => 'float',
            'bobot_praktik_2'   => 'float',
            'faktor_wawancara'  => 'float',
            'nilai_kelulusan'   => 'float',
            'bobot_ujian_tulis' => 'float',
            'proporsi_pg'       => 'float',
        ];
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /** Kolom komposisi yang dibekukan per sesi (exam_sessions.komposisi_nilai). */
    public const SNAPSHOT_FIELDS = [
        'bobot_pg', 'bobot_esai', 'bobot_wawancara', 'bobot_praktik_1', 'bobot_praktik_2',
        'faktor_wawancara', 'nilai_kelulusan', 'bobot_ujian_tulis', 'proporsi_pg',
    ];

    /**
     * Komposisi Nilai yang berlaku untuk sesi: salinan beku sesi itu (exam_sessions.komposisi_nilai) bila ada — mis. sesi lama
     * sebelum paket V2 mengubah komposisi skema — selain itu Komposisi Nilai skema (kelas ujian PG/Esai/Praktik sesi).
     * Semua perhitungan & tampilan nilai per sesi wajib lewat sini, bukan GradingScheme::where('classroom_id', …).
     */
    public static function forSession(?ExamSession $session): ?self
    {
        $classroomId = $session?->referenceExam?->classroom_id;

        if ($session && is_array($session->komposisi_nilai) && $session->komposisi_nilai !== []) {
            $scheme = new self();
            $scheme->forceFill(['classroom_id' => $classroomId] + array_intersect_key($session->komposisi_nilai, array_flip(self::SNAPSHOT_FIELDS)));
            $scheme->frozen = true;

            return $scheme;
        }

        return $classroomId ? self::where('classroom_id', $classroomId)->first() : null;
    }

    /** Salinan komposisi untuk dibekukan di sesi. */
    public function snapshot(): array
    {
        return array_map(fn ($v) => $v === null ? null : (float) $v, $this->only(self::SNAPSHOT_FIELDS));
    }

    /** true = komposisi beku milik sesi (bukan baris grading_schemes). Tidak disimpan. */
    public bool $frozen = false;
}
