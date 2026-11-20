<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    /** Ujian Praktik: panduan + template yang diunduh peserta, hasilnya diunggah (lihat App\Support\UjianPraktik). */
    public const TYPE_PRAKTIK = 'Ujian Praktik';

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'exams_code',
        'title',
        'type',
        'classroom_id',
        'duration',
        'description',
        'random_question',
        'random_answer',
        'show_answer',
        'praktik_panduan',
        'praktik_rubrik',
        'praktik_aspek',
        'esai_panduan',
    ];

    protected function casts(): array
    {
        // Aspek penilaian Ujian Praktik: [{no, nama, bobot}] — lihat App\Support\NilaiPraktik::aspekScore()
        return ['praktik_aspek' => 'array'];
    }

    /**
     * Path file template di disk private & rubrik penilaian asesor — jangan dikirim ke browser
     * (rubrik dikirim eksplisit hanya ke halaman Nilai Praktik / Nilai Esai asesor).
     */
    protected $hidden = ['praktik_template_path', 'praktik_rubrik', 'praktik_aspek', 'esai_panduan'];

    /** Tag yang boleh ada di rubrik praktik (HTML dari paket soal, dirender v-html di halaman asesor). */
    public const RUBRIK_TAGS = '<p><strong><br><table><thead><tbody><tr><th><td>';

    /** Tag yang boleh ada di Panduan Penilaian Umum esai (FR.IA.06). */
    public const PANDUAN_ESAI_TAGS = '<p><strong><br><ul><ol><li>';

    public function isPraktik(): bool
    {
        return $this->type === self::TYPE_PRAKTIK;
    }

    /**
     * lesson
     *
     * @return void
     */
    // public function lesson()
    // {
    //     return $this->belongsTo(Lesson::class);
    // }

    /**
     * classroom
     *
     * @return void
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * questions
     *
     * @return void
     */
    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('id', 'DESC');
    }

    /**
     * questions
     *
     * @return void
     */
    public function answers()
    {
        return $this->hasManyThrough(Answer::class, Question::class)->orderBy('question_id', 'DESC');
    }

    /**
     * essays
     *
     * @return void
     */
    public function essays()
    {
        // Urutan resmi soal esai = urutan dibuat (id naik), sama dengan urutan yang dilihat peserta
        // saat ujian. Bank soal, detail jawaban admin, penilaian asesor, PDF & Excel memakai urutan
        // ini dan mencocokkan jawaban lewat essay_id — jangan lewat posisi/essay_order.
        return $this->hasMany(Essay::class)->orderBy('id');
    }

    /**
     * essays
     *
     * @return void
     */
    public function migas_essays()
    {
        return $this->hasMany(Essay::class)->orderBy('id');
    }

    /**
     * questions
     *
     * @return void
     */
    public function essaysanswers()
    {
        return $this->hasManyThrough(AnswerEssay::class, Essay::class)->orderBy('essay_id', 'DESC');
    }
}
