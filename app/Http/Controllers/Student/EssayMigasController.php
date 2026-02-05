<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AnswerEssay;
use App\Models\Essay;
use App\Models\ExamGroup;
use App\Models\Grade;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EssayMigasController extends Controller
{
    // =========================
    // Helpers
    // =========================
    private function studentId(): int
    {
        return (int) auth()->guard('student')->user()->id;
    }

    private function getExamGroupOrRedirect(int $examGroupId)
    {
        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
            ->where('student_id', $this->studentId())
            ->where('id', $examGroupId)
            ->first();

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        return $exam_group;
    }

    private function getGrade(int $examId, int $sessionId, int $studentId): ?Grade
    {
        return Grade::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->first();
    }

    // =========================
    // Pages
    // =========================
    public function confirmation($id)
    {
        $studentId = $this->studentId();

        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
            ->where('student_id', $studentId)
            ->where('id', (int) $id)
            ->first();

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        $grade = $this->getGrade((int) $exam_group->exam->id, (int) $exam_group->exam_session->id, $studentId);

        return inertia('Student/EssaysMigas/Confirmation', [
            'exam_group' => $exam_group,
            'grade'      => $grade,
        ]);
    }

    public function startEssay($id)
    {
        $studentId = $this->studentId();

        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
            ->where('student_id', $studentId)
            ->where('id', (int) $id)
            ->first();

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        $examId = (int) $exam_group->exam->id;
        $sessionId = (int) $exam_group->exam_session->id;

        $grade = $this->getGrade($examId, $sessionId, $studentId);
        if ($grade) {
            $grade->start_time = Carbon::now();
            $grade->save();
        }

        $essaysQuery = Essay::where('exam_id', $examId);
        $essays = ($exam_group->exam->random_essay === 'Y')
            ? $essaysQuery->inRandomOrder()->get()
            : $essaysQuery->get();

        $essayOrder = 1;

        foreach ($essays as $essay) {
            // karena kamu pakai “1 file”, answer_order gak terlalu penting
            $options = [1];
            if ($exam_group->exam->random_answer === 'Y') {
                shuffle($options);
            }

            $answer = AnswerEssay::where('student_id', $studentId)
                ->where('exam_id', $examId)
                ->where('exam_session_id', $sessionId)
                ->where('essay_id', $essay->id)
                ->first();

            if ($answer) {
                $answer->essay_order = $essayOrder;
                $answer->save();
            } else {
                AnswerEssay::create([
                    'answeressays_code' => 'answess-' . rand(11, 99) . uniqid(),
                    'exam_id'           => $examId,
                    'exam_session_id'   => $sessionId,
                    'essay_id'          => $essay->id,
                    'student_id'        => $studentId,
                    'essay_order'       => $essayOrder,
                    'answer_order'      => implode(",", $options),
                    'answer'            => null,
                    'is_correct'        => 'N',
                ]);
            }

            $essayOrder++;
        }

        return redirect()->route('student.essaysmigas.show', [
            'id'   => (int) $exam_group->id,
            'page' => 1,
        ]);
    }

    public function show($id, $page)
    {
        $studentId = $this->studentId();

        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
            ->where('student_id', $studentId)
            ->where('id', (int) $id)
            ->first();

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        $examId = (int) $exam_group->exam->id;
        $sessionId = (int) $exam_group->exam_session->id;

        // ✅ penting: filter by session juga
        $all_essays = AnswerEssay::with('essay')
            ->where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->orderBy('essay_order', 'ASC')
            ->get();

        $essay_answered = AnswerEssay::where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->whereNotNull('answer')
            ->count();

        $essay_active = AnswerEssay::with('essay.exam')
            ->where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('essay_order', (int) $page)
            ->first();

        $answer_order = [];
        if ($essay_active && $essay_active->answer_order) {
            $answer_order = explode(",", $essay_active->answer_order);
        }

        $grade = $this->getGrade($examId, $sessionId, $studentId);

        // existing_file dari AnswerEssay (karena kamu menyimpan 1 file path di kolom answer)
        // ambil 1 saja yang answer != null
        $existingFile = null;
        $existing = AnswerEssay::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->whereNotNull('answer')
            ->first();

        if ($existing && $existing->answer) {
            $path = $existing->answer;

            $existingFile = [
                'path' => $path,
                'url'  => asset('storage/' . $path),
                'name' => basename($path),
                'size' => Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : null,
            ];
        }

        return inertia('Student/EssaysMigas/Show', [
            'id'              => (int) $id,   // exam_group id
            'page'            => (int) $page,
            'exam_id'         => $examId,
            'exam_session_id' => $sessionId,

            'exam_group'      => $exam_group,
            'all_essays'      => $all_essays,
            'essay_answered'  => $essay_answered,
            'essay_active'    => $essay_active,
            'answer_order'    => $answer_order,
            'duration'        => $grade, // kamu sebelumnya kirim Grade sebagai duration
            'existing_file'   => $existingFile,
        ]);
    }

    // =========================
    // API
    // =========================
    public function updateDuration(Request $request, $grade_id)
    {
        $request->validate([
            'duration' => ['required'],
        ]);

        $grade = Grade::findOrFail((int) $grade_id);
        $grade->duration = $request->duration;
        $grade->save();

        return response()->json([
            'success' => true,
            'message' => 'Duration updated successfully.',
        ]);
    }

    /**
     * (Opsional) Halaman upload terpisah
     */
    public function showUploadPage($exam_id, $exam_session_id)
    {
        $studentId = $this->studentId();

        $examId = (int) $exam_id;
        $sessionId = (int) $exam_session_id;

        $grade = $this->getGrade($examId, $sessionId, $studentId);
        if (!$grade) {
            abort(404, 'Grade tidak ditemukan');
        }

        $allEssays = Essay::where('exam_id', $examId)->get();

        $existing = AnswerEssay::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->whereNotNull('answer')
            ->first();

        $existingFile = null;
        if ($existing && $existing->answer) {
            $path = $existing->answer;
            $existingFile = [
                'path' => $path,
                'url'  => asset('storage/' . $path),
                'name' => basename($path),
                'size' => Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : null,
            ];
        }

        return inertia('Student/EssayMigasUpload', [
            'exam_id'         => $examId,
            'exam_session_id' => $sessionId,
            'duration' => [
                'duration' => (int) ($grade->duration ?? 0),
                'id'       => (int) $grade->id,
            ],
            'all_essays'     => $allEssays,
            'existing_file' => $existingFile,
        ]);
    }

    /**
     * ✅ SATU-SATUNYA upload handler yang dipakai:
     * - upload file ke storage
     * - simpan path ke kolom answer untuk semua AnswerEssay pada exam tsb
     */
    public function answerQuestion(Request $request)
    {
        $studentId = $this->studentId();

        $request->validate([
            'exam_id'         => ['required', 'integer'],
            'exam_session_id' => ['required', 'integer'],
            'duration'        => ['nullable'],
            'file'            => ['required', 'file', 'max:20480'], // 20MB
        ]);

        $examId = (int) $request->exam_id;
        $sessionId = (int) $request->exam_session_id;

        $grade = $this->getGrade($examId, $sessionId, $studentId);
        if (!$grade) {
            return response()->json(['success' => false, 'message' => 'Grade tidak ditemukan.'], 404);
        }

        if ($request->filled('duration')) {
            $grade->duration = $request->duration;
            $grade->save();
        }

        $file = $request->file('file');

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));

        $filename = $safeName
            . '-' . now()->format('YmdHis')
            . '-' . Str::random(6)
            . ($extension ? '.' . $extension : '');

        $directory = "essay_migas_answers/{$examId}/{$sessionId}/{$studentId}";

        // hapus file lama (kalau kamu mau)
        $old = AnswerEssay::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->whereNotNull('answer')
            ->value('answer');

        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $storedPath = $file->storeAs($directory, $filename, 'public');
        $fileUrl = asset('storage/' . $storedPath);

        Log::info('Essay migas file uploaded', [
            'student_id' => $studentId,
            'exam_id' => $examId,
            'exam_session_id' => $sessionId,
            'stored_path' => $storedPath,
            'url' => $fileUrl,
            'size' => $file->getSize(),
        ]);

        // Simpan path ke semua AnswerEssay
        $essayIds = Essay::where('exam_id', $examId)->pluck('id');
        foreach ($essayIds as $essayId) {
            AnswerEssay::updateOrCreate(
                [
                    'exam_id' => $examId,
                    'exam_session_id' => $sessionId,
                    'student_id' => $studentId,
                    'essay_id' => $essayId,
                ],
                [
                    'answer' => $storedPath,
                    'is_correct' => 'N',
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'File jawaban berhasil diupload.',
            'file' => [
                'name' => $originalName,
                'path' => $storedPath,
                'url'  => $fileUrl,
                'size' => $file->getSize(),
            ],
        ]);
    }

    public function download($exam_id, $exam_session_id)
    {
        $studentId = $this->studentId();

        $examId = (int) $exam_id;
        $sessionId = (int) $exam_session_id;

        $path = AnswerEssay::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->whereNotNull('answer')
            ->value('answer');

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'File tidak ditemukan');
        }

        return Storage::disk('public')->download($path, basename($path));
    }

    public function endEssay(Request $request)
    {
        $studentId = $this->studentId();

        $request->validate([
            'exam_id'         => ['required', 'integer'],
            'exam_session_id' => ['required', 'integer'],
            'exam_group_id'   => ['required', 'integer'],
        ]);

        $examId = (int) $request->exam_id;
        $sessionId = (int) $request->exam_session_id;

        $countCorrect = AnswerEssay::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->where('is_correct', 'Y')
            ->count();

        $grade = $this->getGrade($examId, $sessionId, $studentId);
        if ($grade) {
            $grade->end_time = Carbon::now();
            $grade->total_correct = $countCorrect;
            $grade->save();
        }

        return redirect()->route('student.essaysmigas.resultEssay', [
            'essay_group_id' => (int) $request->exam_group_id,
        ]);
    }

    public function resultEssay($exam_group_id)
    {
        $studentId = $this->studentId();

        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
            ->where('student_id', $studentId)
            ->where('id', (int) $exam_group_id)
            ->first();

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        $grade = $this->getGrade((int) $exam_group->exam->id, (int) $exam_group->exam_session->id, $studentId);

        return inertia('Student/EssaysMigas/Result', [
            'exam_group' => $exam_group,
            'grade'      => $grade,
        ]);
    }
}
