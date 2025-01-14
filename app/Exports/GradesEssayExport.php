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
        // Create the is_correct_answers array with numbered entries
        $is_correct_answers = array_map(function($answer, $index) {
            return ['number' => $index + 1, 'answer' => $answer->answer];
        }, $grades->answersEssay->all(), array_keys($grades->answersEssay->all()));
    
        // Initialize the row with common information
        $row = [
            $grades->exam->title,
            $grades->exam_session->title,
            $grades->student->name,
            $grades->exam->classroom->title
        ];
    
        // Add the answer values to the row in separate columns
        foreach ($is_correct_answers as $answer) {
            $row[] = $answer['answer'];
        }
    
        // Add the final grade to the row
        $row[] = $grades->grade;
    
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
            'Ujian',
            'Sesi',
            'Nama Siswa',
            'Skema',
            ...$is_correct_headers, // Spread the dynamic headers
            'Nilai'
        ];
    }
}
