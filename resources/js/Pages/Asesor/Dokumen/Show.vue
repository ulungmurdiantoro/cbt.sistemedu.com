<template>
    <Head><title>Verifikasi Dokumen — {{ student.name }}</title></Head>
    <div class="container-fluid mb-5 mt-4">
        <div class="col-lg-10 col-xl-9 mx-auto">

            <Link :href="`/asesor/penilaian/${exam_session.id}/dokumen`" class="btn btn-primary border-0 shadow mb-3">
                <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali ke Daftar
            </Link>

            <div v-if="$page.props.session?.success" class="alert alert-success border-0 mb-3">
                {{ $page.props.session.success }}
            </div>

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
                                <span class="badge bg-light text-dark border">
                                    {{ doneCount }} / {{ totalReq }} terverifikasi
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Belum ada aplikasi -->
            <div v-if="!application" class="alert alert-warning border-0 shadow">
                <i class="fa fa-exclamation-triangle me-2"></i>
                Peserta ini belum mengajukan permohonan sertifikasi untuk sesi ini.
            </div>

            <template v-else>
                <!-- Daftar dokumen -->
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

                        <!-- Belum diupload -->
                        <div v-if="!getDoc(req.id)" class="text-muted small fst-italic">
                            <i class="fa fa-exclamation-circle me-1"></i>Peserta belum mengupload dokumen ini.
                        </div>

                        <template v-else>
                            <!-- File info + download -->
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div>
                                    <i class="fa fa-file me-1 text-primary"></i>
                                    <span class="small fw-semibold">{{ getDoc(req.id).original_filename }}</span>
                                </div>
                                <a :href="`/asesor/penilaian/${exam_session.id}/dokumen/${student.id}/${getDoc(req.id).id}/download`"
                                    class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fa fa-download me-1"></i> Unduh
                                </a>
                            </div>

                            <!-- Catatan reviewer sebelumnya -->
                            <div v-if="getDoc(req.id).reviewer_notes"
                                class="alert py-2 small mb-3"
                                :class="getDoc(req.id).status === 'rejected' ? 'alert-danger' : 'alert-info'">
                                <i class="fa fa-comment me-1"></i>{{ getDoc(req.id).reviewer_notes }}
                            </div>

                            <!-- Form verifikasi -->
                            <div class="border rounded p-3 bg-light">
                                <div class="small fw-semibold mb-2">Keputusan Verifikasi:</div>

                                <div v-if="activeReject === req.id" class="mb-2">
                                    <textarea class="form-control form-control-sm mb-2"
                                        rows="2" v-model="rejectNotes[req.id]"
                                        placeholder="Tulis alasan penolakan..."></textarea>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-danger" :disabled="saving === req.id"
                                            @click="submitVerify(getDoc(req.id).id, req.id, 'rejected')">
                                            <i class="fa fa-times me-1"></i>
                                            {{ saving === req.id ? 'Menyimpan...' : 'Konfirmasi Tolak' }}
                                        </button>
                                        <button class="btn btn-sm btn-light border" @click="activeReject = null">
                                            Batal
                                        </button>
                                    </div>
                                </div>

                                <div v-else class="d-flex gap-2">
                                    <button class="btn btn-sm btn-success" :disabled="saving === req.id"
                                        @click="submitVerify(getDoc(req.id).id, req.id, 'verified')">
                                        <i class="fa fa-check me-1"></i>
                                        {{ saving === req.id ? 'Menyimpan...' : 'Terima' }}
                                    </button>
                                    <button class="btn btn-sm btn-danger" @click="activeReject = req.id">
                                        <i class="fa fa-times me-1"></i> Tolak
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Verifikasi Akhir -->
                <div class="card border-0 shadow mb-3">
                    <div class="card-header bg-gray-800 text-white fw-semibold">
                        <i class="fa fa-signature me-2"></i>Verifikasi Akhir
                    </div>
                    <div class="card-body">

                        <!-- Sudah ditandatangani: terkunci -->
                        <div v-if="application.asesor_verified_at" class="d-flex align-items-center gap-3">
                            <img :src="`/asesor/penilaian/${exam_session.id}/dokumen/${student.id}/tanda-tangan`"
                                alt="TTD Asesor"
                                style="max-height:80px; max-width:220px; object-fit:contain; border:1px solid #ddd; background:#fff; padding:4px">
                            <div>
                                <span class="badge bg-success mb-1"><i class="fa fa-lock me-1"></i>Terverifikasi &amp; Terkunci</span>
                                <div class="small mt-1"><i class="fa fa-user me-1 text-muted"></i>{{ application.asesor_signature_name }}</div>
                                <div class="small text-muted">{{ formatDateTime(application.asesor_verified_at) }}</div>
                            </div>
                        </div>

                        <!-- Belum ditandatangani: form TTD -->
                        <div v-else>
                            <p class="text-muted small mb-3">
                                Verifikasi akhir menandakan seluruh dokumen peserta ini telah Anda periksa.
                                Setelah ditandatangani, tidak dapat diubah lagi.
                            </p>

                            <div class="mb-2">
                                <label class="fw-semibold small">Nama Penandatangan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm mt-1" v-model="signName"
                                    placeholder="contoh: Budi Santoso, S.T.">
                            </div>

                            <div v-if="auth_asesor?.signature_path" class="mb-2">
                                <label class="fw-semibold small">Tanda Tangan Asesor</label>
                                <div class="p-2 border rounded bg-white mt-1 d-flex align-items-center gap-3">
                                    <img src="/asesor/profile/tanda-tangan" alt="TTD Tersimpan"
                                        style="max-height:60px; max-width:160px; object-fit:contain">
                                    <div class="flex-fill">
                                        <span class="badge bg-success small"><i class="fa fa-check me-1"></i>TTD Tersimpan</span>
                                        <div class="small text-muted mt-1">Akan digunakan otomatis.</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="useSavedSig = false">
                                        <i class="fa fa-pen me-1"></i>Ganti
                                    </button>
                                </div>
                            </div>

                            <div v-if="!auth_asesor?.signature_path || !useSavedSig" class="mb-2">
                                <label class="fw-semibold small">
                                    {{ auth_asesor?.signature_path ? 'TTD Baru (akan mengganti yang tersimpan)' : 'Tanda Tangan Asesor' }}
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex gap-1 mt-1 mb-2">
                                    <button type="button" class="btn btn-sm flex-fill"
                                        :class="sigMode === 'draw' ? 'btn-gray-800' : 'btn-light border'"
                                        @click="switchSigMode('draw')">
                                        <i class="fa fa-pen me-1"></i>Gambar
                                    </button>
                                    <button type="button" class="btn btn-sm flex-fill"
                                        :class="sigMode === 'upload' ? 'btn-gray-800' : 'btn-light border'"
                                        @click="switchSigMode('upload')">
                                        <i class="fa fa-upload me-1"></i>Upload
                                    </button>
                                    <button v-if="auth_asesor?.signature_path" type="button"
                                        class="btn btn-sm btn-light border" @click="useSavedSig = true">
                                        Batal
                                    </button>
                                </div>

                                <div v-show="sigMode === 'draw'">
                                    <div class="border rounded bg-white" style="touch-action:none">
                                        <canvas ref="sigCanvas" style="display:block; width:100%; height:140px; cursor:crosshair"></canvas>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light border mt-1" @click="clearSig">
                                        <i class="fa fa-eraser me-1"></i>Hapus
                                    </button>
                                </div>

                                <div v-show="sigMode === 'upload'">
                                    <input type="file" class="form-control form-control-sm"
                                        accept="image/png,image/jpeg,image/jpg" @change="onSigFileChange">
                                    <div v-if="sigFilePreview" class="mt-2">
                                        <img :src="sigFilePreview"
                                            style="max-height:80px; border:1px solid #ddd; background:#fff; padding:4px">
                                    </div>
                                </div>
                            </div>

                            <div v-if="!showFinalConfirm" class="d-grid mt-3">
                                <button class="btn btn-success" @click="showFinalConfirm = true">
                                    <i class="fa fa-signature me-1"></i>Simpan Verifikasi Akhir
                                </button>
                            </div>
                            <div v-else class="alert alert-warning mt-3 mb-0">
                                <div class="small mb-2">
                                    <i class="fa fa-exclamation-triangle me-1"></i>
                                    Tindakan ini <strong>tidak bisa dibatalkan</strong>. Pastikan tanda tangan sudah benar.
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-success" :disabled="finalSaving" @click="submitFinalVerify">
                                        {{ finalSaving ? 'Menyimpan...' : 'Ya, Tandatangani' }}
                                    </button>
                                    <button class="btn btn-sm btn-light border" @click="showFinalConfirm = false">Batal</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, reactive, nextTick, onMounted, onUnmounted } from 'vue';
