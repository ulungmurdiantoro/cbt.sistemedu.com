<template>
    <Head><title>Laporan Asesmen — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link href="/asesor/dashboard" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-file-alt me-2"></i>FR.AK.05 — Laporan Asesmen</h5>
                        <hr>
                        <table class="table table-bordered mb-0 table-wrap" style="max-width:500px">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" style="width:40%">Sesi</td>
                                    <td>{{ exam_session.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Skema</td>
                                    <td>{{ exam_session.examPg?.classroom?.title ?? exam_session.examEsai?.classroom?.title ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-signature me-2"></i>Tanda Tangan Anda</h6>

                        <div v-if="has_signature" class="d-flex align-items-center gap-3">
                            <img :src="signatureUrl" alt="TTD Anda"
                                style="max-height:70px; max-width:200px; object-fit:contain; border:1px solid #ddd; background:#fff; padding:4px">
                            <div class="flex-fill">
                                <span class="badge bg-success mb-1"><i class="fa fa-check me-1"></i>Tersimpan</span>
                                <div class="small text-muted">Dipakai otomatis untuk membubuhkan TTD Anda di FR.AK.05 dan dokumen lain.</div>
                                <button type="button" class="btn btn-sm btn-outline-secondary border mt-2" @click="toggleSigForm">
                                    <i class="fa fa-pen me-1"></i>{{ showSigForm ? 'Batal' : 'Ganti Tanda Tangan' }}
                                </button>
                            </div>
                        </div>

                        <div v-if="!has_signature" class="alert alert-warning border-0 mb-3">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            Anda belum punya tanda tangan tersimpan. Isi di bawah ini supaya bisa dipakai otomatis untuk FR.AK.05 dan dokumen lain yang memerlukan TTD Anda.
                        </div>

                        <div v-if="!has_signature || showSigForm" :class="{ 'mt-3': has_signature }">
                            <div class="d-flex gap-1 mb-2" style="max-width:300px">
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
                            </div>

                            <div v-show="sigMode === 'draw'" style="max-width:400px">
                                <div class="border rounded bg-white" style="touch-action:none">
                                    <canvas ref="sigCanvas" style="display:block; width:100%; height:140px; cursor:crosshair"></canvas>
                                </div>
                                <button type="button" class="btn btn-sm btn-light border mt-1" @click="clearSig">
                                    <i class="fa fa-eraser me-1"></i>Hapus
                                </button>
                            </div>

                            <div v-show="sigMode === 'upload'" style="max-width:400px">
                                <input type="file" class="form-control form-control-sm"
                                    accept="image/png,image/jpeg,image/jpg" @change="onSigFileChange">
                                <div v-if="sigFilePreview" class="mt-2">
                                    <img :src="sigFilePreview"
                                        style="max-height:80px; border:1px solid #ddd; background:#fff; padding:4px">
                                </div>
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-primary" :disabled="sigSaving" @click="submitSignature">
                                    <i class="fa fa-save me-1"></i>{{ sigSaving ? 'Menyimpan...' : 'Simpan Tanda Tangan' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0"><i class="fa fa-table me-2"></i>Rekomendasi Asesi</h6>
                            <button v-if="rows.length" @click="saveRekomendasi" :disabled="savingRekomendasi"
                                class="btn btn-sm btn-success border-0 shadow">
                                <i class="fa fa-save me-1"></i>
                                {{ savingRekomendasi ? 'Menyimpan...' : 'Simpan Rekomendasi' }}
                            </button>
                        </div>
                        <div v-if="rows.length === 0" class="alert alert-info mb-0">
                            Tidak ada peserta yang ditugaskan di sesi ini.
                        </div>
                        <template v-else>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="text-center" style="width:5%">No.</th>
                                            <th>Nama Asesi</th>
                                            <th class="text-center" style="width:16%">Rekomendasi</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, i) in rows" :key="row.student_id">
                                            <td class="text-center">{{ i + 1 }}</td>
                                            <td>{{ row.name }}</td>
                                            <td class="text-center">
                                                <div v-if="row.is_finalized">
                                                    <span v-if="row.rekomendasi === 'K'" class="badge bg-success">Kompeten</span>
                                                    <span v-else-if="row.rekomendasi === 'BK'" class="badge bg-danger">Belum Kompeten</span>
                                                    <span v-else class="badge bg-secondary">Belum diisi</span>
                                                    <div class="small text-muted mt-1"><i class="fa fa-lock me-1"></i>Terkunci</div>
                                                </div>
                                                <div v-else-if="!row.application_id" class="text-muted small fst-italic">
                                                    Tidak ada aplikasi
                                                </div>
                                                <div v-else class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn"
                                                        :class="rekForm[row.student_id] === 'K' ? 'btn-success' : 'btn-outline-success'"
                                                        @click="rekForm[row.student_id] = 'K'">K</button>
                                                    <button type="button" class="btn"
                                                        :class="rekForm[row.student_id] === 'BK' ? 'btn-danger' : 'btn-outline-danger'"
                                                        @click="rekForm[row.student_id] = 'BK'">BK</button>
                                                </div>
                                            </td>
                                            <td class="small">{{ row.keterangan }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="small text-muted mt-2">
                                Rekomendasi K/BK terpisah dari Verifikasi Akhir dokumen — bisa diisi/diubah kapan saja
                                setelah nilai esai/wawancara selesai dinilai, sampai sesi ini difinalisasi Pengambil Keputusan.
                            </div>
                        </template>
                    </div>
                </div>

                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-pen me-2"></i>Catatan Laporan Asesmen</h6>
                        <div class="small text-muted mb-3">
                            Kolom di bawah sudah diisi teks contoh yang umum dipakai — silakan diedit sesuai kondisi sesi ini, atau langsung disimpan apa adanya.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal Pelaksanaan Asesmen</label>
                            <input type="date" v-model="form.tanggal_asesmen" class="form-control" style="max-width:220px">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Aspek Negatif dan Positif dalam Asesmen</label>
                            <textarea v-model="form.aspek_negatif_positif" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pencatatan Penolakan Hasil Asesmen</label>
                            <textarea v-model="form.pencatatan_penolakan" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Saran Perbaikan (Asesor/Personil Terkait)</label>
                            <textarea v-model="form.saran_perbaikan" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea v-model="form.catatan" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button @click="save" :disabled="saving" class="btn btn-success border-0 shadow">
                                <i class="fa fa-save me-1"></i>
                                {{ saving ? 'Menyimpan...' : 'Simpan Laporan' }}
                            </button>
                            <a v-if="report" :href="downloadUrl" target="_blank" class="btn btn-outline-dark border">
                                <i class="fa fa-file-pdf me-1"></i> Download PDF
                            </a>
                            <span v-else class="align-self-center small text-muted">
                                Simpan laporan terlebih dahulu untuk bisa mengunduh PDF.
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SignaturePad from 'signature_pad';

export default {
    layout: LayoutAsesor,
    components: { Head, Link },

    props: {
        exam_session:  Object,
        rows:          Array,
        report:        Object,
        has_signature: Boolean,
    },

    data() {
        const rekForm = {};
        this.rows.forEach(r => { rekForm[r.student_id] = r.rekomendasi; });

        // Teks contoh diambil dari format resmi FR.AK.05 — dipakai sebagai isian awal
        // supaya asesor tidak mulai dari kosong, tinggal disesuaikan atau langsung disimpan.
        const defaultAspek = 'Peserta mampu mengerjakan semua ujian tulis dengan lancar, mempresentasikan penugasan sesuai ketentuan yang diinformasikan dan menjawab pertanyaan asesor sesuai dengan pemahaman dan pengalaman.';
        const defaultCatatan = 'Seluruh peserta dapat mengikuti semua rangkaian kegiatan dan mengerjakan ujian, sehingga direkomendasikan untuk mendapatkan sertifikat kegiatan sertifikasi.';

        return {
            saving: false,
            savingRekomendasi: false,
            successMsg: '',
            rekForm,
            showSigForm: false,
            sigMode: 'draw',
            sigFile: null,
            sigFilePreview: null,
            sigSaving: false,
            form: {
                tanggal_asesmen:       this.report?.tanggal_asesmen ?? this.exam_session.start_time?.slice(0, 10) ?? '',
                aspek_negatif_positif: this.report?.aspek_negatif_positif ?? defaultAspek,
                pencatatan_penolakan:  this.report?.pencatatan_penolakan ?? '-',
                saran_perbaikan:       this.report?.saran_perbaikan ?? '-',
                catatan:               this.report?.catatan ?? defaultCatatan,
            },
        };
    },

    computed: {
        downloadUrl() {
            return `/dokumen/laporan-asesmen/${this.exam_session.id}/${this.$page.props.auth.user.id}/download`;
        },
        signatureUrl() {
            return '/asesor/tanda-tangan';
        },
    },

    mounted() {
        if (!this.has_signature) {
            this.$nextTick(() => this.initSigPad());
        }
        window.addEventListener('resize', this.handleSigResize);
    },

    beforeUnmount() {
        window.removeEventListener('resize', this.handleSigResize);
        clearTimeout(this.sigResizeTimer);
    },

    methods: {
        // ── Tanda tangan asesor sendiri ──────────────────────────────
        initSigPad() {
            if (!this.$refs.sigCanvas) return;
            const canvas    = this.$refs.sigCanvas;
            const container = canvas.parentElement;
            const ratio     = Math.max(window.devicePixelRatio || 1, 1);
            const savedData = this.sigPad?.toData() ?? [];
            canvas.width    = (container?.clientWidth || canvas.offsetWidth) * ratio;
            canvas.height   = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            this.sigPad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
            if (savedData.length) this.sigPad.fromData(savedData);
        },

        handleSigResize() {
            clearTimeout(this.sigResizeTimer);
            this.sigResizeTimer = setTimeout(this.initSigPad, 200);
        },

        toggleSigForm() {
            this.showSigForm = !this.showSigForm;
            if (this.showSigForm) this.$nextTick(() => this.initSigPad());
        },

        switchSigMode(mode) {
            this.sigMode = mode;
            if (mode === 'draw') this.$nextTick(() => this.initSigPad());
        },

        clearSig() {
            this.sigPad?.clear();
        },

        onSigFileChange(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.sigFile = file;
            this.sigFilePreview = URL.createObjectURL(file);
        },

        submitSignature() {
            const fd = new FormData();

            if (this.sigMode === 'draw') {
                if (!this.sigPad || this.sigPad.isEmpty()) {
                    alert('Tanda tangan wajib digambar atau upload file terlebih dahulu.');
                    return;
                }
                fd.append('signature_data', this.sigPad.toDataURL('image/png'));
            } else {
                if (!this.sigFile) {
                    alert('Pilih file tanda tangan terlebih dahulu.');
                    return;
                }
                fd.append('signature_file', this.sigFile);
            }

            this.sigSaving = true;
            router.post('/asesor/tanda-tangan', fd, {
                forceFormData:  true,
                preserveScroll: true,
                onSuccess: () => {
                    this.successMsg = 'Tanda tangan berhasil disimpan.';
                    this.showSigForm = false;
                },
                onFinish: () => { this.sigSaving = false; },
            });
        },

        save() {
            this.saving = true;
            router.post(
                `/asesor/penilaian/${this.exam_session.id}/laporan-asesmen`,
                this.form,
                {
                    preserveScroll: true,
                    onSuccess: () => { this.successMsg = 'Laporan Asesmen berhasil disimpan.'; },
                    onFinish:  () => { this.saving = false; },
                }
            );
        },

        saveRekomendasi() {
            this.savingRekomendasi = true;
            const rekomendasi = this.rows.map(r => ({
                student_id: r.student_id,
                value:      this.rekForm[r.student_id] ?? null,
            }));
            router.post(
                `/asesor/penilaian/${this.exam_session.id}/laporan-asesmen/rekomendasi`,
                { rekomendasi },
                {
                    preserveScroll: true,
                    onSuccess: () => { this.successMsg = 'Rekomendasi berhasil disimpan.'; },
                    onFinish:  () => { this.savingRekomendasi = false; },
                }
            );
        },
    },
}
</script>
