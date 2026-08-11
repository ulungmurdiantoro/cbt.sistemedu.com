<template>
    <Head><title>Verifikasi Dokumen — {{ student.name }}</title></Head>
    <div class="container-fluid mb-5 mt-4">
        <div class="col-lg-10 col-xl-9 mx-auto">

            <Link :href="`/admin/penilaian/${exam_session.id}/dokumen`" class="btn btn-primary border-0 shadow mb-3">
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
                                <a :href="`/admin/penilaian/${exam_session.id}/dokumen/${student.id}/${getDoc(req.id).id}/download`"
                                    class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fa fa-download me-1"></i> Unduh
                                </a>
                                <span class="badge bg-light text-dark border small">
                                    Kelengkapan (Admin): {{ badgeLabel({ asesor_status: getDoc(req.id).status }) }}
                                </span>
                            </div>

                            <!-- Catatan asesor sebelumnya -->
                            <div v-if="getDoc(req.id).asesor_reviewer_notes"
                                class="alert py-2 small mb-3"
                                :class="getDoc(req.id).asesor_status === 'rejected' ? 'alert-danger' : 'alert-info'">
                                <i class="fa fa-comment me-1"></i>{{ getDoc(req.id).asesor_reviewer_notes }}
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
                            <img :src="`/admin/applications/${application.id}/tanda-tangan/asesor`"
                                alt="TTD Asesor"
                                style="max-height:80px; max-width:220px; object-fit:contain; border:1px solid #ddd; background:#fff; padding:4px">
                            <div>
                                <span class="badge bg-success mb-1"><i class="fa fa-lock me-1"></i>Terverifikasi &amp; Terkunci</span>
                                <div class="small mt-1"><i class="fa fa-user me-1 text-muted"></i>{{ application.asesor_signature_name }}</div>
                                <div class="small text-muted">{{ formatDateTime(application.asesor_verified_at) }}</div>
                            </div>
                        </div>

                        <!-- Peserta belum punya penugasan asesor -->
                        <div v-else-if="!assigned_asesor" class="alert alert-warning border-0 mb-0">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            Peserta ini belum memiliki penugasan asesor.
                            <Link :href="`/admin/penilaian/${exam_session.id}`" class="alert-link">Atur di menu Penugasan Asesor</Link>.
                        </div>

                        <!-- Asesor yang ditugaskan belum punya TTD tersimpan: input langsung di sini -->
                        <div v-else-if="!assigned_asesor.signature_path">
                            <div class="alert alert-warning border-0 mb-3">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                Asesor yang ditugaskan (<strong>{{ assigned_asesor.name }}</strong>) belum memiliki TTD tersimpan.
                                Input TTD-nya di bawah ini untuk menyimpan sebagai TTD default asesor tersebut.
                            </div>

                            <div class="d-flex gap-1 mb-2">
                                <button type="button" class="btn btn-sm flex-fill"
                                    :class="asesorSigMode === 'draw' ? 'btn-gray-800' : 'btn-light border'"
                                    @click="switchAsesorSigMode('draw')">
                                    <i class="fa fa-pen me-1"></i>Gambar
                                </button>
                                <button type="button" class="btn btn-sm flex-fill"
                                    :class="asesorSigMode === 'upload' ? 'btn-gray-800' : 'btn-light border'"
                                    @click="switchAsesorSigMode('upload')">
                                    <i class="fa fa-upload me-1"></i>Upload
                                </button>
                            </div>

                            <div v-show="asesorSigMode === 'draw'">
                                <div class="border rounded bg-white" style="touch-action:none">
                                    <canvas ref="asesorSigCanvas" style="display:block; width:100%; height:140px; cursor:crosshair"></canvas>
                                </div>
                                <button type="button" class="btn btn-sm btn-light border mt-1" @click="clearAsesorSig">
                                    <i class="fa fa-eraser me-1"></i>Hapus
                                </button>
                            </div>

                            <div v-show="asesorSigMode === 'upload'">
                                <input type="file" class="form-control form-control-sm"
                                    accept="image/png,image/jpeg,image/jpg" @change="onAsesorSigFileChange">
                                <div v-if="asesorSigFilePreview" class="mt-2">
                                    <img :src="asesorSigFilePreview"
                                        style="max-height:80px; border:1px solid #ddd; background:#fff; padding:4px">
                                </div>
                            </div>

                            <div class="d-grid mt-3">
                                <button class="btn btn-primary" :disabled="asesorSigSaving" @click="submitAsesorSignature">
                                    <i class="fa fa-save me-1"></i>{{ asesorSigSaving ? 'Menyimpan...' : 'Simpan TTD Asesor' }}
                                </button>
                            </div>
                        </div>

                        <!-- Siap ditandatangani atas nama asesor yang ditugaskan -->
                        <div v-else>
                            <p class="text-muted small mb-3">
                                Verifikasi akhir menandakan seluruh dokumen peserta ini telah diperiksa.
                                Tanda tangan akan dibubuhkan atas nama asesor yang ditugaskan — bukan admin.
                                Setelah ditandatangani, tidak dapat diubah lagi.
                            </p>

                            <div class="p-2 border rounded bg-white mb-3 d-flex align-items-center gap-3">
                                <img :src="`/admin/penilaian/${exam_session.id}/dokumen/${student.id}/tanda-tangan-asesor`"
                                    alt="TTD Asesor Ditugaskan"
                                    style="max-height:60px; max-width:160px; object-fit:contain">
                                <div class="flex-fill">
                                    <span class="badge bg-success small"><i class="fa fa-user-check me-1"></i>{{ assigned_asesor.name }}</span>
                                    <div class="small text-muted mt-1">TTD tersimpan asesor ini akan digunakan.</div>
                                </div>
                            </div>

                            <div v-if="!showFinalConfirm" class="d-grid mt-3">
                                <button class="btn btn-success" @click="showFinalConfirm = true">
                                    <i class="fa fa-signature me-1"></i>Tandatangani sebagai {{ assigned_asesor.name }}
                                </button>
                            </div>
                            <div v-else class="alert alert-warning mt-3 mb-0">
                                <div class="small mb-2">
                                    <i class="fa fa-exclamation-triangle me-1"></i>
                                    Tindakan ini <strong>tidak bisa dibatalkan</strong>. Pastikan seluruh dokumen sudah diperiksa dengan benar.
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
import LayoutAdmin from '../../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, reactive, nextTick, onMounted, onUnmounted } from 'vue';
import SignaturePad from 'signature_pad';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        exam_session:     Object,
        student:          Object,
        application:      Object,
        assigned_asesor:  Object,
    },

    setup(props) {
        const saving       = ref(null);
        const activeReject = ref(null);
        const rejectNotes  = reactive({});

        const showFinalConfirm = ref(false);
        const finalSaving      = ref(false);

        // Input TTD asesor langsung dari halaman ini (dipakai kalau asesor yang
        // ditugaskan belum punya TTD tersimpan) — simpan lewat endpoint yang sama
        // dengan Kelola User supaya jadi TTD default asesor tersebut juga.
        const asesorSigMode        = ref('draw');
        const asesorSigCanvas      = ref(null);
        const asesorSigFile        = ref(null);
        const asesorSigFilePreview = ref(null);
        const asesorSigSaving      = ref(false);
        let   asesorSigPad         = null;
        let   asesorResizeTimer    = null;

        const needsAsesorSigInput = computed(() =>
            props.application && !props.application.asesor_verified_at &&
            props.assigned_asesor && !props.assigned_asesor.signature_path
        );

        const initAsesorSigPad = () => {
            if (!asesorSigCanvas.value) return;
            const canvas    = asesorSigCanvas.value;
            const container = canvas.parentElement;
            const ratio     = Math.max(window.devicePixelRatio || 1, 1);
            const savedData = asesorSigPad?.toData() ?? [];
            canvas.width    = (container?.clientWidth || canvas.offsetWidth) * ratio;
            canvas.height   = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            asesorSigPad    = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
            if (savedData.length) asesorSigPad.fromData(savedData);
        };

        const handleAsesorResize = () => {
            clearTimeout(asesorResizeTimer);
            asesorResizeTimer = setTimeout(initAsesorSigPad, 200);
        };

        onMounted(async () => {
            if (needsAsesorSigInput.value) {
                await nextTick();
                initAsesorSigPad();
                window.addEventListener('resize', handleAsesorResize);
            }
        });

        onUnmounted(() => {
            window.removeEventListener('resize', handleAsesorResize);
            clearTimeout(asesorResizeTimer);
        });

        const switchAsesorSigMode = async (mode) => {
            asesorSigMode.value = mode;
            if (mode === 'draw') { await nextTick(); initAsesorSigPad(); }
        };

        const clearAsesorSig = () => asesorSigPad?.clear();

        const onAsesorSigFileChange = (e) => {
            const file = e.target.files[0];
            if (!file) return;
            asesorSigFile.value        = file;
            asesorSigFilePreview.value = URL.createObjectURL(file);
        };

        const submitAsesorSignature = () => {
            const fd = new FormData();

            if (asesorSigMode.value === 'draw') {
                if (!asesorSigPad || asesorSigPad.isEmpty()) {
                    alert('Tanda tangan wajib digambar atau upload file terlebih dahulu.');
                    return;
                }
                fd.append('signature_data', asesorSigPad.toDataURL('image/png'));
            } else {
                if (!asesorSigFile.value) {
                    alert('Pilih file tanda tangan terlebih dahulu.');
                    return;
                }
                fd.append('signature_file', asesorSigFile.value);
            }

            asesorSigSaving.value = true;
            router.post(
                `/admin/users/${props.assigned_asesor.id}/tanda-tangan`,
                fd,
                {
                    forceFormData:  true,
                    preserveScroll: true,
                    onFinish: () => { asesorSigSaving.value = false; },
                }
            );
        };

        const formatDateTime = (dt) => dt
            ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
            : '—';

        const submitFinalVerify = () => {
            finalSaving.value = true;
            router.post(
                `/admin/penilaian/${props.exam_session.id}/dokumen/${props.student.id}/verifikasi-akhir`,
                {},
                {
                    preserveScroll: true,
                    onFinish: () => { finalSaving.value = false; },
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
            return { pending: 'bg-light', verified: 'bg-success bg-opacity-10', rejected: 'bg-danger bg-opacity-10' }[doc.asesor_status] ?? 'bg-light';
        };

        const badgeClass = (doc) => {
            if (!doc) return 'bg-secondary';
            return { pending: 'bg-warning text-dark', verified: 'bg-success', rejected: 'bg-danger' }[doc.asesor_status] ?? 'bg-secondary';
        };

        const badgeLabel = (doc) => {
            if (!doc) return 'Belum Upload';
            return { pending: 'Menunggu', verified: 'Terverifikasi', rejected: 'Ditolak' }[doc.asesor_status] ?? doc.asesor_status;
        };

        const submitVerify = (docId, reqId, status) => {
            saving.value = reqId;
            router.post(
                `/admin/penilaian/${props.exam_session.id}/dokumen/${props.student.id}/${docId}/verify`,
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
            showFinalConfirm, finalSaving,
            formatDateTime, submitFinalVerify,
            asesorSigMode, asesorSigCanvas, asesorSigFile, asesorSigFilePreview, asesorSigSaving,
            switchAsesorSigMode, clearAsesorSig, onAsesorSigFileChange, submitAsesorSignature,
        };
    },
}
</script>
