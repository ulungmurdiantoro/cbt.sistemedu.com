<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bobot Penilaian Wawancara
    |--------------------------------------------------------------------------
    | Total = (gaya_wawancara + penguasaan_materi + kemampuan_hadapi_pertanyaan
    |          + hasil_worksheet) * bobot_wawancara
    */
    'bobot_wawancara' => 0.075,

    /*
    |--------------------------------------------------------------------------
    | Threshold Kategori Nilai
    |--------------------------------------------------------------------------
    | < remidial       → Remidial (Re-Assessment)
    | < cukup          → Cukup (Average)
    | < bagus           → Bagus (Good)
    | >= bagus          → Bagus Sekali (Excellence)
    */
    'kategori' => [
        'remidial' => 60,
        'cukup'    => 70,
        'bagus'    => 80,
    ],

    /*
    |--------------------------------------------------------------------------
    | Masa Berlaku Sertifikat (tahun)
    |--------------------------------------------------------------------------
    */
    'sertifikat_valid_years' => 3,

    /*
    |--------------------------------------------------------------------------
    | URL Verifikasi Dokumen
    |--------------------------------------------------------------------------
    */
    'verifikasi_url' => 'https://verifikasi-sertifikat.lspedukia.id',

    /*
    |--------------------------------------------------------------------------
    | Data Penandatangan Default (fallback jika template kosong)
    |--------------------------------------------------------------------------
    */
    'penandatangan' => [
        'nama'    => 'Dr. Agung Yulianto, M.Si.',
        'jabatan' => 'Ketua LSP Edukasi Global Cendekia',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kota Penetapan SK Default
    |--------------------------------------------------------------------------
    */
    'kota_default' => 'Semarang',

    /*
    |--------------------------------------------------------------------------
    | Item Menimbang SK (2 item, {skema} akan di-replace)
    |--------------------------------------------------------------------------
    */
    'sk_menimbang' => [
        'Bahwa kepada peserta Uji Kompetensi Sertifikasi yang dinyatakan <strong>Lulus</strong> akan diberikan Sertifikat Kompetensi sedangkan yang dinyatakan <strong>Tidak Lulus</strong> akan diterbitkan Surat Keputusan Ketidaklulusan;',
        'Bahwa sehubungan dengan poin 1 penetapan peserta Uji Kompetensi {skema} yang dinyatakan <strong>Lulus</strong> atau <strong>Belum Lulus</strong> ditetapkan melalui Keputusan Ketua LSP Edukasi Global Cendekia',
    ],

    /*
    |--------------------------------------------------------------------------
    | Item Mengingat SK (13 item regulasi)
    |--------------------------------------------------------------------------
    */
    'sk_mengingat' => [
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

];
