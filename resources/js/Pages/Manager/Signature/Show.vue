<template>
    <Head><title>Tanda Tangan Saya</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-lg-8">

                <Link href="/manager/dashboard" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-signature me-2"></i>Tanda Tangan Pengambil Keputusan</h5>
                        <hr>
                        <p class="small text-muted mb-0">
                            Tanda tangan ini dipakai otomatis untuk membubuhkan TTD Anda pada
                            <strong>Keputusan Sertifikasi</strong> saat sesi difinalisasi. Simpan sekali di sini —
                            tidak perlu diulang tiap sesi.
                        </p>
                    </div>
                </div>

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>

                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-pen me-2"></i>Tanda Tangan Anda</h6>

                        <div v-if="has_signature" class="d-flex align-items-center gap-3">
                            <img :src="signatureUrl" alt="TTD Anda"
                                style="max-height:70px; max-width:200px; object-fit:contain; border:1px solid #ddd; background:#fff; padding:4px">
                            <div class="flex-fill">
                                <span class="badge bg-success mb-1"><i class="fa fa-check me-1"></i>Tersimpan</span>
                                <div class="small text-muted">Atas nama: <strong>{{ signature_name }}</strong></div>
                                <button type="button" class="btn btn-sm btn-outline-secondary border mt-2" @click="toggleSigForm">
                                    <i class="fa fa-pen me-1"></i>{{ showSigForm ? 'Batal' : 'Ganti Tanda Tangan' }}
                                </button>
                            </div>
                        </div>

                        <div v-if="!has_signature" class="alert alert-warning border-0 mb-3">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            Anda belum punya tanda tangan tersimpan. Isi di bawah ini supaya bisa dipakai otomatis
                            untuk Keputusan Sertifikasi.
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

            </div>
        </div>
    </div>
</template>

<script>
import LayoutManager from '../../../Layouts/Manager.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SignaturePad from 'signature_pad';

export default {
    layout: LayoutManager,
    components: { Head, Link },

    props: {
        has_signature:  Boolean,
        signature_name: String,
    },

    data() {
        return {
            successMsg: '',
            showSigForm: false,
            sigMode: 'draw',
            sigFile: null,
            sigFilePreview: null,
            sigSaving: false,
        };
    },

    computed: {
        signatureUrl() {
            return '/manager/tanda-tangan/gambar?t=' + Date.now();
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
            router.post('/manager/tanda-tangan', fd, {
                forceFormData:  true,
                preserveScroll: true,
                onSuccess: () => {
                    this.successMsg = 'Tanda tangan berhasil disimpan.';
                    this.showSigForm = false;
                },
                onFinish: () => { this.sigSaving = false; },
            });
        },
    },
}
</script>
