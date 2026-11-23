<?php

namespace Tests\Feature;

use App\Models\AnswerEssay;
use App\Models\Essay;
use App\Models\ParticipantResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Kompensasi kerjakan ulang esai (mis. peserta lupa menyimpan jawaban) — php artisan ujian:beri-ulang. */
class BeriUlangUjianTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    public function test_esai_dibuka_ulang_dengan_jawaban_tersimpan_tetap(): void
    {
        $classroom = $this->makeClassroom('LEM');   // skema tanpa wajib Upload Tugas (peserta sungguhan sudah mengunggah)
        $pg   = $this->makeExam($classroom);
        $esai = $this->makeExam($classroom, 'Essay', 90);
        $session = $this->makeSession($pg, $esai);
        $student = $this->makeStudent($classroom);
        $this->enroll($student, $pg, $session);
        [$group, $grade] = $this->enroll($student, $esai, $session);

        foreach ([1 => '<p>jawaban tersimpan</p>', 2 => null] as $n => $isi) {
            $essay = Essay::forceCreate(['exam_id' => $esai->id, 'essays_code' => 'E' . Str::random(6), 'question' => "Soal {$n}", 'answer' => '-']);
            AnswerEssay::forceCreate([
                'answeressays_code' => 'answess-' . Str::ulid(), 'exam_id' => $esai->id, 'exam_session_id' => $session->id,
                'essay_id' => $essay->id, 'student_id' => $student->id, 'essay_order' => $n, 'answer_order' => '1',
                'answer' => $isi, 'score' => $isi ? 40 : null,
            ]);
        }
        $grade->forceFill(['start_time' => now()->subHours(2), 'end_time' => now()->subHour(), 'duration' => 0, 'grade' => 40])->save();
        ParticipantResult::forceCreate(['exam_session_id' => $session->id, 'student_id' => $student->id, 'nilai_esai' => 40,
            'nilai_akhir' => 50, 'keputusan' => 'TIDAK_LULUS', 'manager_verified_at' => now()]);
        $args = ['sesi' => $session->id, 'no_peserta' => $student->no_participant, '--ujian' => 'esai', '--alasan' => 'lupa simpan jawaban'];

        // Alasan wajib; dry-run tidak mengubah apa pun
        $this->artisan('ujian:beri-ulang', ['--alasan' => ''] + $args)->assertFailed();
        $this->artisan('ujian:beri-ulang', $args + ['--dry-run' => true])->assertSuccessful();
        $this->assertNotNull($grade->fresh()->end_time);

        $this->artisan('ujian:beri-ulang', $args)->expectsOutputToContain('dibuka ulang')->assertSuccessful();

        $g = $grade->fresh();
        $this->assertNull($g->start_time);
        $this->assertNull($g->end_time);
        $this->assertSame(90 * 60000, (int) $g->duration);
        $this->assertSame('<p>jawaban tersimpan</p>', AnswerEssay::whereNotNull('answer')->value('answer'));
        $this->assertSame(0, AnswerEssay::whereNotNull('score')->count());              // dinilai ulang asesor
        $r = ParticipantResult::first();
        $this->assertNull($r->nilai_esai);
        $this->assertNull($r->keputusan);
        $this->assertNull($r->manager_verified_at);
        $this->assertSame(1, DB::table('exam_retakes')->where('alasan', 'lupa simpan jawaban')->where('jawaban_tersimpan', 1)->count());

        // Peserta mulai lagi: jawaban lama tetap ada
        $this->actingAsStudent($student)->get("/student/essay-start/{$group->id}")->assertRedirect();
        $this->assertNotNull($grade->fresh()->start_time);
        $this->assertSame('<p>jawaban tersimpan</p>', AnswerEssay::whereNotNull('answer')->value('answer'));

        // Yang sudah Final ditolak
        $grade->fresh()->forceFill(['end_time' => now()])->save();
        $r->update(['is_finalized' => true]);
        $this->artisan('ujian:beri-ulang', $args)->expectsOutputToContain('sudah Final')->assertFailed();
    }
}
