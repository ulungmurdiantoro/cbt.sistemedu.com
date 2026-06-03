<?php

/**
 * Konfigurasi teks tetap (fixed) untuk dokumen SP, SK, dan Sertifikat.
 * Semua isi hardcode di sini — tidak ada kustomisasi admin.
 */
return [

    /* ---------------------------------------------------------------
     | Identitas LSP
     * --------------------------------------------------------------- */
    'lsp' => [
        'nama'            => 'LSP EDUKASI GLOBAL CENDEKIA',
        'nama_singkat'    => 'LSP Edukia',
        'alamat'          => 'Kompleks Ruko Teras Bali No. 12, Desa/Kelurahan, Bubakan, Kec. Mijen, Kota Semarang, Jawa Tengah',
        'telp'            => '+62 851-7547-9385',
        'web'             => 'www.lspedukia.id',
        'kota'            => 'Semarang',
        'verifikasi_url'  => 'https://verifikasi-sertifikat.lspedukia.id',
        'kode_akreditasi' => 'LSP-033-IDN',
    ],

    /* ---------------------------------------------------------------
     | Penandatangan
     * --------------------------------------------------------------- */
    'penandatangan' => [
        'nama'    => 'Dr. Agung Yulianto, M.Si.',
        'jabatan' => 'Ketua LSP Edukasi Global Cendekia',
    ],

    /* ---------------------------------------------------------------
     | Asset path (relatif dari base_path())
     * --------------------------------------------------------------- */
    'assets' => [
        'logo_edukia'         => 'resources/lsp-assets/logo-edukia.png',
        'logo_kan'            => 'resources/lsp-assets/logo-kan.png',
        'ttd'                 => 'resources/lsp-assets/ttd.png',
        'bg_sertif_depan'     => 'resources/lsp-assets/bg-sertifikat-depan-kan.png',
        'bg_sertif_kan'       => 'resources/lsp-assets/bg-sertifikat-kan.png',
        'bg_sertif_tanpa_kan' => 'resources/lsp-assets/bg-sertifikat-tanpa-kan.png',
    ],

    /* ---------------------------------------------------------------
     | SP — teks fix
     * --------------------------------------------------------------- */
    'sp' => [
        'perihal' => 'Pemberitahuan Hasil Ujian Kompetensi Sertifikasi Person LSP Edukia',
        'pembuka' => 'Berdasarkan hasil Uji Kompetensi Sertifikasi Person LSP Edukasi Global Cendekia (Edukia) dengan skema {skema} telah dilaksanakan pada {held_on} dengan ini dinyatakan bahwa:',
        'penutup' => 'Info lebih lanjut silahkan hubungi narahubung tim Edukia (+62 858-3270-4071). Demikian pemberitahuan ini kami sampaikan, atas perhatian dan kerjasamanya kami ucapkan terima kasih.',

        /* Lampiran Hal.2 */
        'pembobotan_a' => [
            ['metode' => 'Pilihan Ganda', 'jumlah_soal' => '100', 'durasi' => '120 Menit', 'proporsi' => '65%'],
            ['metode' => 'Esai',          'jumlah_soal' => '10',  'durasi' => '90 Menit',  'proporsi' => '35%'],
        ],
        'pembobotan_b' => [
            ['metode' => 'Ujian Tulis (Pilihan Ganda + Esai)', 'proporsi' => '70%'],
            ['metode' => 'Ujian Lisan + Keterampilan',          'proporsi' => '30%'],
        ],
        'standar_kelulusan' => [
            'Asesi (Peserta) dinyatakan lulus uji kompetensi apabila nilai minimal &ge;60',
            'Asesi (Peserta) dinyatakan tidak lulus jika tidak memenuhi poin 1',
            'Asesi (Peserta) yang dinyatakan tidak lulus dapat mengikuti kegiatan remidial',
        ],
    ],

    /* ---------------------------------------------------------------
     | SK — teks fix
     * --------------------------------------------------------------- */
    'sk' => [
        'menimbang' => [
            'Bahwa kepada peserta Uji Kompetensi Sertifikasi yang dinyatakan <strong>Lulus</strong> akan diberikan Sertifikat Kompetensi sedangkan yang dinyatakan <strong>Tidak Lulus</strong> akan diterbitkan Surat Keputusan Ketidaklulusan;',
            'Bahwa sehubungan dengan poin 1 penetapan peserta Uji Kompetensi {skema} yang dinyatakan <strong>Lulus</strong> atau <strong>Belum Lulus</strong> ditetapkan melalui Keputusan Ketua LSP Edukasi Global Cendekia',
        ],
        'mengingat' => [
            'Standar SNI ISO/IEC 17024:2012 tentang Penilaian Kesesuaian – Persyaratan Umum untuk Lembaga Sertifikasi Person;',
            'Undang-undang Nomor 25 Tahun 2009 tentang Pelayanan Publik;',
            'Undang-undang Republik Indonesia Nomor 5 Tahun 2014 tentang Aparatur Sipil Negara (ASN);',
            'Undang-undang Nomor 20 Tahun 2014 tentang Standardisasi dan Penilaian Kesesuaian;',
            'Undang-undang Nomor 13 Tahun 2003 tentang Ketenagakerjaan;',
            'Peraturan Presiden Republik Indonesia Nomor 8 Tahun 2012 tentang Kerangka Kualifikasi Nasional Indonesia;',
            'Peraturan Menteri Tenaga Kerja dan Transmigrasi Republik Indonesia Nomor 5 Tahun 2014 tentang Sistem Standardisasi Kompetensi Kerja Nasional;',
            'Peraturan Menteri Tenaga Kerja dan Transmigrasi Republik Indonesia Nomor 8 Tahun 2012 tentang Tata Cara Penetapan Standar Kompetensi Kerja Nasional Indonesia;',
            'Undang-undang Republik Indonesia Nomor 11 Tahun 2019 tentang Sistem Nasional Ilmu Pengetahuan dan Teknologi;',
            'Peraturan Pemerintah Republik Indonesia Nomor 11 Tahun 2017 tentang Manajemen Pegawai Negeri Sipil;',
            'Peraturan Pemerintah Republik Indonesia Nomor 34 Tahun 2018 tentang Sistem Standardisasi dan Penilaian Kesesuaian Nasional;',
            'Peraturan Menteri Pendayagunaan Aparatur Negara dan Reformasi Birokrasi Nomor 38 Tahun 2017 tentang Standar Kompetensi Jabatan Aparatur Sipil Negara;',
            'Peraturan Menteri Riset, Teknologi, dan Pendidikan Tinggi Republik Indonesia Nomor 6 Tahun 2022 tentang Ijazah, Sertifikat Kompetensi, Sertifikat Profesi, Gelar, dan Tata Cara Penulisan Gelar di Perguruan Tinggi;',
        ],
        'penutup_hal2' => 'Demikian surat keputusan ini ditetapkan, apabila terdapat kekeliruan dalam penerbitan surat keputusan ini maka akan diperbaiki sebagaimana mestinya.',
        'catatan_qr'   => "Dokumen ini telah ditandatangani\nsecara elektronik menggunakan\nsystem digital yang terintegrasi",
        'kategori' => [
            ['range' => '&lt; Batas Minimal (60)', 'label' => 'Remidial <em>(Re-Assessment)</em>'],
            ['range' => '&gt; 60.00 – &lt; 70.00', 'label' => 'Cukup <em>(Average)</em>'],
            ['range' => '&gt; 70.01 – &lt; 80.00', 'label' => 'Bagus <em>(Good)</em>'],
            ['range' => '&gt; 80.01 – &lt; 100.00', 'label' => 'Bagus Sekali <em>(Excellence)</em>'],
        ],
    ],

];
