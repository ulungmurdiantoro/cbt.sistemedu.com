<template>
    <Head>
        <title>Sesi Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-3 col-12 mb-2">
                        <Link href="/admin/exam_sessions/create" class="btn btn-md btn-primary border-0 shadow w-100" type="button">
                            <i class="fa fa-plus-circle"></i> Tambah
                        </Link>
                    </div>
                    <div class="col-md-9 col-12 mb-2">
                        <form @submit.prevent="handleSearch">
                            <div class="input-group">
                                <input type="text" class="form-control border-0 shadow" v-model="search" placeholder="masukkan kata kunci...">
                                <span class="input-group-text border-0 shadow"><i class="fa fa-search"></i></span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-1">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-centered mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start" style="width:4%">No.</th>
                                        <th class="border-0">Sesi Ujian</th>
                                        <th class="border-0">Jenis Ujian</th>
                                        <th class="border-0" style="width:8%">Peserta</th>
                                        <th class="border-0">Waktu</th>
                                        <th class="border-0 rounded-end text-center" style="width:13%">Aksi</th>
                                    </tr>
                                </thead>
                                <div class="mt-2"></div>
                                <tbody>
                                    <template v-for="(s, index) in exam_sessions.data" :key="s.id">
                                        <!-- Pemisah: baris pertama yang sudah selesai -->
                                        <tr v-if="isFirstFinished(index)" class="separator-row">
                                            <td colspan="6" class="py-1 px-3 text-muted small fw-semibold border-0" style="background:#f8f9fa;border-top:2px dashed #dee2e6 !important;">
                                                <i class="fa fa-check-circle me-1 text-secondary"></i> Sesi Selesai
                                            </td>
                                        </tr>

                                        <tr :class="isActive(s) ? 'table-active-session' : 'table-finished-session'">
                                            <td class="fw-bold text-center">{{ index + 1 + (exam_sessions.current_page - 1) * exam_sessions.per_page }}</td>
                                            <td>
                                                <div class="d-flex align-items-start gap-2">
                                                    <StatusBadge v-if="isActive(s)" tone="accent" label="Aktif" class="mt-1 flex-shrink-0" />
                                                    <StatusBadge v-else tone="success" label="Selesai" class="mt-1 flex-shrink-0" />
                                                    <div>
                                                        <strong>{{ s.title }}</strong>
                                                        <div class="text-muted small">Batch {{ s.kode_batch }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="max-width:240px">
                                                <ul class="list-unstyled mb-0 small">
                                                    <li v-if="s.exam_pg" class="text-truncate" :title="s.exam_pg.title">
                                                        <span class="badge bg-primary me-1">PG</span>{{ s.exam_pg.title }}
                                                    </li>
                                                    <li v-if="s.exam_esai" class="text-truncate" :title="s.exam_esai.title">
                                                        <span class="badge bg-success me-1">Esai</span>{{ s.exam_esai.title }}
                                                    </li>
                                                    <li v-if="s.has_wawancara">
                                                        <span class="badge bg-warning text-dark me-1">Wawancara</span>
                                                    </li>
                                                    <li v-if="!s.exam_pg && !s.exam_esai && !s.has_wawancara" class="text-muted">—</li>
                                                </ul>
                                            </td>
                                            <td class="text-center">{{ s.students_count }}</td>
                                            <td class="small text-nowrap">
                                                <div><span class="text-muted">Mulai:</span> {{ formatDate(s.start_time) }}</div>
                                                <div><span class="text-muted">Selesai:</span> {{ formatDate(s.end_time) }}</div>
                                            </td>
                                            <td class="text-center">
                                                <Link :href="`/admin/exam_sessions/${s.id}`" class="btn btn-sm btn-primary border-0 shadow me-1" title="Enroll Siswa"><i class="fa fa-plus-circle"></i></Link>
                                                <Link :href="`/admin/exam_sessions/${s.id}/edit`" class="btn btn-sm btn-info border-0 shadow me-1"><i class="fa fa-pencil-alt"></i></Link>
                                                <button @click.prevent="destroy(s.id)" class="btn btn-sm btn-danger border-0"><i class="fa fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    </template>

                                    <tr v-if="exam_sessions.data.length === 0">
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="fa fa-stopwatch fa-2x d-block mb-2 text-gray-300"></i>
                                            <strong class="d-block">Belum ada sesi ujian</strong>
                                            <span class="small">Tidak ada sesi yang cocok dengan pencarian Anda.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="exam_sessions.links" align="end" :total="exam_sessions.total" :from="exam_sessions.from" :to="exam_sessions.to" entity="sesi" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination, StatusBadge },
    props: {
        exam_sessions: Object,
    },

    setup(props) {
        const search = ref('' || (new URL(document.location)).searchParams.get('q'));

        const now = new Date();

        const isActive = (s) => new Date(s.end_time) > now;

        // Tampilkan pemisah di baris index pertama yang sudah selesai
        const isFirstFinished = (index) => {
            const data = props.exam_sessions.data;
            if (index === 0) return false;
            return !isActive(data[index]) && isActive(data[index - 1]);
        };

        const handleSearch = () => {
            router.get('/admin/exam_sessions', { q: search.value });
        };

        const formatDate = (dt) => dt
            ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
            : '—';

        const destroy = (id) => {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Anda tidak akan dapat mengembalikan ini!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.delete(`/admin/exam_sessions/${id}`);
                    Swal.fire({ title: 'Deleted!', text: 'Sesi Ujian Berhasil Dihapus!', icon: 'success', timer: 2000, showConfirmButton: false });
                }
            });
        };

        return { search, handleSearch, destroy, isActive, isFirstFinished, formatDate };
    },
}
</script>

<style scoped>
.table-active-session td {
    background-color: #fff;
}
.table-finished-session td {
    background-color: #f8f9fa;
    color: #6c757d;
    opacity: 0.85;
}
.table-finished-session .badge {
    opacity: 0.8;
}
</style>
