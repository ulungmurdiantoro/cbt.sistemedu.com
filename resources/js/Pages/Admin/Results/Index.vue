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
                                <th class="border-0 text-center" style="width:8%">Peserta</th>
                                <th class="border-0 text-center" style="width:9%">Status</th>
                                <th class="border-0 text-center" style="width:11%">Peninjauan</th>
                                <th class="border-0 text-center" style="width:11%">SP</th>
                                <th class="border-0 text-center" style="width:11%">SK &amp; Sertifikat</th>
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
                                    <StatusBadge v-if="new Date(s.end_time) > new Date()" tone="accent" label="Aktif" />
                                    <StatusBadge v-else tone="success" label="Selesai" />
                                </td>
                                <td class="text-center">
                                    <StatusBadge v-bind="stageBadge(s.reviewed_count, s.participant_results_count)" />
                                </td>
                                <td class="text-center">
                                    <StatusBadge v-bind="stageBadge(s.sp_sent_count, s.participant_results_count)" />
                                </td>
                                <td class="text-center">
                                    <StatusBadge v-bind="stageBadge(s.final_sent_count, s.participant_results_count)" />
                                </td>
                                <td class="text-center">
                                    <Link :href="`/admin/results/${s.id}`" class="btn btn-sm btn-primary border-0 shadow">
                                        <i class="fa fa-chart-bar me-1"></i> Rekap
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="sessions.data.length === 0">
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="fa fa-award fa-2x d-block mb-2 text-gray-300"></i>
                                    <strong class="d-block">Belum ada hasil penilaian</strong>
                                    <span class="small">Rekap hasil akan muncul setelah sesi ujian berjalan.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="sessions.links" align="end" :total="sessions.total" :from="sessions.from" :to="sessions.to" entity="sesi" />
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination, StatusBadge },
    props: { sessions: Object },
    methods: {
        // Belum/Sebagian/Sudah — dipakai untuk kolom Peninjauan, SP, dan SK & Sertifikat.
        stageBadge(done, total) {
            if (!total || done === 0) return { tone: 'secondary', label: 'Belum' };
            if (done < total) return { tone: 'accent', label: `Sebagian (${done}/${total})` };
            return { tone: 'success', label: 'Sudah' };
        },
    },
}
</script>
