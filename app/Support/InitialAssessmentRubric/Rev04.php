<?php

namespace App\Support\InitialAssessmentRubric;

/**
 * FR.APL.03 Rev.04 — "Standar Kriteria dan Penilaian Awal Pemohon", satu halaman per
 * skema (SPMI dan Lab ISO/IEC 17025 masing-masing satu halaman untuk beberapa skema).
 * Label & skor disalin dari dokumen (berkas "R4-1", yang membetulkan daftar jabatan
 * Sustainability/ESG/EMS dan baris pelatihan 3D Lifting); salah ketik yang jelas dibetulkan
 * ("Traing", "Lifing", "Audi Internal").
 *
 * Ruang Lingkup hanya memuat skema yang diikuti peserta, tanpa nomor — halaman SPMI dan
 * Lab di dokumen mendaftar semua skemanya, tapi PDF per peserta cukup skemanya sendiri.
 *
 * Kunci kriteria/opsi tersimpan di initial_assessments.answers — jangan diganti setelah
 * dipakai. Kalau dokumen direvisi lagi, buat kelas Rev05 dan naikkan
 * InitialAssessmentRubric::CURRENT, jangan ubah kelas ini.
 *
 * 'sebutkan' => true : opsi "lainnya sebutkan", isiannya disimpan di answers['sebutkan'][kriteria].
 * 'score' => null    : kolom Score di dokumen kosong (tidak menambah nilai).
 * Kriteria 'multi' boleh dikosongkan; kriteria 'single' wajib diisi.
 */
class Rev04
{
    /** Skor menurun dari jumlah opsi sampai 1 (opsi pertama = skor tertinggi), kunci level_{skor}. */
    private static function ladder(array $labels): array
    {
        $score = count($labels);

        return array_map(function (string $label) use (&$score) {
            $option = ['key' => "level_{$score}", 'label' => $label, 'score' => $score];
            $score--;
            return $option;
        }, $labels);
    }

    private static function criterion(string $key, string $label, array $options, string $type = 'single', ?string $hint = null): array
    {
        return array_filter([
            'key'     => $key,
            'label'   => $label,
            'type'    => $type,
            'hint'    => $hint,
            'options' => $options,
        ], fn ($v) => $v !== null);
    }

    private static function pendidikanUmum(): array
    {
        return self::criterion('pendidikan', 'Strata Pendidikan', [
            ['key' => 's2_plus', 'label' => 'S2 dan lebih', 'score' => 3],
            ['key' => 'd4_s1', 'label' => 'D4 atau S1', 'score' => 2],
            ['key' => 'sma_smk_d3', 'label' => 'SMK/SMA atau D3', 'score' => 1],
        ]);
    }

    private static function prodiSainsSosial(): array
    {
        return self::criterion('prodi', 'Prodi Pendidikan', [
            ['key' => 'sains', 'label' => 'Sains', 'score' => 2],
            ['key' => 'sosial', 'label' => 'Sosial', 'score' => 1],
        ]);
    }

    private static function pengalamanBidang(array $labels): array
    {
        return self::criterion('pengalaman_kerja', 'Pengalaman kerja di Bidang yang Sama', self::ladder($labels));
    }

    private static function pelatihanRelevan(): array
    {
        return self::criterion('pelatihan', 'Pelatihan', [
            ['key' => 'relevan', 'label' => 'Pelatihan Relevan dengan Sertifikasi', 'score' => 2],
            ['key' => 'tidak_relevan', 'label' => 'Pelatihan tidak Relevan dengan Sertifikasi', 'score' => 1],
        ]);
    }

    // ── Halaman 1: SPMI / ToT / Tata Kelola ─────────────────────────────

