<template>
    <Head><title>Penilaian Esai — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link href="/asesor/dashboard" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-pen me-2"></i>Penilaian Esai</h5>
                        <hr>
                        <table class="table table-bordered mb-0" style="max-width:500px">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" style="width:40%">Sesi</td>
                                    <td>{{ exam_session.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Ujian</td>
                                    <td>{{ exam_session.exam.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Skema</td>
                                    <td>{{ exam_session.exam.classroom.title }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="students_data.length === 0" class="alert alert-info">
                    Tidak ada peserta yang ditugaskan di sesi ini.
                </div>

                <div v-else class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0"><i class="fa fa-table me-2"></i>Tabel Penilaian Jawaban Esai</h6>
                            <button @click="saveAll" :disabled="saving"
                                class="btn btn-success border-0 shadow">
                                <i class="fa fa-save me-1"></i>
                                {{ saving ? 'Menyimpan...' : 'Simpan Semua Nilai' }}
                            </button>
                        </div>

                        <div v-if="successMsg" class="alert alert-success alert-dismissible">
                            {{ successMsg }}
                            <button type="button" class="btn-close" @click="successMsg = ''"></button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center" style="min-width:100px">No Peserta</th>
                                        <th style="min-width:150px">Nama</th>
                                        <template v-for="(essay, i) in essays" :key="essay.id">
                                            <th style="min-width:260px">Jawaban {{ i + 1 }}</th>
                                            <th class="text-center" style="min-width:90px">Nilai {{ i + 1 }}</th>
                                        </template>
                                        <th class="text-center" style="min-width:110px">Total Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, ri) in form" :key="row.student_id">
                                        <td class="text-center fw-bold">
                                            {{ students_data[ri]?.student?.no_participant ?? '-' }}
                                        </td>
                                        <td>
                                            {{ students_data[ri]?.student?.name ?? '-' }}
                                            <span v-if="students_data[ri]?.attempt > 1" class="badge bg-warning text-dark ms-1" style="font-size:0.65rem">Remidi</span>
                                        </td>

                                        <template v-for="(ans, ai) in row.answers" :key="ans.answer_essay_id">
                                            <td>
                                                <div class="answer-cell">
                                                    <span :class="{ collapsed: !ans.expanded }"
                                                        v-html="ans.answer_text || '<em class=\'text-muted\'>—</em>'">
                                                    </span>
                                                    <button v-if="ans.answer_text && ans.answer_text.length > 120"
                                                        class="btn btn-link btn-sm p-0 ms-1"
                                                        @click="ans.expanded = !ans.expanded">
                                                        {{ ans.expanded ? 'Lebih sedikit' : 'Selengkapnya' }}
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" min="0" max="100" step="0.01"
                                                    v-model.number="ans.score"
                                                    @input="recalcTotal(ri)"
                                                    class="form-control form-control-sm text-center"
                                                    style="width:80px;margin:auto"
                                                    placeholder="0–100" />
                                            </td>
                                        </template>

                                        <td class="text-center fw-bold">
                                            <span :class="row.total !== null ? 'text-success' : 'text-muted'">
                                                {{ row.total !== null ? row.total.toFixed(2) : '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-secondary">
                                    <tr>
                                        <td colspan="2" class="fw-bold">Rata-rata Sesi</td>
                                        <template v-for="(essay, i) in essays" :key="essay.id">
                                            <td></td>
                                            <td class="text-center fw-bold">
                                                {{ columnAvg(i) }}
                                            </td>
                                        </template>
                                        <td class="text-center fw-bold text-success">
                                            {{ sessionAvg() }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, Link, router } from '@inertiajs/vue3';

export default {
    layout: LayoutAsesor,
    components: { Head, Link },

    props: {
        exam_session: Object,
        essays: Array,
        students_data: Array,
    },

    data() {
        return {
            saving: false,
            successMsg: '',
            form: this.buildForm(),
        };
    },

    methods: {
        buildForm() {
            return this.students_data.map(row => {
                const answers = this.essays.map((essay, i) => {
                    const ans = row.answers.find(a => a.essay_order === i + 1) || row.answers[i] || null;
                    return {
                        answer_essay_id: ans?.id ?? null,
                        answer_text: ans?.answer ?? '',
                        score: ans?.score ?? null,
                        expanded: false,
                    };
                });

                const scores = answers.map(a => a.score).filter(s => s !== null && s !== '');
                const total = scores.length > 0
                    ? Math.round(scores.reduce((a, b) => a + Number(b), 0) / scores.length * 100) / 100
                    : null;

                return {
                    student_id: row.student_id,
                    answers,
                    total,
                };
            });
        },

        recalcTotal(ri) {
            const scores = this.form[ri].answers
                .map(a => a.score)
                .filter(s => s !== null && s !== '' && !isNaN(s));
            this.form[ri].total = scores.length > 0
                ? Math.round(scores.reduce((a, b) => a + Number(b), 0) / scores.length * 100) / 100
                : null;
        },

        columnAvg(colIndex) {
            const scores = this.form
                .map(r => r.answers[colIndex]?.score)
                .filter(s => s !== null && s !== '' && !isNaN(s));
            if (!scores.length) return '—';
            return (scores.reduce((a, b) => a + Number(b), 0) / scores.length).toFixed(2);
        },

        sessionAvg() {
            const totals = this.form.map(r => r.total).filter(t => t !== null);
            if (!totals.length) return '—';
            return (totals.reduce((a, b) => a + b, 0) / totals.length).toFixed(2);
        },

        saveAll() {
            this.saving = true;
            router.post(
                `/asesor/penilaian/${this.exam_session.id}/esai`,
                {
                    scores: this.form.map(r => ({
                        student_id: r.student_id,
                        answers: r.answers.map(a => ({
                            answer_essay_id: a.answer_essay_id,
                            score: a.score,
                        })),
                    })),
                },
                {
                    onSuccess: () => {
                        this.successMsg = 'Nilai berhasil disimpan.';
                    },
                    onFinish: () => {
                        this.saving = false;
                    },
                }
            );
        },
    },
}
</script>

<style scoped>
.answer-cell .collapsed {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
