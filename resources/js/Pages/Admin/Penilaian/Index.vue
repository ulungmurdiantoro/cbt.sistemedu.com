<template>
    <Head><title>Penugasan Asesor</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-user-tie me-2"></i>Penugasan Asesor</h5>
                        <hr>
                        <p class="text-muted mb-0">Pilih sesi ujian untuk mengatur penugasan asesor ke peserta.</p>
                    </div>
                </div>

                <!-- Sesi Aktif -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-header bg-success text-white fw-semibold">
                        <i class="fa fa-circle-play me-2"></i>Sesi Aktif
                        <span class="badge bg-light text-success ms-2">{{ activeSessions.length }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width:5%">No</th>
                                        <th>Sesi Ujian</th>
                                        <th>Ujian</th>
                                        <th>Skema</th>
                                        <th>Tipe</th>
                                        <th>Berakhir</th>
                                        <th class="text-center" style="width:12%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="activeSessions.length === 0">
                                        <td colspan="7" class="text-center text-muted py-3">Tidak ada sesi aktif.</td>
                                    </tr>
                                    <tr v-for="(session, i) in activeSessions" :key="session.id">
                                        <td>{{ i + 1 }}</td>
                                        <td>{{ session.title }}</td>
                                        <td>
                                            <div v-if="session.examPg" class="small">{{ session.examPg.title }}</div>
                                            <div v-if="session.examEsai" class="small">{{ session.examEsai.title }}</div>
                                            <div v-if="!session.examPg && !session.examEsai" class="text-muted small">—</div>
                                        </td>
                                        <td>{{ (session.examPg ?? session.examEsai)?.classroom?.title ?? '—' }}</td>
                                        <td>
                                            <span v-if="session.examPg" class="badge bg-info me-1">{{ session.examPg.type }}</span>
                                            <span v-if="session.examEsai" class="badge bg-warning text-dark me-1">{{ session.examEsai.type }}</span>
                                            <span v-if="session.has_wawancara" class="badge bg-secondary">Wawancara</span>
                                        </td>
                                        <td class="small">{{ formatDate(session.end_time) }}</td>
                                        <td class="text-center">
                                            <Link :href="`/admin/penilaian/${session.id}`"
                                                class="btn btn-sm btn-primary border-0 shadow">
                                                <i class="fa fa-users-cog me-1"></i> Atur
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sesi Selesai -->
                <div class="card border-0 shadow">
                    <div class="card-header bg-secondary text-white fw-semibold">
                        <i class="fa fa-circle-check me-2"></i>Sesi Selesai
                        <span class="badge bg-light text-secondary ms-2">{{ endedSessions.length }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width:5%">No</th>
                                        <th>Sesi Ujian</th>
                                        <th>Ujian</th>
                                        <th>Skema</th>
                                        <th>Tipe</th>
                                        <th>Selesai</th>
                                        <th class="text-center" style="width:12%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="endedSessions.length === 0">
                                        <td colspan="7" class="text-center text-muted py-3">Belum ada sesi yang selesai.</td>
                                    </tr>
                                    <tr v-for="(session, i) in endedSessions" :key="session.id" class="text-muted">
                                        <td>{{ i + 1 }}</td>
                                        <td>{{ session.title }}</td>
                                        <td>
                                            <div v-if="session.examPg" class="small">{{ session.examPg.title }}</div>
                                            <div v-if="session.examEsai" class="small">{{ session.examEsai.title }}</div>
                                            <div v-if="!session.examPg && !session.examEsai" class="small">—</div>
                                        </td>
                                        <td>{{ (session.examPg ?? session.examEsai)?.classroom?.title ?? '—' }}</td>
                                        <td>
                                            <span v-if="session.examPg" class="badge bg-info me-1">{{ session.examPg.type }}</span>
                                            <span v-if="session.examEsai" class="badge bg-warning text-dark me-1">{{ session.examEsai.type }}</span>
                                            <span v-if="session.has_wawancara" class="badge bg-secondary">Wawancara</span>
                                        </td>
                                        <td class="small">{{ formatDate(session.end_time) }}</td>
                                        <td class="text-center">
                                            <Link :href="`/admin/penilaian/${session.id}`"
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="fa fa-eye me-1"></i> Lihat
                                            </Link>
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
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        exam_sessions: Array,
        asesors: Array,
    },

    setup(props) {
        const isEnded = (session) => {
            if (!session.end_time) return false;
            return new Date(session.end_time).getTime() < Date.now();
        };

        // Aktif: belum lewat end_time, urut yang berakhir paling cepat dulu
        const activeSessions = computed(() =>
            (props.exam_sessions ?? [])
                .filter(s => !isEnded(s))
                .sort((a, b) => new Date(a.end_time) - new Date(b.end_time))
        );

        // Selesai: sudah lewat end_time, urut yang baru selesai dulu
        const endedSessions = computed(() =>
            (props.exam_sessions ?? [])
                .filter(isEnded)
                .sort((a, b) => new Date(b.end_time) - new Date(a.end_time))
        );

        const formatDate = (dt) => dt
            ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
            : '—';

        return { activeSessions, endedSessions, formatDate };
    },
}
</script>
