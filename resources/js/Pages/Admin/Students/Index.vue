<template>
    <Head>
        <title>Peserta - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <ListToolbar v-model="search" @search="handleSearch">
            <template #actions>
                    <Link href="/admin/students/create" class="btn btn-primary border-0 shadow"><i class="fa fa-plus-circle me-1"></i> Tambah</Link>
                    <Link href="/admin/students/import" class="btn btn-success border-0 shadow text-white"><i class="fa fa-file-excel me-1"></i> Import</Link>
                    <a :href="exportUrl" class="btn btn-info border-0 shadow" title="Download Excel peserta yang pernah mengikuti sesi sertifikasi (ikut kata kunci pencarian)"><i class="fa fa-download me-1"></i> Export</a>
            </template>
        </ListToolbar>

        <div class="row mt-1">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">

                        <div class="table-responsive">
                            <table class="table table-bordered table-centered table-list mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start" style="width:5%">No.</th>
                                        <th class="border-0">No. Peserta</th>
                                        <th class="border-0">Nama</th>
                                        <th class="border-0">Skema</th>
                                        <th class="border-0">Jenis Kelamin</th>
                                        <th class="border-0">Jabatan</th>
                                        <th class="border-0">Asal Institusi</th>
                                        <th class="border-0 rounded-end" style="width:15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(student, index) in students.data" :key="index">
                                        <td class="fw-bold text-center">
                                            {{ ++index + (students.current_page - 1) * students.per_page }}</td>
                                        <td>{{ student.no_participant }}</td>
                                        <td class="cell-wrap">{{ student.name }}</td>
                                        <td class="text-center"><StatusBadge tone="accent" :label="student.classroom?.title" /></td>
                                        <td class="text-center">{{ student.gender }}</td>
                                        <td>{{ student.position }}</td>
                                        <td class="cell-wrap">{{ student.institution }}</td>
                                        <td class="text-center">
                                            <Link :href="`/admin/students/${student.id}/edit`" class="btn btn-sm btn-info border-0 shadow me-2" type="button"><i class="fa fa-pencil-alt"></i></Link>
                                            <button @click.prevent="destroy(student.id)" class="btn btn-sm btn-danger border-0"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr v-if="students.data.length === 0">
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="fa fa-users fa-2x d-block mb-2 text-gray-300"></i>
                                            <strong class="d-block">Belum ada peserta</strong>
                                            <span class="small">Tidak ada peserta yang cocok dengan pencarian Anda.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="students.links" align="end" :total="students.total" :from="students.from" :to="students.to" entity="peserta" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    //import layout
    import LayoutAdmin from '../../../Layouts/Admin.vue';

    //import component pagination
    import Pagination from '../../../Components/Pagination.vue';
    import ListToolbar from '../../../Components/Admin/ListToolbar.vue';

    //import status badge component
    import StatusBadge from '../../../Components/StatusBadge.vue';

    //import Heade and Link from Inertia
    import {
        Head,
        Link,
        router
    } from '@inertiajs/vue3';

    //import ref from vue
    import {
        ref
    } from 'vue';

    //import sweet alert2
    import Swal from 'sweetalert2';

    export default {
        //layout
        layout: LayoutAdmin,

        //register component
        components: {
            ListToolbar,
            Head,
            Link,
            Pagination,
            StatusBadge
        },

        //props
        props: {
            students: Object,
        },

        //inisialisasi composition API
        setup() {

            //define state search
            const search = ref('' || (new URL(document.location)).searchParams.get('q'));

            //define method search
            const handleSearch = () => {
                router.get('/admin/students', {

                    //send params "q" with value from state "search"
                    q: search.value,
                });
            }

            //define method destroy
            const destroy = (id) => {
                Swal.fire({
                        title: 'Apakah Anda yakin?',
                        text: "Anda tidak akan dapat mengembalikan ini!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    })
                    .then((result) => {
                        if (result.isConfirmed) {

                            router.delete(`/admin/students/${id}`, {
                                onSuccess: () => Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Peserta Berhasil Dihapus!.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false,
                                }),
                                onError: (e) => Swal.fire({ title: 'Tidak bisa dihapus', text: e.delete ?? 'Gagal menghapus peserta.', icon: 'error' }),
                            });
                        }
                    })
            }

            // export memakai kata kunci yang sedang diterapkan di URL (bukan yang baru diketik)
            const appliedSearch = (new URL(document.location)).searchParams.get('q');
            const exportUrl = '/admin/students/export' + (appliedSearch ? `?q=${encodeURIComponent(appliedSearch)}` : '');

            //return
            return {
                search,
                exportUrl,
                handleSearch,
                destroy,
            }

        }
    }

</script>

<style>

</style>