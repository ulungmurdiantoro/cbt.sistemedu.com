<template>
    <Head><title>Panduan Asesor</title></Head>

    <div class="container-fluid mb-5 mt-4">
        <div class="row">
            <div class="col-lg-10 col-xl-9 mx-auto">

                <!-- Header -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5 class="mb-1"><i class="fa fa-life-ring me-2 text-primary"></i>Panduan Asesor</h5>
                        <p class="text-muted mb-0 small">
                            Tugas Anda ada di tiga sisi: memberi nilai (esai &amp; wawancara), menandatangani AK.01 untuk
                            peserta yang ditugaskan, lalu menyatakan rekomendasi Kompeten/Belum Kompeten lewat Laporan Asesmen —
                            sebelum diteruskan ke Pengambil Keputusan untuk keputusan akhir. Verifikasi kelengkapan dokumen
                            persyaratan (FR.APL.01) bukan tugas Anda — itu ditangani admin di alur pendaftaran.
                        </p>
                    </div>
                </div>

                <div class="accordion" id="panduanAsesor">

                    <!-- 1 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#s1">
                                <span class="badge bg-primary me-2">1</span> Dashboard tugas
                            </button>
                        </h2>
                        <div id="s1" class="accordion-collapse collapse show" data-bs-parent="#panduanAsesor">
                            <div class="accordion-body">
                                <p>Semua sesi ujian yang peserta-nya sudah ditugaskan ke Anda muncul di sini, dipisah tab <span class="badge bg-success">Sesi Aktif</span> dan <span class="badge bg-secondary">Sesi Selesai</span>. Tiap kartu sesi punya empat tombol aksi:</p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tbody>
                                            <tr><td style="width:160px"><span class="badge bg-gray-200 text-gray-800 border">Esai</span></td><td>Beri nilai jawaban esai seluruh peserta di sesi ini.</td></tr>
                                            <tr><td><span class="badge bg-gray-200 text-gray-800 border">Wawancara</span></td><td>Isi penilaian wawancara per peserta.</td></tr>
                                            <tr><td><span class="badge bg-gray-200 text-gray-800 border">Laporan</span></td><td>Isi rekomendasi Kompeten/Belum Kompeten &amp; unduh FR.AK.05.</td></tr>
                                            <tr><td><span class="badge bg-gray-200 text-gray-800 border">TTD AK.01</span></td><td>Tandatangani FR.AK.01 sendiri untuk tiap peserta yang ditugaskan.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#s2">
                                <span class="badge bg-primary me-2">2</span> Menilai esai
                            </button>
                        </h2>
                        <div id="s2" class="accordion-collapse collapse" data-bs-parent="#panduanAsesor">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Satu tabel, satu baris per peserta — semua jawaban esai sesi itu langsung terlihat berdampingan.</p>
                                <ol class="mb-0">
                                    <li>Isi nilai (0–100) untuk tiap jawaban di kolom masing-masing. Kolom <strong>Total</strong> terisi otomatis, rata-rata dari semua nilai yang sudah diisi pada baris itu.</li>
                                    <li>Baris paling bawah menunjukkan rata-rata sesi per kolom — sekilas melihat sebaran nilai kelas.</li>
                                    <li>Setelah semua terisi, klik <span class="badge bg-success">Simpan Semua Nilai</span> — satu klik menyimpan seluruh tabel sekaligus.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- 3 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#s3">
                                <span class="badge bg-primary me-2">3</span> Menilai wawancara
                            </button>
                        </h2>
                        <div id="s3" class="accordion-collapse collapse" data-bs-parent="#panduanAsesor">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Empat kriteria tetap dinilai per peserta, dengan bobot yang sudah baku.</p>
                                <div class="table-responsive mb-2">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tbody>
                                            <tr><td style="width:40px" class="fw-bold text-center">A</td><td>Gaya Wawancara</td></tr>
                                            <tr><td class="fw-bold text-center">B</td><td>Penguasaan Materi</td></tr>
                                            <tr><td class="fw-bold text-center">C</td><td>Kemampuan Menghadapi Pertanyaan</td></tr>
                                            <tr><td class="fw-bold text-center">D</td><td>Hasil Pengerjaan Worksheet Ujian Keterampilan</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="mb-0"><strong>Total</strong> dihitung otomatis: <code>(A + B + C + D) &times; 0,075</code>. Kolom Catatan tersedia untuk komentar bebas per peserta. Klik <span class="badge bg-success">Simpan Semua Nilai</span> saat selesai.</p>
                            </div>
                        </div>
                    </div>

                    <!-- 4 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#s4">
                                <span class="badge bg-primary me-2">4</span> TTD AK.01
                            </button>
                        </h2>
                        <div id="s4" class="accordion-collapse collapse" data-bs-parent="#panduanAsesor">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">
                                    Membubuhkan tanda tangan Anda di FR.AK.01 (Persetujuan Asesmen, Ketidakberpihakan, Kerahasiaan)
                                    untuk peserta yang ditugaskan kepada Anda — dilakukan sendiri, tanpa perlu admin.
                                    Verifikasi kelengkapan dokumen persyaratan (FR.APL.01) bukan tugas Anda — itu ditangani admin di alur pendaftaran.
                                </p>
                                <ol class="mb-2">
                                    <li>Kalau belum punya TTD tersimpan, isi dulu di kartu <strong>Tanda Tangan Anda</strong> di bagian atas halaman ini (gambar atau upload) — tersimpan otomatis dipakai lagi untuk sesi-sesi berikutnya dan juga FR.AK.05.</li>
                                    <li>Klik <span class="badge bg-success">Tandatangani</span> pada baris peserta yang dituju, lalu konfirmasi.</li>
                                </ol>
                                <div class="alert alert-secondary small mb-0">
                                    <strong>Tidak bisa dibatalkan setelah ditandatangani</strong> — status berubah jadi <span class="badge bg-success">Sudah</span> dan tidak bisa ditandatangani ulang lewat aplikasi.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#s5">
                                <span class="badge bg-primary me-2">5</span> Rekomendasi &amp; Laporan Asesmen (FR.AK.05)
                            </button>
                        </h2>
                        <div id="s5" class="accordion-collapse collapse" data-bs-parent="#panduanAsesor">
                            <div class="accordion-body">
                                <p class="fw-semibold mb-1">Isi rekomendasi per peserta</p>
                                <p class="small text-muted">Untuk tiap baris di tabel <strong>Rekomendasi Asesi</strong>, klik salah satu: <span class="badge bg-success">K &middot; Kompeten</span> atau <span class="badge bg-danger">BK &middot; Belum Kompeten</span>. Klik <span class="badge bg-success">Simpan Rekomendasi</span> untuk menyimpan seluruh tabel — rekomendasi ini <strong>masih bisa diubah kapan saja</strong> sampai Pengambil Keputusan memfinalisasi peserta tersebut, setelah itu baris terkait terkunci.</p>

                                <p class="fw-semibold mb-1 mt-3">Lengkapi catatan laporan</p>
                                <p class="small text-muted">Empat kolom narasi (Aspek Negatif &amp; Positif, Pencatatan Penolakan, Saran Perbaikan, Catatan) sudah terisi teks contoh yang lazim dipakai — sunting seperlunya atau simpan apa adanya. Klik <span class="badge bg-success">Simpan Laporan</span>.</p>

                                <p class="fw-semibold mb-1 mt-3">Unduh FR.AK.05</p>
                                <p class="small text-muted mb-0">Setelah laporan tersimpan, tombol <span class="badge bg-gray-200 text-gray-800 border">Download PDF</span> aktif — dokumen resmi FR.AK.05 berisi tabel rekomendasi, catatan, serta tanda tangan Anda.</p>
                            </div>
                        </div>
                    </div>

                </div>

                <p class="text-muted small text-center mt-4">
                    Selesai sampai di sini, berkas peserta di sesi tersebut siap ditinjau oleh Pengambil Keputusan.
                </p>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head } from '@inertiajs/vue3';

export default {
    layout: LayoutAsesor,
    components: { Head },
}
</script>

<style scoped>
/* Bootstrap build ini tidak mendefinisikan --bs-accordion-bg / bg-light / text-dark,
   jadi tiap bagian dijadikan kartu putih solid sendiri-sendiri (senada kartu "Panduan"
   di atas) — tidak ada elemen yang transparan lagi. */
.accordion-item {
    background-color: #ffffff;
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, .1), 0 1px 2px 0 rgba(0, 0, 0, .06);
    margin-bottom: 14px;
    overflow: hidden;
}
.accordion-item:last-child { margin-bottom: 0; }
.accordion-button {
    background-color: #ffffff;
    color: #1f2937;
    font-weight: 600;
    box-shadow: none;
}
.accordion-button:not(.collapsed) { background-color: #1f2937; color: #fff; }
.accordion-button:not(.collapsed)::after { filter: invert(1) brightness(2); }
.accordion-button:focus { box-shadow: none; }
.accordion-body { background-color: #ffffff; color: #374151; }
.note-box {
    background-color: #eef2f7;
    border-left: 3px solid #9ca3af;
    border-radius: .375rem;
    padding: .65rem 1rem;
    color: #374151;
}
</style>
