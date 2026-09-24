<?php

namespace Tests\Feature\Student;

use App\Models\AnswerEssay;
use App\Models\Essay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class AnswerUploadTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Storage::fake('public');
        Storage::fake('private');

        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom, 'Essay Migas');
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        [$group] = $this->enroll($student, $exam, $session);
        Essay::forceCreate(['exam_id' => $exam->id, 'essays_code' => 'M1', 'question' => 'Kerjakan worksheet', 'answer' => '-']);

        $this->actingAsStudent($student);
        $this->get("/student/essay-migas-start/{$group->id}");

        $this->ctx = compact('exam', 'session', 'student', 'group');
    }

    private function upload(UploadedFile $file)
    {
        return $this->postJson('/student/essay-migas-answer', [
            'exam_id'         => $this->ctx['exam']->id,
            'exam_session_id' => $this->ctx['session']->id,
            'file'            => $file,
        ]);
    }

    public function test_php_file_is_rejected(): void
    {
        $this->upload(UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertEmpty(Storage::disk('public')->allFiles());
        $this->assertEmpty(Storage::disk('private')->allFiles());
    }

    public function test_pdf_is_stored_on_private_disk_and_served_via_controller(): void
    {
        $this->upload(UploadedFile::fake()->create('jawaban.pdf', 100, 'application/pdf'))
            ->assertOk()
            ->assertJsonPath('file.url', route('student.essaysmigas.download', [$this->ctx['exam']->id, $this->ctx['session']->id]));

        $path = AnswerEssay::where('student_id', $this->ctx['student']->id)->value('answer');

        $this->assertStringEndsWith('.pdf', $path);
        Storage::disk('private')->assertExists($path);
        $this->assertEmpty(Storage::disk('public')->allFiles());

        $this->get("/student/essay-migas-download/{$this->ctx['exam']->id}/{$this->ctx['session']->id}")->assertOk();
    }

    public function test_legacy_file_on_public_disk_is_still_downloadable(): void
    {
        $path = "essay_migas_answers/{$this->ctx['exam']->id}/{$this->ctx['session']->id}/{$this->ctx['student']->id}/lama.pdf";
        Storage::disk('public')->put($path, 'isi lama');
        AnswerEssay::where('student_id', $this->ctx['student']->id)->update(['answer' => $path]);

        $this->get("/student/essay-migas-download/{$this->ctx['exam']->id}/{$this->ctx['session']->id}")->assertOk();

        $this->artisan('answer-files:move-private')->assertSuccessful();

        Storage::disk('private')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }
}
