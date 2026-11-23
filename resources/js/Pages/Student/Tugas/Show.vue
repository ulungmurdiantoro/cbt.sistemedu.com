<template>
    <Head>
        <title>{{ tugas.praktik ? 'Ujian Praktik/Keterampilan' : 'Upload Tugas' }} - Aplikasi Ujian Online</title>
    </Head>
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card border-0 shadow mb-4">
                <div class="card-body">
                    <h5 v-if="tugas.praktik"><i class="fa fa-clipboard-check me-2"></i>{{ tugas.title || 'Ujian Praktik/Keterampilan' }}</h5>
                    <h5 v-else><i class="fa fa-upload me-2"></i>Upload Tugas</h5>
                    <hr>

                    <ExamInfoTable :rows="infoRows" />

                    <!-- Beberapa bagian: tiap bagian dibuka dari kartunya sendiri di dashboard (tanpa tab di sini), dikerjakan berurutan -->
                    <div v-if="flashError" class="alert alert-danger border-0 shadow">
                        <i class="fa fa-exclamation-triangle me-2"></i>{{ flashError }}
                    </div>

                    <!-- Bagian berikutnya belum boleh dimulai: bagian sebelumnya belum selesai -->
                    <template v-if="tugas.praktik && timer.timed && !timer.started && timer.start_blocked">
                        <div class="alert alert-warning border-0 shadow">
                            <i class="fa fa-lock me-2"></i>{{ timer.start_blocked }}
                            Hanya satu ujian yang dapat berjalan pada satu waktu.
                        </div>
                        <Link href="/student/dashboard" class="btn btn-secondary border-0 shadow">Kembali ke Dashboard</Link>
                    </template>

                    <!-- Ujian Praktik/Keterampilan berwaktu: belum mulai -->
                    <template v-else-if="tugas.praktik && timer.timed && !timer.started">
                        <div v-if="closesText" class="alert alert-warning border-0 shadow">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            Memulai Bagian {{ bagian }} <strong>menutup {{ closesText }}</strong>: hasilnya tidak dapat diunggah atau diganti lagi.
                        </div>
                        <div class="alert alert-info border-0 shadow">
                            <i class="fa fa-stopwatch me-2"></i>
                            {{ parts.length > 1 ? `Bagian ${bagian}` : 'Ujian Praktik/Keterampilan ini' }} punya dua waktu:
                            <ol class="mb-1 mt-1 ps-3">
                                <li><strong>Waktu pengerjaan {{ timer.durasi }} menit</strong> — mulai berjalan saat Anda menekan
                                    <strong>{{ parts.length > 1 ? `Mulai Bagian ${bagian}` : 'Mulai Ujian Praktik/Keterampilan' }}</strong>, tidak dapat dijeda
                                    atau diulang dari awal. Panduan dan template hanya tersedia selama waktu ini.</li>
                                <li v-if="timer.waktu_unggah"><strong>Waktu unggah {{ timer.waktu_unggah }} menit</strong> — setelah waktu pengerjaan habis,
                                    hasil masih bisa diunggah atau diganti. Setelah itu unggahan ditutup.</li>
                            </ol>
                        </div>
                        <button type="button" class="btn btn-primary border-0 shadow" :disabled="starting" @click="start">
                            <i class="fa fa-play me-2"></i>{{ starting ? 'Memulai...' : (parts.length > 1 ? `Mulai Bagian ${bagian}` : 'Mulai Ujian Praktik/Keterampilan') }}
                        </button>
                        <Link href="/student/dashboard" class="btn btn-secondary border-0 shadow ms-2">Kembali ke Dashboard</Link>
                    </template>

                    <!-- Ujian Praktik/Keterampilan berwaktu: waktu pengerjaan & waktu unggah habis -->
                    <template v-else-if="tugas.praktik && timer.timed && closed">
                        <div class="alert alert-warning border-0 shadow">
                            <i class="fa fa-hourglass-end me-2"></i>
                            <template v-if="timer.closed_by_next">Bagian {{ bagian }} sudah ditutup karena bagian lain sudah dimulai. Hasil tidak dapat diunggah lagi.</template>
                            <template v-else>Waktu {{ parts.length > 1 ? `bagian ${bagian}` : 'Ujian Praktik/Keterampilan' }} sudah habis. Hasil tidak dapat diunggah lagi.</template>
                            <span v-if="!uploadedFile">Anda tidak mengunggah hasil.</span>
                            <template v-if="nextPart">Lanjutkan ke bagian {{ nextPart.bagian }}.</template>
                        </div>
                        <div v-if="uploadedFile" class="alert alert-success border-0 shadow">
                            <i class="fa fa-check-circle me-2"></i>
                            Hasil yang diunggah: <strong>{{ uploadedFile.name }}</strong>
                        </div>
                        <Link v-if="nextPart" :href="`${page_url}?bagian=${nextPart.bagian}`" class="btn btn-primary border-0 shadow me-2">
                            Lanjut ke Bagian {{ nextPart.bagian }}<i class="fa fa-arrow-right ms-2"></i>
                        </Link>
                        <Link href="/student/dashboard" class="btn btn-secondary border-0 shadow">Kembali ke Dashboard</Link>
                    </template>

                    <template v-else-if="tugas.praktik">
                        <template v-if="timer.timed">
                            <!-- Dua durasi: waktu pengerjaan lalu waktu unggah -->
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6">
                                    <div class="timer-box border rounded p-3 h-100" :class="expired ? 'bg-gray-100 text-muted' : (remaining <= 300 ? 'border-danger text-danger' : 'border-primary')">
                                        <div class="small"><i class="fa fa-stopwatch me-1"></i>Waktu pengerjaan ({{ timer.durasi }} menit)</div>
                                        <div class="fs-4 fw-bold">{{ expired ? 'Habis' : formatSeconds(remaining) }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-6" v-if="timer.waktu_unggah">
                                    <div class="timer-box border rounded p-3 h-100" :class="expired ? 'border-danger text-danger' : 'text-muted'">
                                        <div class="small"><i class="fa fa-upload me-1"></i>Waktu unggah ({{ timer.waktu_unggah }} menit)</div>
                                        <div class="fs-4 fw-bold">{{ expired ? formatSeconds(uploadRemaining) : 'Setelah pengerjaan' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="expired" class="alert alert-danger border-0 shadow">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                Waktu pengerjaan habis — panduan dan template sudah ditutup. Unggah hasil Anda sekarang, sebelum waktu unggah habis.
                            </div>
                        </template>

                        <template v-if="!expired">
                            <div class="alert alert-info border-0 shadow">
                                <i class="fa fa-info-circle me-2"></i>
                                Baca panduan, unduh template, isi sesuai panduan, lalu unggah kembali file yang sudah diisi.
                                <template v-if="timer.timed && timer.waktu_unggah">Setelah waktu pengerjaan habis masih ada waktu unggah {{ timer.waktu_unggah }} menit.</template>
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
                        </template>

                        <h6 class="fw-bold">Unggah hasil ujian praktik/keterampilan<span v-if="parts.length > 1"> — bagian {{ bagian }}</span></h6>
                    </template>
                    <div v-else class="alert alert-info border-0 shadow">
                        <i class="fa fa-info-circle me-2"></i>
                        Anda wajib mengunggah tugas sebelum dapat mengerjakan ujian pada sesi ini. Tugas ini akan diperiksa oleh asesor pada saat ujian wawancara.
                    </div>

                    <template v-if="showUploadForm">
                    <div v-if="uploadedFile" class="alert alert-success border-0 shadow">
                        <i class="fa fa-check-circle me-2"></i>
                        Tugas sudah diunggah: <strong>{{ uploadedFile.name }}</strong>
                        <div class="small text-muted" v-if="uploadedFile.uploaded_at">Diunggah pada {{ formatDateTime(uploadedFile.uploaded_at) }}</div>
                    </div>

                    <div v-if="successMsg" class="alert alert-success border-0 shadow">{{ successMsg }}</div>
                    <div v-if="errorMsg" class="alert alert-danger border-0 shadow">{{ errorMsg }}</div>

                    <form @submit.prevent="upload">
                        <div class="mb-3">
                            <input type="file" class="form-control" ref="fileInput" :accept="file_accept" @change="onFileChange">
                            <div class="small text-muted mt-1">Tipe file: {{ file_accept }} — maks. {{ file_max_mb }} MB.</div>
                        </div>

                        <div class="progress mb-3" v-if="uploading" style="height:20px">
                            <div class="progress-bar" role="progressbar" :style="{ width: progress + '%' }">{{ progress }}%</div>
                        </div>

                        <button type="submit" class="btn btn-success border-0 shadow" :disabled="!selectedFile || uploading">
                            {{ uploading ? 'Mengunggah...' : (uploadedFile ? 'Ganti Tugas' : 'Unggah Tugas') }}
                        </button>
                        <Link v-if="uploadedFile && nextPart" :href="`${page_url}?bagian=${nextPart.bagian}`" class="btn btn-primary border-0 shadow ms-2">
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

    import ExamInfoTable from '../../../Components/Student/ExamInfoTable.vue';

    export default {

        //layout
        layout: LayoutStudent,

        //register components
        components: {
            Head,
            Link,
            ExamInfoTable,
        },

        //register props
        props: {
            exam_session: Object,
            // /student/praktik-confirmation/{sesi} (Ujian Praktik/Keterampilan) atau /student/tugas/{sesi} (Upload Tugas)
            page_url:      { type: String, default: '' },
            // { name, no_participant, skema }
            student:       { type: Object, default: () => ({}) },
            existing_file: Object,
            file_accept:   String,
            // Batas unggah efektif (MB): aturan aplikasi dibatasi pengaturan PHP server
            file_max_mb:   { type: Number, default: 20 },
            // { praktik, label, panduan, template_name } — Upload Tugas atau Ujian Praktik/Keterampilan
            tugas:         { type: Object, default: () => ({ praktik: false, label: 'Tugas' }) },
            // Ujian Praktik/Keterampilan berwaktu (dihitung server): { timed, durasi, waktu_unggah, started,
            // expired (waktu pengerjaan habis), remaining_seconds, closed (waktu unggah habis), upload_remaining_seconds }
            timer:         { type: Object, default: () => ({ timed: false }) },
            // Bagian yang sedang dibuka, dan daftar bagian bila Ujian Praktik/Keterampilan lebih dari satu bagian
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
                // Waktu pengerjaan lalu waktu unggah (sisa detik dari server, dihitung mundur dengan jam lokal)
                remaining: this.timer.remaining_seconds ?? 0,
                uploadRemaining: this.timer.upload_remaining_seconds ?? 0,
                expired: !!this.timer.expired,
                closed: !!this.timer.closed,
                tick: null,
            };
        },

        computed: {
            flashError() {
                return this.$page.props.session?.error ?? null;
            },
            // Bagian lain yang ditutup bila bagian ini dimulai ("Bagian 1")
            closesText() {
                const parts = this.timer.closes_parts ?? [];
                return parts.length ? parts.map((b) => `Bagian ${b}`).join(' dan ') : '';
            },
            // Baris informasi — sama urutannya dengan halaman konfirmasi PG/Esai
            infoRows() {
                const durasi = this.timer.timed ? this.timer.durasi : this.tugas.durasi;
                return [
                    { label: 'Nama Lengkap', value: this.student.name },
                    { label: 'No. Peserta', value: this.student.no_participant },
                    { label: 'Skema', value: this.student.skema },
                    { label: 'Sesi', value: this.exam_session.title },
                    { label: 'Ujian', value: this.tugas.praktik ? this.tugas.title : null },
                    { label: 'Tipe Ujian', value: this.tugas.praktik ? 'Ujian Praktik/Keterampilan' : 'Upload Tugas' },
                    { label: 'Bagian', value: this.parts.length > 1 ? `${this.bagian} dari ${this.parts.length}` : null },
                    // Ujian praktik berwaktu: dua durasi (pengerjaan, lalu unggah)
                    { label: durasi ? 'Waktu Pengerjaan' : 'Durasi', value: this.tugas.praktik ? (durasi ? `${durasi} menit` : 'Tanpa batas waktu') : null },
                    { label: 'Waktu Unggah', value: this.tugas.praktik && durasi && this.timer.waktu_unggah ? `${this.timer.waktu_unggah} menit (setelah waktu pengerjaan habis)` : null },
                ];
            },
            bagianQuery() {
                return this.bagian > 1 ? `?bagian=${this.bagian}` : '';
            },
            // Bagian berikutnya yang belum selesai (untuk tombol "Lanjut ke Bagian N")
            nextPart() {
                return this.parts.find((p) => p.bagian > this.bagian && !p.done) ?? null;
            },
            showUploadForm() {
                if (!this.tugas.praktik || !this.timer.timed) return true;
                return this.timer.started && !this.closed;
            },
        },

        // Inertia memakai ulang komponen ini saat pindah bagian atau setelah menekan Mulai (komponen halaman sama), jadi
        // mounted() tidak jalan lagi: timer & status unggahan disetel ulang setiap kali props dari server berganti.
        watch: {
            timer() {
                this.initTimer();
            },
            existing_file(file) {
                this.uploadedFile = file;
                this.selectedFile = null;
                this.successMsg = this.errorMsg = '';
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            },
        },

        mounted() {
            this.initTimer();
        },

        beforeUnmount() {
            if (this.tick) clearInterval(this.tick);
        },

        methods: {
            initTimer() {
                if (this.tick) clearInterval(this.tick);
                this.tick = null;
                this.remaining = this.timer.remaining_seconds ?? 0;
                this.uploadRemaining = this.timer.upload_remaining_seconds ?? 0;
                this.expired = !!this.timer.expired;
                this.closed = !!this.timer.closed;

                if (!(this.timer.timed && this.timer.started && !this.closed)) return;

                // Sisa waktu dari server, dihitung mundur dengan jam lokal
                const workEndsAt = Date.now() + this.remaining * 1000;
                const uploadEndsAt = Date.now() + this.uploadRemaining * 1000;
                this.tick = setInterval(() => {
                    this.remaining = Math.max(0, Math.round((workEndsAt - Date.now()) / 1000));
                    this.uploadRemaining = Math.max(0, Math.round((uploadEndsAt - Date.now()) / 1000));
                    if (this.remaining <= 0) this.expired = true;     // template ditutup, masih bisa unggah
                    if (this.uploadRemaining <= 0) {
                        clearInterval(this.tick);
                        // Unggahan yang sedang berjalan dibiarkan selesai.
                        if (!this.uploading) this.closed = true;
                    }
                }, 1000);
            },

            // Tanggal dari server (ISO UTC, mis. 2026-11-23T02:01:31.000000Z) → waktu lokal perangkat peserta, senada TugasPreview
            formatDateTime(value) {
                const d = value ? new Date(value) : null;
                return d && !isNaN(d) ? d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '';
            },
            formatSeconds(seconds) {
                const s = Math.max(0, Math.round(seconds));
                const h = Math.floor(s / 3600);
                const m = Math.floor((s % 3600) / 60);
                const pad = (n) => String(n).padStart(2, '0');
                return (h > 0 ? `${pad(h)}:` : '') + `${pad(m)}:${pad(s % 60)}`;
            },

            start() {
                const nama = this.parts.length > 1 ? `Bagian ${this.bagian}` : 'Ujian Praktik/Keterampilan';
                const tutup = this.closesText ? `\n\n${this.closesText} akan ditutup: hasilnya tidak dapat diunggah atau diganti lagi.` : '';
                if (!confirm(`Mulai ${nama} sekarang? Waktu pengerjaan ${this.timer.durasi} menit langsung berjalan dan tidak dapat diulang.${tutup}`)) return;
                this.starting = true;
                router.post(`/student/tugas/${this.exam_session.id}/mulai`, { bagian: this.bagian }, {
                    onFinish: () => { this.starting = false; },
                });
            },

            onFileChange(e) {
                const file = e.target.files[0] ?? null;
                this.errorMsg = this.successMsg = '';
                // Cek ukuran di browser dulu: file di atas batas server ditolak PHP setelah menunggu unggahan selesai
                if (file && file.size > this.file_max_mb * 1024 * 1024) {
                    this.errorMsg = `Ukuran file ${(file.size / 1048576).toFixed(1)} MB melebihi batas ${this.file_max_mb} MB. Perkecil/kompres file (mis. ZIP), atau hubungi panitia.`;
                    this.selectedFile = null;
                    e.target.value = '';
                    return;
                }
                this.selectedFile = file;
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
                    this.uploadedFile = { name: this.selectedFile.name, uploaded_at: new Date().toISOString() };
                    this.selectedFile = null;
                    if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                }).catch((err) => {
                    this.errorMsg = err.response?.status === 413
                        ? `File melebihi batas unggah server (maks. ${this.file_max_mb} MB). Perkecil/kompres file, atau hubungi panitia.`
                        : (err.response?.data?.errors?.file?.[0] ?? err.response?.data?.message ?? 'Gagal mengunggah tugas.');
                }).finally(() => {
                    this.uploading = false;
                    if (this.timer.timed && this.timer.started && this.uploadRemaining <= 0) this.closed = true;
                });
            },
        },

    }
</script>

<style>
</style>
