<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class ApplicationsExport extends DefaultValueBinder implements FromCollection, WithMapping, WithHeadings, WithTitle, WithCustomValueBinder
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

    /**
     * Paksa semua sel ditulis sebagai teks. Tanpa ini, NIK dan No. HP
     * (angka panjang) dibaca Excel sebagai angka dan kehilangan presisi
     * (digit belakang jadi 0) karena batas floating point.
     */
    public function bindValue(Cell $cell, $value): bool
    {
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

        return true;
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
            $application->student?->no_participant ?? '-',
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
