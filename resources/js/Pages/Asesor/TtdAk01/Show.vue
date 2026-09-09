<template>
    <Head><title>TTD AK.01 — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link href="/asesor/dashboard" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-signature me-2"></i>TTD AK.01 — {{ exam_session.title }}</h5>
                        <p class="small text-muted mb-0">
                            Menandatangani FR.AK.01 (Persetujuan Asesmen, Ketidakberpihakan, Kerahasiaan) atas nama Anda sendiri,
                            untuk peserta yang ditugaskan kepada Anda di sesi ini. Verifikasi kelengkapan dokumen persyaratan
                            (FR.APL.01) tetap ditangani admin — bagian ini khusus untuk AK.01.
                        </p>
                    </div>
                </div>

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>
                <div v-if="signError" class="alert alert-danger alert-dismissible">
                    {{ signError }}
                    <button type="button" class="btn-close" @click="signError = ''"></button>
                </div>

                <!-- Tanda tangan asesor sendiri -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-pen-nib me-2"></i>Tanda Tangan Anda</h6>

                        <div v-if="has_signature" class="d-flex align-items-center gap-3">
                            <img :src="signatureUrl" alt="TTD Anda"
                                style="max-height:70px; max-width:200px; object-fit:contain; border:1px solid #ddd; background:#fff; padding:4px">
                            <div class="small text-muted">
                                <span class="badge bg-success mb-1"><i class="fa fa-check me-1"></i>Tersimpan</span>
                                — siap dipakai untuk menandatangani AK.01 di bawah.
                                <Link :href="`/asesor/penilaian/${exam_session.id}/laporan-asesmen`" class="d-block mt-1">Ganti tanda tangan di halaman Laporan Asesmen</Link>
                            </div>
                        </div>
                        <div v-else class="alert alert-warning border-0 mb-0">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            Anda belum punya tanda tangan tersimpan. Simpan dulu di halaman
                            <Link :href="`/asesor/penilaian/${exam_session.id}/laporan-asesmen`" class="alert-link">Laporan Asesmen</Link>
                            sebelum bisa menandatangani AK.01 di sini.
                        </div>
                    </div>
                </div>

                <!-- Daftar peserta -->
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-table me-2"></i>Daftar Peserta</h6>
                        <div v-if="rows.length === 0" class="alert alert-info mb-0">
                            Tidak ada peserta yang ditugaskan di sesi ini.
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center" style="width:5%">No.</th>
                                        <th>Nama Peserta</th>
                                        <th class="text-center" style="width:20%">Status AK.01</th>
                                        <th class="text-center" style="width:16%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in rows" :key="row.student_id">
                                        <td class="text-center">{{ i + 1 }}</td>
                                        <td>{{ row.name }}</td>
                                        <td class="text-center">
                                            <span v-if="row.asesor_verified_at" class="badge bg-success">
                                                <i class="fa fa-check-double me-1"></i>Sudah ({{ formatDate(row.asesor_verified_at) }})
                                            </span>
                                            <span v-else-if="!row.app_id" class="text-muted small fst-italic">Belum ada permohonan</span>
                                            <span v-else class="badge bg-secondary">Belum</span>
                                        </td>
                                        <td class="text-center">
                                            <button v-if="row.app_id && !row.asesor_verified_at"
                                                class="btn btn-sm btn-success" :disabled="!has_signature || signing === row.student_id"
                                                @click="signAk01(row)">
                                                <i class="fa fa-signature me-1"></i>
                                                {{ signing === row.student_id ? 'Menyimpan...' : 'Tandatangani' }}
                                            </button>
                                            <span v-else class="text-muted small">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, Link, router } from '@inertiajs/vue3';

export default {
    layout: LayoutAsesor,
    components: { Head, Link },

    props: {
        exam_session:  Object,
        rows:          Array,
        has_signature: Boolean,
    },

    data() {
        return {
            successMsg: '',
            signError: '',
            signing: null,
        };
    },

    computed: {
        signatureUrl() {
            return '/asesor/tanda-tangan';
        },
    },

    methods: {
        formatDate(value) {
            if (!value) return '-';
            return new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        signAk01(row) {
            if (!confirm(`Tandatangani AK.01 untuk "${row.name}"? Tindakan ini tidak bisa dibatalkan.`)) return;

            this.signing = row.student_id;
            this.signError = '';
            router.post(
                `/asesor/penilaian/${this.exam_session.id}/ttd-ak01/${row.student_id}`,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => { this.successMsg = 'AK.01 berhasil ditandatangani.'; },
                    onError:   (errors) => { this.signError = errors.ttd_ak01 ?? 'Gagal menandatangani AK.01.'; },
                    onFinish:  () => { this.signing = null; },
                }
            );
        },
    },
}
</script>
