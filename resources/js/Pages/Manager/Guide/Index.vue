<template>
    <Head><title>Panduan Pengambil Keputusan</title></Head>

    <div class="container-fluid mb-5 mt-4">
        <div class="row">
            <div class="col-lg-10 col-xl-9 mx-auto">

                <!-- Header -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5 class="mb-1"><i class="fa fa-life-ring me-2 text-primary"></i>Panduan Pengambil Keputusan</h5>
                        <p class="text-muted mb-0 small">
                            Pemegang wewenang terakhir: meninjau kelengkapan dokumen, kelayakan awal, laporan asesmen, dan
                            nilai setiap peserta — lalu memutuskan kelulusan dan menerbitkan nomor SK, SP, dan Sertifikat.
                            Di sistem, portal ini masih beralamat <code>/manager/...</code> — nama teknis lama, perannya tetap Pengambil Keputusan.
                        </p>
                    </div>
                </div>

                <div class="accordion shadow-sm" id="panduanManager">

                    <!-- 1 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#m1">
                                <span class="badge bg-primary me-2">1</span> Masuk ke portal
                            </button>
                        </h2>
                        <div id="m1" class="accordion-collapse collapse show" data-bs-parent="#panduanManager">
                            <div class="accordion-body">
                                <ol class="mb-2">
                                    <li>Buka <code>/login</code>, masuk dengan akun Pengambil Keputusan.</li>
                                    <li>Sistem otomatis mengarahkan ke <code>/manager/dashboard</code>.</li>
                                </ol>
                                <div class="alert alert-light border small mb-0">
                                    <i class="fa fa-circle-info me-1 text-muted"></i>
                                    Kalau akun Anda punya peran lain juga, tautan pindah portal muncul di menu samping — tidak perlu keluar untuk berpindah.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#m2">
                                <span class="badge bg-primary me-2">2</span> Dashboard sesi
                            </button>
                        </h2>
                        <div id="m2" class="accordion-collapse collapse" data-bs-parent="#panduanManager">
                            <div class="accordion-body">
                                <p class="mb-0">Daftar seluruh sesi ujian, aktif maupun sudah selesai — semuanya bisa ditinjau, tidak ada batasan waktu. Tiap baris menunjukkan skema, jumlah peserta, dan status <span class="badge bg-success">Aktif</span> / <span class="badge bg-secondary">Selesai</span>. Klik <span class="badge bg-dark">Tinjau</span> pada sesi yang ingin diproses.</p>
                            </div>
                        </div>
                    </div>

                    <!-- 3 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#m3">
                                <span class="badge bg-primary me-2">3</span> Meninjau kelengkapan &amp; preview keputusan
                            </button>
                        </h2>
                        <div id="m3" class="accordion-collapse collapse" data-bs-parent="#panduanManager">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Halaman peninjauan menampilkan satu baris per peserta dengan enam kelompok informasi berdampingan — semua yang perlu diketahui sebelum meluluskan seseorang, dalam satu layar.</p>
                                <div class="table-responsive mb-2">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tbody>
                                            <tr><td style="width:180px" class="fw-semibold">FR.APL.01</td><td><span class="badge bg-success">Lengkap</span> jika semua dokumen persyaratan sudah terverifikasi, atau hitungan <em>x/y</em> jika belum.</td></tr>
                                            <tr><td class="fw-semibold">Dokumen Persyaratan</td><td>Tautan <span class="badge bg-light text-dark border">Lihat Dokumen</span> — buka daftar berkas peserta, mode baca saja.</td></tr>
                                            <tr><td class="fw-semibold">FR.APL.03</td><td><span class="badge bg-success">Layak</span> atau <span class="badge bg-danger">Tidak Layak</span> beserta skor penilaian awal.</td></tr>
                                            <tr><td class="fw-semibold">Laporan Asesmen</td><td>Nama asesor yang ditugaskan, badge <span class="badge bg-success">Kompeten</span>/<span class="badge bg-danger">Belum Kompeten</span>, dan tautan unduh FR.AK.05.</td></tr>
                                            <tr><td class="fw-semibold">PG &middot; Esai &middot; Wawancara &middot; Nilai Akhir</td><td>Rincian nilai per komponen dan hasil akhir gabungannya.</td></tr>
                                            <tr><td class="fw-semibold">Keputusan</td><td><span class="badge bg-success">LULUS</span> atau <span class="badge bg-danger">TIDAK LULUS</span>, dihitung otomatis dari nilai akhir vs KKM skema.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="alert alert-light border small mb-2">
                                    Kolom <strong>FR.APL.01</strong> dan <strong>FR.APL.03</strong> berisi data yang <em>sudah</em> diverifikasi sebelumnya (oleh admin, saat memproses pendaftaran) — di halaman ini Anda hanya meninjau ulang, bukan mengulang verifikasinya dari nol.
                                </div>
                                <p class="fw-semibold mb-1">Pratinjau Berita Acara sebelum memutuskan</p>
                                <p class="small text-muted mb-0">Tombol <span class="badge bg-light text-dark border">Preview Keputusan</span> di kanan atas selalu tersedia, berapa pun peserta yang sudah dicentang — membuka draf lengkap Berita Acara Keputusan Sertifikasi untuk seluruh peserta sesi ini di tab baru, ditandai jelas sebagai <strong>pratinjau</strong>.</p>
                            </div>
                        </div>
                    </div>

                    <!-- 4 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#m4">
                                <span class="badge bg-primary me-2">4</span> Mencentang verifikasi
                            </button>
                        </h2>
                        <div id="m4" class="accordion-collapse collapse" data-bs-parent="#panduanManager">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Setelah meninjau kelima kelompok informasi untuk seorang peserta dan menganggapnya beres, tandai lewat kolom paling kanan — per peserta, tidak perlu menunggu satu sesi selesai ditinjau semua.</p>
                                <ol class="mb-2">
                                    <li>Centang kotak di kolom <strong>Verifikasi</strong> pada baris peserta yang sudah siap. Nama Anda tercatat di bawah centang sebagai bukti siapa yang memverifikasi.</li>
                                    <li>Centang bisa dilepas &amp; dicentang ulang kapan saja untuk peserta yang belum final.</li>
                                    <li>Peserta yang belum siap — misalnya berhalangan hadir saat ujian — cukup dibiarkan tidak dicentang. Mereka tetap bisa menyusul difinalisasi belakangan, mis. bergabung ke batch ujian susulan.</li>
                                </ol>
                                <div class="alert alert-warning small mb-0">
                                    <strong>Minimal satu peserta.</strong> Tombol Finalisasi Semua aktif begitu <em>ada</em> peserta yang tercentang — tidak perlu menunggu seluruh peserta di sesi itu selesai ditinjau.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#m5">
                                <span class="badge bg-primary me-2">5</span> Finalisasi kelulusan
                            </button>
                        </h2>
                        <div id="m5" class="accordion-collapse collapse" data-bs-parent="#panduanManager">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Tindakan penutup: mengunci nilai dan menerbitkan nomor dokumen resmi — tapi <strong>hanya untuk peserta yang sudah dicentang Verifikasi</strong>, bukan otomatis seluruh sesi.</p>
                                <ol class="mb-2">
                                    <li>Cek sekali lagi lewat <span class="badge bg-light text-dark border">Preview Keputusan</span> kalau perlu (langkah 3).</li>
                                    <li>Klik <span class="badge bg-warning text-dark">Finalisasi Semua</span> di kanan atas.</li>
                                    <li>Konfirmasi lewat dialog yang muncul dengan <span class="badge bg-dark">Ya, Finalisasi</span>.</li>
                                </ol>
                                <div class="alert alert-danger small mb-0">
                                    <strong>Tidak bisa dibatalkan untuk peserta yang difinalisasi.</strong> Nilai &amp; keputusan mereka terkunci, nomor SK dan SP otomatis diterbitkan, dan nomor Sertifikat khusus untuk yang berstatus LULUS. Peserta yang <em>belum</em> dicentang tidak ikut terpengaruh — tetap Draft, aman difinalisasi di kesempatan lain.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#m6">
                                <span class="badge bg-primary me-2">6</span> Setelah finalisasi
                            </button>
                        </h2>
                        <div id="m6" class="accordion-collapse collapse" data-bs-parent="#panduanManager">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Baris yang sudah final berubah statusnya dan tidak bisa diverifikasi ulang — baris lain di sesi yang sama boleh saja masih Draft, itu normal.</p>
                                <ul class="mb-0">
                                    <li>Kolom <strong>Status</strong> berubah dari <span class="badge bg-secondary">Draft</span> menjadi <span class="badge bg-dark">Final</span>, per peserta.</li>
                                    <li>Kotak centang Verifikasi ikut terkunci untuk baris yang sudah final — tidak bisa diklik lagi.</li>
                                    <li>Satu sesi wajar berisi campuran <span class="badge bg-dark">Final</span> dan <span class="badge bg-secondary">Draft</span> sekaligus — sisa peserta Draft cukup dicentang &amp; difinalisasi lagi kapan pun mereka siap.</li>
                                    <li>Pengiriman dokumen (SK/SP/Sertifikat) ke peserta ditangani lewat halaman <strong>Rekap Hasil</strong> di Portal Admin — tugas Pengambil Keputusan berhenti sampai keputusan diterbitkan.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- 7 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#m7">
                                <span class="badge bg-primary me-2">7</span> Materai elektronik peserta
                            </button>
                        </h2>
                        <div id="m7" class="accordion-collapse collapse" data-bs-parent="#panduanManager">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Sesuatu yang berjalan di luar portal ini, tapi berguna diketahui saat peserta bertanya kenapa dokumennya belum bisa diunduh.</p>

                                <p class="fw-semibold mb-1">FR.AK.01 — otomatis, tidak perlu tindakan Anda</p>
                                <p class="small text-muted mb-3">Begitu peserta menandatangani pakta integritas (FR.AK.01) di portalnya sendiri, meterai elektronik (Peruri e-Meterai) langsung dibubuhkan otomatis di latar belakang — <strong>gratis</strong>, tidak ada langkah bayar.</p>

                                <p class="fw-semibold mb-1">FR.AK.14 — syarat sebelum unduh SK &amp; Sertifikat</p>
                                <p class="small text-muted mb-2">Untuk peserta yang <span class="badge bg-success">LULUS</span>, ada satu langkah tambahan di portal peserta setelah Anda finalisasi: menandatangani <strong>FR.AK.14 (Surat Pernyataan Pemegang Sertifikat)</strong>, yang juga langsung dibubuhi meterai elektronik otomatis begitu ditandatangani.</p>
                                <div class="alert alert-secondary small mb-0">
                                    Sampai FR.AK.14 selesai ditandatangani &amp; distempel, tombol unduh SK dan Sertifikat <strong>belum muncul</strong> di dashboard peserta — bukan berarti finalisasi Anda gagal. Peserta yang <span class="badge bg-danger">TIDAK LULUS</span> tidak terkena syarat ini, SK mereka tetap langsung bisa diunduh.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <p class="text-muted small text-center mt-4">
                    Setiap keputusan yang sudah difinalisasi tercatat permanen — perubahan lebih lanjut memerlukan penanganan khusus di luar alur normal ini.
                </p>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutManager from '../../../Layouts/Manager.vue';
import { Head } from '@inertiajs/vue3';

export default {
    layout: LayoutManager,
    components: { Head },
}
</script>
