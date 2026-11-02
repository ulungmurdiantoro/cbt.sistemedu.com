<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Peserta yang pernah mengikuti sertifikasi: satu baris per peserta per sesi ujian
 * (peserta yang ikut beberapa sesi muncul beberapa kali). Hasil diambil dari
 * participant_results — kosong bila Rekap Hasil sesi itu belum pernah dibuka.
 */
class StudentsCertificationExport extends DefaultValueBinder implements FromCollection, WithMapping, WithHeadings, WithTitle, WithCustomValueBinder, ShouldAutoSize
{
    private int $no = 0;

    /**
     * @param \Illuminate\Support\Collection $groups  baris exam_groups unik (student_id, exam_session_id)
     * @param \Illuminate\Support\Collection $results participant_results, kunci "student_id-exam_session_id"
     */
    public function __construct(protected $groups, protected $results)
    {
    }

    public function title(): string
    {
        return 'Peserta Sertifikasi';
    }

    public function collection()
    {
        return $this->groups;
    }

    /** Semua sel ditulis sebagai teks supaya NIK / No. HP tidak kehilangan digit. */
    public function bindValue(Cell $cell, $value): bool
    {
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

        return true;
    }

    public function headings(): array
    {
        return [
            'No.',
            'No. Peserta',
            'Nama',
            'Jenis Kelamin',
            'Jabatan',
            'Asal Instansi',
            'Skema Sertifikasi',
            'NIK',
            'Email',
            'No HP',
            'Sesi Ujian',
            'Kode Batch',
            'Tanggal Ujian',
            'Nilai Akhir',
            'Keputusan',
            'Status',
            'No. Sertifikat',
            'No. SK',
            'Berlaku Sampai',
        ];
    }

    public function map($group): array
    {
        $student     = $group->student;
        $participant = $student?->participant;
        $session     = $group->exam_session;
        $result      = $this->results->get($group->student_id . '-' . $group->exam_session_id);

        return [
            ++$this->no,
            $student?->no_participant ?? '-',
            $student?->name ?? '-',
            match ($student?->gender) { 'L' => 'Laki-laki', 'P' => 'Perempuan', default => '-' },
            $student?->position ?? '-',
            $student?->institution ?? '-',
            $student?->classroom?->title ?? '-',
            $participant?->nik ?? '-',
            $participant?->email ?? '-',
            $participant?->hp ?? '-',
            $session?->title ?? '-',
            $session?->kode_batch ?? '-',
            $session?->start_time ? Carbon::parse($session->start_time)->format('d-m-Y') : '-',
            $result?->nilai_akhir ?? '-',
            match ($result?->keputusan) { 'LULUS' => 'LULUS', 'TIDAK_LULUS' => 'TIDAK LULUS', default => '-' },
            $result?->is_finalized ? 'Final' : 'Belum Final',
            $result?->sertifikat_number ?? '-',
            $result?->sk_number ?? '-',
            $result?->valid_until ? $result->valid_until->format('d-m-Y') : '-',
        ];
    }
}
