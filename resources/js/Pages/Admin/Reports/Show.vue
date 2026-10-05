<template>
    <Head>
        <title>Detail Jawaban - {{ grade.student?.name }}</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link :href="grade.session ? `/admin/results/${grade.session.id}` : '/admin/results'" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali ke Rekap Hasil
                </Link>

                <!-- Ringkasan peserta & ujian -->
                <div class="card border-0 shadow mb-3">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div style="min-width:0">
                            <h5 class="fw-bold mb-1">{{ grade.student?.name }}</h5>
                            <div class="small text-muted">
                                {{ [grade.student?.no_participant, grade.student?.position, grade.student?.institution].filter(Boolean).join(' · ') }}
                            </div>
                            <div class="small mt-1">
                                <span class="fw-bolder">{{ grade.exam.title }}</span>
                                <span class="text-muted">
                                    · {{ grade.session?.title }}<template v-if="grade.session?.kode_batch"> (Batch {{ grade.session.kode_batch }})</template>
                                    <template v-if="grade.skema"> · {{ grade.skema }}</template>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 ms-auto">
                            <StatusBadge :tone="typeTone" :label="grade.exam.type" />
                            <div class="text-end">
                                <div class="small text-muted">Nilai</div>
                                <div class="score">{{ fmt(grade.grade) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <PgReview v-if="questions" :questions="questions" />
                <EssayReview v-else :essays="essays ?? []" :files="files" :migas="grade.exam.type === 'Essay Migas'" />

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import PgReview from '../../../Components/AnswerReview/PgReview.vue';
import EssayReview from '../../../Components/AnswerReview/EssayReview.vue';
import { Head, Link } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, StatusBadge, PgReview, EssayReview },
    props: {
        // { id, grade, exam: { id, title, type }, skema, session: { id, title, kode_batch }, student: {...} }
        grade:     Object,
        // Pilihan Ganda: daftar soal + jawaban (null untuk esai)
        questions: Array,
        // Esai / Essay Migas: daftar soal + jawaban + nilai (null untuk PG)
        essays:    Array,
        // Essay Migas: berkas jawaban
        files:     { type: Array, default: () => [] },
    },
    computed: {
        // senada dengan badge tipe ujian di daftar nilai
        typeTone() {
            return { 'Pilihan Ganda': 'accent', 'Essay': 'success', 'Essay Migas': 'secondary' }[this.grade.exam.type] ?? 'neutral';
        },
    },
    methods: {
        fmt(v) {
            return v === null || v === undefined || v === '' ? '—' : Number(v).toFixed(2);
        },
    },
}
</script>

<style scoped>
.score {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
}
</style>
