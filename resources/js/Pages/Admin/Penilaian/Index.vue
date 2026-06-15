<template>
    <Head><title>Penugasan Asesor</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <div class="card border-0 shadow mb-4">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-0"><i class="fa fa-user-tie me-2"></i>Penugasan Asesor</h5>
                            <p class="text-muted small mb-0">Pilih sesi ujian untuk mengatur penugasan asesor ke peserta.</p>
                        </div>
                        <div class="d-flex gap-2 text-center">
                            <div class="px-3 py-1 rounded border">
                                <div class="fw-bold text-success">{{ activeSessions.length }}</div>
                                <div class="text-muted" style="font-size:0.75rem">Aktif</div>
                            </div>
                            <div class="px-3 py-1 rounded border">
                                <div class="fw-bold text-secondary">{{ endedSessions.length }}</div>
                                <div class="text-muted" style="font-size:0.75rem">Selesai</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-centered mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start" style="width:4%">No.</th>
                                        <th class="border-0">Sesi Ujian</th>
                                        <th class="border-0">Jenis Ujian</th>
                                        <th class="border-0" style="width:8%">Peserta</th>
                                        <th class="border-0">Waktu</th>
                                        <th class="border-0 rounded-end text-center" style="width:12%">Aksi</th>
                                    </tr>
                                </thead>
                                <div class="mt-2"></div>
                                <tbody>
                                    <template v-for="(session, index) in exam_sessions" :key="session.id">
                                        <!-- Separator sebelum baris pertama yang sudah selesai -->
                                        <tr v-if="isFirstFinished(index)" class="separator-row">
                                            <td colspan="6" class="py-1 px-3 text-muted small fw-semibold border-0"
                                                style="background:#f8f9fa; border-top:2px dashed #dee2e6 !important;">
                                                <i class="fa fa-check-circle me-1 text-secondary"></i> Sesi Selesai
                                            </td>
                                        </tr>

                                        <tr :class="isActive(session) ? 'table-active-session' : 'table-finished-session'">
                                            <td class="fw-bold text-center">{{ index + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-start gap-2">
                                                    <StatusBadge v-if="isActive(session)" tone="accent" label="Aktif" class="mt-1 flex-shrink-0" />
                                                    <StatusBadge v-else tone="success" label="Selesai" class="mt-1 flex-shrink-0" />
                                                    <div>
                                                        <strong>{{ session.title }}</strong>
                                                        <div class="text-muted small">Batch {{ session.kode_batch }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <ul class="mb-0 ps-3 small">
                                                    <li v-if="session.exam_pg">
                                                        <span class="badge bg-primary me-1">PG</span>
                                                        {{ session.exam_pg.title }}
                                                        <span class="text-muted">({{ session.exam_pg.classroom?.title }})</span>
                                                    </li>
                                                    <li v-if="session.exam_esai">
                                                        <span class="badge bg-success me-1">Esai</span>
                                                        {{ session.exam_esai.title }}
                                                        <span class="text-muted">({{ session.exam_esai.classroom?.title }})</span>
                                                    </li>
                                                    <li v-if="session.has_wawancara">
                                                        <span class="badge bg-warning text-dark me-1">Wawancara</span>
                                                        Penilaian asesor
                                                    </li>
                                                    <li v-if="!session.exam_pg && !session.exam_esai && !session.has_wawancara"
                                                        class="text-muted">—</li>
                                                </ul>
                                            </td>
                                            <td class="text-center">
                                                {{ session.exam_groups_count ?? 0 }}
                                            </td>
                                            <td class="small text-nowrap">
                                                <div><span class="text-muted">Mulai:</span> {{ formatDate(session.start_time) }}</div>
                                                <div><span class="text-muted">Selesai:</span> {{ formatDate(session.end_time) }}</div>
                                            </td>
                                            <td class="text-center">
                                                <Link :href="`/admin/penilaian/${session.id}`"
                                                    class="btn btn-sm border-0 shadow"
                                                    :class="isActive(session) ? 'btn-primary' : 'btn-outline-secondary'"
                                                    :title="isActive(session) ? 'Atur Penugasan' : 'Lihat Penugasan'">
                                                    <i :class="isActive(session) ? 'fa fa-users-cog' : 'fa fa-eye'"></i>
                                                    {{ isActive(session) ? 'Atur' : 'Lihat' }}
                                                </Link>
                                            </td>
                                        </tr>
                                    </template>

                                    <tr v-if="exam_sessions.length === 0">
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="fa fa-user-check fa-2x d-block mb-2 text-gray-300"></i>
                                            <strong class="d-block">Belum ada sesi ujian</strong>
                                            <span class="small">Sesi ujian akan muncul di sini untuk diatur penugasan asesornya.</span>
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
import StatusBadge from '../../../Components/StatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, StatusBadge },
    props: {
        exam_sessions: Array,
        asesors: Array,
    },

    setup(props) {
        const now = new Date();

        const isActive = (s) => new Date(s.end_time) > now;

        const isFirstFinished = (index) => {
            const data = props.exam_sessions;
            if (index === 0) return false;
            return !isActive(data[index]) && isActive(data[index - 1]);
        };

        const activeSessions = computed(() => (props.exam_sessions ?? []).filter(isActive));
        const endedSessions  = computed(() => (props.exam_sessions ?? []).filter(s => !isActive(s)));

        const formatDate = (dt) => dt
            ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
            : '—';

        return { isActive, isFirstFinished, activeSessions, endedSessions, formatDate };
    },
}
</script>

<style scoped>
.table-active-session td {
    background-color: #fff;
}
.table-finished-session td {
    background-color: #f8f9fa;
    color: #6c757d;
    opacity: 0.85;
}
.table-finished-session .badge {
    opacity: 0.8;
}
</style>
