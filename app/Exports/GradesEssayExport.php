<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GradesEssayExport implements FromCollection, WithMapping, WithHeadings
{
    protected $grades;
    
    public function __construct($grades) {
        $this->grades = $grades;
    }

    public function collection()
    {
        return $this->grades;
    }

    public function map($grades) : array {
        // Prepare student and exam data
        $row = [
            $grades->student->no_participant ?? 'N/A',
            $grades->student->name ?? 'N/A',
            $grades->exam->title ?? 'N/A',
            $grades->exam_session->title ?? 'N/A',
            $grades->exam->classroom->title ?? 'N/A',
        ];
    
        // Sort answersEssay by question_id and map them
        $is_correct_answers = $grades->answersEssay->sortBy(function($answer) {
            return $answer->question_id;
        })->values()->map(function ($answer, $index) {
            return [
                'number' => $index + 1,
                'answer' => $answer->answer,
            ];
        })->toArray();
    
        // Append answer values to the row
        foreach ($is_correct_answers as $answer) {
            $row[] = $answer['answer'];
        }
    
        // Append the grade
        $row[] = $grades->grade ?? 0;
    
        return [$row];
    }
    
    public function headings($grades = null) : array {
        $num_answers = $grades && $grades->answersEssay ? count($grades->answersEssay) : 10;
        
        $is_correct_headers = [];
        foreach (range(1, $num_answers) as $index) {
            $is_correct_headers[] = "$index";
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
            ['Nilai']
        );
    }
    
}
