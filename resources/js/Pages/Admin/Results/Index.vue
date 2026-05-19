<template>
    <Head><title>Rekap Hasil - Aplikasi Ujian Online</title></Head>

    <div class="container-fluid mb-5 mt-5">
        <h5 class="fw-bold mb-4">Rekap Hasil Penilaian</h5>

        <div class="card border-0 shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-centered mb-0 rounded">
                        <thead class="thead-dark">
                            <tr>
                                <th class="border-0 rounded-start" style="width:4%">No.</th>
                                <th class="border-0">Sesi Ujian</th>
                                <th class="border-0">Kode Batch</th>
                                <th class="border-0 text-center" style="width:10%">Peserta</th>
                                <th class="border-0 text-center" style="width:10%">Status</th>
                                <th class="border-0 rounded-end" style="width:10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(s, i) in sessions.data" :key="s.id">
                                <td class="text-center fw-bold">{{ i + 1 + (sessions.current_page - 1) * sessions.per_page }}</td>
                                <td>
                                    <div class="fw-semibold">{{ s.title }}</div>
                                    <div class="text-muted small">{{ s.start_time }} – {{ s.end_time }}</div>
                                </td>
                                <td class="small">{{ s.kode_batch }}</td>
                                <td class="text-center">{{ s.participant_results_count }}</td>
                                <td class="text-center">
                                    <span v-if="new Date(s.end_time) > new Date()" class="badge bg-success">Aktif</span>
                                    <span v-else class="badge bg-secondary">Selesai</span>
                                </td>
                                <td class="text-center">
                                    <Link :href="`/admin/results/${s.id}`" class="btn btn-sm btn-primary border-0 shadow">
                                        <i class="fa fa-chart-bar me-1"></i> Rekap
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="sessions.links" align="end" />
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: { sessions: Object },
}
</script>
