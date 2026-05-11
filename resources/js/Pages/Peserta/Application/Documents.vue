<template>
    <Head>
        <title>Upload Dokumen - Portal Peserta Sertifikasi</title>
    </Head>

    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><Link href="/peserta/dashboard">Dashboard</Link></li>
                    <li class="breadcrumb-item"><Link :href="`/peserta/aplikasi/${application.id}/form`">FR.APL.01</Link></li>
                    <li class="breadcrumb-item"><Link :href="`/peserta/aplikasi/${application.id}/pakta`">FR.AK.01</Link></li>
                    <li class="breadcrumb-item active">Bukti Kelengkapan</li>
                </ol>
            </nav>
            <h5 class="fw-bold">Bagian 3 — Bukti Kelengkapan Pemohon</h5>
            <p class="text-muted small">Upload dokumen persyaratan. Format: PDF / JPG / PNG, maks. 5 MB per file.</p>
        </div>
    </div>

    <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
        {{ $page.props.session.success }}
    </div>
    <div v-if="$page.props.session.error" class="alert alert-danger border-0 shadow mb-3">
        {{ $page.props.session.error }}
    </div>

    <div v-if="requirements.length === 0" class="alert alert-warning border-0 shadow">
        <i class="fa fa-exclamation-triangle me-1"></i> Belum ada persyaratan dokumen yang dikonfigurasi untuk skema ini.
    </div>

    <div class="card border-0 shadow mb-4" v-else>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="bg-gray-100">
                    <tr>
                        <th style="width:40px">No.</th>
                        <th>Dokumen</th>
                        <th style="width:140px">Status</th>
                        <th style="width:200px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(req, i) in requirements" :key="req.id">
                        <td class="align-middle">{{ i + 1 }}</td>
                        <td class="align-middle">
                            <div class="fw-semibold small">
                                {{ req.label }}
                                <span v-if="req.is_required" class="text-danger">*</span>
                            </div>
                            <div v-if="req.description" class="text-muted" style="font-size:0.78rem">{{ req.description }}</div>
                            <div v-if="req.document" class="mt-1" style="font-size:0.78rem">
                                <i class="fa fa-paperclip me-1 text-muted"></i>
                                <a :href="`/peserta/aplikasi/${application.id}/dokumen/${req.document.id}/download`" target="_blank" class="text-decoration-none">
                                    {{ req.document.original_filename }}
                                </a>
                            </div>
                            <div v-if="req.document?.reviewer_notes" class="text-danger mt-1" style="font-size:0.78rem">
                                <i class="fa fa-exclamation-circle me-1"></i>{{ req.document.reviewer_notes }}
                            </div>
                        </td>
                        <td class="align-middle">
                            <span v-if="!req.document" class="badge bg-secondary">Belum Upload</span>
                            <span v-else-if="req.document.status === 'pending'" class="badge bg-warning text-dark">Menunggu</span>
                            <span v-else-if="req.document.status === 'verified'" class="badge bg-success">Terverifikasi</span>
                            <span v-else-if="req.document.status === 'rejected'" class="badge bg-danger">Ditolak</span>
                        </td>
                        <td class="align-middle">
                            <div v-if="application.status === 'draft'">
                                <label :for="`file-${req.id}`"
                                    class="btn btn-sm btn-primary mb-1"
                                    :class="{ disabled: uploading[req.id] }">
                                    <span v-if="uploading[req.id]">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                        Mengunggah...
                                    </span>
                                    <span v-else>
                                        <i class="fa fa-upload me-1"></i>
                                        {{ req.document ? 'Ganti' : 'Upload' }}
                                    </span>
                                </label>
                                <input :id="`file-${req.id}`" type="file" class="d-none"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    :disabled="uploading[req.id]"
                                    @change="uploadFile($event, req.id)">
                                <button v-if="req.document && !uploading[req.id]" type="button"
                                    class="btn btn-sm btn-danger mb-1 ms-1"
                                    @click="openDeleteModal(req.document.id)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                            <div v-else class="text-muted small">—</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2 mb-5" v-if="application.status === 'draft'">
        <button type="button" class="btn btn-success shadow" @click="openSubmitModal">
            <i class="fa fa-paper-plane me-1"></i> Submit Permohonan
        </button>
        <Link :href="`/peserta/aplikasi/${application.id}/pakta`" class="btn btn-light border">
            <i class="fa fa-arrow-left me-1"></i> Kembali ke Pakta Integritas
        </Link>
    </div>

    <!-- Modal: Hapus Dokumen -->
    <div v-if="showDeleteModal" class="modal d-block" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h6 class="modal-title fw-bold"><i class="fa fa-trash text-danger me-2"></i>Hapus Dokumen</h6>
                    <button class="btn-close" @click="showDeleteModal = false"></button>
                </div>
                <div class="modal-body">
                    Yakin ingin menghapus dokumen ini? Anda perlu upload ulang jika diperlukan.
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-danger" @click="confirmDelete" :disabled="submitting">
                        <i class="fa fa-trash me-1"></i> Hapus
                    </button>
                    <button class="btn btn-light border" @click="showDeleteModal = false">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Submit Permohonan -->
    <div v-if="showSubmitModal" class="modal d-block" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h6 class="modal-title fw-bold">
                        <i class="fa fa-paper-plane me-2" :class="allComplete ? 'text-success' : 'text-warning'"></i>
                        Konfirmasi Submit Permohonan
                    </h6>
                    <button class="btn-close" @click="showSubmitModal = false"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Pastikan semua persyaratan berikut sudah terpenuhi sebelum submit:</p>

                    <ul class="list-unstyled mb-0">
                        <li v-for="item in checklist" :key="item.label" class="d-flex align-items-start gap-2 mb-2">
                            <span class="mt-1" style="font-size:1rem">
                                <i v-if="item.done" class="fa fa-check-circle text-success"></i>
                                <i v-else class="fa fa-times-circle text-danger"></i>
                            </span>
                            <div>
                                <div class="small fw-semibold" :class="item.done ? 'text-success' : 'text-danger'">
                                    {{ item.label }}
                                </div>
                                <div v-if="!item.done && item.hint" class="text-muted" style="font-size:0.78rem">
                                    {{ item.hint }}
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div v-if="allComplete" class="alert alert-success border-0 mt-3 mb-0 py-2 small">
                        <i class="fa fa-info-circle me-1"></i>
                        Setelah disubmit, data tidak dapat diubah dan permohonan akan menunggu verifikasi admin.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-success" @click="confirmSubmit" :disabled="!allComplete || submitting">
                        <i class="fa fa-paper-plane me-1"></i>
                        {{ submitting ? 'Menyimpan...' : 'Submit Permohonan' }}
                    </button>
                    <button class="btn btn-light border" @click="showSubmitModal = false">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutPeserta from '../../../Layouts/Peserta.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';

