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
    /** Daftar "Ruang Lingkup" persis seperti tercantum di dokumen FR.APL.03, per kelompok skema. */
    private static function ruangLingkup(): array
    {
        return [
            'spmi' => [
                '1. Audit Internal SPMI Terintegrasi ISO 21001:2018',
                '2. Lead Auditor SPMI Terintegrasi ISO 21001:2018',
                '3. Lead Implementer SPMI Terintegrasi ISO 21001:2018',
                '4. Training of Trainer (ToT) Outcome Based education (OBE)',
                '5. Implementer Tata Kelola Organisasi Perguruan Tinggi',
            ],
            'lab' => [
                '1. Audit Internal Standar Laboratorium ISO/IEC 17025:2017',
                '2. Lead Implementer Standar Laboratorium ISO/IEC 17025:2017',
            ],
            'lift' => [
                '1. Lifting Engineer for Medium Lifting',
                '2. Lifting Engineer for Heavy & Critical Lifting',
                '3. 2D Lifting Designer',
                '4. 3D Lifting Designer',
            ],
            'qms' => [
                '1. Laboratory Quality System Officer ISO/IEC 17025 (Petugas Sistem Mutu Laboratorium ISO/IEC 17025)',
                '2. Food Safety Management Officer (Petugas Sistem Keamanan Pangan)',
                '3. Panelis Terlatih Pengujian Sensori Pangan',
                '4. GLP Laboratory Technician/Teknisi Laboratorium Berbasis GLP',
                '5. Laboratory HSE Officer/Petugas K3L Laboratorium',
                '6. Laboratory Operations Officer/Pranata Laboratorium',
                '7. Quality Management System (ISO 9001) Officer',
                '8. QC Laboratory Analyst/Analis QC Laboratorium',
                '9. Quality Assurance Officer',
                '10. Research and Development Officer',
                '11. Regulatory Affairs Officer',
                '12. Environmental Management System (ISO 14001) Officer',
                '13. Sustainability Officer',
                '14. ESG Officer',
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
        $rl     = self::ruangLingkup();

        return [
            // Skema 1-5: SPMI / ToT / Tata Kelola — threshold beda per skema
            9 => ['criteria' => $spmi, 'threshold' => 7,  'ruang_lingkup' => $rl['spmi']], // Implementer Tata Kelola Organisasi Perguruan Tinggi
            5 => ['criteria' => $spmi, 'threshold' => 8,  'ruang_lingkup' => $rl['spmi']], // Lead Implementer SPMI Terintegrasi ISO 21001:2018
            6 => ['criteria' => $spmi, 'threshold' => 9,  'ruang_lingkup' => $rl['spmi']], // Auditor Internal SPMI Terintegrasi ISO 21001:2018
            7 => ['criteria' => $spmi, 'threshold' => 10, 'ruang_lingkup' => $rl['spmi']], // Lead Auditor SPMI Terintegrasi ISO 21001:2018
            8 => ['criteria' => $spmi, 'threshold' => 8,  'ruang_lingkup' => $rl['spmi']], // Training of Trainer (ToT) Outcome Based Education (OBE)

            // Skema 6-7 (dokumen): Lab ISO/IEC 17025 — "nilai > 8" dibulatkan jadi minimum 9
            3 => ['criteria' => $lab, 'threshold' => 9, 'ruang_lingkup' => $rl['lab']], // Auditor Internal Standar Laboratorium ISO/IEC 17025:2017
            4 => ['criteria' => $lab, 'threshold' => 7, 'ruang_lingkup' => $rl['lab']], // Lead Implementer Standar Laboratorium ISO/IEC 17025:2017

            // Skema 8-11 (dokumen): Lifting — threshold sama untuk semua
            13 => ['criteria' => $lift, 'threshold' => 6, 'ruang_lingkup' => $rl['lift']],
            14 => ['criteria' => $lift, 'threshold' => 6, 'ruang_lingkup' => $rl['lift']],
            15 => ['criteria' => $lift, 'threshold' => 6, 'ruang_lingkup' => $rl['lift']],
            16 => ['criteria' => $lift, 'threshold' => 6, 'ruang_lingkup' => $rl['lift']],

            // Skema 12-25 (dokumen): QMS/Lab Officer — threshold sama untuk semua
            10 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            11 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            12 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            17 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            18 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            19 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            20 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            21 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            22 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            23 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            24 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            25 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            26 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
            27 => ['criteria' => $qms, 'threshold' => 6, 'ruang_lingkup' => $rl['qms']],
        ];
    }

    /**
     * Ambil rubrik untuk classroom tertentu. Skema yang belum terdaftar di
     * FR.APL.03 (mis. skema baru) memakai rubrik QMS/Lab Officer sebagai default.
     */
    public static function for(int $classroomId): array
    {
        return self::map()[$classroomId] ?? [
            'criteria'      => self::qmsOfficerCriteria(),
            'threshold'     => 6,
            'ruang_lingkup' => self::ruangLingkup()['qms'],
        ];
    }

    /** Kalimat hasil, meniru format keterangan di dokumen FR.APL.03. */
    public static function resultSentence(int $score, int $threshold): string
    {
        $passed     = $score >= $threshold;
        $comparison = $passed ? "\u{2265} {$threshold}" : "< {$threshold}";
        $outcome    = $passed
            ? 'bisa langsung uji kompetensi melalui jalur portofolio'
            : 'harus training dulu';

        return "Nilai {$score} ({$comparison}) = {$outcome}";
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
