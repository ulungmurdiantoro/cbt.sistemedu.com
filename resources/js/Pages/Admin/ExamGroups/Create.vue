<template>
    <Head>
        <title>Enrolle Peserta - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link :href="`/admin/exam_sessions/${exam_session.id}`" class="btn btn-md btn-primary border-0 shadow mb-3" type="button">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-user-plus"></i> Enroll Peserta</h5>
                        <p class="text-muted small mb-3">
                            Siswa yang dipilih akan didaftarkan ke semua jenis ujian yang aktif di sesi ini
                            (Pilihan Ganda, Esai, dan/atau Wawancara).
                        </p>
                        <hr>
                        <form @submit.prevent="submit">

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                    <thead class="thead-dark">
                                        <tr class="border-0">
                                            <th class="border-0 rounded-start" style="width:5%">
                                                <input type="checkbox" v-model="form.allSelected" @change="selectAll" />
                                            </th>
                                            <th class="border-0">Nama Peserta</th>
                                            <th class="border-0">Skema</th>
                                            <th class="border-0">Jenis Kelamin</th>
                                        </tr>
                                    </thead>
                                    <div class="mt-3"></div>
                                    <tbody>
                                        <tr v-if="students.length === 0">
                                            <td colspan="4" class="text-center text-muted py-3">Semua siswa sudah di-enroll atau tidak ada siswa di skema ini</td>
                                        </tr>
                                        <tr v-for="student of students" :key="student.id">
                                            <td>
                                                <input type="checkbox" v-model="form.student_id" :id="student.id" :value="student.id" number :checked="form.allSelected" />
                                            </td>
                                            <td>{{ student.name }}</td>
                                            <td class="text-center">{{ student.classroom.title }}</td>
                                            <td class="text-center">{{ student.gender }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div v-if="errors.student_id" class="alert alert-danger mt-2">{{ errors.student_id }}</div>
                            </div>

                            <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2" :disabled="form.student_id.length === 0">
                                Simpan ({{ form.student_id.length }} dipilih)
                            </button>
                            <button type="reset" class="btn btn-md btn-warning border-0 shadow" @click="resetForm">Reset</button>
                        </form>
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
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        errors:       Object,
        exam_session: Object,
        students:     Array,
    },

    setup(props) {
        const form = reactive({
            student_id:  [],
            allSelected: false,
        });

        const selectAll = () => {
            form.student_id = form.allSelected ? props.students.map(s => s.id) : [];
        };

        const resetForm = () => {
            form.student_id  = [];
            form.allSelected = false;
        };

        const submit = () => {
            router.post(`/admin/exam_sessions/${props.exam_session.id}/enrolle/store`, {
                student_id: form.student_id,
            }, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Enrolle Peserta Berhasil Disimpan!',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                },
            });
        };

        return { form, selectAll, resetForm, submit };
    },
}
</script>
