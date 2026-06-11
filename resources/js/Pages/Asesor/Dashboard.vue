<template>
    <Head><title>Dashboard Asesor</title></Head>

    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <!-- Header -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5 class="mb-1"><i class="fa fa-clipboard-check me-2"></i>Dashboard Asesor — {{ asesor.name }}</h5>
                        <p class="text-muted mb-0 small">Daftar sesi ujian yang ditugaskan kepada Anda.</p>
                    </div>
                </div>

                <!-- Tab nav -->
                <ul class="nav nav-tabs mb-4" style="border-bottom:2px solid #dee2e6;">
                    <li class="nav-item">
                        <button
                            class="nav-link fw-semibold"
                            :class="{ active: tab === 'active' }"
                            @click="tab = 'active'"
                            style="border-bottom:3px solid transparent; background-color:#fff;"
                            :style="tab === 'active' ? 'border-bottom-color:#0d6efd; color:#0d6efd; background-color:#fff;' : 'color:#6B7280; background-color:#fff;'"
                        >
                            <i class="fa fa-circle-dot me-1 text-success"></i>
                            Sesi Aktif
                            <span class="badge ms-1" :class="active_sessions.length ? 'bg-success' : 'bg-secondary'">
                                {{ active_sessions.length }}
                            </span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button
                            class="nav-link fw-semibold"
                            :class="{ active: tab === 'completed' }"
                            @click="tab = 'completed'"
                            style="border-bottom:3px solid transparent; background-color:#fff;"
                            :style="tab === 'completed' ? 'border-bottom-color:#0d6efd; color:#0d6efd; background-color:#fff;' : 'color:#6B7280; background-color:#fff;'"
                        >
                            <i class="fa fa-check-circle me-1 text-secondary"></i>
                            Sesi Selesai
                            <span class="badge bg-secondary ms-1">{{ completed_sessions.length }}</span>
                        </button>
                    </li>
                </ul>

                <!-- Tab: Aktif -->
                <div v-if="tab === 'active'">
                    <div v-if="active_sessions.length === 0" class="alert alert-info border-0 shadow-sm">
                        <i class="fa fa-info-circle me-1"></i> Tidak ada sesi aktif saat ini.
                    </div>
                    <div v-else class="row">
                        <div v-for="session in active_sessions" :key="session.id" class="col-md-6 mb-4">
                            <SessionCard :session="session" variant="active" />
                        </div>
                    </div>
                </div>

                <!-- Tab: Selesai -->
                <div v-if="tab === 'completed'">
                    <div v-if="completed_sessions.length === 0" class="alert alert-secondary border-0 shadow-sm">
                        <i class="fa fa-info-circle me-1"></i> Belum ada sesi yang selesai.
                    </div>
                    <div v-else class="row">
                        <div v-for="session in completed_sessions" :key="session.id" class="col-md-6 mb-4">
                            <SessionCard :session="session" variant="completed" />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../Layouts/Asesor.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, defineComponent } from 'vue';

const SessionCard = defineComponent({
    components: { Link },
    props: {
        session: Object,
        variant: { type: String, default: 'active' }, // 'active' | 'completed'
    },
    template: `
        <div class="card border-0 shadow h-100" :class="variant === 'completed' ? 'opacity-75' : ''">
            <div class="card-body">
                <!-- Badge status -->
                <div class="mb-2">
                    <span v-if="variant === 'active'" class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="fa fa-circle-dot me-1"></i>Aktif
                    </span>
                    <span v-else class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                        <i class="fa fa-check-circle me-1"></i>Selesai
                    </span>
                </div>

                <h6 class="fw-bold mb-1">{{ session.title }}</h6>

                <p class="text-muted mb-1 small">
                    <i class="fa fa-book me-1"></i>
                    <span v-if="session.examPg">
                        {{ session.examPg.title }}
                        <span class="badge bg-info ms-1">{{ session.examPg.type }}</span>
                    </span>
                    <br v-if="session.examPg && session.examEsai">
                    <span v-if="session.examEsai">
                        <i v-if="!session.examPg" class="fa fa-book me-1"></i>
                        {{ session.examEsai.title }}
                        <span class="badge bg-warning text-dark ms-1">{{ session.examEsai.type }}</span>
                    </span>
                </p>

                <p class="text-muted mb-3 small">
                    <i class="fa fa-layer-group me-1"></i>
                    {{ (session.examPg ?? session.examEsai)?.classroom?.title ?? '—' }}
                    &nbsp;|&nbsp;
                    <i class="fa fa-users me-1"></i>{{ session.student_count }} peserta
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <Link :href="'/asesor/penilaian/' + session.id + '/esai'"
                        :class="variant === 'completed' ? 'btn btn-sm btn-outline-primary' : 'btn btn-sm btn-primary border-0 shadow'">
                        <i class="fa fa-pen me-1"></i> Esai
                    </Link>
                    <Link :href="'/asesor/penilaian/' + session.id + '/wawancara'"
                        :class="variant === 'completed' ? 'btn btn-sm btn-outline-success' : 'btn btn-sm btn-success border-0 shadow'">
                        <i class="fa fa-comments me-1"></i> Wawancara
                    </Link>
                    <Link :href="'/asesor/penilaian/' + session.id + '/dokumen'"
                        :class="variant === 'completed' ? 'btn btn-sm btn-outline-warning text-dark' : 'btn btn-sm btn-warning border-0 shadow text-dark'">
                        <i class="fa fa-folder-open me-1"></i> Dokumen
                    </Link>
                </div>
            </div>
        </div>
    `,
});

export default {
    layout: LayoutAsesor,
    components: { Head, Link, SessionCard },
    props: {
        active_sessions:    Array,
        completed_sessions: Array,
        asesor:             Object,
    },
    setup(props) {
        // Default ke tab selesai jika tidak ada yang aktif
        const tab = ref(props.active_sessions.length > 0 ? 'active' : 'completed');
        return { tab };
    },
}
</script>