import SignaturePad from 'signature_pad';

export default {
    layout: LayoutAsesor,
    components: { Head, Link },
    props: {
        exam_session:  Object,
        student:       Object,
        application:   Object,
        auth_asesor:   Object,
    },

    setup(props) {
        const saving      = ref(null);
        const activeReject = ref(null);
        const rejectNotes = reactive({});

        // Verifikasi akhir — TTD asesor
        const sigMode         = ref('draw');
        const sigCanvas       = ref(null);
        const sigFile         = ref(null);
        const sigFilePreview  = ref(null);
        const signName        = ref(props.auth_asesor?.signature_name ?? '');
        const useSavedSig     = ref(!!props.auth_asesor?.signature_path);
        const showFinalConfirm = ref(false);
        const finalSaving     = ref(false);
        let   sigPad          = null;
        let   resizeTimer     = null;

        const initSigPad = () => {
            if (!sigCanvas.value) return;
            const canvas    = sigCanvas.value;
            const container = canvas.parentElement;
            const ratio     = Math.max(window.devicePixelRatio || 1, 1);
            const savedData = sigPad?.toData() ?? [];
            canvas.width    = (container?.clientWidth || canvas.offsetWidth) * ratio;
            canvas.height   = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            sigPad          = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
            if (savedData.length) sigPad.fromData(savedData);
        };

        const handleResize = () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(initSigPad, 200);
        };

        onMounted(async () => {
            if (props.application && !props.application.asesor_verified_at) {
                await nextTick();
                initSigPad();
                window.addEventListener('resize', handleResize);
            }
        });

        onUnmounted(() => {
            window.removeEventListener('resize', handleResize);
            clearTimeout(resizeTimer);
        });

        const switchSigMode = async (mode) => {
            sigMode.value = mode;
            if (mode === 'draw') { await nextTick(); initSigPad(); }
        };

        const clearSig = () => sigPad?.clear();

        const onSigFileChange = (e) => {
            const file = e.target.files[0];
            if (!file) return;
            sigFile.value        = file;
            sigFilePreview.value = URL.createObjectURL(file);
        };

        const formatDateTime = (dt) => dt
            ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
            : '—';

        const submitFinalVerify = () => {
            if (!signName.value) {
                alert('Nama penandatangan wajib diisi.');
                return;
            }

            const fd = new FormData();
            fd.append('signature_name', signName.value);

            if (!useSavedSig.value) {
                if (sigMode.value === 'draw') {
                    if (!sigPad || sigPad.isEmpty()) {
                        alert('Tanda tangan wajib diisi atau gunakan TTD tersimpan.');
                        return;
                    }
                    fd.append('signature_data', sigPad.toDataURL('image/png'));
                } else {
                    if (!sigFile.value) {
                        alert('Pilih file tanda tangan terlebih dahulu.');
                        return;
                    }
                    fd.append('signature_file', sigFile.value);
                }
            }

            finalSaving.value = true;
            router.post(
                `/asesor/penilaian/${props.exam_session.id}/dokumen/${props.student.id}/verifikasi-akhir`,
                fd,
                {
                    forceFormData:  true,
                    preserveScroll: true,
                    onSuccess: () => { showFinalConfirm.value = false; },
                    onFinish:  () => { finalSaving.value = false; },
                }
            );
        };

        const totalReq = computed(() =>
            props.application?.classroom?.document_requirements?.length ?? 0
        );

        const doneCount = computed(() =>
            props.application?.documents?.filter(d => d.status === 'verified').length ?? 0
        );

        const getDoc = (reqId) =>
            props.application?.documents?.find(d => d.classroom_document_requirement_id === reqId) ?? null;

        const statusLabel = (s) => ({
            draft: 'Draft', submitted: 'Disubmit',
            approved: 'Disetujui', rejected: 'Ditolak',
        }[s] ?? s);

        const docStatusClass = (doc) => {
            if (!doc) return 'bg-light';
            return { pending: 'bg-light', verified: 'bg-success bg-opacity-10', rejected: 'bg-danger bg-opacity-10' }[doc.status] ?? 'bg-light';
        };

        const badgeClass = (doc) => {
            if (!doc) return 'bg-secondary';
            return { pending: 'bg-warning text-dark', verified: 'bg-success', rejected: 'bg-danger' }[doc.status] ?? 'bg-secondary';
        };

        const badgeLabel = (doc) => {
            if (!doc) return 'Belum Upload';
            return { pending: 'Menunggu', verified: 'Terverifikasi', rejected: 'Ditolak' }[doc.status] ?? doc.status;
        };

        const submitVerify = (docId, reqId, status) => {
            saving.value = reqId;
            router.post(
                `/asesor/penilaian/${props.exam_session.id}/dokumen/${props.student.id}/${docId}/verify`,
                { status, reviewer_notes: rejectNotes[reqId] ?? null },
                {
                    preserveState:  true,
                    preserveScroll: true,
                    onSuccess: () => {
                        activeReject.value = null;
                        rejectNotes[reqId] = '';
                    },
                    onFinish: () => { saving.value = null; },
                }
            );
        };

        return {
            saving, activeReject, rejectNotes,
            totalReq, doneCount, getDoc,
            statusLabel, docStatusClass, badgeClass, badgeLabel,
            submitVerify,
            sigMode, sigCanvas, sigFile, sigFilePreview, signName, useSavedSig,
            showFinalConfirm, finalSaving,
            switchSigMode, clearSig, onSigFileChange, formatDateTime, submitFinalVerify,
        };
    },
}
</script>
