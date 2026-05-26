<template>
    <Head>
        <title>Detail Permohonan - {{ application.participant?.name }}</title>
    </Head>

    <div class="row mb-3">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0">Detail Permohonan</h5>
                <p class="text-muted small mb-0">Kode: {{ application.code }}</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span :class="statusBadge(application.status)" class="badge fs-6">{{ statusLabel(application.status) }}</span>
                <Link href="/admin/applications" class="btn btn-sm btn-light border">
                    <i class="fa fa-arrow-left me-1"></i> Kembali
                </Link>
            </div>
        </div>
    </div>

    <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
        {{ $page.props.session.success }}
    </div>
    <div v-if="$page.props.session.error" class="alert alert-danger border-0 shadow mb-3">
        {{ $page.props.session.error }}
    </div>

    <div class="row">
        <!-- Kolom kiri: data peserta & sertifikasi -->
        <div class="col-md-8">

            <!-- Info sertifikasi -->
            <div class="card border-0 shadow mb-4">
                <div class="card-header bg-gray-800 text-white fw-semibold">Data Sertifikasi</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted" style="width:200px">Skema</td><td>{{ application.classroom?.title }}</td></tr>
                        <tr><td class="text-muted">Sesi Ujian</td><td>{{ application.exam_session?.title }}</td></tr>
                        <tr><td class="text-muted">Konteks Asesmen</td><td>{{ application.konteks_asesmen }}</td></tr>
                        <tr><td class="text-muted">Tempat Ujian</td><td>{{ application.tempat_ujian }}</td></tr>
                        <tr><td class="text-muted">Kode Batch</td><td>{{ application.kode_batch }}</td></tr>
                        <tr><td class="text-muted">Tujuan Asesmen</td><td>{{ application.tujuan_asesmen }}</td></tr>
                    </table>
                </div>
            </div>

            <!-- Data pribadi (dari snapshot atau participant) -->
            <div class="card border-0 shadow mb-4">
                <div class="card-header bg-gray-800 text-white fw-semibold">Data Pribadi (FR.APL.01 Bag. 1a)</div>
                <div class="card-body">
                    <table class="table table-sm mb-0" style="table-layout:fixed;width:100%">
                        <colgroup><col style="width:200px"><col></colgroup>
                        <tr v-for="(val, key) in pribadi" :key="key">
                            <td class="text-muted">{{ fieldLabel(key) }}</td>
                            <td class="text-break">{{ fieldValue(key, val) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Data pekerjaan -->
            <div class="card border-0 shadow mb-4">
                <div class="card-header bg-gray-800 text-white fw-semibold">Data Pekerjaan (FR.APL.01 Bag. 1b)</div>
                <div class="card-body">
                    <table class="table table-sm mb-0" style="table-layout:fixed;width:100%">
                        <colgroup><col style="width:200px"><col></colgroup>
                        <tr v-for="(val, key) in pekerjaan" :key="key">
                            <td class="text-muted">{{ fieldLabel(key) }}</td>
                            <td class="text-break">{{ fieldValue(key, val) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Tanda Tangan -->
            <div class="card border-0 shadow mb-4" v-if="application.signature_path">
                <div class="card-header bg-gray-800 text-white fw-semibold">Tanda Tangan Pemohon</div>
                <div class="card-body">
                    <a :href="`/admin/applications/${application.id}/tanda-tangan/pakta`" target="_blank">
                        <img :src="`/admin/applications/${application.id}/tanda-tangan/pakta`" alt="TTD"
                            style="max-height:120px; max-width:320px; border:1px solid #ddd; background:#fff; padding:6px">
                    </a>
                </div>
            </div>

            <!-- Dokumen -->
            <div class="card border-0 shadow mb-4">
                <div class="card-header bg-gray-800 text-white fw-semibold">Bukti Kelengkapan (Bag. 3)</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="bg-gray-100">
                            <tr>
                                <th>Dokumen</th>
                                <th style="width:120px">Status</th>
                                <th style="width:80px">File</th>
                                <th style="width:200px">Aksi Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="req in application.classroom?.document_requirements" :key="req.id">
                                <td class="align-middle">
                                    <div class="fw-semibold small">{{ req.label }} <span v-if="req.is_required" class="text-danger">*</span></div>
                                    <div v-if="getDoc(req.id)?.reviewer_notes" class="text-danger small">{{ getDoc(req.id).reviewer_notes }}</div>
                                </td>
                                <td class="align-middle">
                                    <span v-if="!getDoc(req.id)" class="badge bg-secondary">Belum Upload</span>
                                    <span v-else :class="docBadge(getDoc(req.id).status)" class="badge">{{ docLabel(getDoc(req.id).status) }}</span>
                                </td>
                                <td class="align-middle">
                                    <a v-if="getDoc(req.id)" :href="`/admin/applications/${application.id}/documents/${getDoc(req.id).id}/download`" target="_blank" class="btn btn-sm btn-light border">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                                <td class="align-middle">
                                    <div v-if="getDoc(req.id) && application.status === 'submitted'" class="d-flex gap-1">
                                        <button class="btn btn-xs btn-success btn-sm"
                                            @click="verifyDoc(getDoc(req.id).id, 'verified', '')">✓ OK</button>
                                        <button class="btn btn-xs btn-danger btn-sm"
                                            @click="openRejectDoc(getDoc(req.id).id)">✗ Tolak</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom kanan: aksi -->
        <div class="col-md-4">

            <!-- Status & aksi -->
            <div class="card border-0 shadow mb-4">
                <div class="card-header bg-gray-800 text-white fw-semibold">Aksi</div>
                <div class="card-body">

                    <!-- Akun ujian (jika sudah approved) -->
                    <div v-if="application.student" class="alert alert-success p-3 mb-3">
                        <div class="fw-bold small mb-1">Akun Ujian</div>
                        <div class="fs-5 fw-bold">{{ application.student.no_participant }}</div>
                        <small class="text-muted">Gunakan kode ini untuk login ujian di halaman utama.</small>
                    </div>

                    <!-- Approve: butuh TTD + nama -->
                    <div v-if="application.status === 'submitted'" class="mb-3">
                        <div class="mb-2">
                            <label class="fw-semibold small">Nama Penandatangan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm mt-1" v-model="adminSignName"
                                placeholder="contoh: Dr. Agung Yulianto, M.Si.">
                        </div>

                        <div class="mb-2">
                            <label class="fw-semibold small">Tanda Tangan Admin <span class="text-danger">*</span></label>
                            <div class="d-flex gap-1 mt-1 mb-2">
                                <button type="button" class="btn btn-sm flex-fill"
                                    :class="adminSigMode === 'draw' ? 'btn-gray-800' : 'btn-light border'"
                                    @click="switchAdminSigMode('draw')">
                                    <i class="fa fa-pen me-1"></i>Gambar
                                </button>
                                <button type="button" class="btn btn-sm flex-fill"
                                    :class="adminSigMode === 'upload' ? 'btn-gray-800' : 'btn-light border'"
                                    @click="switchAdminSigMode('upload')">
                                    <i class="fa fa-upload me-1"></i>Upload
                                </button>
                            </div>

                            <div v-show="adminSigMode === 'draw'">
                                <div class="border rounded bg-white" style="touch-action:none">
                                    <canvas ref="adminSigCanvas" style="display:block; width:100%; height:140px; cursor:crosshair"></canvas>
                                </div>
                                <button type="button" class="btn btn-sm btn-light border mt-1" @click="clearAdminSig">
                                    <i class="fa fa-eraser me-1"></i>Hapus
                                </button>
                            </div>

                            <div v-show="adminSigMode === 'upload'">
                                <input type="file" class="form-control form-control-sm"
                                    accept="image/png,image/jpeg,image/jpg"
                                    @change="onAdminSigFileChange">
                                <div v-if="adminSigFilePreview" class="mt-2">
                                    <img :src="adminSigFilePreview" style="max-height:80px; border:1px solid #ddd; background:#fff; padding:4px">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-success" @click="approve" :disabled="processing">
                                <i class="fa fa-check me-1"></i>
                                {{ processing ? 'Memproses...' : 'Setujui & Buat Akun Ujian' }}
                            </button>
                            <button class="btn btn-danger" @click="showRejectForm = !showRejectForm">
                                <i class="fa fa-times me-1"></i> Tolak Permohonan
                            </button>
                            <div v-if="showRejectForm" class="mt-2">
                                <textarea class="form-control mb-2" rows="3" v-model="rejectNotes"
                                    placeholder="Alasan penolakan (wajib diisi)"></textarea>
                                <button class="btn btn-danger btn-sm w-100" @click="reject" :disabled="processing">
                                    Konfirmasi Tolak
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tampilkan TTD admin setelah approved -->
                    <div v-if="application.status === 'approved' && application.admin_signature_path"
                        class="border rounded p-2 mb-3 bg-white">
                        <div class="small fw-semibold mb-1 text-muted">TTD Admin</div>
                        <img :src="`/admin/applications/${application.id}/tanda-tangan/admin`" alt="TTD Admin"
                            style="max-height:80px; max-width:100%; object-fit:contain">
                        <div v-if="application.admin_signature_name" class="small mt-1">
                            <i class="fa fa-user me-1 text-muted"></i>{{ application.admin_signature_name }}
                        </div>
                    </div>

                    <!-- Re-issue akun ujian -->
                    <div v-if="application.status === 'approved'" class="d-grid gap-2 mb-3">
                        <button class="btn btn-warning" @click="showReissueModal = true">
                            <i class="fa fa-refresh me-1"></i> Re-issue Akun Ujian
                        </button>
                    </div>

                    <!-- Info ditolak -->
                    <div v-if="application.status === 'rejected' && application.admin_notes" class="alert alert-danger p-2 small">
                        <strong>Alasan penolakan:</strong> {{ application.admin_notes }}
                    </div>

                    <!-- Info disetujui -->
                    <div v-if="application.status === 'approved'" class="text-muted small">
                        Disetujui oleh {{ application.approver?.name }} pada {{ formatDate(application.approved_at) }}
                    </div>
                </div>
            </div>

            <!-- Riwayat reissue -->
            <div class="card border-0 shadow mb-4" v-if="application.reissue_logs?.length">
                <div class="card-header bg-gray-800 text-white fw-semibold">Riwayat Re-issue</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tr v-for="log in application.reissue_logs" :key="log.id">
                            <td class="align-middle small">
                                <div>{{ log.new_student?.no_participant }}</div>
                                <div class="text-muted" style="font-size:0.75rem">{{ log.reason || '—' }}</div>
                                <div class="text-muted" style="font-size:0.75rem">{{ log.reissued_by?.name }} · {{ formatDate(log.created_at) }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal re-issue akun ujian -->
    <div v-if="showReissueModal" class="modal d-block" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h6 class="modal-title fw-bold">
                        <i class="fa fa-refresh text-warning me-2"></i>Re-issue Akun Ujian
                    </h6>
                    <button class="btn-close" @click="showReissueModal = false"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning border-0 py-2 small mb-3">
                        <i class="fa fa-exclamation-triangle me-1"></i>
                        Akun ujian lama (<strong>{{ application.student?.no_participant }}</strong>) akan <strong>dinonaktifkan</strong> dan diganti dengan akun baru.
                    </div>
                    <label class="fw-semibold small">Alasan re-issue <span class="text-muted">(opsional)</span></label>
                    <textarea class="form-control mt-1" rows="2" v-model="reissueReason"
                        placeholder="mis. akun hilang, salah data, dsb."></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-warning" @click="reissue" :disabled="processing">
                        <i class="fa fa-refresh me-1"></i>
                        {{ processing ? 'Memproses...' : 'Konfirmasi Re-issue' }}
                    </button>
                    <button class="btn btn-light border" @click="showReissueModal = false">Batal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal tolak dokumen -->
    <div v-if="rejectDocId" class="modal d-block" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Tolak Dokumen</h6>
                    <button class="btn-close" @click="rejectDocId = null"></button>
                </div>
                <div class="modal-body">
                    <label class="fw-semibold small">Alasan penolakan</label>
                    <textarea class="form-control" rows="3" v-model="rejectDocNotes"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger btn-sm" @click="verifyDoc(rejectDocId, 'rejected', rejectDocNotes)">Konfirmasi Tolak</button>
                    <button class="btn btn-light btn-sm border" @click="rejectDocId = null">Batal</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, nextTick, onMounted, onUnmounted } from 'vue';
import SignaturePad from 'signature_pad';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: { application: Object },

    setup(props) {
        const processing       = ref(false);
        const showRejectForm   = ref(false);
        const showReissueModal = ref(false);
        const rejectNotes      = ref('');
        const reissueReason    = ref('');
        const rejectDocId     = ref(null);
        const rejectDocNotes  = ref('');

        // Admin TTD state
        const adminSigMode        = ref('draw');
        const adminSigCanvas      = ref(null);
        const adminSigFile        = ref(null);
        const adminSigFilePreview = ref(null);
        const adminSignName       = ref('');
        let   adminSigPad         = null;
        let   resizeTimer         = null;

        const initAdminPad = () => {
            if (!adminSigCanvas.value) return;
            const canvas    = adminSigCanvas.value;
            const container = canvas.parentElement;
            const ratio     = Math.max(window.devicePixelRatio || 1, 1);
            const savedData = adminSigPad?.toData() ?? [];
            canvas.width    = (container?.clientWidth || canvas.offsetWidth) * ratio;
            canvas.height   = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            adminSigPad     = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
            if (savedData.length) adminSigPad.fromData(savedData);
        };

        const handleResize = () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(initAdminPad, 200);
        };

        onMounted(async () => {
            if (props.application.status === 'submitted') {
                await nextTick();
                initAdminPad();
                window.addEventListener('resize', handleResize);
            }
        });

        onUnmounted(() => {
            window.removeEventListener('resize', handleResize);
            clearTimeout(resizeTimer);
        });

        const switchAdminSigMode = async (mode) => {
            adminSigMode.value = mode;
            if (mode === 'draw') { await nextTick(); initAdminPad(); }
        };

        const clearAdminSig = () => adminSigPad?.clear();

        const onAdminSigFileChange = (e) => {
            const file = e.target.files[0];
            if (!file) return;
            adminSigFile.value        = file;
            adminSigFilePreview.value = URL.createObjectURL(file);
        };

        const pribadi  = computed(() => props.application.snapshot_pribadi  ?? buildPribadi());
        const pekerjaan = computed(() => props.application.snapshot_pekerjaan ?? buildPekerjaan());

        function buildPribadi() {
            const p = props.application.participant ?? {};
            return { name: p.name, nik: p.nik, tempat_lahir: p.tempat_lahir, tanggal_lahir: p.tanggal_lahir,
                jenis_kelamin: p.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan', kebangsaan: p.kebangsaan,
                alamat_rumah: p.alamat_rumah, hp: p.hp, email: p.email, kualifikasi_pendidikan: p.kualifikasi_pendidikan };
        }
        function buildPekerjaan() {
            const p = props.application.participant ?? {};
            return { institusi: p.institusi, jabatan: p.jabatan, alamat_kantor: p.alamat_kantor,
                telp_kantor: p.telp_kantor, email_kantor: p.email_kantor };
        }

        const fieldLabels = {
            name: 'Nama Lengkap', nik: 'NIK / No. KTP', tempat_lahir: 'Tempat Lahir',
            tanggal_lahir: 'Tanggal Lahir', jenis_kelamin: 'Jenis Kelamin', kebangsaan: 'Kebangsaan',
            alamat_rumah: 'Alamat Rumah', kode_pos_rumah: 'Kode Pos', telp_rumah: 'Telp. Rumah',
            hp: 'HP / WA', email: 'Email', email_alt: 'Email Alt.',
            kualifikasi_pendidikan: 'Kualifikasi Pendidikan', institusi: 'Institusi / Perusahaan',
            jabatan: 'Jabatan', alamat_kantor: 'Alamat Kantor', kode_pos_kantor: 'Kode Pos Kantor',
            telp_kantor: 'Telp Kantor', fax_kantor: 'Fax', email_kantor: 'Email Kantor',
        };
        const fieldLabel = (key) => fieldLabels[key] ?? key;
        const fieldValue = (key, val) => {
            if (!val) return '—';
            if (key === 'jenis_kelamin') return val === 'L' ? 'Laki-laki' : val === 'P' ? 'Perempuan' : val;
            if (key === 'tanggal_lahir') return String(val).substring(0, 10);
            return val;
        };

        const getDoc      = (reqId) => props.application.documents?.find(d => d.classroom_document_requirement_id === reqId);
        const docLabel    = (s) => ({ pending: 'Menunggu', verified: 'Terverifikasi', rejected: 'Ditolak' }[s] ?? s);
        const docBadge    = (s) => ({ pending: 'bg-warning text-dark', verified: 'bg-success', rejected: 'bg-danger' }[s] ?? 'bg-secondary');
        const statusLabel = (s) => ({ draft:'Draft', submitted:'Disubmit', approved:'Disetujui', rejected:'Ditolak' }[s] ?? s);
        const statusBadge = (s) => ({ draft:'bg-secondary', submitted:'bg-warning text-dark', approved:'bg-success', rejected:'bg-danger' }[s]);
        const formatDate  = (dt) => dt ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';

        const approve = () => {
            if (!adminSignName.value) {
                alert('Nama penandatangan wajib diisi.');
                return;
            }

            const fd = new FormData();
            fd.append('admin_signature_name', adminSignName.value);

            if (adminSigMode.value === 'draw') {
                if (!adminSigPad || adminSigPad.isEmpty()) {
                    alert('Tanda tangan wajib diisi.');
                    return;
                }
                fd.append('admin_signature_data', adminSigPad.toDataURL('image/png'));
            } else {
                if (!adminSigFile.value) {
                    alert('Pilih file tanda tangan terlebih dahulu.');
                    return;
                }
                fd.append('admin_signature_file', adminSigFile.value);
            }

            processing.value = true;
            router.post(`/admin/applications/${props.application.id}/approve`, fd, {
                forceFormData: true,
                onFinish: () => { processing.value = false; },
            });
        };

        const reject = () => {
            if (!rejectNotes.value) return alert('Isi alasan penolakan.');
            processing.value = true;
            router.post(`/admin/applications/${props.application.id}/reject`, { admin_notes: rejectNotes.value }, {
                onSuccess: () => { showRejectForm.value = false; rejectNotes.value = ''; },
                onFinish: () => { processing.value = false; },
            });
        };

        const reissue = () => {
            processing.value = true;
            router.post(`/admin/applications/${props.application.id}/reissue`, { reason: reissueReason.value }, {
                onSuccess: () => { showReissueModal.value = false; reissueReason.value = ''; },
                onFinish: () => { processing.value = false; },
            });
        };

        const openRejectDoc = (docId) => { rejectDocId.value = docId; rejectDocNotes.value = ''; };

        const verifyDoc = (docId, status, notes) => {
            router.post(`/admin/applications/${props.application.id}/documents/${docId}/verify`,
                { status, reviewer_notes: notes },
                { onSuccess: () => { rejectDocId.value = null; } }
            );
        };

        return {
            pribadi, pekerjaan, fieldLabel, fieldValue, getDoc, docLabel, docBadge,
            statusLabel, statusBadge, formatDate,
            processing, showRejectForm, showReissueModal, rejectNotes, reissueReason,
            rejectDocId, rejectDocNotes,
            approve, reject, reissue, openRejectDoc, verifyDoc,
            adminSigMode, adminSigCanvas, adminSigFile, adminSigFilePreview, adminSignName,
            switchAdminSigMode, clearAdminSig, onAdminSigFileChange,
        };
    },
}
</script>