    private static function spmi(string $ruangLingkup): array
    {
        return [
            'criteria' => [
                self::criterion('pendidikan', 'Strata Pendidikan', [
                    ['key' => 's3', 'label' => 'S3', 'score' => 3],
                    ['key' => 's2', 'label' => 'S2', 'score' => 2],
                    ['key' => 's1', 'label' => 'S1', 'score' => 1],
                ]),
                self::criterion('pengalaman_kerja', 'Pengalaman kerja di Bidang yang Sama', [
                    ['key' => 'pimpinan_pm', 'label' => 'Kepala/Pimpinan Penjaminan Mutu; Kepala SPMI/LPM', 'score' => 6],
                    ['key' => 'pimpinan_fakultas', 'label' => 'Pimpinan Fakultas / Biro / Lembaga / UPT', 'score' => 5],
                    ['key' => 'pimpinan_prodi', 'label' => 'Pimpinan Prodi', 'score' => 4],
                    ['key' => 'staf_pm', 'label' => 'Staf Penjaminan Mutu', 'score' => 3],
                    ['key' => 'dosen_tendik', 'label' => 'Dosen / Tendik', 'score' => 2],
                    ['key' => 'asisten_dosen', 'label' => 'Asisten Dosen', 'score' => 1],
                ]),
                self::criterion('durasi_pm', 'Durasi kerja di Penjaminan Mutu', [
                    ['key' => 'gt10', 'label' => 'diatas 10 tahun', 'score' => 4],
                    ['key' => 'y6_10', 'label' => '6 - 10 tahun', 'score' => 3],
                    ['key' => 'y3_5', 'label' => '3 - 5 tahun', 'score' => 2],
                    ['key' => 'y1_3', 'label' => '1 - 3 tahun', 'score' => 1],
                    ['key' => 'none', 'label' => 'Tidak ada pengalaman', 'score' => 0],
                ]),
                self::criterion('durasi_dosen', 'Durasi Kerja Dosen / Tendik', [
                    ['key' => 'gt10', 'label' => 'diatas 10 tahun', 'score' => 5],
                    ['key' => 'y6_10', 'label' => '6 - 10 tahun', 'score' => 4],
                    ['key' => 'y3_5', 'label' => '3 - 5 tahun', 'score' => 3],
                    ['key' => 'y1_3', 'label' => '1 - 3 tahun', 'score' => 2],
                    ['key' => 'lt1', 'label' => '< 1 Tahun', 'score' => 1],
                ]),
                self::pelatihanRelevan(),
            ],
            'threshold'     => 7,
            'ruang_lingkup' => [$ruangLingkup],
        ];
    }

    // ── Halaman 2: Standar Laboratorium ISO/IEC 17025 ────────────────────

    private static function lab(string $ruangLingkup): array
    {
        return [
            'criteria' => [
                self::pendidikanUmum(),
                self::prodiSainsSosial(),
                self::criterion('pengalaman_kerja', 'Pengalaman kerja di lab', [
                    ['key' => 'kepala_lab', 'label' => 'Kepala Laboratorium', 'score' => 6],
                    ['key' => 'manajer_teknis', 'label' => 'Manajer Teknis', 'score' => 5],
                    ['key' => 'manajer_mutu', 'label' => 'Manajer Mutu', 'score' => 4],
                    ['key' => 'penyelia_qa', 'label' => 'Penyelia/ QA', 'score' => 3],
                    ['key' => 'analis', 'label' => 'Analis/Teknisi/QC', 'score' => 2],
                    ['key' => 'lainnya', 'label' => 'lainnya sebutkan', 'score' => 1, 'sebutkan' => true],
                ]),
                self::criterion('durasi_kerja', 'Durasi kerja di Lab', [
                    ['key' => 'gt15', 'label' => 'diatas 15 tahun', 'score' => 4],
                    ['key' => 'y11_15', 'label' => '11 - 15 tahun', 'score' => 3],
                    ['key' => 'y6_10', 'label' => '6 - 10 tahun', 'score' => 2],
                    ['key' => 'y1_5', 'label' => '1 - 5 tahun', 'score' => 1],
                ]),
                self::criterion('pelatihan_topik', 'Pelatihan', [
                    ['key' => 'iso17025', 'label' => 'Pemahaman ISO 17025', 'score' => 2],
                    ['key' => 'dokumen', 'label' => 'Penyusunan dokumen', 'score' => 1],
                    ['key' => 'validasi', 'label' => 'Validasi metode pengujian', 'score' => 1],
                    ['key' => 'penjaminan_mutu', 'label' => 'Penjaminan mutu pengujian', 'score' => 1],
                    ['key' => 'ketidakpastian', 'label' => 'Ketidakpastian pengukuran', 'score' => 1],
                    ['key' => 'audit_internal', 'label' => 'Audit Internal', 'score' => 1],
                    ['key' => 'lainnya', 'label' => 'lainnya sebutkan', 'score' => null, 'sebutkan' => true],
                ], 'multi', 'Boleh pilih lebih dari satu.'),
                self::criterion('pengalaman_auditor', 'Pengalaman Auditor Internal', [
                    ['key' => 'surat_tugas', 'label' => 'Surat Tugas', 'score' => 1],
                ], 'multi', 'Centang bila ada surat tugas sebagai auditor internal.'),
            ],
            'threshold'     => 7,
            'ruang_lingkup' => [$ruangLingkup],
        ];
    }

