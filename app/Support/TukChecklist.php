<?php

namespace App\Support;

/**
 * Kriteria FR.TUK.06 — Checklist Verifikasi TUK Online, per revisi dokumen.
 *
 * Ditanam langsung di sini (bukan dikonfigurasi lewat UI). Jawaban disimpan per kunci butir
 * ('B1', 'C3', ...) di tuk_verifications.items, dan setiap checklist menyimpan revisinya
 * (tuk_verifications.revision). Butir Rev.0 dan Rev.1 memakai kunci yang sama tetapi isinya
 * berbeda, jadi checklist lama selalu ditampilkan & dicetak dengan revisinya sendiri.
 *
 * Dokumen direvisi lagi → tambahkan revisi baru di definition(), daftarkan di REVISIONS,
 * naikkan CURRENT, dan buat view PDF-nya. JANGAN ubah kunci/urutan butir revisi lama.
 */
class TukChecklist
{
    public const REVISIONS = ['Rev.0', 'Rev.1'];

    /** Revisi untuk checklist baru. */
    public const CURRENT = 'Rev.1';

    /** Nilai status butir (sama di semua revisi; labelnya per revisi). */
    public const STATUSES = ['sesuai', 'tidak_sesuai'];

    /** Rev.1 — status baris tabel ketidaksesuaian & kesimpulan setelah verifikasi ulang. */
    public const STATUS_ULANG = ['memenuhi' => 'Memenuhi', 'tidak_memenuhi' => 'Tidak Memenuhi'];

    public static function normalizeRevision(?string $revision): string
    {
        return in_array($revision, self::REVISIONS, true) ? $revision : 'Rev.0';
    }

    /**
     * Definisi lengkap satu revisi: bagian butir + label + pilihan kesimpulan.
     *
     * @return array{
     *   revision: string, sections: array, status_labels: array<string, string>,
     *   kesimpulan_awal: array<string, string>, kesimpulan_awal_keterangan: array<string, string>,
     *   hasil_pemantauan: array<string, string>, kesimpulan_akhir: array<string, string>,
     *   lokasi_options: array<int, string>, dampak_options: array<string, string>, petunjuk: ?string
     * }
     */
    public static function definition(?string $revision = null): array
    {
        $revision = self::normalizeRevision($revision ?? self::CURRENT);

        return $revision === 'Rev.1' ? self::rev1() : self::rev0();
    }

    /** @return array<int, array{key: string, title: string, items: array<int, array{key: string, label: string}>}> */
    public static function sections(?string $revision = null): array
    {
        return self::definition($revision)['sections'];
    }

    /** @return string[] semua kunci butir, mis. ['B1', 'B2', ..., 'F6'] */
    public static function itemKeys(?string $revision = null): array
    {
        return collect(self::sections($revision))->flatMap(fn ($s) => array_column($s['items'], 'key'))->all();
    }

    private static function rev0(): array
    {
        return [
            'revision'      => 'Rev.0',
            'sections'      => self::build([
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
            ]),
            'item_header'   => 'Kriteria Verifikasi',
            'status_labels' => ['sesuai' => 'Sesuai', 'tidak_sesuai' => 'Tidak Sesuai'],
            'kesimpulan_awal' => [
                'layak'           => 'LAYAK',
                'layak_perbaikan' => 'LAYAK DENGAN PERBAIKAN',
                'tidak_layak'     => 'TIDAK LAYAK',
            ],
            'kesimpulan_awal_keterangan' => [
                'layak'           => 'seluruh kriteria wajib yang relevan terpenuhi dan peserta dapat mengikuti asesmen.',
                'layak_perbaikan' => 'ketidaksesuaian telah diperbaiki dan diverifikasi ulang sebelum ujian dimulai.',
                'tidak_layak'     => 'terdapat ketidaksesuaian yang tidak dapat diperbaiki dan/atau berpotensi memengaruhi validitas, keamanan, kerahasiaan atau integritas asesmen sehingga kegiatan direschedule.',
            ],
            'hasil_pemantauan' => [
                'tidak_ada' => 'Tidak terdapat ketidaksesuaian/kejadian khusus',
                'ada'       => 'Terdapat ketidaksesuaian/kejadian khusus',
            ],
            'kesimpulan_akhir' => ['layak' => 'Layak', 'tidak_layak' => 'Tidak Layak'],
            'lokasi_options'   => [],
            'dampak_options'   => [],
            'petunjuk'         => null,
        ];
    }

