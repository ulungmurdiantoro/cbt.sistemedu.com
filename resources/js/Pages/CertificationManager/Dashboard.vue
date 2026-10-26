<template>
    <Head><title>Dashboard Manager Sertifikasi</title></Head>

    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5 class="mb-1"><i class="fa fa-award me-2"></i>Dashboard Manager Sertifikasi</h5>
                        <p class="text-muted mb-0 small">
                            Verifikasi peserta yang sudah diverifikasi Pengambil Keputusan, lalu finalisasi kelulusan.
                        </p>
                    </div>
                </div>

                <PageGuide storage-key="cert_manager.dashboard"><GuideDashboard /></PageGuide>

                <div class="card border-0 shadow">
                    <div class="card-header bg-gray-800 text-white fw-bolder">
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
                                        <th>Pengambil Keputusan</th>
                                        <th class="text-center" style="width:8%">Peserta</th>
                                        <th class="text-center" style="width:9%">Status</th>
                                        <th class="text-center" style="width:13%">Verifikasi PK</th>
                                        <th class="text-center" style="width:13%">Verifikasi Saya</th>
                                        <th class="text-center" style="width:10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(s, i) in allSessions" :key="s.id">
                                        <td>{{ i + 1 }}</td>
                                        <td>
                                            <div class="fw-bolder">{{ s.title }}</div>
                                            <div class="text-muted small">{{ formatDate(s.start_time) }} – {{ formatDate(s.end_time) }}</div>
                                        </td>
                                        <td class="small">{{ classroomTitle(s) }}</td>
                                        <td class="small">
                                            <span v-if="s.pengambil_keputusan">{{ s.pengambil_keputusan.name }}</span>
                                            <span v-else class="text-danger fst-italic">Belum ditunjuk</span>
                                        </td>
                                        <td class="text-center">{{ s.students_count }}</td>
                                        <td class="text-center">
                                            <span v-if="s.end_time && new Date(s.end_time) > new Date()" class="badge bg-primary">Aktif</span>
                                            <span v-else class="badge bg-secondary">Selesai</span>
                                        </td>
                                        <td class="text-center">
                                            <StatusBadge v-bind="progressBadge(s.pk_verified_count, s.students_count)" />
                                        </td>
                                        <td class="text-center">
                                            <StatusBadge v-bind="progressBadge(s.reviewed_count, s.students_count)" />
                                        </td>
                                        <td class="text-center">
                                            <Link :href="`/manager-sertifikasi/sertifikasi/${s.id}`" class="btn btn-sm btn-primary border-0 shadow">
                                                <i class="fa fa-clipboard-check me-1"></i> Tinjau
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="allSessions.length === 0">
                                        <td colspan="9" class="text-center text-muted py-5">
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
import LayoutCertificationManager from '../../Layouts/CertificationManager.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageGuide from '../../Components/PageGuide.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import GuideDashboard from '../../Components/Guide/CertificationManager/Dashboard.vue';

export default {
    layout: LayoutCertificationManager,
    components: { Head, Link, PageGuide, StatusBadge, GuideDashboard },
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

        // Sama dengan stageBadge di Rekap Hasil admin (Admin/Results/Index.vue)
        const progressBadge = (done, total) => {
            if (!total || done === 0) return { tone: 'secondary', label: 'Belum' };
            if (done < total) return { tone: 'accent', label: `Sebagian (${done}/${total})` };
            return { tone: 'success', label: 'Sudah' };
        };

        return { allSessions, classroomTitle, formatDate, progressBadge };
    },
}
</script>