    // ── Halaman 3–6: Lifting ─────────────────────────────────────────────

    private static function liftingDurasi(): array
    {
        return self::criterion('durasi_kerja', 'Durasi Pengalaman Kerja', [
            ['key' => 'gt5', 'label' => 'diatas 5 tahun', 'score' => 5],
            ['key' => 'y4_5', 'label' => '4-5 tahun', 'score' => 4],
            ['key' => 'y2_3', 'label' => '2-3 tahun', 'score' => 3],
            ['key' => 'm6_y1', 'label' => '6 Bulan - 1 tahun', 'score' => 2],
            ['key' => 'm0_6', 'label' => '0-6 bulan', 'score' => 1],
        ]);
    }

    /** Lifting Engineer (Medium / Heavy & Critical): pendidikan teknik, pelatihan hanya "Relevan". */
    private static function liftingEngineer(string $ruangLingkup, array $pengalaman): array
    {
        return [
            'criteria' => [
                self::criterion('pendidikan', 'Strata Pendidikan', [
                    ['key' => 's2_plus', 'label' => 'S2 dan lebih', 'score' => 3],
                    ['key' => 'd4_s1', 'label' => 'D4 atau S1', 'score' => 2],
                    ['key' => 'd3_teknik', 'label' => 'D3 Teknik', 'score' => 1],
                ]),
                self::criterion('prodi', 'Prodi Pendidikan', [
                    ['key' => 'teknik', 'label' => 'Teknik Sipil, Mesin, Kelautan, Perkapalan, Industri, Lainnya', 'score' => 2],
                    ['key' => 'non_teknik', 'label' => 'Non Teknik Sipil, Mesin, Kelautan, Perkapalan', 'score' => 1],
                ]),
                self::pengalamanBidang($pengalaman),
                self::liftingDurasi(),
                self::pelatihanRelevanSaja(),
            ],
            'threshold'     => 6,
            'ruang_lingkup' => [$ruangLingkup],
        ];
    }

    /** Pelatihan dengan satu baris "Relevan" saja — dicentang bila ada, kosong = 0. */
    private static function pelatihanRelevanSaja(): array
    {
        return self::criterion('pelatihan', 'Pelatihan', [
            ['key' => 'relevan', 'label' => 'Pelatihan Relevan dengan Sertifikasi', 'score' => 2],
        ], 'multi', 'Centang bila ada pelatihan yang relevan.');
    }

    /** 2D / 3D Lifting Designer: pendidikan & prodi umum, pelatihan hanya "Relevan". */
    private static function liftingDesigner(string $ruangLingkup, array $pengalaman): array
    {
        return [
            'criteria' => [
                self::pendidikanUmum(),
                self::prodiSainsSosial(),
                self::pengalamanBidang($pengalaman),
                self::liftingDurasi(),
                self::pelatihanRelevanSaja(),
            ],
            'threshold'     => 6,
            'ruang_lingkup' => [$ruangLingkup],
        ];
    }

    // ── Halaman 7–21: Officer / Laboratorium / Manajemen ─────────────────

