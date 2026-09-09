<template>
    <Head><title>TTD AK.01 Asesor — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-4">
        <div class="col-12">

            <Link :href="`/admin/penilaian/${exam_session.id}`" class="btn btn-primary border-0 shadow mb-3">
                <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali ke Penugasan Asesor
            </Link>

            <!-- Info sesi -->
            <div class="card border-0 shadow mb-3">
                <div class="card-body py-3">
                    <h6 class="fw-bold mb-2"><i class="fa fa-signature me-2"></i>TTD AK.01 Asesor (Admin)</h6>
                    <div class="row small">
                        <div class="col-md-6"><span class="text-muted">Sesi:</span> {{ exam_session.title }}</div>
                        <div class="col-md-6"><span class="text-muted">Skema:</span> {{ exam_session.exam_pg?.classroom?.title ?? exam_session.exam_esai?.classroom?.title ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info border-0 shadow-sm small mb-3">
                <i class="fa fa-info-circle me-1"></i>
                Verifikasi dokumen persyaratan (FR.APL.01) dilakukan lewat menu <strong>Permohonan</strong>, terpisah dari halaman ini.
                Di sini hanya untuk membubuhkan tanda tangan AK.01 — tetap dicatat & ditandatangani atas nama
                <strong>asesor yang ditugaskan</strong> ke masing-masing peserta (menu Penugasan Asesor), bukan atas nama admin.
            </div>

            <!-- Summary -->
            <div class="row g-3 mb-3">
                <div class="col-3">
                    <div class="card border-0 shadow text-center py-2">
                        <div class="fs-4 fw-bold text-primary">{{ finalVerified }}</div>
                        <div class="small text-muted">Sudah TTD AK.01</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card border-0 shadow text-center py-2">
                        <div class="fs-4 fw-bold text-secondary">{{ rows.length - finalVerified }}</div>
                        <div class="small text-muted">Belum TTD AK.01</div>
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
                                    <th style="width:12%">No. Peserta</th>
                                    <th>Nama</th>
                                    <th style="width:16%">Asesor Ditugaskan</th>
                                    <th class="text-center" style="width:14%">Status Aplikasi</th>
                                    <th class="text-center" style="width:16%">TTD AK.01</th>
                                    <th class="text-center" style="width:10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in rows" :key="row.student_id" :class="{ 'table-success bg-opacity-10': row.asesor_verified_at }">
                                    <td>{{ i + 1 }}</td>
                                    <td class="fw-bold">{{ row.no_participant }}</td>
                                    <td>{{ row.name }}</td>
                                    <td class="small">
                                        <span v-if="row.assigned_asesor">{{ row.assigned_asesor }}</span>
                                        <span v-else class="text-muted fst-italic">Belum ditugaskan</span>
                                    </td>
                                    <td class="text-center">
                                        <span v-if="!row.app_id" class="badge bg-secondary">Belum Mendaftar</span>
                                        <span v-else-if="row.app_status === 'draft'" class="badge bg-secondary">Draft</span>
                                        <span v-else-if="row.app_status === 'submitted'" class="badge bg-warning text-dark">Disubmit</span>
                                        <span v-else-if="row.app_status === 'approved'" class="badge bg-success">Disetujui</span>
                                        <span v-else-if="row.app_status === 'rejected'" class="badge bg-danger">Ditolak</span>
                                    </td>
                                    <td class="text-center">
                                        <span v-if="row.asesor_verified_at" class="badge bg-primary" :title="row.asesor_verified_at">
                                            <i class="fa fa-check-double me-1"></i>Sudah ({{ formatDate(row.asesor_verified_at) }})
                                        </span>
                                        <span v-else-if="row.app_id" class="badge bg-secondary">
                                            Belum
                                        </span>
                                        <span v-else class="text-muted small">—</span>
                                    </td>
                                    <td class="text-center">
                                        <Link v-if="row.app_id"
                                            :href="`/admin/penilaian/${exam_session.id}/dokumen/${row.student_id}`"
                                            class="btn btn-sm border-0"
                                            :class="row.asesor_verified_at ? 'btn-outline-secondary' : 'btn-primary'">
                                            <i class="fa fa-signature me-1"></i> {{ row.asesor_verified_at ? 'Lihat' : 'Tandatangani' }}
                                        </Link>
                                        <span v-else class="text-muted small">—</span>
                                    </td>
                                </tr>
                                <tr v-if="rows.length === 0">
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada peserta.</td>
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
import LayoutAdmin from '../../../../Layouts/Admin.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        exam_session: Object,
        rows: Array,
    },

    setup(props) {
        const finalVerified = computed(() => props.rows.filter(r => r.asesor_verified_at).length);

        const formatDate = (value) => {
            if (!value) return '-';
            return new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        };

        return { finalVerified, formatDate };
    },
}
</script>
