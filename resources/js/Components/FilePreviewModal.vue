<template>
    <div class="modal d-block" style="background:rgba(0,0,0,.5)" @click.self="$emit('close')">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold mb-0 text-truncate">
                        <i class="fa fa-eye me-2"></i>{{ title }}
                    </h6>
                    <button type="button" class="btn-close" @click="$emit('close')"></button>
                </div>
                <div class="modal-body bg-gray-100 file-preview" @contextmenu.prevent @dragstart.prevent>
                    <div v-if="!previewable" class="alert alert-warning border-0 mb-0">
                        Format <strong>.{{ type.toUpperCase() }}</strong> tidak dapat dipratinjau.
                        Format yang didukung: PDF, JPG, PNG, DOCX.
                    </div>
                    <template v-else>
                        <div v-if="loading" class="text-center text-muted py-5">
                            <i class="fa fa-spinner fa-spin me-2"></i>Memuat pratinjau...
                        </div>
                        <div v-if="error" class="alert alert-danger border-0 mb-0">{{ error }}</div>
                        <img v-if="imageUrl" :src="imageUrl" alt="" class="d-block mx-auto mw-100 shadow-sm bg-white">
                        <div ref="pages"></div>
                    </template>
                </div>
                <div class="modal-footer py-2 justify-content-start small text-muted">
                    <i class="fa fa-lock me-1"></i> Mode pratinjau — file tugas tidak untuk diunduh.
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

/**
 * Pratinjau file di modal tanpa tombol unduh/cetak: PDF dirender pdf.js ke canvas,
 * DOCX lewat docx-preview, gambar sebagai <img>. File diambil lewat axios (endpoint
 * pratinjau menolak dibuka langsung di tab). Pustaka dimuat hanya saat dibutuhkan.
 */
export default {
    props: {
        url:         { type: String, required: true },
        type:        { type: String, required: true },   // ekstensi: pdf, jpg, jpeg, png, docx, ...
        previewable: { type: Boolean, default: true },
        title:       { type: String, default: 'Pratinjau' },
    },

    emits: ['close'],

    data() {
        return {
            loading:  true,
            error:    '',
            imageUrl: null,
        };
    },

    mounted() {
        document.addEventListener('keydown', this.onKeydown);
        if (this.previewable) this.load();
    },

    beforeUnmount() {
        document.removeEventListener('keydown', this.onKeydown);
        this.closed = true;
        if (this.imageUrl) URL.revokeObjectURL(this.imageUrl);
        this.pdf?.destroy();
    },

    methods: {
        onKeydown(e) {
            if (e.key === 'Escape') this.$emit('close');
            // Ctrl/Cmd+S dan +P: jangan simpan / cetak isi pratinjau.
            if ((e.ctrlKey || e.metaKey) && ['s', 'p'].includes(e.key.toLowerCase())) e.preventDefault();
        },

        async load() {
            let blob;
            try {
                ({ data: blob } = await axios.get(this.url, {
                    responseType: 'blob',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                }));
            } catch (e) {
                this.error   = await this.responseMessage(e.response);
                this.loading = false;
                return;
            }
            if (this.closed) return;

            try {
                if (this.type === 'pdf') await this.renderPdf(blob);
                else if (this.type === 'docx') await this.renderDocx(blob);
                else this.imageUrl = URL.createObjectURL(blob);
            } catch {
                if (!this.closed) this.error = 'File tidak dapat ditampilkan — kemungkinan file rusak atau formatnya tidak sesuai ekstensi.';
            } finally {
                this.loading = false;
            }
        },

        async responseMessage(response) {
            try {
                const body = JSON.parse(await response.data.text());
                if (body.message) return body.message;
            } catch { /* bukan JSON */ }
            return response?.status === 404 ? 'File tugas tidak ditemukan.' : 'Gagal memuat pratinjau.';
        },

        async renderPdf(blob) {
            // ?worker&url: worker dibundel Vite sebagai .js — file .mjs belum tentu dilayani
            // Apache dengan MIME JavaScript, padahal module worker mewajibkannya.
            const [pdfjs, { default: workerSrc }] = await Promise.all([
                import('pdfjs-dist/legacy/build/pdf.mjs'),
                import('pdfjs-dist/legacy/build/pdf.worker.min.mjs?worker&url'),
            ]);
            pdfjs.GlobalWorkerOptions.workerSrc = workerSrc;

            this.pdf     = await pdfjs.getDocument({ data: await blob.arrayBuffer() }).promise;
            this.loading = false;

            const container = this.$refs.pages;
            const ratio     = window.devicePixelRatio || 1;

            for (let n = 1; n <= this.pdf.numPages; n++) {
                if (this.closed) return;
                const page     = await this.pdf.getPage(n);
                const viewport = page.getViewport({ scale: container.clientWidth / page.getViewport({ scale: 1 }).width });

                const canvas        = document.createElement('canvas');
                canvas.width        = Math.floor(viewport.width * ratio);
                canvas.height       = Math.floor(viewport.height * ratio);
                canvas.style.width  = `${Math.floor(viewport.width)}px`;
                canvas.className    = 'd-block mx-auto mb-3 mw-100 shadow-sm bg-white';
                container.appendChild(canvas);

                await page.render({
                    canvas,
                    viewport,
                    transform: ratio !== 1 ? [ratio, 0, 0, ratio, 0, 0] : undefined,
                }).promise;
            }
        },

        async renderDocx(blob) {
            const { renderAsync } = await import('docx-preview');
            const container = this.$refs.pages;

            // renderAltChunks: altChunk dirender sebagai <iframe srcdoc> berisi HTML dari
            // dalam file (bisa memuat script) — file peserta tidak tepercaya, jadi matikan.
            await renderAsync(blob, container, null, {
                renderAltChunks: false,
                renderComments:  false,
                renderChanges:   false,
                useBase64URL:    true,
            });

            // Pratinjau saja: link di dokumen tidak perlu bisa diklik (href bisa "javascript:").
            container.querySelectorAll('iframe, script, object, embed').forEach(el => el.remove());
            container.querySelectorAll('a[href]').forEach(a => a.removeAttribute('href'));
        },
    },
}
</script>

<style scoped>
.file-preview {
    min-height: 50vh;
    user-select: none;
}

@media print {
    .file-preview {
        display: none;
    }
}
</style>
