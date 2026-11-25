<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\AnswerEssay;
use App\Models\Essay;
use App\Models\Exam;
use App\Models\InterviewAssessment;
use App\Models\PraktikScore;
use App\Models\Question;
use App\Models\StudentTask;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Rekap Hasil → "Hasil Ujian Lengkap (ZIP)": PDF jawaban PG/Esai per peserta + unggahan praktik + rekap Excel. */
class ExamResultsArchiveTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');

        $classroom = $this->makeClassroom('CLO');
        $pg        = $this->makeExam($classroom);
        $esai      = $this->makeExam($classroom, 'Essay');
        $praktik   = $this->makeExam($classroom, Exam::TYPE_PRAKTIK, 120);
        $praktik->forceFill([
            'title'                 => 'Studi Kasus',
            'praktik_panduan'       => 'Panduan',
            'praktik_template_path' => 'praktik_templates/x.zip',
            'praktik_aspek'         => [['no' => '1', 'nama' => 'Analisis', 'bobot' => 60], ['no' => '2', 'nama' => 'Dokumen', 'bobot' => 40]],
        ])->save();

        $session = $this->makeSession($pg, $esai);
        $session->forceFill(['exam_id_praktik' => $praktik->id, 'title' => 'Sesi CLO/Batch 3'])->save();

        $student = $this->makeStudent($classroom);
        $this->enroll($student, $pg, $session);
        $this->enroll($student, $esai, $session);

        Storage::disk('public')->put('paket-soal/v2/clo/g1.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        $q = Question::forceCreate([
            'exam_id'  => $pg->id,
            'question' => '<p>Soal PG <img src="/storage/paket-soal/v2/clo/g1.png"></p>',
            'option_1' => 'Opsi satu', 'option_2' => 'Opsi dua', 'answer' => 2,
        ]);
        Answer::forceCreate([
            'answers_code' => 'A-' . Str::random(6), 'exam_id' => $pg->id, 'exam_session_id' => $session->id,
            'question_id' => $q->id, 'student_id' => $student->id, 'question_order' => 1, 'answer_order' => '1,2',
            'answer' => 2, 'is_correct' => 'Y',
        ]);

        $e = Essay::forceCreate(['exam_id' => $esai->id, 'essays_code' => 'E-' . Str::random(6), 'question' => '<p>Jelaskan K3</p>', 'answer' => '-']);
        AnswerEssay::forceCreate([
            'answeressays_code' => 'AE-' . Str::random(6), 'exam_id' => $esai->id, 'exam_session_id' => $session->id,
            'essay_id' => $e->id, 'student_id' => $student->id, 'essay_order' => 1, 'answer_order' => 1,
            'answer' => '<p>Jawaban esai peserta</p>', 'score' => 80,
        ]);

        Storage::disk('private')->put("student_tasks/{$session->id}/{$student->id}/hasil.pdf", '%PDF-1.4 tugas');
        StudentTask::forceCreate([
            'student_id' => $student->id, 'exam_session_id' => $session->id, 'bagian' => 1,
            'file_path' => "student_tasks/{$session->id}/{$student->id}/hasil.pdf",
            'original_filename' => 'Hasil Studi Kasus.pdf', 'file_size' => 14, 'uploaded_at' => now(),
        ]);
        PraktikScore::forceCreate([
            'exam_session_id' => $session->id, 'student_id' => $student->id, 'bagian' => 1,
            'score' => 76, 'aspek' => ['1' => 80, '2' => 70],
        ]);

        InterviewAssessment::forceCreate([
            'exam_session_id' => $session->id, 'student_id' => $student->id,
            'asesor_id' => User::forceCreate(['users_code' => 'ASR-' . Str::random(4), 'name' => 'Asesor Wawancara', 'email' => Str::random(8) . '@example.com', 'password' => 'x'])->id,
            'gaya_wawancara' => 80, 'penguasaan_materi' => 85, 'kemampuan_hadapi_pertanyaan' => 90,
            'total_nilai' => 85, 'catatan' => 'Menjawab runtut',
        ]);

        $admin = User::forceCreate([
            'users_code' => 'U-' . Str::random(6), 'name' => 'Admin', 'email' => Str::random(8) . '@example.com', 'password' => bcrypt('x'),
        ]);
        UserRoleAssignment::forceCreate(['user_id' => $admin->id, 'role' => UserRole::Admin->value]);

        $this->ctx = compact('session', 'student', 'admin', 'classroom');
    }

    /** @return array<string, string> entri ZIP → isi */
    private function download(string $query = ''): array
    {
        $response = $this->actingAs($this->ctx['admin'])
            ->get("/admin/results/{$this->ctx['session']->id}/hasil-ujian{$query}");
        $response->assertOk();

        $path = $response->baseResponse->getFile()->getPathname();
        $zip  = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $zip->close();
        @unlink($path);

        return $entries;
    }

    public function test_session_zip_has_answer_pdf_uploads_and_summary(): void
    {
        $no      = $this->ctx['student']->no_participant;
        $folder  = "{$no} - Peserta Test";
        $entries = $this->download();

        $this->assertArrayHasKey('Rekap Hasil Ujian.xlsx', $entries);
        $this->assertArrayHasKey("{$folder}/Hasil Ujian - {$no}.pdf", $entries);
        $this->assertStringStartsWith('%PDF', $entries["{$folder}/Hasil Ujian - {$no}.pdf"]);
        $this->assertSame('%PDF-1.4 tugas', $entries["{$folder}/Ujian Praktik/Hasil Studi Kasus.pdf"]);
    }

    public function test_single_participant_zip_and_unknown_participant_404(): void
    {
        $entries = $this->download('?peserta=' . $this->ctx['student']->id);
        $this->assertCount(3, $entries);

        $other = $this->makeStudent($this->ctx['classroom']);
        $this->actingAs($this->ctx['admin'])
            ->get("/admin/results/{$this->ctx['session']->id}/hasil-ujian?peserta={$other->id}")
            ->assertNotFound();
    }

    public function test_pdf_view_shows_pg_essay_and_praktik(): void
    {
        // Isi PDF terkompresi → tangkap data view, lalu render HTML-nya sendiri
        $data = null;
        \Illuminate\Support\Facades\View::composer('documents.hasil_ujian', function ($view) use (&$data) {
            $data ??= $view->getData();
        });

        $this->download();
        $this->assertNotNull($data);
        $html = view('documents.hasil_ujian', $data)->render();
        $this->assertStringContainsString('Jawaban esai peserta', $html);
        $this->assertStringContainsString('Benar', $html);
        $this->assertStringContainsString('Hasil Studi Kasus.pdf', $html);
        $this->assertStringContainsString('Analisis', $html);
        // wawancara: kriteria A–C (sesi berujian praktik → tanpa D Worksheet) + catatan
        $this->assertStringContainsString('Penguasaan Materi', $html);
        $this->assertStringContainsString('Menjawab runtut', $html);
        $this->assertStringContainsString('Asesor Wawancara', $html);
        $this->assertStringContainsString('rata-rata A–C', $html);
        $this->assertStringNotContainsString('Worksheet', $html);
        // gambar soal /storage/… dibaca dari file lokal, bukan alamat relatif
        $this->assertStringContainsString(Storage::disk('public')->path('paket-soal/v2/clo/g1.png'), $html);
    }
}
