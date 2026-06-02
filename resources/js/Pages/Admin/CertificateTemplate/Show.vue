<template>
    <Head><title>Template Dokumen - Aplikasi Ujian Online</title></Head>

    <div class="container-fluid mt-4 mb-5">
        <h5 class="fw-bold mb-2">Template Dokumen Sertifikasi</h5>
        <p class="text-muted small mb-4">
            Konten dokumen bersifat <strong>tetap (fixed)</strong> sesuai standar LSP Edukasi Global Cendekia.
            Gunakan tombol di bawah untuk melihat pratinjau masing-masing dokumen.
        </p>

        <div class="row g-3">

            <!-- SP -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gray-800 text-white fw-semibold">
                        <i class="fa fa-envelope-open me-2"></i>Surat Pemberitahuan (SP)
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">
                            2 halaman — berisi hasil nilai peserta, pembobotan penilaian, dan standar kelulusan.
                            Tidak ada varian KAN.
                        </p>
                        <a href="/admin/certificate-template/preview/sp" target="_blank"
                            class="btn btn-outline-primary">
                            <i class="fa fa-eye me-1"></i> Preview SP
                        </a>
                    </div>
                </div>
            </div>

            <!-- SK -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gray-800 text-white fw-semibold">
                        <i class="fa fa-gavel me-2"></i>Surat Keputusan (SK)
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">
                            3 halaman — Menimbang, Mengingat, Memutuskan (Hal.1–2) + Lampiran Nilai (Hal.3).
                            Varian KAN menampilkan logo KAN di kop surat.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="/admin/certificate-template/preview/sk/nokan" target="_blank"
                                class="btn btn-outline-secondary">
                                <i class="fa fa-eye me-1"></i> Preview SK <span class="badge bg-secondary ms-1">Tanpa KAN</span>
                            </a>
                            <a href="/admin/certificate-template/preview/sk/kan" target="_blank"
                                class="btn btn-outline-success">
                                <i class="fa fa-eye me-1"></i> Preview SK <span class="badge bg-success ms-1">Dengan KAN</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sertifikat -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-gray-800 text-white fw-semibold">
                        <i class="fa fa-certificate me-2"></i>Sertifikat Kompetensi
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">
                            2 halaman — Hal.1: data peserta + QR. Hal.2: tabel Unit Kompetensi + QR.
                            Varian KAN menggunakan background halaman 2 yang berbeda.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="/admin/certificate-template/preview/sertifikat/nokan" target="_blank"
                                class="btn btn-outline-secondary">
                                <i class="fa fa-eye me-1"></i> Preview Sertifikat <span class="badge bg-secondary ms-1">Tanpa KAN</span>
                            </a>
                            <a href="/admin/certificate-template/preview/sertifikat/kan" target="_blank"
                                class="btn btn-outline-success">
                                <i class="fa fa-eye me-1"></i> Preview Sertifikat <span class="badge bg-success ms-1">Dengan KAN</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Info asset -->
        <div class="alert alert-info border-0 shadow-sm mt-4 small">
            <i class="fa fa-info-circle me-2"></i>
            <strong>Asset gambar</strong> (logo, tanda tangan, background) tersimpan di
            <code>resources/lsp-assets/</code>.
            Untuk mengganti gambar, timpa file di folder tersebut lalu deploy ulang.
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head },
}
</script>
