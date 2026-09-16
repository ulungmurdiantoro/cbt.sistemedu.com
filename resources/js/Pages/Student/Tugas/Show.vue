<template>
    <Head>
        <title>Upload Tugas - Aplikasi Ujian Online</title>
    </Head>
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card border-0 shadow mb-4">
                <div class="card-body">
                    <h5><i class="fa fa-upload me-2"></i>Upload Tugas</h5>
                    <hr>

                    <table class="table table-bordered mb-3" style="max-width:500px">
                        <tbody>
                            <tr>
                                <td class="fw-bold" style="width:40%">Sesi</td>
                                <td>{{ exam_session.title }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-info border-0 shadow">
                        <i class="fa fa-info-circle me-2"></i>
                        Anda wajib mengunggah tugas sebelum dapat mengerjakan ujian pada sesi ini. Tugas ini akan diperiksa oleh asesor pada saat ujian wawancara.
                    </div>

                    <div v-if="uploadedFile" class="alert alert-success border-0 shadow">
                        <i class="fa fa-check-circle me-2"></i>
                        Tugas sudah diunggah: <strong>{{ uploadedFile.name }}</strong>
                        <div class="small text-muted" v-if="uploadedFile.uploaded_at">Diunggah pada {{ uploadedFile.uploaded_at }}</div>
                    </div>

                    <div v-if="successMsg" class="alert alert-success border-0 shadow">{{ successMsg }}</div>
                    <div v-if="errorMsg" class="alert alert-danger border-0 shadow">{{ errorMsg }}</div>

                    <form @submit.prevent="upload">
                        <div class="mb-3">
                            <input type="file" class="form-control" ref="fileInput" @change="onFileChange">
                        </div>

                        <div class="progress mb-3" v-if="uploading" style="height:20px">
                            <div class="progress-bar" role="progressbar" :style="{ width: progress + '%' }">{{ progress }}%</div>
                        </div>

                        <button type="submit" class="btn btn-success border-0 shadow" :disabled="!selectedFile || uploading">
                            {{ uploading ? 'Mengunggah...' : (uploadedFile ? 'Ganti Tugas' : 'Unggah Tugas') }}
                        </button>
                        <Link href="/student/dashboard" class="btn btn-secondary border-0 shadow ms-2">Kembali ke Dashboard</Link>
                    </form>
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
        Link
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
        },

        data() {
            return {
                selectedFile: null,
                uploading: false,
                progress: 0,
                successMsg: '',
                errorMsg: '',
                uploadedFile: this.existing_file,
            };
        },

        methods: {
            onFileChange(e) {
                this.selectedFile = e.target.files[0] ?? null;
            },

            upload() {
                if (!this.selectedFile) return;

                const formData = new FormData();
                formData.append('exam_session_id', this.exam_session.id);
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
                });
            },
        },

    }
</script>

<style>

</style>
