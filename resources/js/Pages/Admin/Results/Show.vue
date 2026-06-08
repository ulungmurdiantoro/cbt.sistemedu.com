<template>
    <Head><title>Rekap Hasil - {{ exam_session.title }}</title></Head>

    <div class="container-fluid mb-5 mt-4">

        <!-- Header -->
        <div class="d-flex align-items-start gap-3 mb-4">
            <Link href="/admin/results" class="btn btn-sm btn-outline-secondary mt-1">
                <i class="fa fa-arrow-left"></i>
            </Link>
            <div class="flex-grow-1">
                <h5 class="mb-0 fw-bold">{{ exam_session.title }}</h5>
                <p class="mb-0 small text-muted">Kode Batch: {{ exam_session.kode_batch }} &bull; {{ exam_session.start_time }} – {{ exam_session.end_time }}</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" @click="distribute" :disabled="!hasFinalized">
                    <i class="fa fa-paper-plane me-1"></i> Kirim ke Peserta
                </button>
                <button class="btn btn-sm btn-warning text-dark fw-semibold" @click="confirmFinalize" :disabled="allFinalized">
                    <i class="fa fa-lock me-1"></i> Finalisasi Semua
                </button>
            </div>
        </div>

        <!-- Scheme info -->
        <div v-if="scheme" class="alert alert-info py-2 small border-0 mb-3">
            <i class="fa fa-info-circle me-1"></i>
            Komposisi: PG <strong>{{ scheme.bobot_pg }}%</strong> · Esai <strong>{{ scheme.bobot_esai }}%</strong> · Wawancara <strong>{{ scheme.bobot_wawancara }}%</strong> · KKM <strong>{{ scheme.nilai_kelulusan }}</strong>
        </div>
        <div v-else class="alert alert-warning py-2 small border-0 mb-3">
            <i class="fa fa-exclamation-triangle me-1"></i>
            Komposisi nilai belum diatur untuk skema ini. Menggunakan default (PG 40%, Esai 35%, Wawancara 25%).
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="alert alert-success py-2 small border-0 mb-3">
            {{ $page.props.flash.success }}
        </div>

        <!-- Table -->
        <div class="card border-0 shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th class="border-0" style="width:3%">No.</th>
                                <th class="border-0">No. Peserta</th>
                                <th class="border-0">Nama</th>
                                <th class="border-0 text-center" v-if="exam_session.exam_id_pg">PG</th>
                                <th class="border-0 text-center" v-if="exam_session.exam_id_esai">Esai</th>
                                <th class="border-0 text-center" v-if="exam_session.has_wawancara">Wawancara</th>
                                <th class="border-0 text-center">Nilai Akhir</th>
                                <th class="border-0 text-center">Keputusan</th>
                                <th class="border-0 text-center">Status</th>
                                <th class="border-0 text-center">No. Dokumen</th>
                                <th class="border-0 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in rows" :key="row.student_id">
                                <td class="text-center">{{ i + 1 }}</td>
                                <td class="small">{{ row.no_participant }}</td>
                                <td>
                                    {{ row.name }}
                                    <span v-if="row.attempt > 1" class="badge bg-warning text-dark ms-1 small">Remidi</span>
                                </td>
                                <td class="text-center num" v-if="exam_session.exam_id_pg">
                                    {{ row.nilai_pg !== null ? fmt(row.nilai_pg) : '—' }}
                                </td>
                                <td class="text-center num" v-if="exam_session.exam_id_esai">
                                    {{ row.nilai_esai !== null ? fmt(row.nilai_esai) : '—' }}
                                </td>
                                <td class="text-center num" v-if="exam_session.has_wawancara">
                                    {{ row.nilai_wawancara !== null ? fmt(row.nilai_wawancara) : '—' }}
                                </td>
                                <td class="text-center fw-bold num">
                                    <span v-if="row.nilai_akhir !== null" :class="nilaiColor(row)">
                                        {{ fmt(row.nilai_akhir) }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-center">
                                    <StatusBadge v-if="row.keputusan === 'LULUS'" tone="success" label="LULUS" />
                                    <StatusBadge v-else-if="row.keputusan === 'TIDAK_LULUS'" tone="danger" label="TIDAK LULUS" />
                                    <span v-else class="text-muted small">—</span>
                                </td>
                                <td class="text-center">
                                    <span v-if="row.is_finalized" class="badge text-white" style="background-color:#212529;">
                                        <i class="fa fa-lock me-1"></i>Final
                                    </span>
                                    <span v-else class="badge text-white" style="background-color:#6c757d;">Draft</span>
                                </td>
                                <td class="small" style="min-width:140px;">
                                    <div v-if="row.is_finalized">
                                        <div v-if="row.sp_number" class="text-muted">
                                            <span class="fw-semibold">SP:</span> {{ row.sp_number }}
                                        </div>
                                        <div v-if="row.sk_number" class="text-muted">
                                            <span class="fw-semibold">SK:</span> {{ row.sk_number }}
                                        </div>
                                        <div v-if="row.sertifikat_number" class="text-muted">
                                            <span class="fw-semibold">Sert:</span> {{ row.sertifikat_number }}
                                        </div>
                                    </div>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-center">
                                    <div v-if="row.is_finalized" class="d-flex gap-1 justify-content-center flex-wrap">

                                        <!-- SP -->
                                        <div class="dropdown">
                                            <a :href="`/admin/results/${exam_session.id}/download-sp/${row.student_id}`"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-secondary"
                                               title="Surat Pemberitahuan">
                                                <i class="fa fa-envelope-open"></i> SP
                                            </a>
                                        </div>

                                        <!-- SK -->
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-dark dropdown-toggle"
                                                data-bs-toggle="dropdown" title="Surat Keputusan">
                                                <i class="fa fa-file-alt"></i> SK
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" style="min-width:160px; font-size:0.82rem;">
                                                <li>
                                                    <a class="dropdown-item"
                                                       :href="`/admin/results/${exam_session.id}/download-sk/${row.student_id}`"
                                                       target="_blank">
                                                        Tanpa KAN
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item"
                                                       :href="`/admin/results/${exam_session.id}/download-sk/${row.student_id}?kan=1`"
                                                       target="_blank">
                                                        Dengan KAN
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Sertifikat (hanya LULUS) -->
                                        <div v-if="row.keputusan === 'LULUS'" class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle"
                                                data-bs-toggle="dropdown" title="Sertifikat Kompetensi">
                                                <i class="fa fa-certificate"></i> Sert.
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" style="min-width:160px; font-size:0.82rem;">
                                                <li>
                                                    <a class="dropdown-item"
                                                       :href="`/admin/results/${exam_session.id}/download-sertifikat/${row.student_id}`"
                                                       target="_blank">
                                                        Tanpa KAN
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item"
                                                       :href="`/admin/results/${exam_session.id}/download-sertifikat/${row.student_id}?kan=1`"
                                                       target="_blank">
                                                        Dengan KAN
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>

                                    </div>
                                    <span v-else class="text-muted small">—</span>
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td colspan="20" class="text-center text-muted py-4">Belum ada peserta terdaftar di sesi ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, StatusBadge },
    props: {
        exam_session: Object,
        rows:         Array,
        scheme:       Object,
    },

    setup(props) {
        const allFinalized  = computed(() => props.rows.length > 0 && props.rows.every(r => r.is_finalized));
        const hasFinalized  = computed(() => props.rows.some(r => r.is_finalized));

        const nilaiColor = (row) => {
            if (row.keputusan === 'LULUS') return 'text-success';
            if (row.keputusan === 'TIDAK_LULUS') return 'text-danger';
            return '';
        };

        // Format nilai dua desimal (Blueprint: 87.50), aman untuk null.
        const fmt = (v) => (v === null || v === undefined || v === '') ? '—' : Number(v).toFixed(2);

        const confirmFinalize = () => {
            Swal.fire({
                title: 'Finalisasi Hasil?',
                html: 'Nilai akan dikunci dan nomor SK / Sertifikat akan diterbitkan.<br><strong>Tindakan ini tidak dapat dibatalkan.</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1f2937',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Finalisasi',
                cancelButtonText: 'Batal',
            }).then(result => {
                if (result.isConfirmed) {
                    router.post(`/admin/results/${props.exam_session.id}/finalize`);
                }
            });
        };

        const distribute = () => {
            Swal.fire({
                title: 'Kirim Hasil ke Peserta?',
                html: 'SK dan Sertifikat akan dikirim ke email dan dashboard peserta yang sudah difinalisasi.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1f2937',
                cancelButtonText: 'Batal',
                confirmButtonText: 'Ya, Kirim',
            }).then(result => {
                if (result.isConfirmed) {
                    router.post(`/admin/results/${props.exam_session.id}/distribute`);
                }
            });
        };

        return { allFinalized, hasFinalized, nilaiColor, fmt, confirmFinalize, distribute };
    },
}
</script>

<style scoped>
/* Angka nilai: tabular-nums agar dua-desimal sejajar antar baris (Blueprint §5). */
.num {
    font-variant-numeric: tabular-nums;
    font-feature-settings: "tnum";
}
</style>
