<template>
    <Head>
        <title>Tipe Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <ListToolbar v-model="search" @search="handleSearch">
            <template #actions>
                    <Link href="/admin/lessons/create" class="btn btn-primary border-0 shadow"><i class="fa fa-plus-circle me-1"></i> Tambah</Link>
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
                                        <th class="border-0">Nama Tipe Ujian</th>
                                        <th class="border-0 rounded-end" style="width:15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(lesson, index) in lessons.data" :key="index">
                                        <td class="fw-bold text-center">{{ ++index + (lessons.current_page - 1) * lessons.per_page }}</td>
                                        <td>{{ lesson.title }}</td>
                                        <td class="text-center">
                                            <Link :href="`/admin/lessons/${lesson.id}/edit`" class="btn btn-sm btn-info border-0 shadow me-2" type="button"><i class="fa fa-pencil-alt"></i></Link>
                                            <button @click.prevent="destroy(lesson.id)" class="btn btn-sm btn-danger border-0"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="lessons.links" align="end" />
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
            lessons: Object,
        },

        //inisialisasi composition API
        setup() {

            //define state search
            const search = ref('' || (new URL(document.location)).searchParams.get('q'));

            //define method search
            const handleSearch = () => {
                router.get('/admin/lessons', {

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

                        router.delete(`/admin/lessons/${id}`);

                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Pelajaran Berhasil Dihapus!.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    }
                })
            }

            //return
            return {
                search,
                handleSearch,
                destroy
            }

        }
    }

</script>

<style>

</style>