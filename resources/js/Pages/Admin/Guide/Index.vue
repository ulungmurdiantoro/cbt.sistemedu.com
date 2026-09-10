<template>
    <Head><title>Panduan Admin</title></Head>

    <div class="container-fluid mb-5 mt-4">
        <div class="row">
            <div class="col-lg-10 col-xl-9 mx-auto">

                <!-- Header -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5 class="mb-1"><i class="fa fa-life-ring me-2 text-primary"></i>Panduan Admin</h5>
                        <p class="text-muted mb-0 small">
                            Admin menyiapkan seluruh fondasi: data master (tipe ujian, skema, ujian, sesi), akun peserta,
                            lalu memproses pendaftaran sertifikasi dari verifikasi dokumen sampai penerbitan FR.AK.01 —
                            termasuk menugaskan asesor. Keputusan kelulusan akhir ada di tangan
                            <strong>Pengambil Keputusan</strong>, bukan admin.
                        </p>
                    </div>
                </div>

                <div class="accordion" id="panduanAdmin">

                    <!-- 1 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#a1">
                                <span class="badge bg-primary me-2">1</span> Lingkup peran &amp; dashboard
                            </button>
                        </h2>
                        <div id="a1" class="accordion-collapse collapse show" data-bs-parent="#panduanAdmin">
                            <div class="accordion-body">
                                <p class="mb-2">Menu di sidebar kiri mengikuti urutan kerja, dari menyiapkan data sampai menutup penilaian:</p>
                                <div class="table-responsive mb-2">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tbody>
                                            <tr><td style="width:190px" class="fw-semibold">Tipe Ujian / Skema / Ujian / Sesi Ujian</td><td>Data master yang harus ada sebelum ujian &amp; sertifikasi bisa jalan.</td></tr>
                                            <tr><td class="fw-semibold">Peserta</td><td>Akun peserta ujian (login soal), termasuk pembagian ke sesi &amp; batch.</td></tr>
                                            <tr><td class="fw-semibold">Permohonan</td><td>Pendaftaran sertifikasi peserta — verifikasi dokumen, approve, terbitkan FR.APL.01 / FR.AK.01.</td></tr>
                                            <tr><td class="fw-semibold">Penugasan Asesor</td><td>Menetapkan asesor per peserta per sesi, plus TTD AK.01 sisi asesor.</td></tr>
                                            <tr><td class="fw-semibold">Hasil Penilaian / Laporan Nilai</td><td>Rekap nilai PG / Esai / Wawancara dan unduhan dokumen hasil.</td></tr>
                                            <tr><td class="fw-semibold">Kelola User</td><td>Akun staf: admin, asesor, Pengambil Keputusan — beserta tanda tangannya.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="note-box small mb-0">
                                    Admin bisa memegang lebih dari satu peran. Kalau akun Anda juga asesor / Pengambil Keputusan,
                                    tombol pindah portal muncul di bagian bawah sidebar.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2">
                                <span class="badge bg-primary me-2">2</span> Menyiapkan data master
                            </button>
                        </h2>
                        <div id="a2" class="accordion-collapse collapse" data-bs-parent="#panduanAdmin">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Urutannya berjenjang — tiap langkah memakai hasil langkah sebelumnya:</p>
                                <ol class="mb-2">
                                    <li><strong>Tipe Ujian</strong> — kategori besar: Pilihan Ganda, Essay, atau Essay Migas (esai dengan unggah berkas).</li>
                                    <li><strong>Skema</strong> — skema sertifikasi: judul, kode skema, KKM (nilai kelulusan), dan daftar <em>dokumen persyaratan</em> (wajib / opsional) yang harus diunggah peserta.</li>
                                    <li><strong>Ujian</strong> — bank soal untuk sebuah skema: soal PG dan/atau esai beserta bobot nilainya.</li>
                                    <li><strong>Sesi Ujian</strong> — jadwal pelaksanaan: waktu mulai/selesai, kode batch, ujian PG &amp; esai yang dipakai, dan apakah ada komponen wawancara.</li>
                                </ol>
                                <div class="alert alert-warning small mb-0">
                                    Bobot PG / Esai / Wawancara pada sesi menentukan cara Nilai Akhir dihitung. Kalau salah satu
                                    komponen tidak dipakai, bobotnya otomatis dinormalisasi ke komponen yang ada.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a3">
                                <span class="badge bg-primary me-2">3</span> Peserta &amp; akun ujian
                            </button>
                        </h2>
                        <div id="a3" class="accordion-collapse collapse" data-bs-parent="#panduanAdmin">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Menu <span class="badge bg-gray-200 text-gray-800 border">Peserta</span> mengatur akun yang dipakai peserta untuk mengerjakan soal.</p>
                                <ul class="mb-2">
                                    <li>Tiap peserta punya <strong>No. Peserta</strong> unik dan terhubung ke satu skema (classroom) serta satu sesi/batch.</li>
                                    <li><strong>Re-issue akun ujian</strong> — dipakai kalau peserta perlu mengulang/masuk ulang: akun lama dinonaktifkan, akun baru dibuat. Jawaban lama tetap tersimpan terpisah.</li>
                                    <li><strong>Ganti Batch</strong> — memindahkan peserta ke sesi/batch lain <em>tanpa</em> membuat akun baru; hanya enrollment sesi yang berubah.</li>
                                </ul>
                                <div class="alert alert-secondary small mb-0">
                                    Setelah re-issue, cek <span class="badge bg-gray-200 text-gray-800 border">Penugasan Asesor</span> pada sesi terkait —
                                    penugasan asesor ikut dipindahkan ke akun baru supaya nama peserta tidak tampil dobel saat asesor menilai.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a4">
                                <span class="badge bg-primary me-2">4</span> Permohonan — verifikasi dokumen &amp; FR.APL.01
                            </button>
                        </h2>
                        <div id="a4" class="accordion-collapse collapse" data-bs-parent="#panduanAdmin">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Menu <span class="badge bg-gray-200 text-gray-800 border">Permohonan</span> berisi pendaftaran sertifikasi yang diajukan peserta dari portalnya sendiri.</p>
                                <ol class="mb-2">
                                    <li>Buka satu permohonan → periksa data diri, data pekerjaan, dan berkas persyaratan yang diunggah.</li>
                                    <li>Setiap dokumen persyaratan ditandai <span class="badge bg-success">Terverifikasi</span> atau <span class="badge bg-danger">Ditolak</span> (dengan catatan alasan).</li>
                                    <li>Status <strong>FR.APL.01</strong> menjadi <span class="badge bg-success">Lengkap</span> begitu semua dokumen <em>wajib</em> terverifikasi — dokumen opsional tidak menghalangi.</li>
                                    <li>FR.APL.01 &amp; FR.APL.03 bisa di-generate sebagai PDF dari halaman permohonan.</li>
                                </ol>
                                <div class="note-box small mb-0">
                                    Verifikasi kelengkapan dokumen ini <strong>tugas admin</strong>, bukan asesor. Pengambil Keputusan
                                    hanya meninjau ulang hasilnya, tidak mengulang dari nol.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a5">
                                <span class="badge bg-primary me-2">5</span> Approve permohonan &amp; TTD LSP
                            </button>
                        </h2>
                        <div id="a5" class="accordion-collapse collapse" data-bs-parent="#panduanAdmin">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Setelah dokumen lengkap, permohonan disetujui — di titik ini tanda tangan pihak LSP dibubuhkan ke FR.AK.01.</p>
                                <ul class="mb-2">
                                    <li>Tombol <span class="badge bg-success">Approve</span> mengunci permohonan dan mencatat nama serta TTD LSP (diambil dari tanda tangan default admin, atur di <span class="badge bg-gray-200 text-gray-800 border">Kelola User</span>).</li>
                                    <li>FR.AK.01 memuat tiga tanda tangan: <strong>Asesi</strong> (peserta, saat menandatangani pakta integritas), <strong>LSP</strong> (saat approve), dan <strong>Asesor</strong> (langkah 6).</li>
                                </ul>
                                <div class="alert alert-warning small mb-0">
                                    <i class="fa fa-stamp me-1"></i>
                                    Meterai elektronik pada FR.AK.01 baru dibubuhkan otomatis <strong>setelah ketiga tanda tangan lengkap</strong> —
                                    bukan saat approve. Lihat bagian 8.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a6">
                                <span class="badge bg-primary me-2">6</span> Penugasan Asesor &amp; TTD AK.01
                            </button>
                        </h2>
                        <div id="a6" class="accordion-collapse collapse" data-bs-parent="#panduanAdmin">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Menu <span class="badge bg-gray-200 text-gray-800 border">Penugasan Asesor</span> → pilih sesi → tetapkan asesor per peserta.</p>
                                <ul class="mb-2">
                                    <li>Satu peserta bisa diberi satu asesor. Asesor lalu melihat peserta itu di portalnya untuk menilai esai / wawancara dan mengisi Laporan Asesmen.</li>
                                    <li>Sub-menu <strong>TTD AK.01 Asesor</strong> — asesor menandatangani FR.AK.01 untuk peserta yang ditugaskan. Bisa dilakukan asesor sendiri dari portalnya, atau oleh admin dari sini bila diperlukan.</li>
                                    <li>Nama &amp; TTD asesor yang muncul di FR.AK.01 diambil dari tanda tangan tersimpan asesor tersebut.</li>
                                </ul>
                                <div class="note-box small mb-0">
                                    Kalau di FR.AK.01 nama/TTD asesor kosong padahal sudah ditugaskan — berarti langkah TTD AK.01 belum
                                    dijalankan, atau asesor belum menyimpan tanda tangannya.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 7 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a7">
                                <span class="badge bg-primary me-2">7</span> Hasil, laporan &amp; kelola user
                            </button>
                        </h2>
                        <div id="a7" class="accordion-collapse collapse" data-bs-parent="#panduanAdmin">
                            <div class="accordion-body">
                                <ul class="mb-2">
                                    <li><strong>Hasil Penilaian</strong> — rekap nilai per sesi begitu asesor selesai menilai; dari sini dokumen hasil bisa diunduh.</li>
                                    <li><strong>Laporan Nilai</strong> — ekspor Excel nilai esai &amp; komponen lain untuk arsip.</li>
                                    <li><strong>Kelola User</strong> — buat/ubah akun staf, tetapkan peran (admin / asesor / Pengambil Keputusan), dan simpan tanda tangan tiap user (gambar langsung atau unggah gambar). TTD ini yang dipakai di dokumen resmi.</li>
                                </ul>
                                <div class="alert alert-secondary small mb-0">
                                    Finalisasi kelulusan &amp; penerbitan nomor SK / SP / Sertifikat dilakukan oleh
                                    <strong>Pengambil Keputusan</strong> di portalnya sendiri, memakai data yang sudah admin siapkan di atas.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 8 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a8">
                                <span class="badge bg-primary me-2">8</span> Materai elektronik
                            </button>
                        </h2>
                        <div id="a8" class="accordion-collapse collapse" data-bs-parent="#panduanAdmin">
                            <div class="accordion-body">
                                <p class="small text-muted mb-2">Pembubuhan meterai (Peruri e-Meterai) berjalan otomatis di latar belakang — admin tidak menekan tombol apa pun.</p>
                                <ul class="mb-2">
                                    <li><strong>FR.AK.01</strong> — meterai dibubuhkan otomatis begitu tanda tangan Asesi + LSP + Asesor <em>ketiganya</em> lengkap.</li>
                                    <li><strong>FR.AK.14</strong> (Surat Pernyataan Pemegang Sertifikat) — ditandatangani peserta yang LULUS setelah finalisasi, lalu dibubuhi meterai otomatis. SK &amp; Sertifikat baru bisa diunduh peserta setelah langkah ini selesai.</li>
                                </ul>
                                <div class="alert alert-danger small mb-0">
                                    Kalau status meterai tersangkut di <em>Sedang Diproses</em> dalam waktu lama, biasanya penyebabnya di
                                    sisi server: <em>queue worker</em> mati, kredensial Peruri salah, atau kuota/saldo habis — perlu
                                    penanganan teknis, bukan diulang dari portal.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <p class="text-muted small text-center mt-4">
                    Alur ringkas: data master → akun peserta → verifikasi dokumen → approve (TTD LSP) → tugaskan asesor (TTD AK.01)
                    → penilaian → Pengambil Keputusan memutuskan.
                </p>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import guideLayout from '../../../Layouts/guideLayout';
import { Head } from '@inertiajs/vue3';

export default {
    layout: guideLayout(LayoutAdmin),
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
