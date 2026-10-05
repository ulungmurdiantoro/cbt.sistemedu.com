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
                        <SessionTable :sessions="exam_sessions" count-key="exam_groups_count" actions-width="12%"
                            empty-text="Sesi ujian akan muncul di sini untuk diatur penugasan asesornya.">
                            <template #actions="{ session, active }">
                                <Link :href="`/admin/penilaian/${session.id}`"
                                    class="btn btn-sm border-0 shadow"
                                    :class="active ? 'btn-primary' : 'btn-outline-secondary'"
                                    :title="active ? 'Atur Penugasan' : 'Lihat Penugasan'">
                                    <i :class="active ? 'fa fa-users-cog' : 'fa fa-eye'"></i>
                                    {{ active ? 'Atur' : 'Lihat' }}
                                </Link>
                            </template>
                        </SessionTable>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import SessionTable from '../../../Components/SessionTable.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, SessionTable },
    props: {
        exam_sessions: Array,
        asesors: Array,
    },

    setup(props) {
        const now = new Date();

        const isActive = (s) => new Date(s.end_time) > now;

        const activeSessions = computed(() => (props.exam_sessions ?? []).filter(isActive));
        const endedSessions  = computed(() => (props.exam_sessions ?? []).filter(s => !isActive(s)));

        return { activeSessions, endedSessions };
    },
}
</script>

