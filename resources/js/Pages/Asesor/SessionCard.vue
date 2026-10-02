<template>
    <div class="card border-0 shadow h-100" :class="variant === 'completed' ? 'opacity-75' : ''">
        <div class="card-body">
            <!-- Badge status -->
            <div class="mb-2">
                <StatusBadge v-if="variant === 'active'" tone="success">
                    <i class="fa fa-dot-circle me-1"></i>Aktif
                </StatusBadge>
                <StatusBadge v-else tone="neutral">
                    <i class="fa fa-check-circle me-1"></i>Selesai
                </StatusBadge>
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
                    <span class="badge bg-warning text-gray-800 ms-1">{{ session.examEsai.type }}</span>
                </span>
            </p>

            <p class="text-muted mb-3 small">
                <i class="fa fa-layer-group me-1"></i>
                {{ classroomTitle }}
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
                <Link :href="'/asesor/penilaian/' + session.id + '/laporan-asesmen'"
                    :class="variant === 'completed' ? 'btn btn-sm btn-outline-dark' : 'btn btn-sm btn-gray-800 border-0 shadow'">
                    <i class="fa fa-file-alt me-1"></i> Laporan
                </Link>
                <Link :href="'/asesor/penilaian/' + session.id + '/ttd-ak01'"
                    :class="variant === 'completed' ? 'btn btn-sm btn-outline-warning' : 'btn btn-sm btn-warning border-0 shadow'">
                    <i class="fa fa-signature me-1"></i> TTD AK.01
                </Link>
            </div>
        </div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import StatusBadge from '../../Components/StatusBadge.vue';

export default {
    components: { Link, StatusBadge },
    props: {
        session: { type: Object, required: true },
        variant: { type: String, default: 'active' }, // 'active' | 'completed'
    },
    computed: {
        classroomTitle() {
            const exam = this.session.examPg ?? this.session.examEsai;
            return exam?.classroom?.title ?? '—';
        },
    },
}
</script>
