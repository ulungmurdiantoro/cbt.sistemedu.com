<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ApplicationsExport implements FromCollection, WithMapping, WithHeadings, WithTitle
{
    protected $applications;

    public function __construct($applications)
    {
        $this->applications = $applications;
    }

    public function title(): string
    {
        return 'Peserta';
    }

    public function collection()
    {
        return $this->applications;
    }

    public function headings(): array
    {
        return [
            'No. Peserta',
            'Nama Lengkap beserta Gelar',
            'Jenis Kelamin',
            'Jabatan',
            'Asal Instansi',
            'Skema Sertifikasi yang dipilih',
            'EMAIL',
            'No HP',
            'NIK',
        ];
    }

    public function map($application): array
    {
        $participant = $application->participant;

        return [
            $application->student->no_participant ?? '-',
            $participant->name ?? '-',
            $participant->jenis_kelamin === 'L' ? 'Laki-laki' : ($participant->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
            $participant->jabatan ?? '-',
            $participant->institusi ?? '-',
            $application->classroom->title ?? '-',
            $participant->email ?? '-',
            $participant->hp ?? '-',
            $participant->nik ?? '-',
        ];
    }
}