    private static function officer(string $ruangLingkup, array $pengalaman): array
    {
        return [
            'criteria' => [
                self::pendidikanUmum(),
                self::prodiSainsSosial(),
                self::pengalamanBidang($pengalaman),
                self::criterion('durasi_kerja', 'Durasi Pengalaman Kerja', [
                    ['key' => 'gt5', 'label' => 'diatas 5 tahun', 'score' => 4],
                    ['key' => 'y4_5', 'label' => '4-5 tahun', 'score' => 3],
                    ['key' => 'y2_3', 'label' => '2-3 tahun', 'score' => 2],
                    ['key' => 'y0_1', 'label' => '0-1 tahun', 'score' => 1],
                ]),
                self::pelatihanRelevan(),
            ],
            'threshold'     => 6,
            'ruang_lingkup' => [$ruangLingkup],
        ];
    }

    /**
     * Peta classroom_id => rubrik. ID mengikuti tabel classrooms produksi (kode kelas di komentar).
     * Threshold = nilai minimum lulus (>=).
     */
    public static function map(): array
    {
        return [
            // Halaman 1 — SPMI / ToT / Tata Kelola
            6 => self::spmi('Audit Internal SPMI Terintegrasi ISO 21001:2018'), // AIL
            7 => self::spmi('Lead Auditor SPMI Terintegrasi ISO 21001:2018'), // LAD
            5 => self::spmi('Lead Implementer SPMI Terintegrasi ISO 21001:2018'), // IMR
            8 => self::spmi('Training of Trainer (ToT) Outcome Based Education (OBE)'), // ToT
            9 => self::spmi('Implementer Tata Kelola Organisasi Perguruan Tinggi'), // TKO

            // Halaman 2 — Lab ISO/IEC 17025
            3 => self::lab('Audit Internal Standar Laboratorium ISO/IEC 17025:2017'), // AUI
            4 => self::lab('Lead Implementer Standar Laboratorium ISO/IEC 17025:2017'), // LIM

            // Halaman 3–6 — Lifting
            13 => self::liftingEngineer('Lifting Engineer for Medium Lifting', [ // LEM
                'Senior Lifting Engineer',
                'Lifting Engineer/yang bertanggung jawab atas lifting plan',
                'Fresh Graduate D3/S1 Teknik dengan pelatihan Lifting Engineer',
                'Lifting Supervisor',
                'Senior Lifting Drafter yang terlibat penyusunan lifting plan',
                'Junior Lifting Drafter yang terlibat penyusunan lifting plan',
            ]),
            14 => self::liftingEngineer('Lifting Engineer for Heavy & Critical Lifting', [ // LHC
                'Lead/Senior Lifting Engineer untuk Heavy & Critical Lift; Lifting Technical Authority',
                'Lifting Engineer/Heavy Lift Engineer yang menyusun atau memvalidasi critical lifting plan',
                'Lifting Supervisor/Rigging Engineer senior pada heavy/critical lifting',
                'Lifting Planner/Engineer pendukung pada tandem lift/complex lifting',
                'Junior Engineer/Designer/Rigger senior yang terlibat proyek heavy lifting',
                'Junior Drafter/Rigger/Fresh graduate dengan paparan operasi lifting',
            ]),
            15 => self::liftingDesigner('2D Lifting Designer', [ // LDT — 4 jenjang (skor 4–1)
                'Lead Lifting Designer',
                'Senior 2D Lifting Designer',
                'Junior 2D Lifting Designer',
                'CAD operator yang lulus pelatihan Lifting Drafting',
            ]),
            16 => self::liftingDesigner('3D Lifting Designer', [ // DLD
                'Lead 3D Lifting Designer/Lead Simulation Engineer/Lifting Engineering Lead',
                'Senior 3D Lifting Designer/Lifting Simulation Engineer',
                '3D Lifting Designer/CAD 3D Engineer untuk lifting/rigging',
                '3D Modeler/Designer yang terlibat simulasi lifting',
                'Junior 3D Designer/CAD Operator pendukung lifting',
                'Fresh graduate/drafter dengan paparan software 3D lifting',
            ]),

            // Halaman 7–21
            12 => self::officer('Laboratory Quality System Officer ISO/IEC 17025 (Petugas Sistem Mutu Laboratorium ISO/IEC 17025)', [ // LQO (dulu ISL)
                'Kepala Lab/Manajer Mutu/Quality Manager Laboratorium',
                'Manajer Teknis/Deputy Quality Manager/Koordinator Sistem Mutu',
                'QA Laboratorium/Penyelia/Staf Mutu Senior',
                'Analis Senior/Document Controller/Staff QA',
                'Analis/Teknisi yang terlibat pengendalian mutu dan dokumen',
                'Asisten Lab/Fresh graduate dengan paparan sistem mutu',
            ]),
            11 => self::officer('Food Safety Management Officer (Petugas Sistem Keamanan Pangan)', [ // FMO (dulu FSI)
                'Food Safety Manager/QA Manager/Quality Head di industri pangan',
                'Food Safety Team Leader/HACCP Coordinator/QA Supervisor',
                'QA/QC Officer/Food Safety Officer yang aktif mengelola sistem',
                'QC/Production Supervisor yang terlibat HACCP/ISO 22000',
                'QC Analyst/Production Staff yang menjalankan kontrol keamanan pangan',
                'Fresh graduate/magang industri pangan',
            ]),
            10 => self::officer('Panelis Terlatih Pengujian Sensori Pangan', [ // PSP
                'Sensory Laboratory Manager/Panel Leader/Sensory Program Lead',
                'Senior Sensory Analyst/Panel Coordinator',
                'Sensory Analyst/QA Sensory Officer',
                'QC/QA Analyst yang rutin melakukan pengujian sensori',
                'Panelis terlatih/teknisi laboratorium sensori',
                'Panelis pemula/asisten lab/fresh graduate',
            ]),
            17 => self::officer('GLP Laboratory Technician/Teknisi Laboratorium Berbasis GLP', [ // GLP
                'Laboratory Manager/Study Director/QA Manager pada laboratorium GLP',
                'Senior Scientist/Senior Technician/QA GLP',
                'Laboratory Technician/Analyst berpengalaman di lingkungan GLP',
                'Analis/Teknisi yang menjalankan prosedur GLP secara rutin',
                'Junior Technician/Research Assistant',
                'Asisten Lab/Fresh graduate dengan paparan GLP',
            ]),
            18 => self::officer('Laboratory HSE Officer/Petugas K3L Laboratorium', [ // K3L
                'HSE Manager/Laboratory HSE Manager/K3L Lead',
                'HSE Officer Senior/Ahli K3/Lab Safety Officer senior',
                'HSE Officer/Lab Safety Officer/K3L Officer',
                'Penyelia Lab/QA/Manajer Teknis yang memegang fungsi K3L',
                'Analis/Teknisi yang menjadi anggota tim K3/HSE',
                'Asisten Lab/Fresh graduate dengan paparan K3L',
            ]),
            19 => self::officer('Laboratory Operations Officer/Pranata Laboratorium', [ // LOP
                'Kepala Laboratorium/Operations Manager',
                'Manajer Teknis/Penyelia Laboratorium',
                'Senior Analyst/Senior Laboratory Technician',
                'Pranata Laboratorium/Analyst/Laboratory Technician',
                'Junior Analyst/Junior Technician',
                'Asisten Lab/Fresh graduate',
            ]),
            20 => self::officer('Quality Management System (ISO 9001) Officer', [ // QMS
                'Quality Manager/Management Representative/Head of QA',
                'QA Manager/Lead Auditor/QMS Coordinator',
                'QA Officer/Internal Auditor/QMS Officer',
                'QC/QA Staff/Document Controller senior',
                'Staff operasional yang aktif menjalankan QMS',
                'Fresh graduate/magang dengan paparan ISO 9001',
            ]),
            21 => self::officer('QC Laboratory Analyst/Analis QC Laboratorium', [ // QCA
                'QC Laboratory Manager/Head of QC',
                'QC Supervisor/Senior Analyst',
                'QC Analyst/Analytical Chemist/Laboratory Analyst',
                'Junior Analyst/Technician yang rutin melakukan pengujian',
                'Laboratory Technician/Assistant Analyst',
                'Asisten lab/fresh graduate',
            ]),
            22 => self::officer('Quality Assurance Officer', [ // QAO
                'QA Manager/Head of Quality',
                'QA Supervisor/Quality System Coordinator',
                'QA Officer/Quality Engineer/Internal Auditor',
                'QA Staff/Document Control/CAPA Coordinator',
                'QC/Operational Staff yang terlibat kegiatan QA',
                'Fresh graduate/magang QA',
            ]),
            23 => self::officer('Research and Development Officer', [ // RDO
                'R&D Manager/Research Director/Principal Scientist',
                'R&D Supervisor/Senior Researcher/Project Lead',
                'R&D Officer/Researcher/Product Development Scientist',
                'Junior Researcher/Product Development Officer',
                'Research Assistant/Lab Analyst pendukung R&D',
                'Fresh graduate/magang R&D',
            ]),
            24 => self::officer('Regulatory Affairs Officer', [ // RAQ
                'Regulatory Affairs Manager/Head of Regulatory/Compliance Manager',
                'Senior Regulatory Affairs Specialist/Supervisor',
                'Regulatory Affairs Officer/Specialist',
                'Registration Officer/Compliance Officer/Document Specialist',
                'Junior Regulatory Staff/Admin Registrasi',
                'Fresh graduate/magang regulatory',
            ]),
            25 => self::officer('Sustainability Officer', [ // SBO
                'Head of Sustainability/ESG Director/CSR & Sustainability Manager',
                'Sustainability Manager/Senior Sustainability Specialist',
                'Sustainability Officer/CSR Officer/ESG Specialist',
                'HSE/Corporate Secretary/Planning Officer yang mengelola program keberlanjutan',
                'Data/Reporting/Program Staff pendukung sustainability',
                'Fresh graduate/magang sustainability/CSR',
            ]),
            26 => self::officer('ESG Officer', [ // ESG
                'Head of ESG/ESG Director/Sustainability Director',
                'ESG Manager/Senior ESG Specialist/Sustainability Manager',
                'ESG Officer/Sustainability Officer/ESG Analyst',
                'CSR/Risk/Environmental/Corporate Secretary Officer dengan tugas ESG',
                'Data/Reporting/Program Staff yang mendukung ESG',
                'Fresh graduate/magang ESG/sustainability',
            ]),
            27 => self::officer('Environmental Management System (ISO 14001) Officer', [ // EMS
                'Environmental Manager/HSE Manager/EMS Manager',
                'Environmental Supervisor/ISO 14001 Lead Auditor/EMS Coordinator',
                'Environmental Officer/HSE Officer/EMS Officer',
                'Environmental Staff/Compliance Staff yang aktif pada ISO 14001',
                'Operational Staff yang terlibat pengelolaan lingkungan',
                'Fresh graduate/magang lingkungan',
            ]),
            28 => self::officer('Corporate Legal Officer', [ // CLO
                'Head of Legal/Legal Director/General Counsel',
                'Legal Manager/Senior Corporate Counsel',
                'Legal Supervisor/Corporate Legal Counsel/Compliance Supervisor',
                'Legal Officer/Compliance Officer/Industrial Relations Officer/Corporate Secretary Officer',
                'Legal Staff/Legal Admin/Junior Legal Officer',
                'Fresh graduate/magang hukum',
            ]),
        ];
    }

    /**
     * Skema yang tidak ada di dokumen Rev.04: pola halaman officer dengan jenjang jabatan
     * umum. Ruang lingkup kosong → PDF menampilkan nama skema.
     */
    public static function fallback(): array
    {
        return array_merge(self::officer('', [
            'Kepala / Manajer / Direktur',
            'Supervisor / Koordinator senior',
            'Officer / Spesialis',
            'Staf senior yang terlibat di bidang skema',
            'Staf / Analis / Teknisi pendukung',
            'Fresh graduate / magang',
        ]), ['ruang_lingkup' => []]);
    }

    /** Kalimat Ketentuan di Rev.04. */
    public static function outcome(): array
    {
        return [
            'pass' => 'memenuhi persyaratan dasar dan diperbolehkan lanjut ke tahap berikutnya',
            'fail' => 'belum memenuhi persyaratan dasar dan tidak diperbolehkan lanjut ke tahap berikutnya',
        ];
    }
}
