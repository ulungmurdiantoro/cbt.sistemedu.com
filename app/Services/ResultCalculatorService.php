<?php

namespace App\Services;

use App\Models\AnswerEssay;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\GradingScheme;
use App\Models\InterviewAssessment;
use App\Models\ParticipantResult;

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

        $bobotPg         = $scheme?->bobot_pg        ?? 0;
        $bobotEsai       = $scheme?->bobot_esai      ?? 0;
        $bobotWawancara  = $scheme?->bobot_wawancara ?? 0;
        $nilaiKelulusan  = $scheme?->nilai_kelulusan ?? 70;

        $studentIds = $session->exam_groups
            ->pluck('student_id')
            ->unique()
            ->values();

        // Batch fetch — hindari n+1 query per peserta
        $pgGrades = $session->exam_id_pg
            ? Grade::where('exam_session_id', $session->id)
                ->where('exam_id', $session->exam_id_pg)
                ->whereIn('student_id', $studentIds)
                ->pluck('grade', 'student_id')
            : collect();

        $esaiScores = $session->exam_id_esai
            ? AnswerEssay::where('exam_session_id', $session->id)
                ->where('exam_id', $session->exam_id_esai)
                ->whereIn('student_id', $studentIds)
                ->whereNotNull('score')
                ->selectRaw('student_id, AVG(score) as avg_score')
                ->groupBy('student_id')
                ->pluck('avg_score', 'student_id')
            : collect();

        $wawancaraScores = InterviewAssessment::where('exam_session_id', $session->id)
            ->whereIn('student_id', $studentIds)
            ->pluck('total_nilai', 'student_id');

        $existingResults = ParticipantResult::where('exam_session_id', $session->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $results = [];

        foreach ($studentIds as $studentId) {
            $nilaiPg        = isset($pgGrades[$studentId])        ? (float) $pgGrades[$studentId]                  : null;
            $nilaiEsai      = isset($esaiScores[$studentId])      ? round((float) $esaiScores[$studentId], 2)      : null;
            $nilaiWawancara = isset($wawancaraScores[$studentId]) ? (float) $wawancaraScores[$studentId]           : null;

            // Normalise weights based on which components exist
            $bobotTotal = 0;
            if ($session->exam_id_pg   && $nilaiPg        !== null) $bobotTotal += $bobotPg;
            if ($session->exam_id_esai && $nilaiEsai      !== null) $bobotTotal += $bobotEsai;
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
            $result = $existingResults->get($studentId);
            if (!$result) {
                $result = new ParticipantResult();
                $result->exam_session_id = $session->id;
                $result->student_id      = $studentId;
            }

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
}
