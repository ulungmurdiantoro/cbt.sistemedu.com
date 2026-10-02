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

            <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
                {{ $page.props.session.success }}
            </div>
            <div v-if="$page.props.session.error" class="alert alert-danger border-0 shadow mb-3">
                {{ $page.props.session.error }}
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
                <div class="col-3" v-if="$page.props.materaiEnabled">
                    <div class="card border-0 shadow text-center py-2">
                        <div class="fs-4 fw-bold text-success">{{ stampedCount }}</div>
                        <div class="small text-muted">Sudah Bermaterai</div>
                    </div>
                </div>
            </div>

            <!-- Tabel peserta -->
            <div class="card border-0 shadow">
                <div class="card-header bg-gray-800 text-white fw-bolder d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>
                        <i class="fa fa-users me-2"></i>Daftar Peserta
                        <span class="badge bg-gray-200 text-gray-800 ms-2">{{ rows.length }}</span>
                    </span>
                    <button v-if="$page.props.materaiEnabled" type="button" class="btn btn-sm btn-secondary"
                        :disabled="readyToStamp.length === 0 || processing" @click="stampAll">
                        <i class="fa fa-stamp me-1"></i>Bubuhkan Materai ({{ readyToStamp.length }})
                    </button>
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
                                    <th v-if="$page.props.materaiEnabled" class="text-center" style="width:14%">Materai FR.AK.01</th>
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
                                        <span v-else-if="row.app_status === 'submitted'" class="badge bg-warning text-gray-800">Disubmit</span>
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
                                    <td v-if="$page.props.materaiEnabled" class="text-center">
                                        <span v-if="!row.app_id" class="text-muted small">—</span>
                                        <template v-else-if="row.materai_status === 'stamped'">
                                            <StatusBadge tone="success" :title="formatDate(row.materai_stamped_at)">Sudah</StatusBadge>
                                            <a :href="`/admin/applications/${row.app_id}/materai/download`" target="_blank"
                                                class="ms-1" title="Lihat dokumen bermaterai">
                                                <i class="fa fa-file-pdf"></i>
                                            </a>
                                        </template>
                                        <StatusBadge v-else-if="isProcessing(row)" tone="accent">
                                            <i class="fa fa-spinner fa-spin me-1"></i>Diproses
                                        </StatusBadge>
                                        <StatusBadge v-else-if="!row.ttd_lengkap" tone="neutral" title="Menunggu TTD Asesi, LSP & Asesor lengkap">
                                            Menunggu TTD
                                        </StatusBadge>
                                        <template v-else>
                                            <StatusBadge v-if="row.materai_status === 'failed'" tone="danger"
                                                :title="row.materai_failure_reason || 'tidak diketahui'" class="me-1">Gagal</StatusBadge>
                                            <button type="button" class="btn btn-sm btn-gray-800 border-0" :disabled="processing" @click="stampOne(row)">
                                                <i class="fa fa-stamp me-1"></i>{{ row.materai_status === 'failed' ? 'Ulangi' : 'Bubuhkan' }}
                                            </button>
                                        </template>
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
                                    <td :colspan="$page.props.materaiEnabled ? 8 : 7" class="text-center text-muted py-4">Tidak ada peserta.</td>
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
import StatusBadge from '../../../../Components/StatusBadge.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, StatusBadge },
    props: {
        exam_session: Object,
        rows: Array,
    },

    setup(props) {
        const processing = ref(false);

        const finalVerified = computed(() => props.rows.filter(r => r.asesor_verified_at).length);
        const stampedCount  = computed(() => props.rows.filter(r => r.materai_status === 'stamped').length);

        const isProcessing = (row) => ['pending_payment', 'paid'].includes(row.materai_status);
        const readyToStamp = computed(() => props.rows.filter(r =>
            r.app_id && r.ttd_lengkap && ['none', 'failed'].includes(r.materai_status)
        ));

        const formatDate = (value) => {
            if (!value) return '-';
            return new Date(value).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        };

        const post = (url) => {
            processing.value = true;
            router.post(url, {}, {
                preserveScroll: true,
                onFinish: () => { processing.value = false; },
            });
        };

        const stampOne = (row) => {
            if (!confirm(`Bubuhkan e-meterai FR.AK.01 untuk ${row.name}? Ini memakai 1 kuota meterai.`)) return;
            post(`/admin/penilaian/${props.exam_session.id}/dokumen/${row.student_id}/materai`);
        };

        const stampAll = () => {
            const n = readyToStamp.value.length;
            if (!confirm(`Bubuhkan e-meterai FR.AK.01 untuk ${n} peserta yang TTD-nya sudah lengkap? Ini memakai ${n} kuota meterai.`)) return;
            post(`/admin/penilaian/${props.exam_session.id}/dokumen-materai`);
        };

        // Pembubuhan jalan di background (queue). Selama masih ada yang "Diproses",
        // tabel dimuat ulang tiap 4 detik sampai hasilnya keluar.
        let pollTimer = null;
        const stopPoll = () => { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } };
        watch(() => props.rows.some(isProcessing), (anyProcessing) => {
            if (anyProcessing && !pollTimer) {
                pollTimer = setInterval(() => {
                    router.reload({ only: ['rows'], preserveScroll: true, preserveState: true });
                }, 4000);
            } else if (!anyProcessing) {
                stopPoll();
            }
        }, { immediate: true });
        onUnmounted(stopPoll);

        return {
            processing, finalVerified, stampedCount, readyToStamp,
            isProcessing, formatDate, stampOne, stampAll,
        };
    },
}
</script>
