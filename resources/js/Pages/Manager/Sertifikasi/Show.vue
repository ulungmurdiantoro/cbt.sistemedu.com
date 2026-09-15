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
            <a v-if="rows.length && !exam_session.keputusan_number" :href="`/manager/sertifikasi/${exam_session.id}/keputusan/preview`"
                target="_blank" class="btn btn-sm btn-outline-secondary border">
                <i class="fa fa-eye me-1"></i> Preview Keputusan
            </a>
            <a v-if="exam_session.keputusan_number" :href="`/manager/sertifikasi/${exam_session.id}/keputusan`"
                target="_blank" class="btn btn-sm btn-outline-dark border">
                <i class="fa fa-file-pdf me-1"></i> Keputusan Sertifikasi
            </a>
            <button class="btn btn-sm btn-warning text-dark fw-semibold" @click="confirmFinalize" :disabled="!hasFinalizableRows"
                :title="!hasFinalizableRows ? 'Centang Verifikasi minimal satu peserta terlebih dahulu' : ''">
                <i class="fa fa-lock me-1"></i> Finalisasi Semua
            </button>
        </div>

        <div class="alert alert-info py-2 small border-0 mb-3">
            <i class="fa fa-info-circle me-1"></i>
            Tinjau kelengkapan FR.APL.01, kelayakan FR.APL.03, dan laporan asesmen (rekomendasi asesor) sebelum finalisasi.
            Centang <strong>Verifikasi</strong> untuk peserta yang sudah siap — hanya peserta yang tercentang yang akan difinalisasi.
            Peserta yang belum dicentang (mis. berhalangan hadir) tetap Draft dan bisa difinalisasi belakangan, mis. saat ikut batch susulan.
            Pakai <strong>Preview Keputusan</strong> untuk melihat draf berita acara sebelum benar-benar difinalisasi.
        </div>

        <div v-if="!has_signature" class="alert alert-warning py-2 small border-0 mb-3">
            <i class="fa fa-exclamation-triangle me-1"></i>
            Anda belum punya tanda tangan tersimpan — kotak TTD pada Keputusan Sertifikasi akan kosong.
            <Link href="/manager/tanda-tangan" class="alert-link">Simpan tanda tangan Anda dulu</Link>
            sebelum finalisasi.
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
                                <th class="border-0 text-center">Dokumen Persyaratan</th>
                                <th class="border-0 text-center">FR.APL.01</th>
                                <th class="border-0 text-center">FR.APL.03</th>
                                <th class="border-0 text-center">FR.AK.01</th>
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

                                <!-- Dokumen Persyaratan -->
                                <td class="text-center">
                                    <span v-if="row.apl01_complete" class="badge bg-success">
                                        <i class="fa fa-check me-1"></i>Lengkap
                                    </span>
                                    <span v-else class="badge bg-warning text-dark">
                                        {{ row.apl01_verified }} / {{ row.apl01_total }}
                                    </span>
                                    <button type="button" class="btn btn-link btn-sm p-0 d-block w-100 text-center mt-1" style="font-size:inherit"
                                        @click="openDokumenModal(row)">
                                        <i class="fa fa-eye me-1"></i>Lihat Dokumen
                                    </button>
                                </td>

                                <!-- FR.APL.01 -->
                                <td class="text-center">
                                    <a v-if="row.application_id" :href="`/manager/applications/${row.application_id}/fr-apl-01`"
                                        target="_blank" class="d-block small" title="Generate FR.APL.01">
                                        <i class="fa fa-file-pdf me-1"></i>FR.APL.01
                                    </a>
                                    <span v-else class="text-muted small">—</span>
                                </td>

                                <!-- FR.APL.03 -->
                                <td class="text-center">
                                    <span v-if="!row.apl03_done" class="badge bg-secondary">Belum</span>
                                    <span v-else-if="row.apl03_eligible" class="badge bg-success">
                                        <i class="fa fa-check me-1"></i>Layak ({{ row.apl03_score }})
                                    </span>
                                    <span v-else class="badge bg-danger">Tidak Layak ({{ row.apl03_score }})</span>
                                    <a v-if="row.apl03_done && row.application_id" :href="`/manager/applications/${row.application_id}/fr-apl-03`"
                                        target="_blank" class="d-block small mt-1" title="Generate FR.APL.03">
                                        <i class="fa fa-file-pdf me-1"></i>FR.APL.03
                                    </a>
                                </td>

                                <!-- FR.AK.01 -->
                                <td class="text-center">
                                    <a v-if="row.application_id" :href="`/manager/applications/${row.application_id}/fr-ak-01`"
                                        target="_blank" class="d-block small" title="Generate FR.AK.01">
                                        <i class="fa fa-file-pdf me-1"></i>FR.AK.01
                                    </a>
                                    <span v-else class="text-muted small">—</span>
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
                                <td colspan="14" class="text-center text-muted py-4">Belum ada peserta terdaftar di sesi ini.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal: Lihat Dokumen Peserta -->
        <div v-if="dokumenModal.open" class="modal d-block" style="background:rgba(0,0,0,.5)">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold mb-0">
                            <i class="fa fa-folder-open me-2"></i>Dokumen — {{ dokumenModal.data?.student?.name ?? dokumenModal.rowName }}
                        </h6>
                        <button class="btn-close" @click="closeDokumenModal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="dokumenModal.loading" class="text-center text-muted py-4">
                            <i class="fa fa-spinner fa-spin me-1"></i> Memuat...
                        </div>
                        <div v-else-if="dokumenModal.error" class="alert alert-danger border-0 mb-0">{{ dokumenModal.error }}</div>
                        <template v-else-if="dokumenModal.data">
                            <div class="small text-muted mb-3">
                                No. Peserta: <span class="fw-semibold">{{ dokumenModal.data.student?.no_participant }}</span>
                            </div>

                            <div v-if="!dokumenModal.data.application" class="alert alert-warning border-0 mb-0">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                Peserta ini belum mengajukan permohonan sertifikasi untuk sesi ini.
                            </div>

                            <template v-else>
                                <div v-for="req in dokumenModal.data.application.classroom.document_requirements" :key="req.id"
                                    class="card border mb-2">
                                    <div class="card-header d-flex justify-content-between align-items-center py-2"
                                        :class="docStatusClass(getModalDoc(req.id))">
                                        <span class="fw-semibold small">
                                            {{ req.label }}
                                            <span v-if="req.is_required" class="text-danger">*</span>
                                        </span>
                                        <span class="badge" :class="badgeClass(getModalDoc(req.id))">
                                            {{ badgeLabel(getModalDoc(req.id)) }}
                                        </span>
                                    </div>
                                    <div class="card-body py-2">
                                        <div v-if="req.description" class="small text-muted mb-2">{{ req.description }}</div>
                                        <div v-if="!getModalDoc(req.id)" class="text-muted small fst-italic">
                                            <i class="fa fa-exclamation-circle me-1"></i>Peserta belum mengupload dokumen ini.
                                        </div>
                                        <template v-else>
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <i class="fa fa-file me-1 text-primary"></i>
                                                    <span class="small fw-semibold">{{ getModalDoc(req.id).original_filename }}</span>
                                                </div>
                                                <a :href="`/manager/dokumen/${exam_session.id}/${dokumenModal.data.student.id}/${getModalDoc(req.id).id}/download`"
                                                    class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fa fa-eye me-1"></i> Preview
                                                </a>
                                            </div>
                                            <div v-if="getModalDoc(req.id).reviewer_notes"
                                                class="alert py-2 small mt-2 mb-0"
                                                :class="getModalDoc(req.id).status === 'rejected' ? 'alert-danger' : 'alert-info'">
                                                <i class="fa fa-comment me-1"></i>{{ getModalDoc(req.id).reviewer_notes }}
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="card border mb-0">
                                    <div class="card-header bg-gray-800 text-white fw-semibold py-2">
                                        <i class="fa fa-signature me-2"></i>Laporan Asesmen / Verifikasi Akhir
                                    </div>
                                    <div class="card-body">
                                        <div v-if="dokumenModal.data.application.asesor_verified_at">
                                            <span class="badge bg-success mb-1"><i class="fa fa-lock me-1"></i>Terverifikasi &amp; Terkunci</span>
                                            <div class="small mt-1"><i class="fa fa-user me-1 text-muted"></i>{{ dokumenModal.data.application.asesor_signature_name }}</div>
                                            <div class="mt-1">
                                                <span v-if="dokumenModal.data.application.asesor_rekomendasi === 'K'" class="badge bg-success">Rekomendasi: Kompeten</span>
                                                <span v-else-if="dokumenModal.data.application.asesor_rekomendasi === 'BK'" class="badge bg-danger">Rekomendasi: Belum Kompeten</span>
                                            </div>
                                        </div>
                                        <div v-else-if="!dokumenModal.data.assigned_asesor" class="alert alert-warning border-0 mb-0">
                                            <i class="fa fa-exclamation-triangle me-2"></i>Peserta ini belum memiliki penugasan asesor.
                                        </div>
                                        <div v-else class="alert alert-secondary border-0 mb-0">
                                            <i class="fa fa-hourglass-half me-2"></i>
                                            Menunggu Verifikasi Akhir oleh asesor yang ditugaskan (<strong>{{ dokumenModal.data.assigned_asesor }}</strong>).
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light border btn-sm" @click="closeDokumenModal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import LayoutManager from '../../../Layouts/Manager.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutManager,
    components: { Head, Link, StatusBadge },
    props: {
        exam_session:  Object,
        rows:          Array,
        has_signature: Boolean,
    },

    setup(props) {
        const allFinalized = computed(() => props.rows.length > 0 && props.rows.every(r => r.is_finalized));
        const hasFinalizableRows = computed(() => props.rows.some(r => !r.is_finalized && r.manager_verified_at));

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
            const sigWarning = props.has_signature
                ? ''
                : '<br><br><span style="color:#b45309"><strong>Perhatian:</strong> Anda belum menyimpan tanda tangan — kotak TTD pada Keputusan Sertifikasi akan kosong.</span>';
            Swal.fire({
                title: 'Finalisasi Kelulusan?',
                html: 'Hanya peserta yang sudah dicentang <strong>Verifikasi</strong> yang akan difinalisasi — nilai dikunci dan nomor SK / Sertifikat diterbitkan untuk yang LULUS. Peserta yang belum dicentang tetap Draft.<br><strong>Tindakan ini tidak dapat dibatalkan untuk peserta yang difinalisasi.</strong>' + sigWarning,
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

        // Modal "Lihat Dokumen" — dimuat via fetch supaya tetap di halaman
        // Tinjau Sertifikasi ini, tidak pindah ke halaman terpisah.
        const dokumenModal = reactive({ open: false, loading: false, error: '', data: null, rowName: '' });

        const openDokumenModal = (row) => {
            dokumenModal.open = true;
            dokumenModal.loading = true;
            dokumenModal.error = '';
            dokumenModal.data = null;
            dokumenModal.rowName = row.name;

            fetch(`/manager/dokumen/${props.exam_session.id}/${row.student_id}/data`, {
                headers: { 'Accept': 'application/json' },
            })
                .then(res => { if (!res.ok) throw new Error('Gagal memuat dokumen peserta.'); return res.json(); })
                .then(data => { dokumenModal.data = data; })
                .catch(e => { dokumenModal.error = e.message || 'Gagal memuat dokumen peserta.'; })
                .finally(() => { dokumenModal.loading = false; });
        };

        const closeDokumenModal = () => { dokumenModal.open = false; };

        const getModalDoc = (reqId) =>
            dokumenModal.data?.application?.documents?.find(d => d.classroom_document_requirement_id === reqId) ?? null;

        const docStatusClass = (doc) => {
            if (!doc) return 'bg-light';
            return ({ pending: 'bg-light', verified: 'bg-success bg-opacity-10', rejected: 'bg-danger bg-opacity-10' })[doc.status] ?? 'bg-light';
        };
        const badgeClass = (doc) => {
            if (!doc) return 'bg-secondary';
            return ({ pending: 'bg-warning text-dark', verified: 'bg-success', rejected: 'bg-danger' })[doc.status] ?? 'bg-secondary';
        };
        const badgeLabel = (doc) => {
            if (!doc) return 'Belum Upload';
            return ({ pending: 'Menunggu', verified: 'Terverifikasi', rejected: 'Ditolak' })[doc.status] ?? doc.status;
        };

        return {
            allFinalized, hasFinalizableRows, togglingId, nilaiColor, fmt, toggleVerify, confirmFinalize,
            dokumenModal, openDokumenModal, closeDokumenModal, getModalDoc, docStatusClass, badgeClass, badgeLabel,
        };
    },
}
</script>

<style scoped>
.num {
    font-variant-numeric: tabular-nums;
    font-feature-settings: "tnum";
}
</style>
