<template>
    <Head>
        <title>Permohonan Sertifikasi - Admin</title>
    </Head>

    <div class="row mb-3">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Permohonan Sertifikasi</h5>
            <span class="badge bg-primary fs-6">{{ applications.total }} permohonan</span>
        </div>
    </div>

    <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
        {{ $page.props.session.success }}
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow mb-4">
        <div class="card-body py-3">
            <form @submit.prevent="applyFilter" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm" v-model="filterForm.q" placeholder="Cari nama / email peserta...">
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" v-model="filterForm.status">
                        <option value="">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Disubmit</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-sm btn-gray-800">Filter</button>
                    <button type="button" class="btn btn-sm btn-light border ms-1" @click="resetFilter">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="bg-gray-100">
                    <tr>
                        <th>Peserta</th>
                        <th>Skema</th>
                        <th>Sesi</th>
                        <th>Tgl Submit</th>
                        <th style="width:110px">Status</th>
                        <th style="width:80px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="applications.data.length === 0">
                        <td colspan="6" class="text-center text-muted py-4">Tidak ada data</td>
                    </tr>
                    <tr v-for="app in applications.data" :key="app.id">
                        <td class="align-middle">
                            <div class="fw-semibold small">{{ app.participant?.name }}</div>
                            <div class="text-muted" style="font-size:0.78rem">{{ app.participant?.email }}</div>
                        </td>
                        <td class="align-middle small">{{ app.classroom?.title }}</td>
                        <td class="align-middle small">{{ app.exam_session?.title }}</td>
                        <td class="align-middle small">{{ app.submitted_at ? formatDate(app.submitted_at) : '—' }}</td>
                        <td class="align-middle">
                            <span :class="statusBadge(app.status)" class="badge">{{ statusLabel(app.status) }}</span>
                        </td>
                        <td class="align-middle">
                            <Link :href="`/admin/applications/${app.id}`" class="btn btn-sm btn-info">
                                <i class="fa fa-eye"></i>
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-3" v-if="applications.last_page > 1">
        <nav>
            <ul class="pagination pagination-sm">
                <li class="page-item" v-for="link in applications.links" :key="link.label"
                    :class="{ active: link.active, disabled: !link.url }">
                    <Link class="page-link" :href="link.url ?? '#'" v-html="link.label"></Link>
                </li>
            </ul>
        </nav>
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
        applications: Object,
        filters:      Object,
    },

    setup(props) {
        const filterForm = reactive({
            q:      props.filters?.q ?? '',
            status: props.filters?.status ?? '',
        });

        const applyFilter = () => {
            router.get('/admin/applications', filterForm, { preserveState: true });
        };

        const resetFilter = () => {
            filterForm.q = ''; filterForm.status = '';
            router.get('/admin/applications');
        };

        const formatDate = (dt) => new Date(dt).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });

        const statusLabel = (s) => ({ draft:'Draft', submitted:'Disubmit', approved:'Disetujui', rejected:'Ditolak' }[s] ?? s);
        const statusBadge = (s) => ({ draft:'bg-secondary', submitted:'bg-warning text-dark', approved:'bg-success', rejected:'bg-danger' }[s] ?? 'bg-secondary');

        return { filterForm, applyFilter, resetFilter, formatDate, statusLabel, statusBadge };
    },
}
</script>
