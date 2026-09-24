<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GradesEssayMigasExport implements FromCollection, WithMapping, WithHeadings, WithTitle
{
    protected $grades;
    protected string $title;

    public function __construct($grades, string $title = 'Esai Migas')
    {
        $this->grades = $grades;
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function collection()
    {
        return $this->grades;
    }

    public function map($grades): array
    {
        // Data utama peserta
        $row = [
            $grades->student->no_participant ?? 'N/A',
            $grades->student->name ?? 'N/A',
            $grades->exam->title ?? 'N/A',
            $grades->exam_session->title ?? 'N/A',
            $grades->exam->classroom->title ?? 'N/A',
        ];

        /**
         * ESSAY MIGAS:
         * semua jawaban sama → ambil 1 saja
         * File ada di disk private → link ke route unduh admin (perlu login admin).
         */
        $answer = collect($grades->answersEssay ?? [])
            ->where('exam_id', $grades->exam_id)
            ->where('exam_session_id', $grades->exam_session_id)
            ->whereNotNull('answer')
            ->first();

        if ($answer) {
            $row[] = route('admin.essay_migas.download', $answer->id);
        } else {
            $row[] = '';
        }

        // RETURN 1 DIMENSI (WAJIB)
        return $row;
    }

    public function headings(): array
    {
        return [
            'No Peserta',
            'Nama Siswa',
            'Ujian',
            'Sesi',
            'Skema',
            'Link Jawaban Essay Migas',
        ];
    }
}
