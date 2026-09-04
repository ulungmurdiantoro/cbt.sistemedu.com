<template>
    <Head><title>Dashboard Pengambil Keputusan</title></Head>

    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5 class="mb-1"><i class="fa fa-award me-2"></i>Dashboard Pengambil Keputusan</h5>
                        <p class="text-muted mb-0 small">
                            Tinjau kelengkapan dokumen, laporan asesmen, dan nilai peserta sebelum finalisasi kelulusan.
                        </p>
                    </div>
                </div>

                <div class="card border-0 shadow">
                    <div class="card-header bg-gray-800 text-white fw-semibold">
                        <i class="fa fa-list me-2"></i>Sesi Ujian
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th style="width:5%">#</th>
                                        <th>Sesi Ujian</th>
                                        <th>Skema</th>
                                        <th class="text-center" style="width:10%">Peserta</th>
                                        <th class="text-center" style="width:12%">Status</th>
                                        <th class="text-center" style="width:12%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(s, i) in allSessions" :key="s.id">
                                        <td>{{ i + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ s.title }}</div>
                                            <div class="text-muted small">{{ formatDate(s.start_time) }} – {{ formatDate(s.end_time) }}</div>
                                        </td>
                                        <td class="small">{{ classroomTitle(s) }}</td>
                                        <td class="text-center">{{ s.participant_results_count }}</td>
                                        <td class="text-center">
                                            <span v-if="s.end_time && new Date(s.end_time) > new Date()" class="badge bg-primary">Aktif</span>
                                            <span v-else class="badge bg-secondary">Selesai</span>
                                        </td>
                                        <td class="text-center">
                                            <Link :href="`/manager/sertifikasi/${s.id}`" class="btn btn-sm btn-primary border-0 shadow">
                                                <i class="fa fa-clipboard-check me-1"></i> Tinjau
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="allSessions.length === 0">
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="fa fa-award fa-2x d-block mb-2 text-gray-300"></i>
                                            Belum ada sesi ujian.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutManager from '../../Layouts/Manager.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

export default {
    layout: LayoutManager,
    components: { Head, Link },
    props: {
        active_sessions:    Array,
        completed_sessions: Array,
    },
    setup(props) {
        const allSessions = computed(() => [...props.active_sessions, ...props.completed_sessions]);

        const classroomTitle = (s) => (s.exam_pg ?? s.exam_esai)?.classroom?.title ?? '—';
        const formatDate = (dt) => dt
            ? new Date(dt).toLocaleDateString('id-ID', { dateStyle: 'medium' })
            : '—';

        return { allSessions, classroomTitle, formatDate };
    },
}
</script>
