<template>
    <Head>
        <title>Detail Sesi Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link href="/admin/exam_sessions" class="btn btn-md btn-primary border-0 shadow mb-3" type="button">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <!-- Detail sesi -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-stopwatch"></i> Detail Sesi Ujian</h5>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0 rounded">
                                <tbody>
                                    <tr>
                                        <td style="width:30%" class="fw-bold">Nama Sesi</td>
                                        <td>{{ exam_session.title }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Kode Batch</td>
                                        <td>{{ exam_session.kode_batch }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Ujian Pilihan Ganda</td>
                                        <td>
                                            <template v-if="exam_session.exam_pg">
                                                {{ exam_session.exam_pg.title }}
                                                <span class="text-muted small ms-2">({{ exam_session.exam_pg.classroom?.title }})</span>
                                            </template>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Ujian Esai</td>
                                        <td>
                                            <template v-if="exam_session.exam_esai">
                                                {{ exam_session.exam_esai.title }}
                                                <span class="text-muted small ms-2">({{ exam_session.exam_esai.classroom?.title }})</span>
                                            </template>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Ujian Wawancara</td>
                                        <td>
                                            <span v-if="exam_session.has_wawancara" class="badge bg-warning text-dark">Aktif</span>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Mulai</td>
                                        <td>{{ exam_session.start_time }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Selesai</td>
                                        <td>{{ exam_session.end_time }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Enrolled Students -->
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-user-plus"></i> Enrolled Siswa</h5>
                        <hr>

                        <Link :href="`/admin/exam_sessions/${exam_session.id}/enrolle/create`" class="btn btn-md btn-primary border-0 shadow me-2" type="button">
                            <i class="fa fa-user-plus"></i> Enroll Siswa
                        </Link>

                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start" style="width:5%">No.</th>
                                        <th class="border-0">Nama Siswa</th>
                                        <th class="border-0">Skema</th>
                                        <th class="border-0">Jenis Kelamin</th>
                                        <th class="border-0 rounded-end" style="width:15%">Aksi</th>
                                    </tr>
                                </thead>
                                <div class="mt-2"></div>
                                <tbody>
                                    <tr v-for="(student, index) in students.data" :key="student.id">
                                        <td class="fw-bold text-center">{{ ++index + (students.current_page - 1) * students.per_page }}</td>
                                        <td>{{ student.name }}</td>
                                        <td class="text-center">{{ student.classroom?.title }}</td>
                                        <td class="text-center">{{ student.gender }}</td>
                                        <td class="text-center">
                                            <button @click.prevent="destroy(student.id)" class="btn btn-sm btn-danger border-0">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="students.data.length === 0">
                                        <td colspan="5" class="text-center text-muted py-3">Belum ada siswa yang di-enroll</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="students.links" align="end" />
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: {
        errors:       Object,
        exam_session: Object,
        students:     Object,
    },

    setup(props) {
        const destroy = (student_id) => {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Siswa akan dihapus dari semua ujian di sesi ini!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.delete(`/admin/exam_sessions/${props.exam_session.id}/enrolle/${student_id}/destroy`);
                    Swal.fire({ title: 'Deleted!', text: 'Siswa Berhasil Dihapus!', icon: 'success', timer: 2000, showConfirmButton: false });
                }
            });
        };

        return { destroy };
    },
}
</script>
