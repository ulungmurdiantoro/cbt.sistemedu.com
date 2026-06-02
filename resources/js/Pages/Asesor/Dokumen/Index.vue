<template>
    <Head><title>Verifikasi Dokumen — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-4">
        <div class="col-lg-10 col-xl-9 mx-auto">

            <Link href="/asesor/dashboard" class="btn btn-primary border-0 shadow mb-3">
                <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
            </Link>

            <!-- Info sesi -->
            <div class="card border-0 shadow mb-3">
                <div class="card-body py-3">
                    <h6 class="fw-bold mb-2"><i class="fa fa-folder-open me-2"></i>Verifikasi Dokumen Peserta</h6>
                    <div class="row small">
                        <div class="col-md-6"><span class="text-muted">Sesi:</span> {{ exam_session.title }}</div>
                        <div class="col-md-6"><span class="text-muted">Skema:</span> {{ exam_session.exam_pg?.classroom?.title ?? exam_session.exam_esai?.classroom?.title ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="row g-3 mb-3">
                <div class="col-4">
                    <div class="card border-0 shadow text-center py-2">
                        <div class="fs-4 fw-bold text-success">{{ allVerified }}</div>
                        <div class="small text-muted">Semua Lengkap</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card border-0 shadow text-center py-2">
                        <div class="fs-4 fw-bold text-warning">{{ hasRejected }}</div>
                        <div class="small text-muted">Ada Ditolak</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card border-0 shadow text-center py-2">
                        <div class="fs-4 fw-bold text-secondary">{{ notSubmitted }}</div>
                        <div class="small text-muted">Belum Submit</div>
                    </div>
                </div>
            </div>

            <!-- Tabel peserta -->
            <div class="card border-0 shadow">
                <div class="card-header bg-gray-800 text-white fw-semibold">
                    <i class="fa fa-users me-2"></i>Daftar Peserta
                    <span class="badge bg-light text-dark ms-2">{{ rows.length }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th style="width:5%">#</th>
                                    <th style="width:15%">No. Peserta</th>
                                    <th>Nama</th>
                                    <th class="text-center" style="width:15%">Status Aplikasi</th>
                                    <th class="text-center" style="width:20%">Dokumen</th>
                                    <th class="text-center" style="width:10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in rows" :key="row.student_id">
                                    <td>{{ i + 1 }}</td>
                                    <td class="fw-bold">{{ row.no_participant }}</td>
                                    <td>{{ row.name }}</td>
                                    <td class="text-center">
                                        <span v-if="!row.app_id" class="badge bg-secondary">Belum Mendaftar</span>
                                        <span v-else-if="row.app_status === 'draft'" class="badge bg-secondary">Draft</span>
                                        <span v-else-if="row.app_status === 'submitted'" class="badge bg-warning text-dark">Disubmit</span>
                                        <span v-else-if="row.app_status === 'approved'" class="badge bg-success">Disetujui</span>
                                        <span v-else-if="row.app_status === 'rejected'" class="badge bg-danger">Ditolak</span>
                                    </td>
                                    <td class="text-center">
                                        <template v-if="row.app_id">
                                            <span class="badge bg-success me-1" v-if="row.verified > 0">
                                                <i class="fa fa-check me-1"></i>{{ row.verified }} OK
                                            </span>
                                            <span class="badge bg-danger me-1" v-if="row.rejected > 0">
                                                <i class="fa fa-times me-1"></i>{{ row.rejected }} Ditolak
                                            </span>
                                            <span class="badge bg-warning text-dark me-1" v-if="row.pending > 0">
                                                {{ row.pending }} Menunggu
                                            </span>
                                            <span class="text-muted small" v-if="row.uploaded === 0">Belum ada dokumen</span>
                                        </template>
                                        <span v-else class="text-muted small">—</span>
                                    </td>
                                    <td class="text-center">
                                        <Link v-if="row.app_id"
                                            :href="`/asesor/penilaian/${exam_session.id}/dokumen/${row.student_id}`"
                                            class="btn btn-sm btn-primary border-0">
                                            <i class="fa fa-folder-open me-1"></i> Periksa
                                        </Link>
                                        <span v-else class="text-muted small">—</span>
                                    </td>
                                </tr>
                                <tr v-if="rows.length === 0">
                                    <td colspan="6" class="text-center text-muted py-4">Tidak ada peserta.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

export default {
    layout: LayoutAsesor,
    components: { Head, Link },
    props: {
        exam_session: Object,
        rows: Array,
    },

    setup(props) {
        const allVerified   = computed(() => props.rows.filter(r => r.app_id && r.verified > 0 && r.pending === 0 && r.rejected === 0).length);
        const hasRejected   = computed(() => props.rows.filter(r => r.rejected > 0).length);
        const notSubmitted  = computed(() => props.rows.filter(r => !r.app_id || r.app_status === 'draft').length);

        return { allVerified, hasRejected, notSubmitted };
    },
}
</script>
