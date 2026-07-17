<template>
    <Head>
        <title>Permohonan Sertifikasi - Admin</title>
    </Head>

    <div class="container-fluid mb-5 mt-5">
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
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width:700px">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width:200px">Peserta</th>
                            <th>Skema / Sesi</th>
                            <th style="width:110px">Tgl Submit</th>
                            <th style="width:110px">Status</th>
                            <th style="width:70px" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="applications.data.length === 0">
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fa fa-inbox fa-2x d-block mb-2 text-gray-300"></i>
                                <strong class="d-block">Tidak ada permohonan</strong>
                                <span class="small">Belum ada permohonan sertifikasi yang cocok dengan filter Anda.</span>
                            </td>
                        </tr>
                        <tr v-for="app in applications.data" :key="app.id">
                            <td>
                                <div class="fw-semibold small">{{ app.participant?.name }}</div>
                                <div class="text-muted" style="font-size:0.78rem">{{ app.participant?.email }}</div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-truncate" style="max-width:340px" :title="app.classroom?.title">
                                    {{ app.classroom?.title ?? '—' }}
                                </div>
                                <div class="text-muted text-truncate" style="font-size:0.78rem; max-width:340px" :title="app.exam_session?.title">
                                    <i class="fa fa-calendar-alt me-1"></i>{{ app.exam_session?.title ?? '—' }}
                                </div>
                                <div class="text-muted" style="font-size:0.75rem">
                                    <i class="fa fa-hashtag me-1"></i>Batch: {{ app.exam_session?.kode_batch ?? app.kode_batch ?? '—' }}
                                </div>
                            </td>
                            <td class="small">{{ app.submitted_at ? formatDate(app.submitted_at) : '—' }}</td>
                            <td>
                                <StatusBadge :tone="statusTone(app.status)" :label="statusLabel(app.status)" />
                                <div v-if="app.rejected_documents_count > 0" class="mt-1">
                                    <span class="badge bg-danger" style="font-size:0.68rem" title="Ada dokumen yang ditolak, permohonan belum ada keputusan akhir">
                                        <i class="fa fa-exclamation-triangle me-1"></i>{{ app.rejected_documents_count }} dokumen ditolak
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <Link :href="`/admin/applications/${app.id}`" class="btn btn-sm btn-info">
                                    <i class="fa fa-eye"></i>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <Pagination :links="applications.links" align="end" :total="applications.total" :from="applications.from" :to="applications.to" entity="permohonan" />
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import Pagination from '../../../Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, StatusBadge, Pagination },
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
        // tone badge senada dengan Blueprint §4
        const statusTone = (s) => ({ draft:'neutral', submitted:'secondary', approved:'success', rejected:'danger' }[s] ?? 'neutral');

        return { filterForm, applyFilter, resetFilter, formatDate, statusLabel, statusTone };
    },
}
</script>
