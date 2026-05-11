<template>
    <Head>
        <title>Dashboard - Portal Peserta Sertifikasi</title>
    </Head>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="alert alert-success border-0 shadow">
                Selamat Datang, <strong>{{ $page.props.auth.participant.name }}</strong>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Permohonan Sertifikasi Saya</h5>
            <Link href="/peserta/skema" class="btn btn-gray-800 btn-sm shadow">
                <i class="fa fa-plus me-1"></i> Tambah Skema
            </Link>
        </div>
    </div>

    <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
        {{ $page.props.session.success }}
    </div>
    <div v-if="$page.props.session.error" class="alert alert-danger border-0 shadow mb-3">
        {{ $page.props.session.error }}
    </div>

    <div v-if="applications.length === 0" class="alert alert-info border-0 shadow">
        <i class="fa fa-info-circle me-1"></i> Belum ada permohonan. Klik <strong>Tambah Skema</strong> untuk mendaftar.
    </div>

    <div class="row">
        <div class="col-md-6 mb-4" v-for="app in applications" :key="app.id">
            <div class="card border-0 shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold mb-0">{{ app.classroom.title }}</h6>
                        <span :class="statusBadge(app.status)" class="badge">{{ statusLabel(app.status) }}</span>
                    </div>
                    <p class="text-muted small mb-2">{{ app.exam_session?.title }}</p>

                    <table class="table table-sm mb-3">
                        <tr>
                            <td class="text-muted small" style="width:45%">Konteks Asesmen</td>
                            <td class="small">{{ app.konteks_asesmen }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Tempat Ujian</td>
                            <td class="small">{{ app.tempat_ujian }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Kode Batch</td>
                            <td class="small">{{ app.kode_batch }}</td>
                        </tr>
                        <tr v-if="app.student">
                            <td class="text-muted small fw-bold">No. Peserta Ujian</td>
                            <td class="small fw-bold text-success">{{ app.student.no_participant }}</td>
                        </tr>
                    </table>

                    <!-- Progress Steps -->
                    <div class="d-flex align-items-start mb-3" style="font-size:0.72rem">
                        <template v-for="(step, idx) in appSteps(app)" :key="idx">
                            <div class="d-flex flex-column align-items-center text-center" style="min-width:52px">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1"
                                    :class="step.done ? 'bg-success text-white' : 'bg-light border text-muted'"
                                    style="width:26px;height:26px;font-size:0.75rem;flex-shrink:0">
                                    <i v-if="step.done" class="fa fa-check"></i>
                                    <span v-else>{{ idx + 1 }}</span>
                                </div>
                                <div :class="step.done ? 'text-success fw-semibold' : 'text-muted'">{{ step.label }}</div>
                            </div>
                            <div v-if="idx < 3" class="flex-fill mt-2 mx-1"
                                :style="{ borderTop: '2px solid', borderColor: step.done ? '#198754' : '#dee2e6' }">
                            </div>
                        </template>
                    </div>

                    <!-- Aksi berdasarkan status -->
                    <div v-if="app.status === 'draft'" class="d-flex gap-2 flex-wrap">
                        <Link :href="`/peserta/aplikasi/${app.id}/form`" class="btn btn-sm btn-primary">
                            <i class="fa fa-edit me-1"></i> Isi Formulir
                        </Link>
                        <Link :href="`/peserta/aplikasi/${app.id}/dokumen`" class="btn btn-sm btn-secondary">
                            <i class="fa fa-upload me-1"></i> Upload Dokumen
                        </Link>
                    </div>

                    <div v-else-if="app.status === 'submitted'" class="text-muted small">
                        <i class="fa fa-clock me-1"></i> Menunggu verifikasi admin
                    </div>

                    <div v-else-if="app.status === 'rejected'" class="d-flex flex-column gap-2">
                        <div class="alert alert-danger p-2 small mb-0" v-if="app.admin_notes">
                            <i class="fa fa-exclamation-circle me-1"></i>
                            <strong>Alasan penolakan:</strong> {{ app.admin_notes }}
                        </div>
                        <button class="btn btn-sm btn-warning" @click="openRevisiModal(app)">
                            <i class="fa fa-edit me-1"></i> Revisi & Submit Ulang
                        </button>
                    </div>

                    <div v-else-if="app.status === 'approved'" class="alert alert-success p-2 small mb-0">
                        <i class="fa fa-check-circle me-1"></i>
                        Permohonan disetujui. Login ujian menggunakan No. Peserta di atas.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal: Konfirmasi Revisi -->
    <div v-if="revisiTarget" class="modal d-block" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h6 class="modal-title fw-bold">
                        <i class="fa fa-edit text-warning me-2"></i>Revisi Permohonan
                    </h6>
                    <button class="btn-close" @click="revisiTarget = null"></button>
                </div>
                <div class="modal-body">
                    <p class="small mb-2">Permohonan akan dikembalikan ke status <strong>Draft</strong> sehingga Anda dapat memperbaiki dan submit ulang.</p>
                    <div v-if="revisiTarget.admin_notes" class="alert alert-danger p-2 small">
                        <strong>Alasan penolakan:</strong> {{ revisiTarget.admin_notes }}
                    </div>
                    <p class="small text-muted mb-0">Data formulir, tanda tangan, dan dokumen yang sudah diupload akan tetap tersimpan.</p>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-warning" @click="confirmRevisi" :disabled="processing">
                        <i class="fa fa-edit me-1"></i>{{ processing ? 'Memproses...' : 'Mulai Revisi' }}
                    </button>
                    <button class="btn btn-light border" @click="revisiTarget = null">Batal</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutPeserta from '../../../Layouts/Peserta.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

export default {
    layout: LayoutPeserta,
    components: { Head, Link },
    props: {
        applications: Array,
    },

    setup() {
        const processing  = ref(false);
        const revisiTarget = ref(null);

        const statusLabel = (status) => ({
            draft:     'Draft',
            submitted: 'Disubmit',
            approved:  'Disetujui',
            rejected:  'Ditolak',
        }[status] ?? status);

        const statusBadge = (status) => ({
            draft:     'bg-secondary',
            submitted: 'bg-warning text-dark',
            approved:  'bg-success',
            rejected:  'bg-danger',
        }[status] ?? 'bg-secondary');

        const appSteps = (app) => [
            { label: 'Formulir',  done: !!app.signature_form_path },
            { label: 'Pakta',     done: !!app.pakta_signed_at },
            { label: 'Dokumen',   done: app.docs_required > 0 && app.docs_uploaded >= app.docs_required },
            { label: 'Submit',    done: app.status !== 'draft' },
        ];

        const openRevisiModal = (app) => { revisiTarget.value = app; };

        const confirmRevisi = () => {
            if (!revisiTarget.value) return;
            processing.value = true;
            router.post(`/peserta/aplikasi/${revisiTarget.value.id}/revisi`, {}, {
                onFinish: () => { processing.value = false; revisiTarget.value = null; },
            });
        };

        return { statusLabel, statusBadge, appSteps, processing, revisiTarget, openRevisiModal, confirmRevisi };
    },
}
</script>