export default {
    layout: LayoutPeserta,
    components: { Head, Link },
    props: {
        application: Object,
        requirements: Array,
    },

    setup(props) {
        const submitting       = ref(false);
        const showDeleteModal  = ref(false);
        const showSubmitModal  = ref(false);
        const pendingDeleteId  = ref(null);
        const uploading        = reactive({});

        // --- Checklist kelengkapan ---
        const checklist = computed(() => {
            const requiredMissing = props.requirements
                .filter(r => r.is_required && !r.document)
                .map(r => r.label);

            return [
                {
                    label: 'Tanda tangan formulir FR.APL.01',
                    done:  !!props.application.signature_form_path,
                    hint:  'Kembali ke halaman FR.APL.01 dan simpan tanda tangan.',
                },
                {
                    label: 'Pakta integritas FR.AK.01 ditandatangani',
                    done:  !!props.application.pakta_signed_at,
                    hint:  'Kembali ke halaman FR.AK.01 dan simpan tanda tangan.',
                },
                {
                    label: requiredMissing.length === 0
                        ? 'Semua dokumen wajib sudah diupload'
                        : `Dokumen wajib belum diupload: ${requiredMissing.join(', ')}`,
                    done:  requiredMissing.length === 0,
                    hint:  'Upload semua dokumen yang bertanda *.',
                },
            ];
        });

        const allComplete = computed(() => checklist.value.every(i => i.done));

        // --- Aksi ---
        const uploadFile = (event, requirementId) => {
            const file = event.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('requirement_id', requirementId);
            formData.append('file', file);
            uploading[requirementId] = true;
            router.post(`/peserta/aplikasi/${props.application.id}/dokumen`, formData, {
                forceFormData: true,
                onFinish: () => {
                    delete uploading[requirementId];
                    event.target.value = '';
                },
            });
        };

        const openDeleteModal = (docId) => {
            pendingDeleteId.value = docId;
            showDeleteModal.value = true;
        };

        const confirmDelete = () => {
            showDeleteModal.value = false;
            router.delete(`/peserta/aplikasi/${props.application.id}/dokumen/${pendingDeleteId.value}`);
        };

        const openSubmitModal  = () => { showSubmitModal.value = true; };

        const confirmSubmit = () => {
            submitting.value = true;
            router.post(`/peserta/aplikasi/${props.application.id}/submit`, {}, {
                onFinish: () => { submitting.value = false; showSubmitModal.value = false; },
            });
        };

        return {
            submitting, uploading, showDeleteModal, showSubmitModal, checklist, allComplete,
            uploadFile, openDeleteModal, confirmDelete, openSubmitModal, confirmSubmit,
        };
    },
}
</script>
