<template>
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
                <Link :href="'/asesor/penilaian/' + session.id + '/dokumen'"
                    :class="variant === 'completed' ? 'btn btn-sm btn-outline-warning text-dark' : 'btn btn-sm btn-warning border-0 shadow text-dark'">
                    <i class="fa fa-folder-open me-1"></i> Dokumen
                </Link>
            </div>
        </div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';

export default {
    components: { Link },
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
