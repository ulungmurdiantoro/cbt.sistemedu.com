<?php

namespace App\Support;

/**
 * Rubrik Penilaian Awal Kelayakan Pemohon (FR.APL.03 Rev.03).
 *
 * Kriteria & ambang batas per skema ditanam langsung di sini (bukan
 * dikonfigurasi lewat UI). Kalau dokumen sumbernya direvisi, ubah di sini.
 */
class InitialAssessmentRubric
{
    /** Kriteria bersama untuk skema SPMI/ToT/TKO (classroom_id 5,6,7,8,9). Threshold beda per skema. */
    private static function spmiCriteria(): array
    {
        return [
            [
                'key'   => 'pendidikan',
                'label' => 'Strata Pendidikan',
                'type'  => 'single',
                'options' => [
                    ['key' => 's3', 'label' => 'S3', 'score' => 3],
                    ['key' => 's2', 'label' => 'S2', 'score' => 2],
                    ['key' => 's1', 'label' => 'S1', 'score' => 1],
                ],
            ],
            [
                'key'   => 'pengalaman_kerja',
                'label' => 'Pengalaman kerja di Bidang yang Sama',
                'type'  => 'single',
                'options' => [
                    ['key' => 'pimpinan_pm', 'label' => 'Pimpinan Penjaminan Mutu', 'score' => 6],
                    ['key' => 'pimpinan_fakultas', 'label' => 'Pimpinan Fakultas / Biro / Lembaga / UPT', 'score' => 5],
                    ['key' => 'pimpinan_prodi', 'label' => 'Pimpinan Prodi', 'score' => 4],
                    ['key' => 'staf_pm', 'label' => 'Staf Penjaminan Mutu', 'score' => 3],
                    ['key' => 'dosen_tendik', 'label' => 'Dosen / Tendik', 'score' => 2],
                ],
            ],
            [
                'key'   => 'durasi_pm',
                'label' => 'Durasi kerja di Penjaminan Mutu',
                'type'  => 'single',
                'options' => [
                    ['key' => 'gt10', 'label' => 'diatas 10 tahun', 'score' => 5],
                    ['key' => 'y6_10', 'label' => '6 - 10 tahun', 'score' => 3],
                    ['key' => 'y3_5', 'label' => '3 - 5 tahun', 'score' => 2],
                    ['key' => 'y1_3', 'label' => '1 - 3 tahun', 'score' => 1],
                    ['key' => 'none', 'label' => 'Tidak ada pengalaman', 'score' => 0],
                ],
            ],
            [
                'key'   => 'durasi_dosen',
                'label' => 'Durasi Kerja Dosen / Tendik',
                'type'  => 'single',
                'options' => [
                    ['key' => 'gt10', 'label' => 'diatas 10 tahun', 'score' => 5],
                    ['key' => 'y6_10', 'label' => '6 - 10 tahun', 'score' => 4],
                    ['key' => 'y3_5', 'label' => '3 - 5 tahun', 'score' => 3],
                    ['key' => 'y1_3', 'label' => '1 - 3 tahun', 'score' => 2],
                ],
            ],
            [
                'key'   => 'pelatihan',
                'label' => 'Pelatihan',
                'type'  => 'single',
                'options' => [
                    ['key' => 'relevan', 'label' => 'Pelatihan Relevan dengan Sertifikasi', 'score' => 2],
                    ['key' => 'tidak_relevan', 'label' => 'Pelatihan tidak Relevan dengan Sertifikasi', 'score' => 1],
                ],
            ],
        ];
    }

