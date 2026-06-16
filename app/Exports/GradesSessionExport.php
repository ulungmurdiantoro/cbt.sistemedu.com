<?php

namespace App\Exports;

use App\Models\ExamSession;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Ekspor nilai satu sesi ujian sebagai workbook multi-sheet.
 *
 * Satu sesi bisa berisi ujian Pilihan Ganda DAN Esai sekaligus, sehingga
 * setiap peserta punya dua Grade (per exam). Tiap jenis ujian diekspor ke
 * sheet-nya sendiri agar tidak tercampur / redundan.
 */
class GradesSessionExport implements WithMultipleSheets
{
    protected ExamSession $examSession;
    protected $grades;

    public function __construct(ExamSession $examSession, $grades)
    {
        $this->examSession = $examSession;
        $this->grades = $grades;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Sheet Pilihan Ganda
        if ($this->examSession->examPg) {
            $pgGrades = $this->grades
                ->where('exam_id', $this->examSession->exam_id_pg)
                ->values();
            $sheets[] = new GradesExport($pgGrades, 'Pilihan Ganda');
        }

        // Sheet Esai / Esai Migas
        if ($this->examSession->examEsai) {
            $esaiGrades = $this->grades
                ->where('exam_id', $this->examSession->exam_id_esai)
                ->values();

            $sheets[] = $this->examSession->examEsai->type === 'Essay Migas'
                ? new GradesEssayMigasExport($esaiGrades)
                : new GradesEssayExport($esaiGrades);
        }

        // Fallback: sesi tanpa exam terdefinisi → ekspor apa adanya
        if (empty($sheets)) {
            $sheets[] = new GradesExport($this->grades);
        }

        return $sheets;
    }
}