    private static function rev1(): array
    {
        return [
            'revision'      => 'Rev.1',
            'sections'      => self::build([
                'B' => ['VERIFIKASI IDENTITAS', [
                    'Nama peserta sesuai dengan daftar peserta asesmen.',
                    'Identitas peserta telah diverifikasi sesuai mekanisme LSP.',
                    'Wajah peserta sesuai dengan identitas/data peserta.',
                    'Nama akun Zoom dapat diidentifikasi sebagai peserta.',
                ]],
                'C' => ['RUANG DAN KONDISI LINGKUNGAN', [
                    'Ruangan/area asesi cukup tenang dan kondusif untuk pelaksanaan asesmen.',
                    'Pencahayaan memadai sehingga wajah dan aktivitas asesi dapat terlihat jelas.',
                    'Asesi mengikuti asesmen secara individual dan tidak memperoleh bantuan dari pihak lain.',
                    'Tidak terdapat pihak lain di sekitar asesi yang dapat memengaruhi integritas asesmen.',
                    'Meja/area kerja bebas dari perangkat atau bahan lain yang tidak diizinkan.',
                    'Asesi bersedia menunjukkan kondisi ruangan/area kerja melalui kamera apabila diminta Pengawas Ujian.',
                    'Lokasi memungkinkan asesi mengikuti rangkaian asesmen tanpa gangguan yang signifikan.',
                ]],
                'D' => ['SARANA DAN PRASARANA TEKNOLOGI', [
                    'Tersedia komputer/laptop yang mampu menjalankan Zoom Meeting dan sistem/aplikasi ujian LSP secara bersamaan serta berfungsi dan menampilkan wajah asesi dengan jelas.',
                    'Tersedia device tambahan (tab/HP/laptop) dengan kamera yang jelas dan dapat diarahkan untuk verifikasi area sekitar selama pelaksanaan ujian berlangsung.',
                    'Mikrofon dan speaker/audio berfungsi dengan baik.',
                    'Perangkat memiliki sumber daya listrik yang memadai dan charger/adaptor tersedia selama asesmen.',
                    'Tersedia koneksi internet yang memadai untuk menjalankan video conference dan sistem ujian; tersedia mekanisme komunikasi dengan Pengawas Ujian apabila koneksi terputus.',
                    'Asesi dapat mengakses tautan/platform ujian yang ditetapkan LSP dan mengaktifkan kamera serta mikrofon selama asesmen.',
                ]],
                'E' => ['KEAMANAN DAN INTEGRITAS UJIAN', [
                    'Asesi tidak menerima bantuan, komunikasi, referensi, aplikasi, situs, atau sumber informasi yang tidak diizinkan.',
                    'Asesi tidak merekam, memotret, menyalin, atau menyebarluaskan materi ujian.',
                    'Asesi tidak meninggalkan area asesmen tanpa izin Pengawas Ujian dan tetap berada dalam jangkauan kamera selama asesmen.',
                    'Pengawas Ujian dapat berkomunikasi dengan asesi, mengamati asesi, dan meminta verifikasi ulang kondisi lingkungan apabila terdapat indikasi ketidaksesuaian.',
                ]],
                'F' => ['PEMANTAUAN SELAMA UJIAN', [
                    'Pengawas dapat melihat asesi dengan jelas melalui Zoom Meeting.',
                    'Pengawas dapat berkomunikasi dengan asesi selama asesmen.',
                    'Pengawas dapat meminta asesi mengarahkan kamera untuk memverifikasi kondisi lingkungan.',
                    'Pengawas mencatat gangguan, pelanggaran, atau ketidaksesuaian yang terjadi.',
                    'Ketidaksesuaian yang dapat memengaruhi validitas atau integritas asesmen ditindaklanjuti sebelum asesi memulai atau melanjutkan asesmen.',
                    // Sama dengan F1 — begitu tertulis di dokumen FR.TUK.06 Rev.1.
                    'Pengawas dapat melihat asesi dengan jelas melalui Zoom Meeting.',
                ]],
            ]),
            'item_header'   => 'Persyaratan Wajib',
            'status_labels' => ['sesuai' => 'Memenuhi', 'tidak_sesuai' => 'Tidak Memenuhi'],
            'kesimpulan_awal' => [
                'memenuhi'       => 'MEMENUHI',
                'tidak_memenuhi' => 'TIDAK MEMENUHI',
            ],
            'kesimpulan_awal_keterangan' => [
                'memenuhi'       => 'seluruh persyaratan wajib telah dipenuhi dan peserta dapat mengikuti asesmen.',
                'tidak_memenuhi' => 'terdapat satu atau lebih persyaratan wajib yang belum/tidak terpenuhi. Asesmen belum dapat dimulai sampai dilakukan perbaikan dan verifikasi ulang.',
            ],
            'hasil_pemantauan' => [
                'tidak_ada' => 'Tidak terdapat gangguan/pelanggaran/ketidaksesuaian yang memengaruhi validitas atau integritas asesmen.',
                'ada'       => 'Terdapat gangguan/pelanggaran/ketidaksesuaian dan telah ditindaklanjuti sesuai prosedur.',
            ],
            'kesimpulan_akhir' => ['memenuhi' => 'Memenuhi', 'tidak_memenuhi' => 'Tidak Memenuhi'],
            'lokasi_options'   => ['Lembaga Pelatihan/Organisasi', 'Tempat Tinggal', 'Kantor Asesi', 'Lainnya'],
            'dampak_options'   => [
                'tidak_ada'          => 'Tidak ada',
                'dihentikan'         => 'Dihentikan sementara',
                'dijadwalkan_ulang'  => 'Dijadwalkan ulang',
                'lainnya'            => 'Lainnya',
            ],
            'petunjuk' => 'Seluruh butir pada formulir ini merupakan persyaratan wajib TUK Online. Beri tanda centang pada kolom Memenuhi atau Tidak Memenuhi. Asesmen hanya dapat dimulai apabila seluruh persyaratan wajib telah dinyatakan Memenuhi. Jika terdapat persyaratan yang dapat diperbaiki sebelum asesmen dimulai, lakukan perbaikan dan verifikasi ulang terlebih dahulu.',
        ];
    }

    /** @param array<string, array{0: string, 1: string[]}> $sections */
    private static function build(array $sections): array
    {
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
}
