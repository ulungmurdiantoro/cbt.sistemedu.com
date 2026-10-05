<template>
    <Head><title>Rekap Hasil - {{ exam_session.title }}</title></Head>

    <div class="container-fluid mb-5 mt-4">

        <!-- Header -->
        <div class="d-flex flex-wrap align-items-start gap-3 mb-4">
            <Link href="/admin/results" class="btn btn-sm btn-outline-secondary mt-1">
                <i class="fa fa-arrow-left"></i>
            </Link>
            <div class="flex-grow-1">
                <h5 class="mb-0 fw-bold">{{ exam_session.title }}</h5>
                <p class="mb-0 small text-muted">Kode Batch: {{ exam_session.kode_batch }} &bull; {{ exam_session.start_time }} – {{ exam_session.end_time }}</p>
            </div>
            <div class="d-flex flex-column align-items-end gap-1 ms-auto">
                <div class="d-flex flex-wrap justify-content-end gap-2 text-nowrap">
                    <!-- Eks menu Laporan Nilai -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary fw-bolder dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fa fa-download me-1"></i> Laporan Nilai
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="font-size:0.85rem;">
                            <li>
                                <a class="dropdown-item" :href="`/admin/reports/export?exam_session_id=${exam_session.id}`" target="_blank">
                                    <i class="fa fa-file-excel text-success me-2"></i>Excel (nilai per ujian)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" :href="`/admin/reports/export-pdf?exam_session_id=${exam_session.id}&layout=ringkas`" target="_blank">
                                    <i class="fa fa-file-pdf text-danger me-2"></i>PDF Ringkas (A4)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" :href="`/admin/reports/export-pdf?exam_session_id=${exam_session.id}&layout=lebar`" target="_blank">
                                    <i class="fa fa-file-pdf text-danger me-2"></i>PDF Lebar (A0)
                                </a>
                            </li>
                        </ul>
                    </div>
                    <button class="btn btn-sm btn-outline-success fw-bolder" @click="distributeSp" :disabled="!hasFinalized">
                        <i class="fa fa-paper-plane me-1"></i> 1. Kirim SP
                    </button>
                    <button class="btn btn-sm btn-success text-white fw-bolder" @click="distribute" :disabled="!hasFinalized">
                        <i class="fa fa-paper-plane me-1"></i> 2. Kirim SK &amp; Sertifikat
                    </button>
                </div>
                <div class="small text-muted">
                    SP terkirim: {{ spSentCount }}/{{ finalizedCount }} &bull; SK/Sertifikat terkirim: {{ finalSentCount }}/{{ finalizedCount }}
                </div>
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

        <div class="alert alert-secondary py-2 small border-0 mb-3">
            <i class="fa fa-lock me-1"></i>
            Finalisasi kelulusan sekarang jadi wewenang <strong>Pengambil Keputusan</strong> (Portal Pengambil Keputusan).
            Halaman ini hanya untuk memantau &amp; mengirim dokumen yang sudah difinalisasi.
        </div>

        <!-- Flash -->
        <div v-if="$page.props.session?.success" class="alert alert-success py-2 small border-0 mb-3">
            {{ $page.props.session.success }}
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
                                <th class="border-0 text-center" v-if="show_tugas">Tugas</th>
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
                                    <span v-if="row.attempt > 1" class="badge bg-warning text-gray-800 ms-1 small">Remidi</span>
                                </td>
                                <td class="text-center" v-if="show_tugas">
                                    <a v-if="tugas[row.student_id]"
                                       :href="`/admin/results/${exam_session.id}/tugas/${row.student_id}`"
                                       target="_blank"
                                       :title="`Lihat ${tugas[row.student_id].original_filename}`"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <!-- Angka PG / Esai → detail jawaban peserta (eks Laporan Nilai) -->
                                <td class="text-center num" v-if="exam_session.exam_id_pg">
                                    <Link v-if="row.grade_id_pg" :href="`/admin/reports/${row.grade_id_pg}`" class="text-primary text-nowrap" title="Detail jawaban PG">
                                        {{ row.nilai_pg !== null ? fmt(row.nilai_pg) : '—' }}<i class="fa fa-search-plus small ms-1"></i>
                                    </Link>
                                    <template v-else>{{ row.nilai_pg !== null ? fmt(row.nilai_pg) : '—' }}</template>
                                </td>
                                <td class="text-center num" v-if="exam_session.exam_id_esai">
                                    <Link v-if="row.grade_id_esai" :href="`/admin/reports/${row.grade_id_esai}`" class="text-primary text-nowrap" title="Detail jawaban esai">
                                        {{ row.nilai_esai !== null ? fmt(row.nilai_esai) : '—' }}<i class="fa fa-search-plus small ms-1"></i>
                                    </Link>
                                    <template v-else>{{ row.nilai_esai !== null ? fmt(row.nilai_esai) : '—' }}</template>
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
                                            <span class="fw-bolder">SP:</span> {{ row.sp_number }}
                                        </div>
                                        <div v-if="row.sk_number" class="text-muted">
                                            <span class="fw-bolder">SK:</span> {{ row.sk_number }}
                                        </div>
                                        <div v-if="row.sertifikat_number" class="text-muted">
                                            <span class="fw-bolder">Sert:</span> {{ row.sertifikat_number }}
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
        tugas:        Object,
        show_tugas:   Boolean,
    },

    setup(props) {
        const hasFinalized    = computed(() => props.rows.some(r => r.is_finalized));
        const finalizedCount  = computed(() => props.rows.filter(r => r.is_finalized).length);
        const spSentCount     = computed(() => props.rows.filter(r => r.sp_distributed_at).length);
        const finalSentCount  = computed(() => props.rows.filter(r => r.distributed_at).length);

        const nilaiColor = (row) => {
            if (row.keputusan === 'LULUS') return 'text-success';
            if (row.keputusan === 'TIDAK_LULUS') return 'text-danger';
            return '';
        };

        // Format nilai dua desimal (Blueprint: 87.50), aman untuk null.
        const fmt = (v) => (v === null || v === undefined || v === '') ? '—' : Number(v).toFixed(2);

        const distributeSp = () => {
            Swal.fire({
                title: 'Kirim SP ke Peserta?',
                html: 'Surat Pernyataan (SP) akan dikirim ke email peserta yang sudah difinalisasi, supaya mereka bisa memeriksa & mengajukan revisi (mis. typo nama) sebelum SK &amp; Sertifikat resmi diterbitkan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1f2937',
                cancelButtonText: 'Batal',
                confirmButtonText: 'Ya, Kirim SP',
            }).then(result => {
                if (result.isConfirmed) {
                    router.post(`/admin/results/${props.exam_session.id}/distribute-sp`);
                }
            });
        };

        const distribute = () => {
            const belumSp = finalizedCount.value - spSentCount.value;
            Swal.fire({
                title: 'Kirim SK & Sertifikat ke Peserta?',
                html: 'SK dan Sertifikat akan dikirim ke email dan dashboard peserta yang sudah difinalisasi.'
                    + (belumSp > 0 ? `<br><br><span class="text-warning"><i class="fa fa-exclamation-triangle"></i> ${belumSp} peserta belum dikirimi SP — pastikan sudah diberi kesempatan mengajukan revisi sebelum lanjut.</span>` : ''),
                icon: 'question',
                input: 'checkbox',
                inputValue: 1,
                inputPlaceholder: 'Sertakan logo KAN pada SK & Sertifikat',
                showCancelButton: true,
                confirmButtonColor: '#1f2937',
                cancelButtonText: 'Batal',
                confirmButtonText: 'Ya, Kirim',
            }).then(result => {
                if (result.isConfirmed) {
                    router.post(`/admin/results/${props.exam_session.id}/distribute`, {
                        kan: !!result.value,
                    });
                }
            });
        };

        return { hasFinalized, finalizedCount, spSentCount, finalSentCount, nilaiColor, fmt, distributeSp, distribute };
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
