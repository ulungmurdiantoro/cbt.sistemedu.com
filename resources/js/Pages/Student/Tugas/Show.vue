<template>
    <Head>
        <title>{{ tugas.praktik ? 'Ujian Praktik' : 'Upload Tugas' }} - Aplikasi Ujian Online</title>
    </Head>
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card border-0 shadow mb-4">
                <div class="card-body">
                    <h5 v-if="tugas.praktik"><i class="fa fa-clipboard-check me-2"></i>Ujian Praktik<span v-if="tugas.title" class="text-muted fw-normal"> — {{ tugas.title }}</span></h5>
                    <h5 v-else><i class="fa fa-upload me-2"></i>Upload Tugas</h5>
                    <hr>

                    <table class="table table-bordered mb-3" style="max-width:500px">
                        <tbody>
                            <tr>
                                <td class="fw-bold" style="width:40%">Sesi</td>
                                <td>{{ exam_session.title }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Ujian Praktik beberapa bagian: setiap bagian punya waktu & unggahan sendiri -->
                    <div v-if="parts.length > 1" class="mb-3">
                        <div class="small text-muted mb-2">
                            Ujian Praktik ini terdiri dari {{ parts.length }} bagian. Setiap bagian dimulai, diberi waktu, dan diunggah sendiri.
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <Link v-for="p in parts" :key="p.bagian" :href="`/student/tugas/${exam_session.id}?bagian=${p.bagian}`"
                                class="btn btn-sm border text-start part-tab" :class="p.bagian === bagian ? 'btn-primary' : 'btn-gray-100'">
                                <div class="fw-bolder">Bagian {{ p.bagian }}<span v-if="p.durasi"> · {{ p.durasi }} menit</span></div>
                                <div class="small text-truncate">{{ p.title }}</div>
                                <div class="small">
                                    <template v-if="p.uploaded"><i class="fa fa-check-circle me-1"></i>Sudah diunggah</template>
                                    <template v-else-if="p.closed"><i class="fa fa-hourglass-end me-1"></i>Waktu habis</template>
                                    <template v-else-if="p.started"><i class="fa fa-stopwatch me-1"></i>Sedang berjalan</template>
                                    <template v-else>Belum dikerjakan</template>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Ujian Praktik berwaktu: belum mulai -->
                    <template v-if="tugas.praktik && timer.timed && !timer.started">
                        <div class="alert alert-info border-0 shadow">
                            <i class="fa fa-stopwatch me-2"></i>
                            {{ parts.length > 1 ? `Bagian ${bagian}` : 'Ujian Praktik ini' }} berdurasi <strong>{{ timer.durasi }} menit</strong>. Waktu mulai berjalan saat Anda menekan
                            <strong>{{ parts.length > 1 ? `Mulai Bagian ${bagian}` : 'Mulai Ujian Praktik' }}</strong> dan tidak dapat dihentikan atau diulang. Panduan dan template tampil setelah Anda mulai.
                            Unggah hasil sebelum waktu habis; setelah itu unggahan ditutup.
                        </div>
                        <button type="button" class="btn btn-primary border-0 shadow" :disabled="starting" @click="start">
                            <i class="fa fa-play me-2"></i>{{ starting ? 'Memulai...' : (parts.length > 1 ? `Mulai Bagian ${bagian}` : 'Mulai Ujian Praktik') }}
                        </button>
                        <Link href="/student/dashboard" class="btn btn-secondary border-0 shadow ms-2">Kembali ke Dashboard</Link>
                    </template>

                    <!-- Ujian Praktik berwaktu: waktu habis -->
                    <template v-else-if="tugas.praktik && timer.timed && expired">
                        <div class="alert alert-warning border-0 shadow">
                            <i class="fa fa-hourglass-end me-2"></i>
                            Waktu {{ parts.length > 1 ? `bagian ${bagian}` : 'Ujian Praktik' }} sudah habis. Hasil tidak dapat diunggah lagi.
                            <span v-if="!uploadedFile">Anda tidak mengunggah hasil.</span>
                            <template v-if="nextPart">Lanjutkan ke bagian {{ nextPart.bagian }}.</template>
                            <template v-else>Anda tetap dapat mengerjakan ujian lain pada sesi ini.</template>
                        </div>
                        <div v-if="uploadedFile" class="alert alert-success border-0 shadow">
                            <i class="fa fa-check-circle me-2"></i>
                            Hasil yang diunggah: <strong>{{ uploadedFile.name }}</strong>
                        </div>
                        <Link v-if="nextPart" :href="`/student/tugas/${exam_session.id}?bagian=${nextPart.bagian}`" class="btn btn-primary border-0 shadow me-2">
                            Lanjut ke Bagian {{ nextPart.bagian }}<i class="fa fa-arrow-right ms-2"></i>
                        </Link>
                        <Link href="/student/dashboard" class="btn btn-secondary border-0 shadow">Kembali ke Dashboard</Link>
                    </template>

                    <template v-else-if="tugas.praktik">
                        <div v-if="timer.timed" class="timer-box border rounded p-3 mb-3 d-flex align-items-center justify-content-between"
                            :class="remaining <= 300 ? 'border-danger text-danger' : 'border-primary'">
                            <span><i class="fa fa-stopwatch me-2"></i>Sisa waktu Ujian Praktik</span>
                            <span class="fs-4 fw-bold">{{ remainingText }}</span>
                        </div>

                        <div class="alert alert-info border-0 shadow">
                            <i class="fa fa-info-circle me-2"></i>
                            Baca panduan, unduh template, isi sesuai panduan, lalu unggah kembali file yang sudah diisi.
                            <template v-if="parts.length > 1">Setiap bagian wajib diunggah (atau waktunya habis) sebelum Anda dapat mengerjakan ujian lain pada sesi ini.</template>
                            <template v-else>Anda wajib mengunggah hasilnya sebelum dapat mengerjakan ujian pada sesi ini.</template>
                        </div>

                        <div v-if="tugas.panduan" class="border rounded p-3 mb-3">
                            <h6 class="fw-bold mb-2">Panduan</h6>
                            <div class="small" style="white-space: pre-line">{{ tugas.panduan }}</div>
                        </div>

                        <div class="mb-4">
                            <a v-if="tugas.template_name" :href="`/student/tugas/${exam_session.id}/template${bagianQuery}`"
                                class="btn btn-primary border-0 shadow">
                                <i class="fa fa-download me-2"></i>Unduh Template
                            </a>
                            <span v-if="tugas.template_name" class="small text-muted ms-2">{{ tugas.template_name }}</span>
                            <div v-else class="alert alert-warning border-0 shadow mb-0">
                                <i class="fa fa-exclamation-triangle me-2"></i>Template belum tersedia. Hubungi panitia.
                            </div>
                        </div>

                        <h6 class="fw-bold">Unggah hasil ujian praktik<span v-if="parts.length > 1"> — bagian {{ bagian }}</span></h6>
                    </template>
                    <div v-else class="alert alert-info border-0 shadow">
                        <i class="fa fa-info-circle me-2"></i>
                        Anda wajib mengunggah tugas sebelum dapat mengerjakan ujian pada sesi ini. Tugas ini akan diperiksa oleh asesor pada saat ujian wawancara.
                    </div>

                    <template v-if="showUploadForm">
                    <div v-if="uploadedFile" class="alert alert-success border-0 shadow">
                        <i class="fa fa-check-circle me-2"></i>
                        Tugas sudah diunggah: <strong>{{ uploadedFile.name }}</strong>
                        <div class="small text-muted" v-if="uploadedFile.uploaded_at">Diunggah pada {{ uploadedFile.uploaded_at }}</div>
                    </div>

                    <div v-if="successMsg" class="alert alert-success border-0 shadow">{{ successMsg }}</div>
                    <div v-if="errorMsg" class="alert alert-danger border-0 shadow">{{ errorMsg }}</div>

                    <form @submit.prevent="upload">
                        <div class="mb-3">
                            <input type="file" class="form-control" ref="fileInput" :accept="file_accept" @change="onFileChange">
                            <div class="small text-muted mt-1">Tipe file: {{ file_accept }} — maks. 20 MB.</div>
                        </div>

                        <div class="progress mb-3" v-if="uploading" style="height:20px">
                            <div class="progress-bar" role="progressbar" :style="{ width: progress + '%' }">{{ progress }}%</div>
                        </div>

                        <button type="submit" class="btn btn-success border-0 shadow" :disabled="!selectedFile || uploading">
                            {{ uploading ? 'Mengunggah...' : (uploadedFile ? 'Ganti Tugas' : 'Unggah Tugas') }}
                        </button>
                        <Link v-if="uploadedFile && nextPart" :href="`/student/tugas/${exam_session.id}?bagian=${nextPart.bagian}`" class="btn btn-primary border-0 shadow ms-2">
                            Lanjut ke Bagian {{ nextPart.bagian }}<i class="fa fa-arrow-right ms-2"></i>
                        </Link>
                        <Link href="/student/dashboard" class="btn btn-secondary border-0 shadow ms-2">Kembali ke Dashboard</Link>
                    </form>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    //import layout student
    import LayoutStudent from '../../../Layouts/Student.vue';

    //import Head, Link dari Inertia
    import {
        Head,
        Link,
        router
    } from '@inertiajs/vue3';

    //import axios
    import axios from 'axios';

    export default {

        //layout
        layout: LayoutStudent,

        //register components
        components: {
            Head,
            Link,
        },

        //register props
        props: {
            exam_session: Object,
            existing_file: Object,
            file_accept:   String,
            // { praktik, label, panduan, template_name } — Upload Tugas atau Ujian Praktik
            tugas:         { type: Object, default: () => ({ praktik: false, label: 'Tugas' }) },
            // Ujian Praktik berwaktu: { timed, durasi, started, expired, remaining_seconds } (dihitung server)
            timer:         { type: Object, default: () => ({ timed: false }) },
            // Bagian yang sedang dibuka, dan daftar bagian bila Ujian Praktik lebih dari satu bagian
            // ([{ bagian, title, durasi, started, uploaded, closed, done }])
            bagian:        { type: Number, default: 1 },
            parts:         { type: Array, default: () => [] },
        },

        data() {
            return {
                selectedFile: null,
                uploading: false,
                progress: 0,
                successMsg: '',
                errorMsg: '',
                uploadedFile: this.existing_file,
                starting: false,
                remaining: this.timer.remaining_seconds ?? 0,
                expired: !!this.timer.expired,
                tick: null,
            };
        },

        computed: {
            bagianQuery() {
                return this.bagian > 1 ? `?bagian=${this.bagian}` : '';
            },
            // Bagian berikutnya yang belum selesai (untuk tombol "Lanjut ke Bagian N")
            nextPart() {
                return this.parts.find((p) => p.bagian > this.bagian && !p.done) ?? null;
            },
            showUploadForm() {
                if (!this.tugas.praktik || !this.timer.timed) return true;
                return this.timer.started && !this.expired;
            },
            remainingText() {
                const s = Math.max(0, this.remaining);
                const h = Math.floor(s / 3600);
                const m = Math.floor((s % 3600) / 60);
                const pad = (n) => String(n).padStart(2, '0');
                return (h > 0 ? `${pad(h)}:` : '') + `${pad(m)}:${pad(s % 60)}`;
            },
        },

        mounted() {
            if (this.timer.timed && this.timer.started && !this.expired) {
                // Sisa waktu dari server; dihitung mundur dari jam lokal sejak halaman dibuka.
                const endsAt = Date.now() + this.remaining * 1000;
                this.tick = setInterval(() => {
                    this.remaining = Math.round((endsAt - Date.now()) / 1000);
                    if (this.remaining <= 0) {
                        clearInterval(this.tick);
                        // Unggahan yang sedang berjalan dibiarkan selesai (server memberi toleransi singkat).
                        if (!this.uploading) this.expired = true;
                    }
                }, 1000);
            }
        },

        beforeUnmount() {
            if (this.tick) clearInterval(this.tick);
        },

        methods: {
            start() {
                if (!confirm(`Mulai Ujian Praktik sekarang? Waktu ${this.timer.durasi} menit langsung berjalan dan tidak dapat diulang.`)) return;
                this.starting = true;
                router.post(`/student/tugas/${this.exam_session.id}/mulai`, { bagian: this.bagian }, {
                    onFinish: () => { this.starting = false; },
                });
            },

            onFileChange(e) {
                this.selectedFile = e.target.files[0] ?? null;
            },

            upload() {
                if (!this.selectedFile) return;

                const formData = new FormData();
                formData.append('exam_session_id', this.exam_session.id);
                formData.append('bagian', this.bagian);
                formData.append('file', this.selectedFile);

                this.uploading = true;
                this.progress = 0;
                this.successMsg = '';
                this.errorMsg = '';

                axios.post('/student/tugas', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    onUploadProgress: (evt) => {
                        this.progress = evt.total ? Math.round((evt.loaded * 100) / evt.total) : 0;
                    },
                }).then(() => {
                    this.successMsg = 'Tugas berhasil diunggah.';
                    this.uploadedFile = { name: this.selectedFile.name, uploaded_at: null };
                    this.selectedFile = null;
                    if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                }).catch((err) => {
                    this.errorMsg = err.response?.data?.message ?? 'Gagal mengunggah tugas.';
                }).finally(() => {
                    this.uploading = false;
                    if (this.timer.timed && this.timer.started && this.remaining <= 0) this.expired = true;
                });
            },
        },

    }
</script>

<style>
    .part-tab { min-width: 180px; max-width: 280px; }
</style>
