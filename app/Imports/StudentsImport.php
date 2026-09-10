<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    /** Nama-nama baris yang dilewati karena sudah terdaftar di skema yang sama. */
    public array $skipped = [];

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Cegah kasus "roster di-import dua kali dengan penomoran baru": nama
        // orang yang sama masuk lagi sebagai Student berbeda (no_participant beda,
        // tapi nama & skema sama) — lalu tampil dobel di penilaian & sertifikasi.
        $exists = Student::where('classroom_id', (int) $row['classroom_id'])
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $row['name']))])
            ->exists();

        if ($exists) {
            $this->skipped[] = trim((string) $row['name']);
            return null; // ToModel: mengembalikan null => baris dilewati
        }

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
