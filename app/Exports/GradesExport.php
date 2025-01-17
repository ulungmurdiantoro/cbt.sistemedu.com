<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GradesExport implements FromCollection, WithMapping, WithHeadings
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
    
        // Sort answers by question_id and map them
        $is_correct_answers = $grades->answers->sortBy(function($answer) {
            return $answer->question_id;
        })->values()->map(function ($answer, $index) {
            return [
                'number' => $index + 1,
                'is_correct' => $answer->is_correct,
            ];
        })->toArray();
    
        // Append is_correct values to the row
        foreach ($is_correct_answers as $answer) {
            $row[] = $answer['is_correct'];
        }
    
        // Append the grade
        $row[] = $grades->grade ?? 0;
    
        return [$row];
    }
    
    
    
    public function headings($grades = null) : array {
        $num_answers = $grades && $grades->answers ? count($grades->answers) : 100;
        
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