    /** Kriteria bersama untuk skema Lab ISO/IEC 17025 Auditor & Lead Implementer (classroom_id 3,4). */
    private static function labIso17025Criteria(): array
    {
        return [
            [
                'key'   => 'pendidikan',
                'label' => 'Strata Pendidikan',
                'type'  => 'single',
                'options' => [
                    ['key' => 's2_plus', 'label' => 'S2 dan lebih', 'score' => 2],
                    ['key' => 'd4_s1', 'label' => 'D4 atau S1', 'score' => 1],
                    ['key' => 'sma_smk_d3', 'label' => 'SMK/SMA atau D3', 'score' => 0],
                ],
            ],
            [
                'key'   => 'prodi',
                'label' => 'Prodi Pendidikan',
                'type'  => 'single',
                'options' => [
                    ['key' => 'sains', 'label' => 'Sains', 'score' => 2],
                    ['key' => 'sosial', 'label' => 'Sosial', 'score' => 1],
                ],
            ],
            [
                'key'   => 'pengalaman_kerja',
                'label' => 'Pengalaman kerja di lab',
                'type'  => 'single',
                'options' => [
                    ['key' => 'kepala_lab', 'label' => 'Kepala Lab', 'score' => 4],
                    ['key' => 'manajer_teknis', 'label' => 'Manajer Teknis', 'score' => 3],
                    ['key' => 'manajer_mutu', 'label' => 'Manajer Mutu', 'score' => 3],
                    ['key' => 'penyelia', 'label' => 'Penyelia', 'score' => 2],
                    ['key' => 'analis', 'label' => 'Analis', 'score' => 1],
                    ['key' => 'lainnya', 'label' => 'Lainnya', 'score' => 0],
                ],
            ],
            [
                'key'   => 'durasi_kerja',
                'label' => 'Durasi kerja di Lab',
                'type'  => 'single',
                'options' => [
                    ['key' => 'gt15', 'label' => 'diatas 15 tahun', 'score' => 4],
                    ['key' => 'y11_15', 'label' => '11 - 15 tahun', 'score' => 3],
                    ['key' => 'y6_10', 'label' => '6 - 10 tahun', 'score' => 2],
                    ['key' => 'y1_5', 'label' => '1 - 5 tahun', 'score' => 1],
                ],
            ],
            [
                'key'   => 'pelatihan_topik',
                'label' => 'Pelatihan (boleh pilih lebih dari satu)',
                'type'  => 'multi',
                'options' => [
                    ['key' => 'iso17025', 'label' => 'Pemahaman ISO 17025', 'score' => 1],
                    ['key' => 'dokumen', 'label' => 'Penyusunan dokumen', 'score' => 1],
                    ['key' => 'validasi', 'label' => 'Validasi metode pengujian', 'score' => 1],
                    ['key' => 'penjaminan_mutu', 'label' => 'Penjaminan mutu pengujian', 'score' => 1],
                    ['key' => 'ketidakpastian', 'label' => 'Ketidakpastian pengukuran', 'score' => 1],
                    ['key' => 'audit_internal', 'label' => 'Audit Internal', 'score' => 1],
                ],
            ],
            [
                'key'   => 'pengalaman_auditor',
                'label' => 'Pengalaman Auditor Internal',
                'type'  => 'single',
                'options' => [
                    ['key' => 'surat_tugas', 'label' => 'Ada Surat Tugas', 'score' => 1],
                    ['key' => 'tidak_ada', 'label' => 'Tidak ada', 'score' => 0],
                ],
            ],
        ];
    }

    /** Kriteria bersama untuk skema Lifting (classroom_id 13,14,15,16). */
    private static function liftingCriteria(): array
    {
        return [
            [
                'key'   => 'pendidikan',
                'label' => 'Strata Pendidikan',
                'type'  => 'single',
                'options' => [
                    ['key' => 's2_plus', 'label' => 'S2 dan lebih', 'score' => 3],
                    ['key' => 'd3_s1', 'label' => 'D3 & S1', 'score' => 2],
                    ['key' => 'sma_smk', 'label' => 'SMA/SMK', 'score' => 1],
                ],
            ],
            [
                'key'   => 'prodi',
                'label' => 'Prodi Pendidikan',
                'type'  => 'single',
                'options' => [
                    ['key' => 'sains', 'label' => 'Sains', 'score' => 2],
                    ['key' => 'sosial', 'label' => 'Sosial', 'score' => 1],
                ],
            ],
            [
                'key'   => 'jabatan',
                'label' => 'Jabatan Kerja',
                'type'  => 'single',
                'options' => [
                    ['key' => 'project_manager', 'label' => 'Project Manager', 'score' => 6],
                    ['key' => 'lifting_engineer', 'label' => 'Lifting Engineer / Mechanical Engineer', 'score' => 5],
                    ['key' => 'lifting_supervisor', 'label' => 'Lifting Supervisor', 'score' => 4],
                    ['key' => 'designer', 'label' => 'Designer', 'score' => 3],
                    ['key' => 'junior_drafter', 'label' => 'Junior Drafter / Rigger', 'score' => 2],
                    ['key' => 'fresh_graduate', 'label' => 'Fresh graduate / lainnya', 'score' => 1],
                ],
            ],
            [
                'key'   => 'durasi_kerja',
                'label' => 'Durasi Pengalaman Kerja',
                'type'  => 'single',
                'options' => [
                    ['key' => 'gt5', 'label' => 'diatas 5 tahun', 'score' => 4],
                    ['key' => 'y4_5', 'label' => '4-5 tahun', 'score' => 3],
                    ['key' => 'y2_3', 'label' => '2-3 tahun', 'score' => 2],
                    ['key' => 'y0_1', 'label' => '0-1 tahun', 'score' => 1],
                ],
            ],
            [
                'key'   => 'pelatihan',
                'label' => 'Pelatihan',
                'type'  => 'single',
                'options' => [
                    ['key' => 'relevan', 'label' => 'Pelatihan Relevan dengan Sertifikasi', 'score' => 2],
                    ['key' => 'tidak_relevan', 'label' => 'Pelatihan tidak Relevan dengan Sertifikasi', 'score' => 1],
                ],
            ],
        ];
    }

