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

    public function map($grades): array {
        // Log the original answers array
        error_log('Original Answers: ' . print_r($grades->answers->all(), true));
        
        // Create the is_correct_answers array with numbered entries and question_id
        $is_correct_answers = array_map(function ($answer, $index) {
            return [
                'number' => $index + 1,
                'question_id' => $answer->question_id ?? null, // Add a null check
                'is_correct' => $answer->is_correct ?? null   // Add a null check
            ];
        }, $grades->answers->all(), array_keys($grades->answers->all()));
        
        // Log after mapping
        error_log('Mapped is_correct_answers: ' . print_r($is_correct_answers, true));
    
        // Sort the is_correct_answers array by question_id
        usort($is_correct_answers, function ($a, $b) {
            return ($a['question_id'] ?? PHP_INT_MAX) <=> ($b['question_id'] ?? PHP_INT_MAX);
        });
    
        // Log after sorting
        error_log('Sorted is_correct_answers: ' . print_r($is_correct_answers, true));
        
        // Initialize the row with common information
        $row = [
            $grades->student->no_participant,
            $grades->student->name,
            $grades->exam->title,
            $grades->exam_session->title,
            $grades->exam->classroom->title
        ];
        
        // Add the is_correct values to the row in separate columns
        foreach ($is_correct_answers as $answer) {
            $row[] = $answer['is_correct'] ?? null;
        }
        
        // Add the final grade to the row
        $row[] = $grades->grade;
        
        // Log the final row
        error_log('Final Row: ' . print_r($row, true));
        
        return [$row];
    }
    
    public function headings() : array {
        // Assume a fixed number of answersEssay for headings (e.g., 10). Adjust this value as needed.
        $num_answers = 10;
        
        // Create dynamic column headers for answer answersEssay
        $is_correct_headers = [];
        foreach (range(1, $num_answers) as $index) {
            $is_correct_headers[] = "$index";
        }
    
        return [
            'Nama Peserta',
            'Nama Siswa',
            'Ujian',
            'Sesi',
            'Skema',
            ...$is_correct_headers, // Spread the dynamic headers
            'Nilai'
        ];
    }
}
