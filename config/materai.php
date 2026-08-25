<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans — pembayaran materai oleh peserta
    |--------------------------------------------------------------------------
    */
    'midtrans' => [
        'server_key'    => env('MIDTRANS_SERVER_KEY'),
        'client_key'    => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'is_sanitized'  => true,
        'is_3ds'        => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Harga materai (dibayar peserta per dokumen FR.AK.01)
    |--------------------------------------------------------------------------
    */
    'price' => env('MATERAI_PRICE', 10000),

    /*
    |--------------------------------------------------------------------------
    | Peruri e-Meterai (PJPU) — pembubuhan materai ke PDF
    |--------------------------------------------------------------------------
    | Diisi setelah akun Enterprise Peruri selesai diverifikasi.
    */
    'peruri' => [
        'base_url'  => env('PERURI_BASE_URL'),
        'client_id' => env('PERURI_CLIENT_ID'),
        'username'  => env('PERURI_USERNAME'),
        'password'  => env('PERURI_PASSWORD'),
    ],

];