    /**
     * Kriteria bersama untuk skema QMS/Lab Officer (classroom_id 10,11,12,17-27).
     * Juga dipakai sebagai fallback default untuk skema yang belum terdaftar di FR.APL.03.
     */
    private static function qmsOfficerCriteria(): array
    {
        return [
            [
                'key'   => 'pendidikan',
                'label' => 'Strata Pendidikan',
                'type'  => 'single',
                'options' => [
                    ['key' => 's2_plus', 'label' => 'S2 dan lebih', 'score' => 3],
                    ['key' => 'd3_s1', 'label' => 'D3 & S1', 'score' => 2],
                    ['key' => 'sma_smk', 'label' => 'SMA/SMK', 'score' => 1],
                ],
            ],
            [
                'key'   => 'prodi',
                'label' => 'Prodi Pendidikan',
                'type'  => 'single',
                'options' => [
                    ['key' => 'sains', 'label' => 'Sains', 'score' => 2],
                    ['key' => 'sosial', 'label' => 'Sosial', 'score' => 1],
                ],
            ],
            [
                'key'   => 'pengalaman_kerja',
                'label' => 'Pengalaman kerja di lab',
                'type'  => 'single',
                'options' => [
                    ['key' => 'kepala_lab', 'label' => 'Kepala Lab / Ketua / Direktur', 'score' => 6],
                    ['key' => 'manajer_teknis', 'label' => 'Manajer Teknis / Manajer', 'score' => 5],
                    ['key' => 'manajer_mutu', 'label' => 'Manajer Mutu / Koordinator / Kasubag / SPV', 'score' => 4],
                    ['key' => 'penyelia', 'label' => 'Penyelia / Subkoordinator', 'score' => 3],
                    ['key' => 'staff', 'label' => 'Staff Laboratorium atau Analis / Staff secara general', 'score' => 2],
                    ['key' => 'mahasiswa', 'label' => 'Mahasiswa / Asisten Lab / Co Ass Lab / Fresh graduate atau lainnya', 'score' => 1],
                ],
            ],
            [
                'key'   => 'durasi_kerja',
                'label' => 'Durasi Pengalaman Kerja',
                'type'  => 'single',
                'options' => [
                    ['key' => 'gt5', 'label' => 'diatas 5 tahun', 'score' => 4],
                    ['key' => 'y4_5', 'label' => '4-5 tahun', 'score' => 3],
                    ['key' => 'y2_3', 'label' => '2-3 tahun', 'score' => 2],
                    ['key' => 'y0_1', 'label' => '0-1 tahun', 'score' => 1],
                ],
            ],
            [
                'key'   => 'pelatihan',
                'label' => 'Pelatihan',
                'type'  => 'single',
                'options' => [
                    ['key' => 'relevan', 'label' => 'Pelatihan Relevan dengan Sertifikasi', 'score' => 2],
                    ['key' => 'tidak_relevan', 'label' => 'Pelatihan tidak Relevan dengan Sertifikasi', 'score' => 1],
                ],
            ],
        ];
    }

