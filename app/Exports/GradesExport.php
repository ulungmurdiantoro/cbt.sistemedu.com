<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GradesExport implements FromCollection, WithMapping, WithHeadings
{    
    /**
     * grade
     *
     * @var mixed
     */
    protected $grades;
    
    /**
     * __construct
     *
     * @param  mixed $grade
     * @return void
     */
    public function __construct($grades) {
        $this->grades = $grades;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->grades;
    }

    public function map($grades) : array {
        // Create the is_correct_answers array with numbered entries
        $is_correct_answers = array_map(function($answer, $index) {
            return ['number' => $index + 1, 'is_correct' => $answer->is_correct, 'student_id' => $answer->student_id];
        }, $grades->answers->all(), array_keys($grades->answers->all()));
    
        // Initialize the row with common information
        $row = [
            $grades->exam->title,
            $grades->exam_session->title,
            $grades->student->name,
            $grades->exam->classroom->title
        ];
    
        // Add the is_correct values to the row in separate columns if student IDs match
        foreach ($is_correct_answers as $answer) {
            if ($grades->student->id == $answer['student_id']) {
                $row[] = $answer['is_correct'];
            }
        }
    
        // Add the final grade to the row
        $row[] = $grades->grade;
    
        return [$row];
    }    
    
    public function headings() : array {
        // Assume a fixed number of answers for headings (e.g., 10). Adjust this value as needed.
        $num_answers = 100;
        
        // Create dynamic column headers for is_correct answers
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