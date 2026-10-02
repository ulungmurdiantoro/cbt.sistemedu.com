<template>
    <Head><title>Tugas {{ student.no_participant }} — {{ student.name }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <div class="card border-0 shadow mb-4">
                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-break">
                            <h5 class="mb-1"><i class="fa fa-file-alt me-2"></i>Tugas Peserta</h5>
                            <div class="small text-muted">{{ student.no_participant }} — {{ student.name }} · {{ exam_session.title }}</div>
                            <div class="small mt-1">
                                <span class="fw-bolder">{{ tugas.original_filename }}</span>
                                <span class="text-muted"> · {{ fileSize }}<template v-if="uploadedAt"> · diupload {{ uploadedAt }}</template></span>
                            </div>
                        </div>
                        <a :href="`${baseUrl}/unduh`" class="btn btn-primary border-0 shadow">
                            <i class="fa fa-download me-1"></i> Unduh
                        </a>
                    </div>
                </div>

                <div class="card border-0 shadow">
                    <div class="card-body bg-gray-100 p-2 p-md-3">
                        <div v-if="!tugas.previewable" class="text-center py-5">
                            <i class="fa fa-file fa-3x text-gray-400 mb-3"></i>
                            <p class="mb-3">File <strong>.{{ tugas.type.toUpperCase() }}</strong> tidak bisa ditampilkan di browser.
                                Unduh untuk membukanya.</p>
                            <a :href="`${baseUrl}/unduh`" class="btn btn-primary border-0 shadow">
                                <i class="fa fa-download me-1"></i> Unduh
                            </a>
                        </div>
                        <template v-else>
                            <div v-if="loading" class="text-center text-muted py-5">
                                <i class="fa fa-spinner fa-spin me-2"></i>Memuat pratinjau...
                            </div>
                            <div v-if="error" class="alert alert-danger border-0 mb-0">{{ error }}</div>
                            <iframe v-if="tugas.type === 'pdf' && blobUrl" :src="blobUrl" :title="tugas.original_filename"
                                class="d-block w-100 border-0 bg-white" style="height:80vh"></iframe>
                            <img v-else-if="blobUrl" :src="blobUrl" :alt="tugas.original_filename"
                                class="d-block mx-auto mw-100 shadow-sm bg-white">
                            <div ref="docx"></div>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

export default {
    layout: LayoutAsesor,
    components: { Head },

    props: {
        exam_session: Object,
        student: Object,
        tugas: Object,
    },

    data() {
        return {
            loading: true,
            error:   '',
            blobUrl: null,
        };
    },

    computed: {
        baseUrl() {
            return `/asesor/penilaian/${this.exam_session.id}/wawancara/tugas/${this.student.id}`;
        },
        fileSize() {
            const kb = (this.tugas.file_size ?? 0) / 1024;
            return kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(kb))} KB`;
        },
        uploadedAt() {
            return this.tugas.uploaded_at
                ? new Date(this.tugas.uploaded_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
                : null;
        },
    },

    mounted() {
        if (this.tugas.previewable) this.load();
    },

    beforeUnmount() {
        if (this.blobUrl) URL.revokeObjectURL(this.blobUrl);
    },

    methods: {
        // File diambil lewat XHR lalu ditampilkan dari blob: link file langsung sering
        // dicegat pengaturan browser / download manager (IDM) dan malah diunduh.
        async load() {
            try {
                const { data } = await axios.get(`${this.baseUrl}/file`, { responseType: 'blob' });

                if (this.tugas.type === 'docx') await this.renderDocx(data);
                else this.blobUrl = URL.createObjectURL(data);
            } catch (e) {
                this.error = e.response
                    ? 'Gagal memuat file tugas. Silakan unduh untuk membukanya.'
                    : 'File tidak dapat ditampilkan — kemungkinan rusak atau formatnya tidak sesuai ekstensi. Silakan unduh.';
            } finally {
                this.loading = false;
            }
        },

        async renderDocx(blob) {
            const { renderAsync } = await import('docx-preview');
            const container = this.$refs.docx;

            // altChunk dirender docx-preview sebagai <iframe srcdoc> berisi HTML dari dalam
            // file (bisa memuat script) — file peserta tidak tepercaya, jadi dimatikan.
            await renderAsync(blob, container, null, {
                renderAltChunks: false,
                renderComments:  false,
                renderChanges:   false,
                useBase64URL:    true,
            });

            // Link di dokumen dibuat mati (href bisa berisi "javascript:").
            container.querySelectorAll('iframe, script, object, embed').forEach(el => el.remove());
            container.querySelectorAll('a[href]').forEach(a => a.removeAttribute('href'));
        },
    },
}
</script>
