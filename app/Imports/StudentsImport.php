<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Student([
            'no_participant'    => $row['no_participant'],
            'name'              => $row['name'],
            'gender'            => $row['gender'],
            'position'          => $row['position'],
            'institution'       => $row['institution'],
            'classroom_id'      => (int) $row['classroom_id'],
        ]);
    }
        
    /**
     * rules
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'no_participant' => 'unique:students,no_participant',
        ];
    }
}