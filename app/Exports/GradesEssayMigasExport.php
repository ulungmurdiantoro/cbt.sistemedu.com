<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GradesEssayMigasExport implements FromCollection, WithMapping, WithHeadings
{
    protected $grades;
    protected string $baseUrl;

    public function __construct($grades)
    {
        $this->grades = $grades;

        // base URL download file
        $this->baseUrl = 'https://lsp-cbt.sistemedu.com/storage/';
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
         * DB menyimpan path:
         * essay_migas_answers/27/77/484/xxx.pdf
         */
        $answer = collect($grades->answersEssay ?? [])
            ->first();

        if ($answer && !empty($answer->answer)) {
            // normalisasi path
            $path = ltrim(str_replace('\\', '/', trim($answer->answer)), '/');

            // gabungkan jadi URL publik
            $row[] = $this->baseUrl . $path;
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
