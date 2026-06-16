<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GradesEssayExport implements FromCollection, WithMapping, WithHeadings, WithTitle
{
    protected $grades;
    protected string $title;

    public function __construct($grades, string $title = 'Esai')
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
        // Prepare student and exam data
        $row = [
            $grades->student->no_participant ?? 'N/A',
            $grades->student->name ?? 'N/A',
            $grades->exam->title ?? 'N/A',
            $grades->exam_session->title ?? 'N/A',
            $grades->exam->classroom->title ?? 'N/A',
        ];

        // Sort answersEssay by essay_order and map them
        $is_correct_answers = $grades->answersEssay
            ->sortBy('essay_order')
            ->values()
            ->map(function ($answer, $index) {
                return [
                    'number' => $index + 1,
                    'answer' => $answer->answer ?? '',
                    'score'  => $answer->score ?? '',
                ];
            })
            ->toArray();

        // Append answer text and score after each
        foreach ($is_correct_answers as $answer) {
            $row[] = $answer['answer'];
            $row[] = $answer['score'];
        }

        // Append the grade
        $row[] = $grades->grade ?? 0;

        return [$row];
    }

    public function headings($grades = null): array
    {
        $num_answers = $grades && $grades->answersEssay ? count($grades->answersEssay) : 10;

        $is_correct_headers = [];
        foreach (range(1, $num_answers) as $index) {
            $is_correct_headers[] = $index;
            $is_correct_headers[] = "Nilai $index";
        }

        return array_merge(
            [
                'No Peserta',
                'Nama Siswa',
                'Ujian',
                'Sesi',
                'Skema',
            ],
            $is_correct_headers,
            ['Total Nilai']
        );
    }
}
