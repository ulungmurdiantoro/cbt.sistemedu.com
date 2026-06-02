<template>
    <Head><title>Penilaian Esai — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-4">
        <div class="row">
            <div class="col-lg-10 col-xl-9 mx-auto">

                <Link href="/asesor/dashboard" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <!-- Info sesi -->
                <div class="card border-0 shadow mb-3">
                    <div class="card-body py-3">
                        <h6 class="fw-bold mb-2"><i class="fa fa-pen me-2"></i>Penilaian Esai</h6>
                        <div class="row small">
                            <div class="col-md-4"><span class="text-muted">Sesi:</span> {{ exam_session.title }}</div>
                            <div class="col-md-4"><span class="text-muted">Ujian:</span> {{ exam_session.examEsai?.title ?? '-' }}</div>
                            <div class="col-md-4"><span class="text-muted">Skema:</span> {{ exam_session.examEsai?.classroom?.title ?? exam_session.examPg?.classroom?.title ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="students_data.length === 0" class="alert alert-info">
                    Tidak ada peserta yang ditugaskan di sesi ini.
                </div>

                <template v-else>
                    <!-- Action bar (sticky top) -->
                    <div class="card border-0 shadow mb-3" style="position:sticky; top:10px; z-index:1020">
                        <div class="card-body py-2">
                            <div class="row align-items-center g-2">
                                <div class="col-md-3 small">
                                    <span class="fw-semibold">Peserta {{ currentIndex + 1 }} dari {{ form.length }}</span>
                                </div>
                                <div class="col-md-5">
                                    <select class="form-select form-select-sm" v-model.number="currentIndex">
                                        <option v-for="(s, i) in students_data" :key="s.student_id" :value="i">
                                            {{ s.student?.no_participant ?? '-' }} — {{ s.student?.name ?? '-' }}
                                            {{ form[i]?.total !== null ? `(${form[i].total.toFixed(2)})` : '(belum dinilai)' }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex justify-content-end gap-1">
                                    <button class="btn btn-sm btn-light border" :disabled="currentIndex === 0"
                                        @click="currentIndex--">
                                        <i class="fa fa-chevron-left"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border" :disabled="currentIndex >= form.length - 1"
                                        @click="currentIndex++">
                                        <i class="fa fa-chevron-right"></i>
                                    </button>
                                    <button @click="saveAll" :disabled="saving"
                                        class="btn btn-sm btn-success border-0">
                                        <i class="fa fa-save me-1"></i>
                                        {{ saving ? 'Menyimpan...' : 'Simpan Semua' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="successMsg" class="alert alert-success alert-dismissible">
                        {{ successMsg }}
                        <button type="button" class="btn-close" @click="successMsg = ''"></button>
                    </div>

                    <!-- Info peserta aktif -->
                    <div class="card border-0 shadow mb-3 bg-light">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <div class="small text-muted">No. Peserta</div>
                                    <div class="fw-bold">{{ currentStudent?.student?.no_participant ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="small text-muted">Nama</div>
                                    <div class="fw-bold">
                                        {{ currentStudent?.student?.name ?? '-' }}
                                        <span v-if="currentStudent?.attempt > 1" class="badge bg-warning text-dark ms-1">Remidi</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="small text-muted">Total Nilai</div>
                                    <div class="fw-bold">
                                        <span :class="currentRow?.total !== null ? 'text-success' : 'text-muted'">
                                            {{ currentRow?.total !== null ? currentRow.total.toFixed(2) : '— belum lengkap' }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="small text-muted">Progress</div>
                                    <div class="fw-bold">
                                        {{ answeredCount(currentRow) }} / {{ essays.length }} dinilai
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card per soal esai -->
                    <div v-for="(ans, ai) in currentRow.answers" :key="ans.answer_essay_id ?? ai"
                        class="card border-0 shadow mb-3">
                        <div class="card-header bg-gray-800 text-white fw-semibold d-flex justify-content-between">
                            <span><i class="fa fa-question-circle me-2"></i>Soal {{ ai + 1 }}</span>
                            <span class="badge bg-light text-dark" v-if="ans.score !== null && ans.score !== ''">
                                Nilai: {{ ans.score }}
                            </span>
                        </div>
                        <div class="card-body">
                            <!-- Pertanyaan -->
                            <div class="mb-3">
                                <div class="small fw-semibold text-muted mb-1">PERTANYAAN</div>
                                <div class="p-3 bg-light rounded" style="line-height:1.7"
                                    v-html="essays[ai]?.question || '<em class=\'text-muted\'>—</em>'">
                                </div>
                            </div>

                            <!-- Jawaban peserta -->
                            <div class="mb-3">
                                <div class="small fw-semibold text-muted mb-1">JAWABAN PESERTA</div>
                                <div class="p-3 border rounded bg-white" style="line-height:1.7; min-height:80px"
                                    v-html="ans.answer_text || '<em class=\'text-muted\'>(Peserta tidak menjawab)</em>'">
                                </div>
                            </div>

                            <!-- Input nilai -->
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="fw-semibold small mb-1">Nilai (0–100) <span class="text-danger">*</span></label>
                                    <input type="number" min="0" max="100" step="0.01"
                                        v-model.number="ans.score"
                                        @input="recalcTotal(currentIndex)"
                                        class="form-control"
                                        placeholder="Masukkan nilai">
                                </div>
                                <div class="col-md-8 small text-muted">
                                    <i class="fa fa-info-circle me-1"></i>
                                    Total nilai dihitung otomatis sebagai rata-rata dari semua nilai soal.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom nav -->
                    <div class="d-flex justify-content-between mt-4 mb-3">
                        <button class="btn btn-light border" :disabled="currentIndex === 0"
                            @click="goPrev">
                            <i class="fa fa-chevron-left me-1"></i> Peserta Sebelumnya
                        </button>
                        <button class="btn btn-light border" :disabled="currentIndex >= form.length - 1"
                            @click="goNext">
                            Peserta Berikutnya <i class="fa fa-chevron-right ms-1"></i>
                        </button>
                    </div>

                    <!-- Ringkasan semua peserta (collapsible) -->
                    <div class="card border-0 shadow mt-4">
                        <div class="card-header bg-light fw-semibold d-flex justify-content-between"
                            @click="showSummary = !showSummary" style="cursor:pointer">
                            <span><i class="fa fa-list me-2"></i>Ringkasan Semua Peserta</span>
                            <i :class="showSummary ? 'fa fa-chevron-up' : 'fa fa-chevron-down'"></i>
                        </div>
                        <div v-show="showSummary" class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle mb-0">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>#</th>
                                            <th>No. Peserta</th>
                                            <th>Nama</th>
                                            <th class="text-center">Dinilai</th>
                                            <th class="text-center">Total</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, i) in form" :key="row.student_id"
                                            :class="{ 'table-active': i === currentIndex }">
                                            <td>{{ i + 1 }}</td>
                                            <td class="fw-bold">{{ students_data[i]?.student?.no_participant ?? '-' }}</td>
                                            <td>{{ students_data[i]?.student?.name ?? '-' }}</td>
                                            <td class="text-center">{{ answeredCount(row) }} / {{ essays.length }}</td>
                                            <td class="text-center fw-bold">
                                                <span :class="row.total !== null ? 'text-success' : 'text-muted'">
                                                    {{ row.total !== null ? row.total.toFixed(2) : '—' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary" @click="currentIndex = i; scrollTop()">
                                                    Nilai
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-secondary">
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold">Rata-rata Sesi</td>
                                            <td class="text-center fw-bold text-success">{{ sessionAvg() }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>

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
            showSummary: false,
            currentIndex: 0,
            form: this.buildForm(),
        };
    },

    computed: {
        currentRow() {
            return this.form[this.currentIndex] ?? null;
        },
        currentStudent() {
            return this.students_data[this.currentIndex] ?? null;
        },
    },

    methods: {
        buildForm() {
            return this.students_data.map(row => {
                const answers = this.essays.map((_, i) => {
                    const ans = row.answers.find(a => a.essay_order === i + 1) || row.answers[i] || null;
                    return {
                        answer_essay_id: ans?.id ?? null,
                        answer_text:     ans?.answer ?? '',
                        score:           ans?.score ?? null,
                    };
                });

                const scores = answers.map(a => a.score).filter(s => s !== null && s !== '');
                const total  = scores.length > 0
                    ? Math.round(scores.reduce((a, b) => a + Number(b), 0) / scores.length * 100) / 100
                    : null;

                return { student_id: row.student_id, answers, total };
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

        answeredCount(row) {
            if (!row) return 0;
            return row.answers.filter(a => a.score !== null && a.score !== '' && !isNaN(a.score)).length;
        },

        sessionAvg() {
            const totals = this.form.map(r => r.total).filter(t => t !== null);
            if (!totals.length) return '—';
            return (totals.reduce((a, b) => a + b, 0) / totals.length).toFixed(2);
        },

        scrollTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        goPrev() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.scrollTop();
            }
        },

        goNext() {
            if (this.currentIndex < this.form.length - 1) {
                this.currentIndex++;
                this.scrollTop();
            }
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
                    preserveState:  true,
                    preserveScroll: true,
                    onSuccess: () => { this.successMsg = 'Nilai berhasil disimpan.'; },
                    onFinish:  () => { this.saving = false; },
                }
            );
        },
    },
}
</script>
