<template>
    <Head><title>Lihat Dokumen — {{ student.name }}</title></Head>
    <div class="container-fluid mb-5 mt-4">
        <div class="col-lg-10 col-xl-9 mx-auto">

            <Link :href="`/manager/sertifikasi/${exam_session.id}`" class="btn btn-primary border-0 shadow mb-3">
                <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
            </Link>

            <!-- Info peserta -->
            <div class="card border-0 shadow mb-3">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-1">
                                <i class="fa fa-user me-2"></i>{{ student.name }}
                            </h6>
                            <div class="small text-muted">
                                No. Peserta: <span class="fw-semibold">{{ student.no_participant }}</span>
                                &nbsp;|&nbsp; Sesi: {{ exam_session.title }}
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-2 mt-md-0">
                            <span v-if="!application" class="badge bg-secondary">Belum ada aplikasi</span>
                            <template v-else>
                                <span class="badge me-1"
                                    :class="application.status === 'approved' ? 'bg-success' : application.status === 'submitted' ? 'bg-warning text-dark' : 'bg-secondary'">
                                    {{ statusLabel(application.status) }}
                                </span>
                                <span class="badge bg-secondary">
                                    {{ doneCount }} / {{ totalReq }} terverifikasi
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!application" class="alert alert-warning border-0 shadow">
                <i class="fa fa-exclamation-triangle me-2"></i>
                Peserta ini belum mengajukan permohonan sertifikasi untuk sesi ini.
            </div>

            <template v-else>
                <!-- Daftar dokumen (lihat & unduh saja) -->
                <div v-for="req in application.classroom.document_requirements" :key="req.id"
                    class="card border-0 shadow mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center py-2"
                        :class="docStatusClass(getDoc(req.id))">
                        <span class="fw-semibold small">
                            {{ req.label }}
                            <span v-if="req.is_required" class="text-danger">*</span>
                        </span>
                        <span class="badge" :class="badgeClass(getDoc(req.id))">
                            {{ badgeLabel(getDoc(req.id)) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div v-if="req.description" class="small text-muted mb-2">{{ req.description }}</div>

                        <div v-if="!getDoc(req.id)" class="text-muted small fst-italic">
                            <i class="fa fa-exclamation-circle me-1"></i>Peserta belum mengupload dokumen ini.
                        </div>

                        <template v-else>
                            <div class="d-flex align-items-center gap-3">
                                <div>
                                    <i class="fa fa-file me-1 text-primary"></i>
                                    <span class="small fw-semibold">{{ getDoc(req.id).original_filename }}</span>
                                </div>
                                <a :href="`/manager/dokumen/${exam_session.id}/${student.id}/${getDoc(req.id).id}/download`"
                                    class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fa fa-eye me-1"></i> Preview
                                </a>
                            </div>

                            <div v-if="getDoc(req.id).asesor_reviewer_notes"
                                class="alert py-2 small mt-3 mb-0"
                                :class="getDoc(req.id).asesor_status === 'rejected' ? 'alert-danger' : 'alert-info'">
                                <i class="fa fa-comment me-1"></i>{{ getDoc(req.id).asesor_reviewer_notes }}
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Ringkasan Verifikasi Akhir (baca saja) -->
                <div class="card border-0 shadow mb-3">
                    <div class="card-header bg-gray-800 text-white fw-semibold">
                        <i class="fa fa-signature me-2"></i>Laporan Asesmen / Verifikasi Akhir
                    </div>
                    <div class="card-body">
                        <div v-if="application.asesor_verified_at" class="d-flex align-items-center gap-3">
                            <div>
                                <span class="badge bg-success mb-1"><i class="fa fa-lock me-1"></i>Terverifikasi &amp; Terkunci</span>
                                <div class="small mt-1"><i class="fa fa-user me-1 text-muted"></i>{{ application.asesor_signature_name }}</div>
                                <div class="mt-1">
                                    <span v-if="application.asesor_rekomendasi === 'K'" class="badge bg-success">Rekomendasi: Kompeten</span>
                                    <span v-else-if="application.asesor_rekomendasi === 'BK'" class="badge bg-danger">Rekomendasi: Belum Kompeten</span>
                                </div>
                                <div class="small text-muted mt-1">{{ formatDateTime(application.asesor_verified_at) }}</div>
                            </div>
                        </div>
                        <div v-else-if="!assigned_asesor" class="alert alert-warning border-0 mb-0">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            Peserta ini belum memiliki penugasan asesor.
                        </div>
                        <div v-else class="alert alert-secondary border-0 mb-0">
                            <i class="fa fa-hourglass-half me-2"></i>
                            Menunggu Verifikasi Akhir oleh asesor yang ditugaskan (<strong>{{ assigned_asesor }}</strong>).
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </div>
</template>

<script>
import LayoutManager from '../../../Layouts/Manager.vue';
import { Head, Link } from '@inertiajs/vue3';

export default {
    layout: LayoutManager,
    components: { Head, Link },
    props: {
        exam_session:     Object,
        student:          Object,
        application:      Object,
        assigned_asesor:  String,
    },

    computed: {
        totalReq() {
            return this.application?.classroom?.document_requirements?.length ?? 0;
        },
        doneCount() {
            return this.application?.documents?.filter(d => d.asesor_status === 'verified').length ?? 0;
        },
    },

    methods: {
        getDoc(reqId) {
            return this.application?.documents?.find(d => d.classroom_document_requirement_id === reqId) ?? null;
        },
        statusLabel(s) {
            return ({ draft: 'Draft', submitted: 'Disubmit', approved: 'Disetujui', rejected: 'Ditolak' })[s] ?? s;
        },
        docStatusClass(doc) {
            if (!doc) return 'bg-light';
            return ({ pending: 'bg-light', verified: 'bg-success bg-opacity-10', rejected: 'bg-danger bg-opacity-10' })[doc.asesor_status] ?? 'bg-light';
        },
        badgeClass(doc) {
            if (!doc) return 'bg-secondary';
            return ({ pending: 'bg-warning text-dark', verified: 'bg-success', rejected: 'bg-danger' })[doc.asesor_status] ?? 'bg-secondary';
        },
        badgeLabel(doc) {
            if (!doc) return 'Belum Upload';
            return ({ pending: 'Menunggu', verified: 'Terverifikasi', rejected: 'Ditolak' })[doc.asesor_status] ?? doc.asesor_status;
        },
        formatDateTime(dt) {
            return dt ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
        },
    },
}
</script>
