<template>
    <Head>
        <title>Laporan Nilai Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-filter"></i> Filter Nilai Ujian</h5>
                        <hr>
                        <form @submit.prevent="filter">
                            <div class="row">
                                <div class="col-md-9">
                                    <label class="control-label" for="name">Sesi Ujian</label>
                                    <select class="form-select" v-model="form.exam_session_id">
                                        <option v-for="(session, index) in exam_sessions" :key="index" :value="session.id">
                                            {{ session.title }} — {{ session.exam.title }} / {{ session.exam.classroom.title }} ({{ session.exam.type }})
                                        </option>
                                    </select>
                                    <div v-if="errors.exam_session_id" class="alert alert-danger mt-2">
                                        {{ errors.exam_session_id }}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold text-white">*</label>
                                    <button type="submit" class="btn btn-md btn-primary border-0 shadow w-100">
                                        <i class="fa fa-filter"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div v-if="grades.length > 0" class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-9 col-12">
                                <h5 class="mt-2"><i class="fa fa-chart-line"></i> Laporan Nilai Ujian</h5>
                            </div>
                            <div class="col-md-3 col-12">
                                <a :href="`/admin/reports/export?exam_session_id=${form.exam_session_id}`" target="_blank" class="btn btn-success btn-md border-0 shadow w-100 text-white">
                                    <i class="fa fa-file-excel"></i> DOWNLOAD EXCEL
                                </a>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start" style="width:5%">No.</th>
                                        <th class="border-0">Ujian</th>
                                        <th class="border-0">Sesi</th>
                                        <th class="border-0">Nama Peserta</th>
                                        <th class="border-0">Skema</th>
                                        <th class="border-0">Tipe Ujian</th>
                                        <th class="border-0">Nilai</th>
                                        <th class="border-0 rounded-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(grade, index) in grades" :key="grade.id">
                                        <td class="fw-bold text-center">{{ index + 1 }}</td>
                                        <td>{{ grade.exam.title }}</td>
                                        <td>{{ grade.exam_session.title }}</td>
                                        <td>{{ grade.student.name }}</td>
                                        <td class="text-center">{{ grade.exam.classroom.title }}</td>
                                        <td>{{ grade.exam.type }}</td>
                                        <td class="fw-bold text-center">{{ grade.grade }}</td>
                                        <td class="text-center">
                                            <Link :href="`/admin/reports/${grade.id}`" class="btn btn-sm btn-primary border-0 shadow me-2" type="button">
                                                <i class="fa fa-plus-circle"></i>
                                            </Link>
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
import { reactive } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        errors: Object,
        exam_sessions: Array,
        grades: Array,
    },
    setup() {
        const form = reactive({
            exam_session_id: '' || (new URL(document.location)).searchParams.get('exam_session_id'),
        });

        const filter = () => {
            router.get('/admin/reports/filter', {
                exam_session_id: form.exam_session_id,
            });
        };

        return { form, filter };
    }
}
</script>

<style>
</style>