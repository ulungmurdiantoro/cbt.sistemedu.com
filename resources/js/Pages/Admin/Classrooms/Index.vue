<template>
    <Head>
        <title>Skema - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <ListToolbar v-model="search" @search="handleSearch">
            <template #actions>
                    <Link href="/admin/classrooms/create" class="btn btn-primary border-0 shadow"><i class="fa fa-plus-circle me-1"></i> Tambah</Link>
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
                                        <th class="border-0">Nama Skema</th>
                                        <th class="border-0">ID (Kode Skema)</th>
                                        <th class="border-0 rounded-end" style="width:20%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(classroom, index) in classrooms.data" :key="index">
                                        <td class="fw-bold text-center">{{ ++index + (classrooms.current_page - 1) * classrooms.per_page }}</td>
                                        <td class="cell-wrap">
                                            <div class="fw-bolder">{{ classroom.title }}</div>
                                            <div v-if="classroom.title_en" class="text-muted fst-italic small">{{ classroom.title_en }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-bolder">{{ classroom.id }} </div>
                                            <div v-if="classroom.kode_skema" class="text-muted fst-italic small">({{ classroom.kode_skema }})</div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-nowrap gap-1 justify-content-center">
                                                <Link :href="`/admin/classrooms/${classroom.id}/requirements`" class="btn btn-sm btn-secondary border-0 shadow" title="Persyaratan Dokumen"><i class="fa fa-file-alt"></i></Link>
                                                <Link :href="`/admin/classrooms/${classroom.id}/competency-units`" class="btn btn-sm btn-success border-0 shadow" title="Unit Kompetensi"><i class="fa fa-list-ol"></i></Link>
                                                <Link :href="`/admin/classrooms/${classroom.id}/grading-scheme`" class="btn btn-sm btn-warning border-0 shadow" title="Bobot Penilaian"><i class="fa fa-percent"></i></Link>
                                                <Link :href="`/admin/classrooms/${classroom.classrooms_code}/edit`" class="btn btn-sm btn-info border-0 shadow" type="button" title="Edit"><i class="fa fa-pencil-alt"></i></Link>
                                                <button @click.prevent="destroy(classroom.id)" class="btn btn-sm btn-danger border-0" title="Hapus"><i class="fa fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="classrooms.data.length === 0">
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="fa fa-layer-group fa-2x d-block mb-2 text-gray-300"></i>
                                            <strong class="d-block">Belum ada skema</strong>
                                            <span class="small">Tidak ada skema yang cocok dengan pencarian Anda.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="classrooms.links" align="end" :total="classrooms.total" :from="classrooms.from" :to="classrooms.to" entity="skema" />
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
            Pagination
        },

        //props
        props: {
            classrooms: Object,
        },

        //inisialisasi composition API
        setup() {

            //define state search
            const search = ref('' || (new URL(document.location)).searchParams.get('q'));

            //define method search
            const handleSearch = () => {
                router.get('/admin/classrooms', {

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

                        router.delete(`/admin/classrooms/${id}`, {
                            onSuccess: () => Swal.fire({
                                title: 'Deleted!',
                                text: 'Skema Berhasil Dihapus!.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                            }),
                            onError: (e) => Swal.fire({ title: 'Tidak bisa dihapus', text: e.delete ?? 'Gagal menghapus skema.', icon: 'error' }),
                        });
                    }
                })
            }

            //return
            return {
                search,
                handleSearch,
                destroy,
            }

        }
    }

</script>

<style>

</style>