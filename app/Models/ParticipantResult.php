<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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

    // Nomor resmi tidak pernah mengandung spasi — mis. "SPT/EDUKIA /IX" hasil
    // ketikan manual dirapikan saat disimpan.
    protected function skNumber(): Attribute
    {
        return Attribute::make(set: fn ($value) => self::cleanNumber($value));
    }

    protected function spNumber(): Attribute
    {
        return Attribute::make(set: fn ($value) => self::cleanNumber($value));
    }

    public static function cleanNumber(?string $value): ?string
    {
        return $value === null ? null : preg_replace('/\s+/', '', $value);
    }

    /** Baris yang sudah memegang nomor SK/SP resmi. */
    public function scopeNumbered(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNotNull('sk_number')->orWhereNotNull('sp_number'));
    }

    /**
     * Tolak penghapusan yang lewat cascade FK ikut menghapus baris bernomor
     * SK/SP resmi — nomornya hilang tanpa jejak dan penomoran jadi loncat.
     */
    public static function preventLosingNumbers(Builder $results, string $subject): void
    {
        $count = $results->numbered()->count();

        if ($count > 0) {
            throw ValidationException::withMessages([
                'delete' => "{$subject} sudah memegang {$count} nomor SK/SP resmi, jadi tidak bisa dihapus (nomornya akan ikut hilang dan penomoran loncat).",
            ]);
        }
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
     * Peserta di sesi ujian sebelum materai.first_session_id dibebaskan dari
     * materai FR.AK.14 (tetap harus TTD) — apa pun status TTD/materainya.
     */
    public function frAk14ExemptFromMaterai(): bool
    {
        $first = config('materai.first_session_id');

        return $first !== null && $this->exam_session_id < (int) $first;
    }

    /**
     * FR.AK.14 dianggap selesai (syarat unduh SK & Sertifikat): cukup sudah
     * ditandatangani kalau materai OFF atau pesertanya dibebaskan (sesi lama);
     * selain itu harus sudah 'stamped'.
     */
    public function frAk14Completed(): bool
    {
        if (!config('materai.enabled') || $this->frAk14ExemptFromMaterai()) {
            return $this->fr_ak_14_signed_at !== null;
        }

        return $this->materai_status === 'stamped';
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
