<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans — TIDAK DIPAKAI LAGI untuk materai (lihat catatan Peruri di bawah).
    |--------------------------------------------------------------------------
    | Dibiarkan di sini kalau-kalau dipakai lagi untuk keperluan lain di masa
    | depan, tapi MateraiController tidak lagi memanggil ini.
    */
    'midtrans' => [
        'server_key'    => env('MIDTRANS_SERVER_KEY'),
        'client_key'    => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'is_sanitized'  => true,
        'is_3ds'        => true,
    ],

    'price' => env('MATERAI_PRICE', 10000), // tidak dipakai lagi — materai gratis untuk peserta

    /*
    |--------------------------------------------------------------------------
    | Saklar utama e-meterai
    |--------------------------------------------------------------------------
    | false = SEMUA pembubuhan materai (FR.AK.01 & FR.AK.14) dimatikan: tidak
    |         ada job yang dijalankan, tombol/status materai disembunyikan, dan
    |         gembok unduhan SK & Sertifikat cukup meminta peserta menandatangani
    |         FR.AK.14 (tanpa materai).
    | true  = alur lengkap dengan Peruri. Aktifkan HANYA setelah kredensial &
    |         URL production terpasang — di staging Peruri materainya spesimen.
    */
    'enabled' => (bool) env('MATERAI_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Sesi ujian pertama yang WAJIB materai FR.AK.14
    |--------------------------------------------------------------------------
    | Peserta di sesi ujian dengan ID LEBIH KECIL dari angka ini dibebaskan dari
    | materai selamanya, kapan pun mereka menandatangani FR.AK.14: tidak
    | dibubuhi (tidak memakai saldo), tapi tetap harus TTD, dan setelah TTD bisa
    | mengunduh SK & Sertifikat. Peserta di sesi ini dan sesudahnya wajib materai
    | (hanya kalau 'enabled' = true). Kosong = tanpa pembebasan — SET SEBELUM
    | mengaktifkan 'enabled', kalau tidak semua peserta lulus ikut terkunci.
    */
    'first_session_id' => env('MATERAI_FIRST_SESSION_ID') ?: null,

    /*
    |--------------------------------------------------------------------------
    | Pembubuhan otomatis
    |--------------------------------------------------------------------------
    | true  = materai FR.AK.01 dibubuhkan otomatis begitu ketiga TTD lengkap.
    | false = MANUAL — admin membubuhkan lewat tombol di halaman permohonan
    |         (admin/applications/{id}). Berguna untuk kontrol biaya / tracking,
    |         atau saat menangani backlog dokumen lama.
    */
    'auto_stamp' => env('MATERAI_AUTO_STAMP', true),

    /*
    |--------------------------------------------------------------------------
    | Peruri e-Meterai (PJPU) — mode On-Premise
    |--------------------------------------------------------------------------
    | Dokumen tidak diupload ke server Peruri. Login & Generate Serial Number
    | tetap ke Peruri, tapi pembubuhan (stamping) dilakukan lokal oleh
    | container "Sign Adapter" (lihat docs/deploy-sign-adapter-peruri.md).
    */
    'peruri' => [
        'username'         => env('PERURI_USERNAME'),
        'password'         => env('PERURI_PASSWORD'),

        // Endpoint resmi Peruri — beda nilai untuk staging vs production,
        // lihat dokumen "Akses API Onpremise". Contoh staging:
        //   login_url       = https://backendservicestg.e-meterai.co.id/api/users/login
        //   generate_sn_url = https://stampv2stg.e-meterai.co.id/chanel/stampv2
        //   jenisdoc_url    = https://stampv2stg.e-meterai.co.id/jenisdoc
        'login_url'        => env('PERURI_LOGIN_URL'),
        'generate_sn_url'  => env('PERURI_GENERATE_SN_URL'),
        'jenisdoc_url'     => env('PERURI_JENISDOC_URL'),

        // Container Sign Adapter yang di-deploy sendiri (lokal ke server ini).
        'sign_adapter_url' => env('PERURI_SIGN_ADAPTER_URL', 'http://127.0.0.1:8080'),

        // Folder bersama antara aplikasi Laravel & container Sign Adapter.
        'sharefolder'      => env('PERURI_SHAREFOLDER', storage_path('app/peruri-sharefolder')),

        'profile_name'     => env('PERURI_PROFILE_NAME', 'emeteraicertificateSigner'),
        'location'         => env('PERURI_LOCATION', 'SEMARANG'),

        // Kode jenis dokumen ("Surat Pernyataan") — cek ulang lewat API Jenis
        // Document (jenisdoc_url) saat testing, sebelum dipakai di production.
        'namadoc'          => env('PERURI_NAMADOC', '3'),

        // true = lewati panggilan jaringan sungguhan, kembalikan PDF asli
        // apa adanya seolah sudah distempel. Dipakai untuk rehearsal lokal
        // tanpa perlu container Sign Adapter berjalan.
        'fake'             => env('PERURI_FAKE', false),
    ],

];
