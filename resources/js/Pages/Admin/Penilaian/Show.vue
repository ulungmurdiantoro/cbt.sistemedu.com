<template>
    <Head><title>Penugasan Asesor — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link href="/admin/penilaian" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-user-tie me-2"></i>Penugasan Asesor — {{ exam_session.title }}</h5>
                        <hr>
                        <table class="table table-bordered mb-0" style="max-width:500px">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" style="width:35%">Sesi</td>
                                    <td>{{ exam_session.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Ujian</td>
                                    <td>{{ exam_session.examPg?.title ?? exam_session.examEsai?.title ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Skema</td>
                                    <td>{{ exam_session.examPg?.classroom?.title ?? exam_session.examEsai?.classroom?.title ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="students.length === 0" class="alert alert-info">
                    Belum ada peserta yang terdaftar di sesi ini.
                </div>

                <div v-else class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0"><i class="fa fa-table me-2"></i>Penugasan Asesor per Peserta</h6>
                            <button @click="save" :disabled="saving" class="btn btn-success border-0 shadow">
                                <i class="fa fa-save me-1"></i>
                                {{ saving ? 'Menyimpan...' : 'Simpan Penugasan' }}
                            </button>
                        </div>

                        <div v-if="successMsg" class="alert alert-success alert-dismissible">
                            {{ successMsg }}
                            <button type="button" class="btn-close" @click="successMsg = ''"></button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width:5%">No</th>
                                        <th>No Peserta</th>
                                        <th>Nama Peserta</th>
                                        <th style="min-width:220px">Asesor yang Ditugaskan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in form" :key="row.student_id">
                                        <td>{{ i + 1 }}</td>
                                        <td>{{ students[i]?.no_participant }}</td>
                                        <td>{{ students[i]?.name }}</td>
                                        <td>
                                            <select v-model="row.user_id" class="form-select form-select-sm">
                                                <option :value="null">— Belum ditugaskan —</option>
                                                <option v-for="a in asesors" :key="a.id" :value="a.id">
                                                    {{ a.name }}
                                                </option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },

    props: {
        exam_session: Object,
        students: Array,
        asesors: Array,
        assignments: Array,
    },

    data() {
        // Index assignments by student_id for quick lookup
        const assignMap = {};
        this.assignments.forEach(a => { assignMap[a.student_id] = a.user_id; });

        return {
            saving: false,
            successMsg: '',
            form: this.students.map(s => ({
                student_id: s.id,
                user_id: assignMap[s.id] ?? null,
            })),
        };
    },

    methods: {
        save() {
            this.saving = true;
            router.post(
                `/admin/penilaian/${this.exam_session.id}/penugasan`,
                { assignments: this.form },
                {
                    onSuccess: () => { this.successMsg = 'Penugasan berhasil disimpan.'; },
                    onFinish:  () => { this.saving = false; },
                }
            );
        },
    },
}
</script>
