<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\ClassroomCompetencyUnit;
use App\Models\ClassroomDocumentRequirement;
use Illuminate\Database\Seeder;

class CompetencyUnitsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Persyaratan dokumen ─────────────────────────────────────────
        $reqPt = [
            ['code' => 'IJAZAH',     'label' => 'Ijazah terakhir',                                              'description' => null,                                                                                                                                            'is_required' => true,  'order' => 1],
            ['code' => 'JABATAN_PT', 'label' => 'Pengalaman bekerja di bagian Penjaminan Mutu / Jabatan struktural lainnya', 'description' => 'Jabatan dan Lama Menjabat. Dapat dibuktikan dengan SK atau keterangan dari Perguruan Tinggi. (Jika ada)', 'is_required' => false, 'order' => 2],
            ['code' => 'PENGALAMAN', 'label' => 'Pengalaman kerja sebagai dosen/tendik',                        'description' => 'SK atau surat keterangan',                                                                                                                     'is_required' => true,  'order' => 3],
            ['code' => 'CV',         'label' => 'CV',                                                           'description' => null,                                                                                                                                            'is_required' => true,  'order' => 4],
            ['code' => 'SERTIFIKAT', 'label' => 'Sertifikat Pelatihan',                                         'description' => null,                                                                                                                                            'is_required' => true,  'order' => 5],
        ];

        $reqNonPt = [
            ['code' => 'IJAZAH',     'label' => 'Ijazah terakhir',                                              'description' => null,                                                                                                                                                             'is_required' => true,  'order' => 1],
            ['code' => 'JABATAN_LB', 'label' => 'Pengalaman bekerja di Laboratorium / Jabatan struktural lainnya', 'description' => 'Jabatan dan Lama Menjabat. Dapat dibuktikan dengan SK atau keterangan dari Perguruan Tinggi / perusahaan. (Jika ada)', 'is_required' => false, 'order' => 2],
            ['code' => 'PENGALAMAN', 'label' => 'Pengalaman kerja di laboratorium',                             'description' => 'SK atau surat keterangan',                                                                                                                                      'is_required' => true,  'order' => 3],
            ['code' => 'CV',         'label' => 'CV',                                                           'description' => null,                                                                                                                                                             'is_required' => true,  'order' => 4],
            ['code' => 'SERTIFIKAT', 'label' => 'Sertifikat pelatihan yang relevan',                            'description' => null,                                                                                                                                                             'is_required' => true,  'order' => 5],
        ];

        // ── Data skema ──────────────────────────────────────────────────
        // Format unit: [kode_unit, judul_unit (ID), judul_unit_en (EN, opsional), kode_unit_asli (ref. SKKNI, opsional)]
        $skemas = [

            // ── A ──────────────────────────────────────────────────────
            [
                'title'           => 'Auditor Internal SPMI terintegrasi ISO 21001:2018',
                'classrooms_code' => 'AIL',
                'kode_skema'      => 'EDUKIA-AIL-2024-001',
                'req'             => 'pt',
                'units'      => [
                    ['SP.AIL.001.01', 'Memahami Pengetahuan Dasar Terkait Audit'],
                    ['SP.AIL.002.01', 'Melaksanakan Kegiatan Audit Internal'],
                    ['SP.AIL.003.01', 'Memahami Konsep Integrasi SPMI dan ISO 21001:2018'],
                    ['SP.AIL.004.01', 'Mengevaluasi Penerapan Integrasi Siklus Plan ISO 21001:2018 ke dalam SPMI'],
                    ['SP.AIL.005.01', 'Mengevaluasi Penerapan Integrasi Siklus Do ISO 21001:2018 ke dalam SPMI'],
                    ['SP.AIL.006.01', 'Mengevaluasi Penerapan Integrasi Siklus Check ISO 21001:2018 ke dalam SPMI'],
                    ['SP.AIL.007.01', 'Mengevaluasi Penerapan Integrasi Siklus Act ISO 21001:2018 ke dalam SPMI'],
                ],
            ],

            // ── B ──────────────────────────────────────────────────────
            [
                'title'           => 'Lead Auditor SPMI terintegrasi ISO 21001:2018',
                'classrooms_code' => 'LAD',
                'kode_skema'      => 'EDUKIA-LAD-2024-002',
                'req'             => 'pt',
                'units'      => [
                    ['SP.LAD.001.01', 'Memahami Pengetahuan Dasar Terkait Audit'],
                    ['SP.LAD.002.01', 'Melaksanakan Kegiatan Audit Internal'],
                    ['SP.LAD.003.01', 'Memahami Konsep Integrasi SPMI dan ISO 21001:2018'],
                    ['SP.LAD.004.01', 'Mengevaluasi Penerapan Integrasi Siklus Plan ISO 21001:2018 ke dalam SPMI'],
                    ['SP.LAD.005.01', 'Mengevaluasi Penerapan Integrasi Siklus Do ISO 21001:2018 ke dalam SPMI'],
                    ['SP.LAD.006.01', 'Mengevaluasi Penerapan Integrasi Siklus Check ISO 21001:2018 ke dalam SPMI'],
                    ['SP.LAD.007.01', 'Mengevaluasi Penerapan Integrasi Siklus Act ISO 21001:2018 ke dalam SPMI'],
                    ['SP.LAD.008.01', 'Mengelola Program Audit Internal'],
                ],
            ],

            // ── C ──────────────────────────────────────────────────────
            [
                'title'           => 'Lead Implementer SPMI Terintegrasi ISO 21001:2018',
                'classrooms_code' => 'IMR',
                'kode_skema'      => 'EDUKIA-IMR-2024-003',
                'req'             => 'pt',
                'units'      => [
                    ['SP.IMR.001.01', 'Mengelola Implementasi Standar'],
                    ['SP.IMR.002.01', 'Memahami Konsep SPMI Terintegrasi ISO 21001:2018'],
                    ['SP.IMR.003.01', 'Menyiapkan Kebutuhan Dokumen SPMI'],
                    ['SP.IMR.004.01', 'Menerapkan Siklus Plan ISO 21001:2018 ke dalam SPMI'],
                    ['SP.IMR.005.01', 'Menerapkan Siklus Do ISO 21001:2018 ke dalam SPMI'],
                    ['SP.IMR.006.01', 'Menerapkan Siklus Check ISO 21001:2018 ke dalam SPMI'],
                    ['SP.IMR.007.01', 'Menerapkan Siklus Act ISO 21001:2018 ke dalam SPMI'],
                ],
            ],

            // ── D ──────────────────────────────────────────────────────
            [
                'title'           => 'Training of Trainer (ToT) Outcome Based Education (OBE)',
                'classrooms_code' => 'ToT',
                'kode_skema'      => 'EDUKIA-ToT-2024-004',
                'req'             => 'pt',
                'units'      => [
                    ['SP.ToT.001.01', 'Mendesain Program Pembelajaran Outcome Based Education (OBE)'],
                    ['SP.ToT.002.01', 'Menyusun RPS dan Bahan Ajar Pembelajaran Outcome Based Education (OBE)'],
                    ['SP.ToT.003.01', 'Merencanakan Pembelajaran Outcome Based Education (OBE)'],
                    ['SP.ToT.004.01', 'Melaksanakan Pembelajaran Outcome Based Education (OBE)'],
                    ['SP.ToT.005.01', 'Mengevaluasi Hasil Pembelajaran Outcome Based Education (OBE)'],
                    ['SP.ToT.006.01', 'Mengembangkan Program Pembelajaran Outcome Based Education (OBE)'],
                ],
            ],

            // ── E ──────────────────────────────────────────────────────
            [
                'title'           => 'Implementer Tata Kelola Organisasi Perguruan Tinggi',
                'classrooms_code' => 'TKO',
                'kode_skema'      => 'EDUKIA-TKO-2024-005',
                'req'             => 'pt',
                'units'      => [
                    ['SP.TKO.001.01', 'Menyusun Rencana Bisnis Organisasi Perguruan Tinggi'],
                    ['SP.TKO.002.01', 'Merancang Design Organisasi Perguruan Tinggi (Membangun proses bisnis)'],
                    ['SP.TKO.003.01', 'Mengelola Tata Pamong Organisasi Perguruan Tinggi'],
                    ['SP.TKO.004.01', 'Mengembangkan Pola Kepemimpinan'],
                    ['SP.TKO.005.01', 'Mengelola Organisasi Perguruan Tinggi'],
                    ['SP.TKO.006.01', 'Menerapkan Etika dan Integritas Organisasi Perguruan Tinggi'],
                ],
            ],

            // ── F ──────────────────────────────────────────────────────
            [
                'title'           => 'Auditor Internal Standar Laboratorium ISO/IEC 17025:2017',
                'classrooms_code' => 'AUI',
                'kode_skema'      => 'EDUKIA-AUI-2024-006',
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.AUI.001.01', 'Memahami Pengetahuan Dasar terkait Audit Internal'],
                    ['SP.AUI.002.01', 'Melaksanakan Kegiatan Audit Internal'],
                    ['SP.AUI.003.01', 'Memahami Konsep Audit Internal ISO/IEC 17025:2017'],
                    ['SP.AUI.004.01', 'Mengevaluasi Penerapan Siklus Plan ISO/IEC 17025:2017'],
                    ['SP.AUI.005.01', 'Mengevaluasi Penerapan Siklus Do ISO/IEC 17025:2017'],
                    ['SP.AUI.006.01', 'Mengevaluasi Penerapan Siklus Check ISO/IEC 17025:2017'],
                    ['SP.AUI.007.01', 'Mengevaluasi Penerapan Siklus Act ISO/IEC 17025:2017'],
                    ['SP.AUI.008.01', 'Mengelola Program Audit Internal'],
                ],
            ],

            // ── G ──────────────────────────────────────────────────────
            [
                'title'           => 'Lead Implementer Standar Laboratorium ISO/IEC 17025:2017',
                'classrooms_code' => 'LIM',
                'kode_skema'      => 'EDUKIA-LIM-2024-007',
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.LIM.001.01', 'Memahami Implementasi dan Interpretasi Standar ISO/IEC 17025:2017'],
                    ['SP.LIM.002.01', 'Menyiapkan Kebutuhan Dokumen ISO/IEC 17025:2017'],
                    ['SP.LIM.003.01', 'Menerapkan manajemen laboratorium'],
                    ['SP.LIM.004.01', 'Menerapkan Siklus Plan ISO/IEC 17025:2017'],
                    ['SP.LIM.005.01', 'Menerapkan Siklus Do ISO/IEC 17025:2017'],
                    ['SP.LIM.006.01', 'Menerapkan Siklus Check ISO/IEC 17025:2017'],
                    ['SP.LIM.007.01', 'Menerapkan Siklus Act ISO/IEC 17025:2017'],
                ],
            ],

            // ── H ──────────────────────────────────────────────────────
            [
                'title'           => 'Lifting Engineer for Medium Lifting',
                'classrooms_code' => 'LEM',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['F.410140.001.01', 'Menerapkan Komunikasi di Tempat Kerja', 'Implementing Workplace Communication'],
                    ['F.42LFE00.001.1', 'Menyusun pekerjaan persiapan perencanaan operasi pesawat angkat & angkut', 'Preparing Preliminary Work for Lifting and Mechanical Handling Operations'],
                    ['F.42LFE00.002.1', 'Menyusun rencana operasi pengangkatan (lifting plan) untuk beban kurang dari 50 ton', 'Developing a Lifting Plan for Loads Under 50 Tons'],
                    ['F.42LFE00.003.1', 'Melakukan kajian risiko dan pengendaliannya', 'Conducting Risk Assessment and Control'],
                    ['F.42LFE00.004.1', 'Mengawasi proses pengangkatan dan pemasangan beban sesuai Lifting Plan', 'Supervising Lifting and Installation Operations in Accordance with the Lifting Plan'],
                    ['F.42LFE00.005.1', 'Melakukan evaluasi kinerja pelaksanaan Lifting Plan', 'Evaluating the Performance of Lifting Plan Execution'],
                ],
            ],

            // ── I ──────────────────────────────────────────────────────
            [
                'title'           => 'Lifting Engineer for Heavy & Critical Lifting Operation',
                'classrooms_code' => 'LHC',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['F.410140.001.01', 'Menerapkan Komunikasi di Tempat Kerja', 'Implementing Workplace Communication'],
                    ['F.42LFE00.001.1', 'Menyusun pekerjaan persiapan perencanaan operasi pesawat angkat & angkut', 'Preparing Preliminary Work for Lifting and Mechanical Handling Operations'],
                    ['F.42LFE00.002.1', 'Menyusun rencana operasi pengangkatan (lifting plan) untuk beban lebih dari 50 ton atau berjenis critical lifting', 'Developing a Lifting Plan for Loads Over 50 Tons or Critical Lifting Operations'],
                    ['F.42LFE00.003.1', 'Melakukan kajian risiko dan pengendaliannya', 'Conducting Risk Assessment and Control'],
                    ['F.42LFE00.004.1', 'Mengawasi proses pengangkatan dan pemasangan beban sesuai Lifting Plan', 'Supervising Lifting and Installation Operations in Accordance with the Lifting Plan'],
                    ['F.42LFE00.005.1', 'Melakukan evaluasi kinerja pelaksanaan Lifting Plan', 'Evaluating the Performance of Lifting Plan Execution'],
                ],
            ],

            // ── J ──────────────────────────────────────────────────────
            [
                'title'           => '2D Lifting Designer',
                'classrooms_code' => 'LDT',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.LDE2.001.01', 'Menerapkan Komunikasi di Tempat Kerja', 'Implementing Workplace Communication'],
                    ['SP.LDE2.002.01', 'Memahami Spesifikasi Crane & Lifting Gear', 'Understanding Crane and Lifting Gear Specifications'],
                    ['SP.LDE2.003.01', 'Memahami Kaidah Operasi Lifting yang Aman', 'Comprehending Safe Lifting Operation Principles'],
                    ['SP.LDE2.004.01', 'Memahami Lifting/Rigging Study', 'Understanding Lifting and Rigging Studies'],
                    ['SP.LDE2.005.01', 'Mampu membuat Lifting Plan Drawing 2D', 'Proficiency in Developing 2D Lifting Plan Drawings'],
                ],
            ],

            // ── K ──────────────────────────────────────────────────────
            [
                'title'           => '3D Lifting Designer',
                'classrooms_code' => 'DLD',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.LDE3.001.01', 'Menerapkan Komunikasi di Tempat Kerja', 'Implementing Workplace Communication'],
                    ['SP.LDE3.002.01', 'Memahami Spesifikasi Crane & Lifting Gear', 'Understanding Crane and Lifting Gear Specifications'],
                    ['SP.LDE3.003.01', 'Memahami Kaidah Operasi Lifting yang Aman', 'Comprehending Safe Lifting Operation Principles'],
                    ['SP.LDE3.004.01', 'Memahami Lifting/Rigging Study', 'Understanding Lifting and Rigging Studies'],
                    ['SP.LDE3.005.01', 'Mampu membuat Lifting Modelling 3D & Lifting Plan Drawing', 'Proficiency in 3D Lifting Modelling and Developing Lifting Plan Drawings'],
                ],
            ],

            // ── L ──────────────────────────────────────────────────────
            [
                'title'           => 'Laboratory Quality System Officer ISO/IEC 17025 / Petugas Sistem Mutu Laboratorium ISO/IEC 17025',
                'classrooms_code' => 'LQO',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.ISL.001.01', 'Memahami Prinsip Ketidakberpihakan dan Kerahasiaan (Klausul 4 ISO 17025)', 'Understanding the Principles of Impartiality and Confidentiality (ISO 17025 Clause 4)'],
                    ['SP.ISL.002.01', 'Memahami Struktur Organisasi Laboratorium yang Sesuai (Klausul 5 ISO 17025)', 'Understanding Compliant Laboratory Organizational Structures (ISO 17025 Clause 5)'],
                    ['SP.ISL.003.01', 'Memahami Pengelolaan Persyaratan Sumber Daya (Klausul 6 ISO 17025)', 'Understanding the Management of Resource Requirements (ISO 17025 Clause 6)'],
                    ['SP.ISL.004.01', 'Memahami dan Menganalisis Persyaratan Proses (Klausul 7 ISO 17025)', 'Understanding and Analyzing Process Requirements (ISO 17025 Clause 7)'],
                    ['SP.ISL.005.01', 'Memahami Pengembangan Sistem Manajemen Laboratorium (Klausul 8 ISO 17025)', 'Understanding the Development of Laboratory Management Systems (ISO 17025 Clause 8)'],
                ],
            ],

            // ── M ──────────────────────────────────────────────────────
            [
                'title'           => 'Food Safety Management Officer / Petugas Sistem Keamanan Pangan',
                'classrooms_code' => 'FMO',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.IKP.001.01', 'Menguasai Prinsip Dasar dan Regulasi Keamanan Pangan', 'Mastering Fundamental Principles and Food Safety Regulatory Compliance'],
                    ['SP.IKP.002.01', 'Mengimplementasikan Program Prasyarat (PRPs - Prerequisite Programs)', 'Implementing Prerequisite Programs (PRPs)'],
                    ['SP.IKP.003.01', 'Mengembangkan dan Menerapkan Rencana HACCP', 'Developing and Implementing a HACCP Plan'],
                    ['SP.IKP.004.01', 'Mengelola Pengendalian Operasional Keamanan Pangan', 'Managing Food Safety Operational Controls'],
                    ['SP.IKP.005.01', 'Melaksanakan Verifikasi dan Peningkatan Berkelanjutan FSMS', 'Conducting Verification and Continual Improvement of the FSMS'],
                    ['SP.IKP.006.01', 'Mengelola Komunikasi dan Pelatihan Keamanan Pangan', 'Managing Food Safety Communication and Training'],
                ],
            ],

            // ── N ──────────────────────────────────────────────────────
            [
                'title'           => 'Panelis Terlatih Pengujian Sensori Pangan',
                'classrooms_code' => 'PSP',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.PSP.001.01', 'Menguasai Prinsip Fundamental Analisis Sensori dan Fisiologi Indrawi', 'Mastering the Fundamental Principles of Sensory Analysis and Sensory Physiology'],
                    ['SP.PSP.002.01', 'Melaksanakan Prosedur Uji Pembedaan (Discrimination Testing)', 'Conducting Discrimination Testing Procedures'],
                    ['SP.PSP.003.01', 'Melaksanakan Prosedur Uji Deskriptif Kuantitatif (Quantitative Descriptive Analysis)', 'Conducting Quantitative Descriptive Analysis (QDA) Procedures'],
                    ['SP.PSP.004.01', 'Menerapkan Praktik Laboratorium Sensori yang Baik (Good Sensory Practices)', 'Implementing Good Sensory Practices (GSP)'],
                    ['SP.PSP.005.01', 'Mengelola Kinerja dan Konsistensi Penilaian Sensori Pribadi', 'Managing Personal Sensory Performance and Assessment Consistency'],
                    ['SP.PSP.006.01', 'Menguasai Prinsip Fundamental Analisis Sensori dan Fisiologi Indrawi', 'Mastering the Fundamental Principles of Sensory Analysis and Sensory Physiology'],
                ],
            ],

            // ── O ──────────────────────────────────────────────────────
            [
                'title'           => 'GLP Laboratory Technician / Teknisi Laboratorium Berbasis GLP',
                'classrooms_code' => 'GLP',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.GLP.001.01', 'Melakukan Persiapan Penerapan GLP', 'Preparing for GLP Implementation'],
                    ['SP.GLP.002.01', 'Melaksanakan Pengujian Sesuai Prinsip GLP', 'Conducting Testing in Accordance with GLP Principles'],
                    ['SP.GLP.003.01', 'Melakukan Pengendalian Mutu dan Data', 'Performing Quality and Data Control'],
                    ['SP.GLP.004.01', 'Mengelola Limbah dan Pasca Pengujian', 'Managing Laboratory Waste and Post-Testing Activities'],
                ],
            ],

            // ── P ──────────────────────────────────────────────────────
            [
                'title'           => 'Laboratory HSE Officer / Petugas K3L Laboratorium',
                'classrooms_code' => 'K3L',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.K3L.001.01', 'Melakukan Identifikasi Bahaya dan Penilaian Risiko (HIRADC) di Laboratorium', 'Conducting Hazard Identification, Risk Assessment, and Determining Controls (HIRADC) in the Laboratory', 'M.71KKK01.002.1'],
                    ['SP.K3L.002.01', 'Mengelola Penyimpanan dan Penanganan Bahan Kimia Berbahaya (B3)', 'Managing the Storage and Handling of Hazardous Chemicals', 'M.749000.017.01'],
                    ['SP.K3L.003.01', 'Melakukan Pengelolaan dan Penyimpanan Limbah B3 Laboratorium', 'Managing the Storage and Disposal of Hazardous Laboratory Waste', 'E.38PLB00.008.1'],
                    ['SP.K3L.004.01', 'Mengelola Tindakan Tanggap Darurat di Laboratorium', 'Managing Laboratory Emergency Response Procedures', 'M.71KKK01.007.1'],
                    ['SP.K3L.005.01', 'Melakukan Inspeksi K3 dan Lingkungan Kerja Laboratorium', 'Conducting Occupational Health and Safety (OHS) and Workplace Environment Inspections in the Laboratory', 'IMG.KK02.012.01'],
                ],
            ],

            // ── Q ──────────────────────────────────────────────────────
            [
                'title'           => 'Laboratory Operations Officer / Pranata Laboratorium',
                'classrooms_code' => 'LOP',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.SPL.001.01', 'Menetapkan Konteks Organisasi dan Perencanaan Mutu (Plan)', 'Establishing Organizational Context and Quality Planning (Plan)'],
                    ['SP.SPL.002.01', 'Mengelola Sumber Daya dan Operasional (Do)', 'Managing Resources and Operations (Do)'],
                    ['SP.SPL.003.01', 'Melakukan Evaluasi Kinerja (Check)', 'Conducting Performance Evaluation (Check)'],
                    ['SP.SPL.004.01', 'Melakukan Peningkatan Berkelanjutan (Act)', 'Implementing Continual Improvement (Act)'],
                ],
            ],

            // ── R ──────────────────────────────────────────────────────
            [
                'title'           => 'Quality Management System (ISO 9001) Officer',
                'classrooms_code' => 'QMS',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.SMM.001.01', 'Menganalisis Konteks Organisasi dan Pihak Berkepentingan', 'Analyzing Organizational Context and Interested Parties'],
                    ['SP.SMM.002.01', 'Menyusun Perencanaan Mutu dan Manajemen Risiko', 'Developing Quality Planning and Risk Management'],
                    ['SP.SMM.003.01', 'Mengelola Sumber Daya dan Informasi Terdokumentasi', 'Managing Resources and Documented Information'],
                    ['SP.SMM.004.01', 'Mengendalikan Operasional dan Penyedia Eksternal', 'Controlling Operations and External Providers'],
                    ['SP.SMM.005.01', 'Melakukan Evaluasi Kinerja dan Peningkatan Berkelanjutan', 'Conducting Performance Evaluation and Continual Improvement'],
                ],
            ],

            // ── S ──────────────────────────────────────────────────────
            [
                'title'           => 'QC Laboratory Analyst / Analis QC Laboratorium',
                'classrooms_code' => 'QCA',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.QCA.001.01', 'Melakukan Kaji Ulang Permintaan, Tender, dan Kontrak Pengujian'],
                    ['SP.QCA.002.01', 'Memilih, Memverifikasi, dan Memvalidasi Metode Pengujian'],
                    ['SP.QCA.003.01', 'Melaksanakan Pengambilan Sampel (Sampling)'],
                    ['SP.QCA.004.01', 'Menangani dan Menyiapkan sampel untuk Analisis'],
                    ['SP.QCA.005.01', 'Membuat dan Mengelola Rekaman Teknis Pengujian'],
                    ['SP.QCA.006.01', 'Melaksanakan Penjaminan Mutu Hasil Pengujian'],
                    ['SP.QCA.007.01', 'Mengevaluasi Ketidakpastian Pengukuran'],
                    ['SP.QCA.008.01', 'Menyusun Laporan Hasil Uji'],
                    ['SP.QCA.009.01', 'Mengidentifikasi dan Mengendalikan Pekerjaan yang Tidak Sesuai'],
                ],
            ],

            // ── T ──────────────────────────────────────────────────────
            [
                'title'           => 'Quality Assurance Officer',
                'classrooms_code' => 'QAO',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.QAO.001.01', 'Mengelola dan Mengendalikan Dokumen Sistem Manajemen Mutu'],
                    ['SP.QAO.002.01', 'Mengimplementasikan Sistem Manajemen Mutu Sesuai Standar yang Berlaku'],
                    ['SP.QAO.003.01', 'Melaksanakan Audit Internal Sistem Manajemen Mutu'],
                    ['SP.QAO.004.01', 'Mengidentifikasi dan Mengendalikan Ketidaksesuaian'],
                    ['SP.QAO.005.01', 'Melaksanakan Tindakan Korektif dan Tindakan Pencegahan'],
                    ['SP.QAO.006.01', 'Melakukan Analisis Risiko dan Peluang dalam Sistem Manajemen Mutu'],
                    ['SP.QAO.007.01', 'Melakukan Pemantauan, Pengukuran, dan Evaluasi Kinerja Mutu'],
                    ['SP.QAO.008.01', 'Melaksanakan Pengendalian Rekaman dan Pelaporan Kinerja Mutu'],
                    ['SP.QAO.009.01', 'Menerapkan Prinsip Perbaikan Berkelanjutan (Continuous Improvement)'],
                ],
            ],

            // ── U ──────────────────────────────────────────────────────
            [
                'title'           => 'Research and Development Officer',
                'classrooms_code' => 'RDO',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.RDO.001.01', 'Merencanakan Kegiatan Penelitian dan Pengembangan', 'Planning Research and Development Activities'],
                    ['SP.RDO.002.01', 'Melaksanakan Kegiatan Penelitian dan Pengembangan', 'Executing Research and Development Activities'],
                    ['SP.RDO.003.01', 'Melakukan Analisis dan Validasi Hasil Penelitian', 'Analyzing and Validating Research Results'],
                    ['SP.RDO.004.01', 'Mengelola Dokumentasi dan Pelaporan Kegiatan R&D', 'Managing Documentation and Reporting of R&D Activities'],
                    ['SP.RDO.005.01', 'Mengelola Implementasi dan Peningkatan Berkelanjutan Hasil Pengembangan', 'Managing the Implementation and Continual Improvement of Development Results'],
                ],
            ],

            // ── V ──────────────────────────────────────────────────────
            [
                'title'           => 'Regulatory Affairs Officer',
                'classrooms_code' => 'RAQ',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.RAO.001.01', 'Menerapkan Prinsip Kepatuhan Regulasi dan Etika Profesi', 'Applying Regulatory Compliance Principles and Professional Ethics'],
                    ['SP.RAO.002.01', 'Menyusun dan Mengevaluasi Dokumen Registrasi dan Perizinan Produk', 'Drafting and Evaluating Product Registration and Licensing Documents'],
                    ['SP.RAO.003.01', 'Melakukan Proses Pengajuan Registrasi dan Perizinan Produk kepada Otoritas Terkait', 'Conducting the Submission Process for Product Registration and Licensing to Relevant Authorities'],
                    ['SP.RAO.004.01', 'Melakukan Pemantauan Perubahan Regulasi dan Analisis Dampaknya terhadap Produk/Perusahaan', 'Monitoring Regulatory Changes and Analyzing Their Impact on Products and the Company'],
                    ['SP.RAO.005.01', 'Mengelola Arsip dan Sistem Dokumentasi Regulatory Affairs', 'Managing Regulatory Affairs Archives and Documentation Systems'],
                    ['SP.RAO.006.01', 'Melakukan Evaluasi Kepatuhan Produk dan Menyusun Tindak Lanjut Ketidaksesuaian (Compliance Management)', 'Evaluating Product Compliance and Formulating Corrective Actions for Non-Conformities'],
                ],
            ],

            // ── W ──────────────────────────────────────────────────────
            [
                'title'           => 'Sustainability Officer',
                'classrooms_code' => 'SBO',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.SDR.001.01', 'Mengidentifikasi aspek dan dampak keberlanjutan operasional', 'Identifying Operational Sustainability Aspects and Impacts'],
                    ['SP.SDR.002.01', 'Merencanakan program peningkatan kinerja lingkungan dan sosial', 'Planning Environmental and Social Performance Improvement Programs'],
                    ['SP.SDR.003.01', 'Mengimplementasikan program keberlanjutan organisasi', 'Implementing Organizational Sustainability Programs'],
                    ['SP.SDR.004.01', 'Memantau dan mengevaluasi capaian target keberlanjutan', 'Monitoring and Evaluating Sustainability Target Achievements'],
                    ['SP.SDR.005.01', 'Mengomunikasikan kinerja keberlanjutan internal', 'Communicating Internal Sustainability Performance'],
                    ['SP.SDR.006.01', 'Mendukung pengelolaan data kinerja keberlanjutan', 'Supporting Sustainability Performance Data Management'],
                ],
            ],

            // ── X ──────────────────────────────────────────────────────
            [
                'title'           => 'ESG Officer',
                'classrooms_code' => 'ESG',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.ESG.001.01', 'Mengidentifikasi dan memetakan pemangku kepentingan', 'Identifying and Mapping Stakeholders'],
                    ['SP.ESG.002.01', 'Mengidentifikasi isu dan risiko ESG', 'Identifying ESG Issues and Risks'],
                    ['SP.ESG.003.01', 'Melakukan penilaian dampak dan risiko ESG', 'Conducting ESG Impact and Risk Assessments'],
                    ['SP.ESG.004.01', 'Menyusun matriks materialitas', 'Developing a Materiality Matrix'],
                    ['SP.ESG.005.01', 'Mengintegrasikan risiko ESG ke dalam manajemen risiko organisasi', 'Integrating ESG Risks into Enterprise Risk Management (ERM)'],
                    ['SP.ESG.006.01', 'Menyiapkan informasi pengungkapan ESG', 'Preparing ESG Disclosure Information'],
                    ['SP.ESG.007.01', 'Mendukung tata kelola dan kebijakan ESG organisasi', 'Supporting Organizational ESG Governance and Policy'],
                ],
            ],

            // ── Y ──────────────────────────────────────────────────────
            [
                'title'           => 'Environmental Management System (ISO 14001) Officer',
                'classrooms_code' => 'EMS',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.EMS.001.01', 'Menerapkan Konteks Organisasi dalam SML'],
                    ['SP.EMS.002.01', 'Mengidentifikasi Aspek dan Dampak Lingkungan'],
                    ['SP.EMS.003.01', 'Mengidentifikasi dan Mengevaluasi Kewajiban Kepatuhan'],
                    ['SP.EMS.004.01', 'Menyusun Sasaran dan Program Lingkungan'],
                    ['SP.EMS.005.01', 'Mengendalikan Operasional dan Dokumen SML'],
                    ['SP.EMS.006.01', 'Melaksanakan Pemantauan dan Pengukuran Kinerja Lingkungan'],
                    ['SP.EMS.007.01', 'Melaksanakan Audit Internal SML'],
                    ['SP.EMS.008.01', 'Menindaklanjuti Ketidaksesuaian dan Tindakan Perbaikan'],
                ],
            ],

            // ── Z ──────────────────────────────────────────────────────
            [
                'title'           => 'Corporate Legal Officer',
                'classrooms_code' => 'CLO',
                'kode_skema'      => null,
                'req'             => 'non-pt',
                'units'      => [
                    ['SP.CLO.001.01', 'Melakukan Pemenuhan Perizinan Usaha dan Legalitas Korporasi', 'Ensuring Business Licensing Compliance and Corporate Legality'],
                    ['SP.CLO.002.01', 'Menyusun dan Meninjau Dokumen Hukum Perusahaan', 'Drafting and Reviewing Corporate Legal Documents'],
                    ['SP.CLO.003.01', 'Menyusun Legal Opinion dan Rekomendasi Hukum', 'Formulating Legal Opinions and Recommendations'],
                    ['SP.CLO.004.01', 'Mengelola Administrasi dan Arsip Hukum Korporasi', 'Managing Corporate Legal Administration and Archives'],
                    ['SP.CLO.005.01', 'Menyusun Laporan Legal dan Kepatuhan Secara Berkala', 'Preparing Periodic Legal and Compliance Reports'],
                    ['SP.CLO.006.01', 'Melakukan Monitoring dan Analisis Perubahan Regulasi', 'Monitoring and Analyzing Regulatory Changes'],
                    ['SP.CLO.007.01', 'Melakukan Legal Due Diligence & Audit Kepatuhan Hukum', 'Conducting Legal Due Diligence and Compliance Audits'],
                    ['SP.CLO.008.01', 'Mendukung Audit Eksternal dan Pemeriksaan', 'Supporting External Audits and Inspections'],
                    ['SP.CLO.009.01', 'Mengelola Hubungan dengan Regulasi dan Stakeholder', 'Managing Relations with Regulators and Stakeholders'],
                    ['SP.CLO.010.01', 'Menangani Pemeriksaan dan Investigasi oleh Regulator', 'Handling Regulatory Inquiries and Investigations'],
                ],
            ],

        ];

        // ── Proses insert ───────────────────────────────────────────────
        foreach ($skemas as $skema) {
            $code = $skema['classrooms_code'] ?? strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $skema['title']), 0, 6));

            $classroom = Classroom::updateOrCreate(
                ['title' => $skema['title']],
                array_filter([
                    'classrooms_code' => $code,
                    'kode_skema'      => $skema['kode_skema'] ?? null,
                ], fn($v) => $v !== null)
            );

            // Unit kompetensi — sinkron per classroom (upsert + prune kode lama).
            // Aman: unit kompetensi hanya dibaca sebagai daftar (sertifikat/SK),
            // tidak direferensikan by-id oleh data peserta.
            $unitCodes = [];
            foreach ($skema['units'] as $order => $unit) {
                $kode     = $unit[0];
                $judul    = $unit[1];
                $judulEn  = $unit[2] ?? null;
                $kodeAsli = $unit[3] ?? null;
                $unitCodes[] = $kode;

                ClassroomCompetencyUnit::updateOrCreate(
                    ['classroom_id' => $classroom->id, 'kode_unit' => $kode],
                    [
                        'judul_unit'     => $judul,
                        'judul_unit_en'  => $judulEn,
                        'kode_unit_asli' => $kodeAsli,
                        'order'          => $order + 1,
                    ],
                );
            }
            // hapus unit lama (kode berubah/dihapus) untuk skema ini saja
            ClassroomCompetencyUnit::where('classroom_id', $classroom->id)
                ->whereNotIn('kode_unit', $unitCodes)
                ->delete();

            // Persyaratan dokumen — idempoten, tanpa delete (cocokkan per classroom_id + code).
            // ID lama dipertahankan sehingga dokumen peserta yang sudah terunggah
            // (application_documents, cascadeOnDelete) TIDAK ikut terhapus.
            $reqs = $skema['req'] === 'pt' ? $reqPt : $reqNonPt;
            foreach ($reqs as $req) {
                ClassroomDocumentRequirement::updateOrCreate(
                    ['classroom_id' => $classroom->id, 'code' => $req['code']],
                    [
                        'label'       => $req['label'],
                        'description' => $req['description'],
                        'is_required' => $req['is_required'],
                        'order'       => $req['order'],
                    ],
                );
            }

            $this->command->info("✓ {$classroom->title} ({$classroom->id}) — " . count($skema['units']) . " unit");
        }
    }
}
