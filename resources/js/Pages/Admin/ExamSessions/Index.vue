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
                        <SessionTable :sessions="exam_sessions.data"
                            :offset="(exam_sessions.current_page - 1) * exam_sessions.per_page">
                            <template #actions="{ session }">
                                <Link :href="`/admin/exam_sessions/${session.id}`" class="btn btn-sm btn-primary border-0 shadow me-1" title="Detail sesi & peserta"><i class="fa fa-folder-open"></i></Link>
                                <Link :href="`/admin/exam_sessions/${session.id}/edit`" class="btn btn-sm btn-info border-0 shadow me-1" title="Edit sesi"><i class="fa fa-pencil-alt"></i></Link>
                                <button @click.prevent="destroy(session.id)" class="btn btn-sm btn-danger border-0" title="Hapus sesi"><i class="fa fa-trash"></i></button>
                            </template>
                        </SessionTable>
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
import SessionTable from '../../../Components/SessionTable.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination, SessionTable },
    props: {
        exam_sessions: Object,
    },

    setup() {
        const search = ref('' || (new URL(document.location)).searchParams.get('q'));

        const handleSearch = () => {
            router.get('/admin/exam_sessions', { q: search.value });
        };

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
                    router.delete(`/admin/exam_sessions/${id}`, {
                        onSuccess: () => Swal.fire({ title: 'Deleted!', text: 'Sesi Ujian Berhasil Dihapus!', icon: 'success', timer: 2000, showConfirmButton: false }),
                        onError: (e) => Swal.fire({ title: 'Tidak bisa dihapus', text: e.delete ?? 'Gagal menghapus sesi ujian.', icon: 'error' }),
                    });
                }
            });
        };

        return { search, handleSearch, destroy };
    },
}
</script>

