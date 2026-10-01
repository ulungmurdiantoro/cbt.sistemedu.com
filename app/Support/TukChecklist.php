<?php

namespace App\Support;

/**
 * Kriteria FR.TUK.06 — Checklist Verifikasi TUK Online (Rev.0).
 *
 * Ditanam langsung di sini (bukan dikonfigurasi lewat UI). Jawaban disimpan per
 * kunci butir ('B1', 'C3', ...) di tuk_verifications.items — kalau dokumen
 * sumbernya direvisi, JANGAN ubah urutan/kunci butir yang sudah ada; tambahkan
 * butir baru di akhir bagiannya.
 */
class TukChecklist
{
    public const STATUSES = ['sesuai', 'tidak_sesuai'];

    public const KESIMPULAN_AWAL = [
        'layak'           => 'LAYAK',
        'layak_perbaikan' => 'LAYAK DENGAN PERBAIKAN',
        'tidak_layak'     => 'TIDAK LAYAK',
    ];

    public const KESIMPULAN_AWAL_KETERANGAN = [
        'layak'           => 'seluruh kriteria wajib yang relevan terpenuhi dan peserta dapat mengikuti asesmen.',
        'layak_perbaikan' => 'ketidaksesuaian telah diperbaiki dan diverifikasi ulang sebelum ujian dimulai.',
        'tidak_layak'     => 'terdapat ketidaksesuaian yang tidak dapat diperbaiki dan/atau berpotensi memengaruhi validitas, keamanan, kerahasiaan atau integritas asesmen sehingga kegiatan direschedule.',
    ];

    public const HASIL_PEMANTAUAN = [
        'tidak_ada' => 'Tidak terdapat ketidaksesuaian/kejadian khusus',
        'ada'       => 'Terdapat ketidaksesuaian/kejadian khusus',
    ];

    public const KESIMPULAN_AKHIR = [
        'layak'       => 'Layak',
        'tidak_layak' => 'Tidak Layak',
    ];

    /** @return array<int, array{key: string, title: string, items: array<int, array{key: string, label: string}>}> */
    public static function sections(): array
    {
        $sections = [
            'B' => ['VERIFIKASI IDENTITAS', [
                'Nama peserta sesuai dengan daftar peserta asesmen.',
                'Identitas peserta telah diverifikasi sesuai mekanisme LSP.',
                'Wajah peserta sesuai dengan identitas/data peserta.',
                'Nama akun Zoom dapat diidentifikasi sebagai peserta.',
            ]],
            'C' => ['KONDISI LINGKUNGAN TUK ONLINE', [
                'Ruangan/area peserta kondusif untuk pelaksanaan asesmen.',
                'Pencahayaan memadai dan wajah peserta terlihat jelas.',
                'Tidak terdapat pihak lain yang membantu atau mengganggu peserta.',
                'Meja/area kerja bebas dari materi atau alat bantu yang tidak diizinkan.',
                'Peserta telah menunjukkan kondisi sekitar sesuai instruksi Pengawas Ujian.',
                'Lingkungan memungkinkan peserta mengikuti asesmen tanpa gangguan signifikan.',
            ]],
            'D' => ['PERANGKAT DAN KONEKSI', [
                'Komputer/laptop dapat digunakan untuk asesmen.',
                'Kamera/webcam berfungsi dengan baik.',
                'Mikrofon berfungsi dengan baik.',
                'Speaker/audio berfungsi dengan baik.',
                'Koneksi internet memadai untuk Zoom Meeting dan sistem ujian.',
                'Peserta dapat mengakses sistem/aplikasi ujian.',
                'Perangkat memiliki sumber daya listrik/baterai yang memadai.',
            ]],
            'E' => ['KEAMANAN DAN INTEGRITAS UJIAN', [
                'Peserta memahami larangan menerima bantuan pihak lain.',
                'Peserta memahami ketentuan penggunaan referensi sesuai metode ujian.',
                'Tidak terdapat perangkat komunikasi/alat bantu lain yang tidak diizinkan.',
                'Peserta memahami larangan merekam, memotret, menyalin atau menyebarluaskan materi ujian.',
                'Peserta memahami kewajiban tetap berada dalam pengawasan kamera.',
                'Peserta memahami bahwa meninggalkan area asesmen harus mendapat izin Pengawas Ujian.',
            ]],
            'F' => ['PEMANTAUAN SELAMA UJIAN', [
                'Kamera peserta aktif selama asesmen.',
                'Wajah/aktivitas peserta dapat dipantau dengan jelas.',
                'Tidak terdapat pihak lain yang memberikan bantuan.',
                'Tidak ditemukan penggunaan materi/perangkat yang tidak diizinkan.',
                'Peserta tidak meninggalkan area asesmen tanpa izin.',
                'Tidak terjadi gangguan teknis yang memengaruhi validitas asesmen.',
            ]],
        ];

        $result = [];
        foreach ($sections as $key => [$title, $labels]) {
            $result[] = [
                'key'   => $key,
                'title' => $title,
                'items' => array_map(
                    fn ($label, $i) => ['key' => $key . ($i + 1), 'label' => $label],
                    $labels,
                    array_keys($labels)
                ),
            ];
        }

        return $result;
    }

    /** @return string[] semua kunci butir, mis. ['B1', 'B2', ..., 'F6'] */
    public static function itemKeys(): array
    {
        return collect(self::sections())->flatMap(fn ($s) => array_column($s['items'], 'key'))->all();
    }
}
