<template>
    <!-- Konfirmasi sebelum mulai ujian PG / Esai / Esai Migas — tampilan senada halaman Ujian Praktik/Keterampilan (Student/Tugas/Show) -->
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card border-0 shadow mb-4">
                <div class="card-body">
                    <h5><i class="fa fa-file-alt me-2"></i>{{ exam_group.exam.title }}</h5>
                    <hr>

                    <ExamInfoTable :rows="[
                        { label: 'Nama Lengkap', value: exam_group.student.name },
                        { label: 'No. Peserta', value: exam_group.student.no_participant },
                        { label: 'Skema', value: exam_group.student.classroom?.title },
                        { label: 'Sesi', value: exam_group.exam_session?.title },
                        { label: 'Ujian', value: exam_group.exam.title },
                        { label: 'Tipe Ujian', value: exam_group.exam.type },
                        { label: 'Durasi', value: `${exam_group.exam.duration} menit` },
                    ]" />

                    <div v-if="hasDescription" class="border rounded p-3 mb-3">
                        <h6 class="fw-bold mb-2">Deskripsi Ujian</h6>
                        <div class="small" v-html="exam_group.exam.description"></div>
                    </div>

                    <!-- Satu ujian berjalan per sesi: ujian lain harus diakhiri dulu -->
                    <template v-if="grade.end_time == null && grade.start_time == null && lock.blocked">
                        <div class="alert alert-warning border-0 shadow">
                            <i class="fa fa-lock me-2"></i>{{ lock.blocked }}
                        </div>
                    </template>
                    <template v-else-if="grade.end_time == null">
                        <div v-if="closesText" class="alert alert-warning border-0 shadow">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            Memulai ujian ini <strong>menutup {{ closesText }}</strong>: hasilnya tidak dapat diunggah atau diganti lagi.
                        </div>
                        <div class="alert alert-info border-0 shadow">
                            <i class="fa fa-stopwatch me-2"></i>
                            Ujian ini berdurasi <strong>{{ exam_group.exam.duration }} menit</strong>. Waktu mulai berjalan saat Anda menekan
                            <strong>Mulai Ujian</strong> dan tidak dapat diulang dari awal.
                            <template v-if="toleransi">Bila koneksi internet terputus, Anda mendapat toleransi paling lama <strong>{{ toleransi }} menit</strong>
                                di luar durasi ujian; setelah itu ujian ditutup.</template>
                        </div>
                        <Link :href="startUrl" class="btn btn-primary border-0 shadow">
                            <i class="fa fa-play me-2"></i>Mulai Ujian
                        </Link>
                    </template>
                    <div v-else class="alert alert-success border-0 shadow">
                        <i class="fa fa-check-circle me-2"></i>Anda sudah mengerjakan ujian ini.
                    </div>
                    <Link href="/student/dashboard" class="btn btn-secondary border-0 shadow" :class="{ 'ms-2': grade.end_time == null && !(grade.start_time == null && lock.blocked) }">Kembali ke Dashboard</Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { Link } from '@inertiajs/vue3';
    import ExamInfoTable from './ExamInfoTable.vue';

    export default {
        components: { Link, ExamInfoTable },

        props: {
            exam_group: Object,
            grade: Object,
            // /student/exam-start/{id}, /student/essay-start/{id}, /student/essay-migas-start/{id}
            startUrl: String,
            // { blocked, closes } — lihat BaseExamController::confirmationLock
            lock: { type: Object, default: () => ({ blocked: null, closes: [] }) },
        },

        computed: {
            closesText() {
                const parts = this.lock.closes ?? [];
                return parts.length ? parts.map((b) => `Ujian Praktik/Keterampilan Bagian ${b}`).join(' dan ') : '';
            },
            toleransi() {
                return this.$page.props.toleransi_koneksi_menit;
            },
            hasDescription() {
                const d = this.exam_group.exam.description ?? '';
                return d.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim() !== '';
            },
        },
    }
</script>