    /**
     * Peta classroom_id => rubrik (kriteria + threshold nilai kelulusan).
     * Threshold selalu berupa nilai minimum yang harus dicapai (>=).
     */
    public static function map(): array
    {
        $spmi   = self::spmiCriteria();
        $lab    = self::labIso17025Criteria();
        $lift   = self::liftingCriteria();
        $qms    = self::qmsOfficerCriteria();

        return [
            // Skema 1-5: SPMI / ToT / Tata Kelola — threshold beda per skema
            9 => ['criteria' => $spmi, 'threshold' => 7],  // Implementer Tata Kelola Organisasi Perguruan Tinggi
            5 => ['criteria' => $spmi, 'threshold' => 8],  // Lead Implementer SPMI Terintegrasi ISO 21001:2018
            6 => ['criteria' => $spmi, 'threshold' => 9],  // Auditor Internal SPMI Terintegrasi ISO 21001:2018
            7 => ['criteria' => $spmi, 'threshold' => 10], // Lead Auditor SPMI Terintegrasi ISO 21001:2018
            8 => ['criteria' => $spmi, 'threshold' => 8],  // Training of Trainer (ToT) Outcome Based Education (OBE)

            // Skema 6-7 (dokumen): Lab ISO/IEC 17025 — "nilai > 8" dibulatkan jadi minimum 9
            3 => ['criteria' => $lab, 'threshold' => 9], // Auditor Internal Standar Laboratorium ISO/IEC 17025:2017
            4 => ['criteria' => $lab, 'threshold' => 7], // Lead Implementer Standar Laboratorium ISO/IEC 17025:2017

            // Skema 8-11 (dokumen): Lifting — threshold sama untuk semua
            13 => ['criteria' => $lift, 'threshold' => 6],
            14 => ['criteria' => $lift, 'threshold' => 6],
            15 => ['criteria' => $lift, 'threshold' => 6],
            16 => ['criteria' => $lift, 'threshold' => 6],

            // Skema 12-25 (dokumen): QMS/Lab Officer — threshold sama untuk semua
            10 => ['criteria' => $qms, 'threshold' => 6],
            11 => ['criteria' => $qms, 'threshold' => 6],
            12 => ['criteria' => $qms, 'threshold' => 6],
            17 => ['criteria' => $qms, 'threshold' => 6],
            18 => ['criteria' => $qms, 'threshold' => 6],
            19 => ['criteria' => $qms, 'threshold' => 6],
            20 => ['criteria' => $qms, 'threshold' => 6],
            21 => ['criteria' => $qms, 'threshold' => 6],
            22 => ['criteria' => $qms, 'threshold' => 6],
            23 => ['criteria' => $qms, 'threshold' => 6],
            24 => ['criteria' => $qms, 'threshold' => 6],
            25 => ['criteria' => $qms, 'threshold' => 6],
            26 => ['criteria' => $qms, 'threshold' => 6],
            27 => ['criteria' => $qms, 'threshold' => 6],
        ];
    }

    /**
     * Ambil rubrik untuk classroom tertentu. Skema yang belum terdaftar di
     * FR.APL.03 (mis. skema baru) memakai rubrik QMS/Lab Officer sebagai default.
     */
    public static function for(int $classroomId): array
    {
        return self::map()[$classroomId] ?? [
            'criteria'  => self::qmsOfficerCriteria(),
            'threshold' => 6,
        ];
    }

    /**
     * Hitung total skor dari jawaban yang dikirim.
     * $answers: [criterion_key => option_key] untuk type single,
     *           [criterion_key => [option_key, ...]] untuk type multi.
     */
    public static function score(array $rubric, array $answers): int
    {
        $total = 0;

        foreach ($rubric['criteria'] as $criterion) {
            $given = $answers[$criterion['key']] ?? null;

            if ($criterion['type'] === 'multi') {
                $selected = is_array($given) ? $given : [];
                foreach ($criterion['options'] as $option) {
                    if (in_array($option['key'], $selected, true)) {
                        $total += $option['score'];
                    }
                }
                continue;
            }

            foreach ($criterion['options'] as $option) {
                if ($option['key'] === $given) {
                    $total += $option['score'];
                    break;
                }
            }
        }

        return $total;
    }
}
