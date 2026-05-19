<?php

namespace App\Services;

use App\Models\AnswerEssay;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\GradingScheme;
use App\Models\InterviewAssessment;
use App\Models\ParticipantResult;
use App\Models\Student;

class ResultCalculatorService
{
    /**
     * Recalculate & upsert ParticipantResult for every enrolled student in a session.
     * Returns array of upserted ParticipantResult models.
     */
    public function recalcForSession(ExamSession $session): array
    {
        $session->loadMissing(['examPg.classroom', 'examEsai.classroom', 'exam_groups']);

        $classroomId = $session->referenceExam?->classroom_id;
        $scheme      = $classroomId
            ? GradingScheme::where('classroom_id', $classroomId)->first()
            : null;

        $bobotPg         = $scheme?->bobot_pg         ?? 40;
        $bobotEsai       = $scheme?->bobot_esai       ?? 35;
        $bobotWawancara  = $scheme?->bobot_wawancara  ?? 25;
        $nilaiKelulusan  = $scheme?->nilai_kelulusan  ?? 70;

        $studentIds = $session->exam_groups
            ->pluck('student_id')
            ->unique()
            ->values();

        $results = [];

        foreach ($studentIds as $studentId) {
            $nilaiPg         = $this->getNilaiPg($session->id, $studentId, $session->exam_id_pg);
            $nilaiEsai       = $this->getNilaiEsai($session->id, $studentId, $session->exam_id_esai);
            $nilaiWawancara  = $this->getNilaiWawancara($session->id, $studentId);

            // Normalise weights based on which components exist
            $bobotTotal = 0;
            if ($session->exam_id_pg  && $nilaiPg        !== null) $bobotTotal += $bobotPg;
            if ($session->exam_id_esai && $nilaiEsai     !== null) $bobotTotal += $bobotEsai;
            if ($session->has_wawancara && $nilaiWawancara !== null) $bobotTotal += $bobotWawancara;

            $nilaiAkhir = null;
            if ($bobotTotal > 0) {
                $sum = 0;
                if ($nilaiPg        !== null) $sum += $nilaiPg        * ($bobotPg        / $bobotTotal);
                if ($nilaiEsai      !== null) $sum += $nilaiEsai      * ($bobotEsai      / $bobotTotal);
                if ($nilaiWawancara !== null) $sum += $nilaiWawancara * ($bobotWawancara / $bobotTotal);
                $nilaiAkhir = round($sum, 2);
            }

            $keputusan = $nilaiAkhir !== null
                ? ($nilaiAkhir >= $nilaiKelulusan ? 'LULUS' : 'TIDAK_LULUS')
                : null;

            /** @var ParticipantResult $result */
            $result = ParticipantResult::firstOrNew([
                'exam_session_id' => $session->id,
                'student_id'      => $studentId,
            ]);

            // Only update scores if not yet finalized
            if (!$result->is_finalized) {
                $result->nilai_pg        = $nilaiPg;
                $result->nilai_esai      = $nilaiEsai;
                $result->nilai_wawancara = $nilaiWawancara;
                $result->nilai_akhir     = $nilaiAkhir;
                $result->keputusan       = $keputusan;
            }

            $result->save();
            $results[] = $result;
        }

        return $results;
    }

    private function getNilaiPg(int $sessionId, int $studentId, ?int $examId): ?float
    {
        if (!$examId) return null;

        $grade = Grade::where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->first();

        return $grade ? (float) $grade->grade : null;
    }

    private function getNilaiEsai(int $sessionId, int $studentId, ?int $examId): ?float
    {
        if (!$examId) return null;

        $avg = AnswerEssay::where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->whereNotNull('score')
            ->avg('score');

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    private function getNilaiWawancara(int $sessionId, int $studentId): ?float
    {
        $ia = InterviewAssessment::where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->first();

        return $ia ? (float) $ia->total_nilai : null;
    }
}
