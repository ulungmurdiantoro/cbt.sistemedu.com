<?php

namespace App\Imports;

use App\Models\Essay;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EssaysImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Essay([
            // 'essays_code'     => $row['essays_code'],
            'exam_id'           => (int) $row['exam_id'],
            'question'          => $row['question'],
            'answer'            => $row['answer'],
        ]);
    }
}
