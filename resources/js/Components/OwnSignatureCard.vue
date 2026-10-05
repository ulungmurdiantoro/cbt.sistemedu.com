<template>
    <!--
      Kartu "Tanda Tangan Anda": tampilkan TTD tersimpan milik user yang login dan editornya
      (gambar di kanvas atau unggah gambar). Dipakai di Laporan Asesmen dan TTD AK.01 asesor.
      Setiap simpan membuat file baru, jadi dokumen yang sudah ditandatangani tetap memakai TTD lamanya.
    -->
    <div class="card border-0 shadow mb-4">
        <div class="card-body">
            <h6 class="mb-3"><i class="fa fa-signature me-2"></i>Tanda Tangan Anda</h6>

            <div v-if="hasSignature" class="d-flex flex-wrap align-items-center gap-3">
                <img :src="`${imageUrl}?v=${imgVersion}`" alt="TTD Anda" class="sig-img">
                <div class="flex-fill">
                    <span class="badge bg-success mb-1"><i class="fa fa-check me-1"></i>Tersimpan</span>
                    <div class="small text-muted">{{ usage }}</div>
                    <button type="button" class="btn btn-sm btn-gray-100 border mt-2" @click="toggleForm">
                        <i :class="showForm ? 'fa fa-times' : 'fa fa-pen'" class="me-1"></i>{{ showForm ? 'Batal' : 'Ganti Tanda Tangan' }}
                    </button>
                </div>
            </div>

            <div v-else class="alert alert-warning border-0 mb-3">
                <i class="fa fa-exclamation-triangle me-2"></i>Anda belum punya tanda tangan tersimpan. {{ missingHint }}
            </div>

            <!-- Catatan tambahan dari halaman pemakai -->
            <slot />

            <div v-if="!hasSignature || showForm" :class="{ 'mt-3': hasSignature }">
                <div class="d-flex gap-1 mb-2" style="max-width:300px">
                    <button type="button" class="btn btn-sm flex-fill"
                        :class="mode === 'draw' ? 'btn-gray-800' : 'btn-gray-100 border'"
                        @click="switchMode('draw')">
                        <i class="fa fa-pen me-1"></i>Gambar
                    </button>
                    <button type="button" class="btn btn-sm flex-fill"
                        :class="mode === 'upload' ? 'btn-gray-800' : 'btn-gray-100 border'"
                        @click="switchMode('upload')">
                        <i class="fa fa-upload me-1"></i>Upload
                    </button>
                </div>

                <div v-show="mode === 'draw'" style="max-width:400px">
                    <div class="border rounded bg-white" style="touch-action:none">
                        <canvas ref="canvas" style="display:block; width:100%; height:140px; cursor:crosshair"></canvas>
                    </div>
                    <button type="button" class="btn btn-sm btn-gray-100 border mt-1" @click="clearPad">
                        <i class="fa fa-eraser me-1"></i>Hapus
                    </button>
                </div>

                <div v-show="mode === 'upload'" style="max-width:400px">
                    <input type="file" class="form-control form-control-sm"
                        accept="image/png,image/jpeg,image/jpg" @change="onFileChange">
                    <div v-if="filePreview" class="mt-2">
                        <img :src="filePreview" class="sig-img">
                    </div>
                </div>

                <div v-if="error" class="alert alert-danger border-0 py-2 small mt-2 mb-0" style="max-width:400px">{{ error }}</div>

                <div class="mt-3">
                    <button type="button" class="btn btn-primary" :disabled="saving" @click="submit">
                        <i class="fa fa-save me-1"></i>{{ saving ? 'Menyimpan...' : 'Simpan Tanda Tangan' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { router } from '@inertiajs/vue3';
import SignaturePad from 'signature_pad';

export default {
    name: 'OwnSignatureCard',
    props: {
        hasSignature: { type: Boolean, default: false },
        // Kalimat di bawah badge "Tersimpan": dipakai untuk apa TTD ini
        usage:        { type: String, default: '' },
        // Kalimat lanjutan saat belum punya TTD
        missingHint:  { type: String, default: 'Buat di bawah ini.' },
        saveUrl:      { type: String, default: '/asesor/tanda-tangan' },
        imageUrl:     { type: String, default: '/asesor/tanda-tangan' },
    },
    emits: ['saved'],

    data() {
        return {
            showForm:    false,
            mode:        'draw',
            file:        null,
            filePreview: null,
            saving:      false,
            error:       '',
            // Pemecah cache gambar: URL TTD selalu sama, isinya berganti setelah disimpan
            imgVersion:  Date.now(),
        };
    },

    mounted() {
        if (!this.hasSignature) this.$nextTick(this.initPad);
        window.addEventListener('resize', this.onResize);
    },

    beforeUnmount() {
        window.removeEventListener('resize', this.onResize);
        clearTimeout(this.resizeTimer);
    },

    methods: {
        initPad() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            const ratio     = Math.max(window.devicePixelRatio || 1, 1);
            const savedData = this.pad?.toData() ?? [];
            canvas.width  = (canvas.parentElement?.clientWidth || canvas.offsetWidth) * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            this.pad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
            if (savedData.length) this.pad.fromData(savedData);
        },

        onResize() {
            clearTimeout(this.resizeTimer);
            this.resizeTimer = setTimeout(this.initPad, 200);
        },

        toggleForm() {
            this.showForm = !this.showForm;
            this.error = '';
            if (this.showForm) this.$nextTick(this.initPad);
        },

        switchMode(mode) {
            this.mode = mode;
            this.error = '';
            if (mode === 'draw') this.$nextTick(this.initPad);
        },

        clearPad() {
            this.pad?.clear();
        },

        onFileChange(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.file = file;
            this.filePreview = URL.createObjectURL(file);
        },

        submit() {
            const fd = new FormData();
            this.error = '';

            if (this.mode === 'draw') {
                if (!this.pad || this.pad.isEmpty()) {
                    this.error = 'Gambar tanda tangan Anda dulu, atau pilih Upload.';
                    return;
                }
                fd.append('signature_data', this.pad.toDataURL('image/png'));
            } else {
                if (!this.file) {
                    this.error = 'Pilih file gambar tanda tangan terlebih dahulu.';
                    return;
                }
                fd.append('signature_file', this.file);
            }

            this.saving = true;
            router.post(this.saveUrl, fd, {
                forceFormData:  true,
                preserveScroll: true,
                // State halaman dipertahankan supaya pesan sukses & isian lain tidak hilang
                preserveState:  true,
                onSuccess: () => {
                    this.showForm    = false;
                    this.file        = null;
                    this.filePreview = null;
                    this.pad?.clear();
                    this.imgVersion  = Date.now();
                    this.$emit('saved');
                },
                onError: (errors) => {
                    this.error = errors.signature_file ?? errors.signature_data ?? 'Tanda tangan gagal disimpan.';
                },
                onFinish: () => { this.saving = false; },
            });
        },
    },
}
</script>

<style scoped>
.sig-img {
    max-height: 70px;
    max-width: 200px;
    object-fit: contain;
    border: 1px solid #ddd;
    background: #fff;
    padding: 4px;
}
</style>
