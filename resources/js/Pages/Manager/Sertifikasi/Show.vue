<template>
    <Head><title>Tinjau Sertifikasi - {{ exam_session.title }}</title></Head>

    <div class="container-fluid mb-5 mt-4">

        <!-- Header -->
        <div class="d-flex align-items-start gap-3 mb-4">
            <Link href="/manager/dashboard" class="btn btn-sm btn-outline-secondary mt-1">
                <i class="fa fa-arrow-left"></i>
            </Link>
            <div class="flex-grow-1">
                <h5 class="mb-0 fw-bold">{{ exam_session.title }}</h5>
                <p class="mb-0 small text-muted">Kode Batch: {{ exam_session.kode_batch }} &bull; {{ exam_session.start_time }} – {{ exam_session.end_time }}</p>
            </div>
            <button class="btn btn-sm btn-warning text-dark fw-semibold" @click="confirmFinalize" :disabled="allFinalized || !allVerified"
                :title="!allVerified && !allFinalized ? 'Semua peserta harus dicentang Verifikasi terlebih dahulu' : ''">
                <i class="fa fa-lock me-1"></i> Finalisasi Semua
            </button>
        </div>

        <div class="alert alert-info py-2 small border-0 mb-3">
            <i class="fa fa-info-circle me-1"></i>
            Tinjau kelengkapan FR.APL.01, kelayakan FR.APL.03, dan laporan asesmen (rekomendasi asesor) sebelum finalisasi.
            Centang <strong>Verifikasi</strong> untuk tiap peserta setelah ditinjau — semua peserta harus dicentang sebelum "Finalisasi Semua" bisa diklik.
            Finalisasi mengunci nilai &amp; menerbitkan nomor SK/Sertifikat berdasarkan nilai akhir vs KKM.
        </div>

        <div v-if="$page.props.session?.success" class="alert alert-success py-2 small border-0 mb-3">
            {{ $page.props.session.success }}
        </div>

        <div class="card border-0 shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" style="font-size:0.85rem">
                        <thead class="thead-dark">
                            <tr>
                                <th class="border-0" style="width:3%">No.</th>
                                <th class="border-0">Peserta</th>
                                <th class="border-0 text-center">FR.APL.01</th>
                                <th class="border-0 text-center">FR.APL.03</th>
                                <th class="border-0">Laporan Asesmen</th>
                                <th class="border-0 text-center">PG</th>
                                <th class="border-0 text-center">Esai</th>
                                <th class="border-0 text-center">Wawancara</th>
                                <th class="border-0 text-center">Nilai Akhir</th>
                                <th class="border-0 text-center">Keputusan</th>
                                <th class="border-0 text-center">Verifikasi</th>
                                <th class="border-0 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in rows" :key="row.student_id">
                                <td class="text-center">{{ i + 1 }}</td>
                                <td>
                                    <div class="fw-semibold">{{ row.name }}</div>
                                    <div class="text-muted small">{{ row.no_participant }}</div>
                                </td>

                                <!-- FR.APL.01 -->
                                <td class="text-center">
                                    <span v-if="row.apl01_complete" class="badge bg-success">
                                        <i class="fa fa-check me-1"></i>Lengkap
                                    </span>
                                    <span v-else class="badge bg-warning text-dark">
                                        {{ row.apl01_verified }} / {{ row.apl01_total }}
                                    </span>
                                    <a :href="`/manager/dokumen/${exam_session.id}/${row.student_id}`"
                                        target="_blank" class="d-block small mt-1" title="Lihat Dokumen">
                                        <i class="fa fa-eye me-1"></i>Lihat Dokumen
                                    </a>
                                </td>

                                <!-- FR.APL.03 -->
                                <td class="text-center">
                                    <span v-if="!row.apl03_done" class="badge bg-secondary">Belum</span>
                                    <span v-else-if="row.apl03_eligible" class="badge bg-success">
                                        <i class="fa fa-check me-1"></i>Layak ({{ row.apl03_score }})
                                    </span>
                                    <span v-else class="badge bg-danger">Tidak Layak ({{ row.apl03_score }})</span>
                                </td>

                                <!-- Laporan Asesmen -->
                                <td>
                                    <div v-if="row.asesor_name" class="small">
                                        <i class="fa fa-user-tie me-1 text-muted"></i>{{ row.asesor_name }}
                                    </div>
                                    <div v-else class="text-muted small fst-italic">Belum ditugaskan</div>
                                    <span v-if="row.asesor_rekomendasi === 'K'" class="badge bg-success mt-1">Kompeten</span>
                                    <span v-else-if="row.asesor_rekomendasi === 'BK'" class="badge bg-danger mt-1">Belum Kompeten</span>
                                    <span v-else class="badge bg-secondary mt-1">Belum ada rekomendasi</span>
                                    <a v-if="row.asesor_id" :href="`/dokumen/laporan-asesmen/${exam_session.id}/${row.asesor_id}/download`"
                                        target="_blank" class="d-block small mt-1" title="Download FR.AK.05">
                                        <i class="fa fa-file-pdf me-1"></i>FR.AK.05
                                    </a>
                                </td>

                                <td class="text-center num">{{ row.nilai_pg !== null ? fmt(row.nilai_pg) : '—' }}</td>
                                <td class="text-center num">{{ row.nilai_esai !== null ? fmt(row.nilai_esai) : '—' }}</td>
                                <td class="text-center num">{{ row.nilai_wawancara !== null ? fmt(row.nilai_wawancara) : '—' }}</td>
                                <td class="text-center fw-bold num">
                                    <span v-if="row.nilai_akhir !== null" :class="nilaiColor(row)">{{ fmt(row.nilai_akhir) }}</span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-center">
                                    <StatusBadge v-if="row.keputusan === 'LULUS'" tone="success" label="LULUS" />
                                    <StatusBadge v-else-if="row.keputusan === 'TIDAK_LULUS'" tone="danger" label="TIDAK LULUS" />
                                    <span v-else class="text-muted small">—</span>
                                </td>
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center mb-0">
                                        <input class="form-check-input" type="checkbox"
                                            :checked="!!row.manager_verified_at"
                                            :disabled="row.is_finalized || togglingId === row.student_id"
                                            @change="toggleVerify(row)">
                                    </div>
                                    <div v-if="row.manager_verified_at" class="small text-muted mt-1">{{ row.manager_verified_by }}</div>
                                </td>
                                <td class="text-center">
                                    <span v-if="row.is_finalized" class="badge text-white" style="background-color:#212529;">
                                        <i class="fa fa-lock me-1"></i>Final
                                    </span>
                                    <span v-else class="badge text-white" style="background-color:#6c757d;">Draft</span>
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td colspan="12" class="text-center text-muted py-4">Belum ada peserta terdaftar di sesi ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import LayoutManager from '../../../Layouts/Manager.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutManager,
    components: { Head, Link, StatusBadge },
    props: {
        exam_session: Object,
        rows:         Array,
    },

    setup(props) {
        const allFinalized = computed(() => props.rows.length > 0 && props.rows.every(r => r.is_finalized));
        const allVerified  = computed(() => props.rows.length > 0 && props.rows.every(r => r.is_finalized || r.manager_verified_at));

        const togglingId = ref(null);

        const nilaiColor = (row) => {
            if (row.keputusan === 'LULUS') return 'text-success';
            if (row.keputusan === 'TIDAK_LULUS') return 'text-danger';
            return '';
        };

        const fmt = (v) => (v === null || v === undefined || v === '') ? '—' : Number(v).toFixed(2);

        const toggleVerify = (row) => {
            togglingId.value = row.student_id;
            router.post(
                `/manager/sertifikasi/${props.exam_session.id}/verifikasi/${row.student_id}`,
                {},
                {
                    preserveScroll: true,
                    preserveState:  true,
                    onFinish: () => { togglingId.value = null; },
                }
            );
        };

        const confirmFinalize = () => {
            Swal.fire({
                title: 'Finalisasi Kelulusan?',
                html: 'Nilai akan dikunci dan nomor SK / Sertifikat akan diterbitkan untuk peserta yang LULUS.<br><strong>Tindakan ini tidak dapat dibatalkan.</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1f2937',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Finalisasi',
                cancelButtonText: 'Batal',
            }).then(result => {
                if (result.isConfirmed) {
                    router.post(`/manager/sertifikasi/${props.exam_session.id}/finalize`);
                }
            });
        };

        return { allFinalized, allVerified, togglingId, nilaiColor, fmt, toggleVerify, confirmFinalize };
    },
}
</script>

<style scoped>
.num {
    font-variant-numeric: tabular-nums;
    font-feature-settings: "tnum";
}
</style>
