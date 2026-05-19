<template>
    <Head><title>Dashboard Asesor</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-clipboard-check me-2"></i>Dashboard Asesor — {{ asesor.name }}</h5>
                        <hr>
                        <p class="text-muted mb-0">Berikut daftar sesi ujian yang ditugaskan kepada Anda.</p>
                    </div>
                </div>

                <div v-if="exam_sessions.length === 0" class="alert alert-info">
                    Belum ada penugasan penilaian untuk Anda.
                </div>

                <div v-else class="row">
                    <div v-for="session in exam_sessions" :key="session.id" class="col-md-6 mb-4">
                        <div class="card border-0 shadow h-100">
                            <div class="card-body">
                                <h6 class="fw-bold">{{ session.title }}</h6>
                                <p class="text-muted mb-1">
                                    <i class="fa fa-book me-1"></i>{{ session.exam.title }}
                                    <span class="badge bg-secondary ms-1">{{ session.exam.type }}</span>
                                </p>
                                <p class="text-muted mb-3">
                                    <i class="fa fa-layer-group me-1"></i>{{ session.exam.classroom.title }}
                                    &nbsp;|&nbsp;
                                    <i class="fa fa-users me-1"></i>{{ session.student_count }} peserta ditugaskan
                                </p>
                                <div class="d-flex gap-2">
                                    <Link :href="`/asesor/penilaian/${session.id}/esai`"
                                        class="btn btn-sm btn-primary border-0 shadow">
                                        <i class="fa fa-pen me-1"></i> Nilai Esai
                                    </Link>
                                    <Link :href="`/asesor/penilaian/${session.id}/wawancara`"
                                        class="btn btn-sm btn-success border-0 shadow">
                                        <i class="fa fa-comments me-1"></i> Nilai Wawancara
                                    </Link>
                                </div>
                            </div>
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

export default {
    layout: LayoutAsesor,
    components: { Head, Link },
    props: {
        exam_sessions: Array,
        asesor: Object,
    },
}
</script>
