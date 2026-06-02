<template>
    <Head><title>Penilaian Esai — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-4">
        <div class="row">
            <div class="col-12">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <Link href="/asesor/dashboard" class="btn btn-md btn-primary border-0 shadow">
                        <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                    </Link>
                    <button @click="saveAll" :disabled="saving" class="btn btn-success border-0 shadow">
                        <i class="fa fa-save me-1"></i>
                        {{ saving ? 'Menyimpan...' : 'Simpan Semua Nilai' }}
                    </button>
                </div>

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

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>

                <div v-if="students_data.length === 0" class="alert alert-info">
                    Tidak ada peserta yang ditugaskan di sesi ini.
                </div>

                <!-- Daftar peserta (master) -->
                <div v-else class="card border-0 shadow">
                    <div class="card-header bg-gray-800 text-white fw-semibold d-flex justify-content-between align-items-center">
                        <span><i class="fa fa-users me-2"></i>Daftar Peserta</span>
                        <span class="small">
                            Selesai dinilai: {{ fullyScoredCount }} / {{ form.length }}
                            &nbsp;|&nbsp; Rata-rata: <span class="badge bg-light text-dark">{{ sessionAvg() }}</span>
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th style="width:5%">#</th>
                                        <th style="width:18%">No. Peserta</th>
                                        <th>Nama</th>
                                        <th class="text-center" style="width:14%">Progress</th>
                                        <th class="text-center" style="width:12%">Total</th>
                                        <th class="text-center" style="width:12%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in form" :key="row.student_id"
                                        style="cursor:pointer" @click="openDrawer(i)">
                                        <td>{{ i + 1 }}</td>
                                        <td class="fw-bold">{{ students_data[i]?.student?.no_participant ?? '-' }}</td>
                                        <td>
                                            {{ students_data[i]?.student?.name ?? '-' }}
                                            <span v-if="students_data[i]?.attempt > 1" class="badge bg-warning text-dark ms-1" style="font-size:0.65rem">Remidi</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge"
                                                :class="answeredCount(row) === essays.length ? 'bg-success' : (answeredCount(row) > 0 ? 'bg-warning text-dark' : 'bg-secondary')">
                                                {{ answeredCount(row) }} / {{ essays.length }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <span :class="row.total !== null ? 'text-success' : 'text-muted'">
                                                {{ row.total !== null ? row.total.toFixed(2) : '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-primary" @click.stop="openDrawer(i)">
                                                <i class="fa fa-pen me-1"></i> Nilai
                                            </button>
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

    <!-- Backdrop + Drawer penilaian (slide dari kanan) -->
    <transition name="fade">
        <div v-if="drawerOpen" class="drawer-backdrop" @click="closeDrawer"></div>
    </transition>

    <transition name="slide">
        <div v-if="drawerOpen && currentRow" class="scoring-drawer shadow-lg">
            <!-- Header drawer (sticky) -->
            <div class="drawer-header bg-gray-800 text-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa fa-user me-2"></i>{{ currentStudent?.student?.name ?? '-' }}
                        <span v-if="currentStudent?.attempt > 1" class="badge bg-warning text-dark ms-1">Remidi</span>
                    </h6>
                    <button class="btn btn-sm btn-light" @click="closeDrawer">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center small">
                    <span>
                        {{ currentStudent?.student?.no_participant ?? '-' }}
                        &nbsp;|&nbsp; Peserta {{ currentIndex + 1 }} / {{ form.length }}
                    </span>
                    <span>
                        Total:
                        <span class="badge" :class="currentRow.total !== null ? 'bg-success' : 'bg-secondary'">
                            {{ currentRow.total !== null ? currentRow.total.toFixed(2) : 'belum lengkap' }}
                        </span>
                    </span>
                </div>
            </div>

            <!-- Body drawer (scroll internal) -->
            <div class="drawer-body">
                <div v-for="(ans, ai) in currentRow.answers" :key="ans.answer_essay_id ?? ai"
                    class="card border mb-3">
                    <div class="card-header bg-light fw-semibold d-flex justify-content-between py-2">
                        <span><i class="fa fa-question-circle me-2"></i>Soal {{ ai + 1 }}</span>
                        <span class="badge bg-dark" v-if="ans.score !== null && ans.score !== ''">
                            Nilai: {{ ans.score }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="small fw-semibold text-muted mb-1">PERTANYAAN</div>
                            <div class="p-2 bg-light rounded" style="line-height:1.6; font-size:0.9rem"
                                v-html="essays[ai]?.question || '<em class=\'text-muted\'>—</em>'">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="small fw-semibold text-muted mb-1">JAWABAN PESERTA</div>
                            <div class="p-2 border rounded bg-white" style="line-height:1.6; font-size:0.9rem; min-height:70px"
                                v-html="ans.answer_text || '<em class=\'text-muted\'>(Peserta tidak menjawab)</em>'">
                            </div>
                        </div>

                        <div>
                            <label class="fw-semibold small mb-1">Nilai (0–100) <span class="text-danger">*</span></label>
                            <input type="number" min="0" max="100" step="0.01"
                                v-model.number="ans.score"
                                @input="recalcTotal(currentIndex)"
                                class="form-control"
                                placeholder="Masukkan nilai">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer drawer (sticky): navigasi antar peserta -->
            <div class="drawer-footer bg-light border-top">
                <div class="d-flex justify-content-between gap-2">
                    <button class="btn btn-light border" :disabled="currentIndex === 0" @click="goPrev">
                        <i class="fa fa-chevron-left me-1"></i> Sebelumnya
                    </button>
                    <button @click="saveAll" :disabled="saving" class="btn btn-success border-0 flex-fill">
                        <i class="fa fa-save me-1"></i>
                        {{ saving ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                    <button class="btn btn-light border" :disabled="currentIndex >= form.length - 1" @click="goNext">
                        Berikutnya <i class="fa fa-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </transition>
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
            drawerOpen: false,
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
        fullyScoredCount() {
            return this.form.filter(r => this.answeredCount(r) === this.essays.length && this.essays.length > 0).length;
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

        openDrawer(i) {
            this.currentIndex = i;
            this.drawerOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closeDrawer() {
            this.drawerOpen = false;
            document.body.style.overflow = '';
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

        goPrev() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.scrollDrawerTop();
            }
        },

        goNext() {
            if (this.currentIndex < this.form.length - 1) {
                this.currentIndex++;
                this.scrollDrawerTop();
            }
        },

        scrollDrawerTop() {
            this.$nextTick(() => {
                const body = this.$el.querySelector?.('.drawer-body');
                if (body) body.scrollTop = 0;
            });
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

    beforeUnmount() {
        document.body.style.overflow = '';
    },
}
</script>

<style scoped>
.drawer-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .45);
    z-index: 1040;
}

.scoring-drawer {
    position: fixed;
    top: 0;
    right: 0;
    height: 100vh;
    width: 90%;
    max-width: 960px;
    background: #fff;
    z-index: 1050;
    display: flex;
    flex-direction: column;
}

.drawer-header {
    padding: 1rem 1.25rem;
    flex-shrink: 0;
}

.drawer-body {
    flex: 1 1 auto;
    overflow-y: auto;
    padding: 1.25rem;
}

.drawer-footer {
    flex-shrink: 0;
    padding: .75rem 1.25rem;
}

/* transitions */
.slide-enter-active,
.slide-leave-active {
    transition: transform .28s ease;
}
.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity .28s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
