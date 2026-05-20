<template>
    <Head>
        <title>Kelola User - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row mb-3">
            <div class="col-md-6">
                <Link href="/admin/users/create" class="btn btn-primary border-0 shadow">
                    <i class="fa fa-plus-circle me-1"></i> Tambah User
                </Link>
            </div>
        </div>

        <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
            {{ $page.props.session.success }}
        </div>

        <div class="card border-0 shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-centered mb-0 rounded">
                        <thead class="thead-dark">
                            <tr class="border-0">
                                <th class="border-0 rounded-start" style="width:4%">No.</th>
                                <th class="border-0">Kode</th>
                                <th class="border-0">Nama</th>
                                <th class="border-0">Email</th>
                                <th class="border-0" style="width:10%">Role</th>
                                <th class="border-0 rounded-end" style="width:12%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(user, index) in users.data" :key="user.id">
                                <td class="fw-bold text-center">
                                    {{ index + 1 + (users.current_page - 1) * users.per_page }}
                                </td>
                                <td class="small text-muted">{{ user.users_code }}</td>
                                <td>
                                    <span class="fw-semibold">{{ user.name }}</span>
                                    <span v-if="user.id === $page.props.auth.user.id" class="badge bg-info ms-1 small">Anda</span>
                                </td>
                                <td class="small">{{ user.email }}</td>
                                <td class="text-center">
                                    <span :class="user.role === 'admin' ? 'badge bg-primary' : 'badge bg-success'">
                                        {{ user.role === 'admin' ? 'Admin' : 'Asesor' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <Link :href="`/admin/users/${user.id}/edit`" class="btn btn-sm btn-info border-0 shadow me-1">
                                        <i class="fa fa-pencil-alt"></i>
                                    </Link>
                                    <button
                                        v-if="user.id !== $page.props.auth.user.id"
                                        @click="destroy(user)"
                                        class="btn btn-sm btn-danger border-0">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="text-center text-muted py-4">Belum ada user.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="users.links" align="end" />
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
        users: Object,
    },

    setup() {
        const destroy = (user) => {
            Swal.fire({
                title: 'Hapus user?',
                text: `"${user.name}" akan dihapus permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.delete(`/admin/users/${user.id}`);
                }
            });
        };

        return { destroy };
    },
}
</script>
