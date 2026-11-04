<template>
    <Head><title>Penilaian Esai — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-12">

                <SessionHeader title="Penilaian Esai" icon="fa-pen" :exam-session="exam_session"
                    :extra="[{ label: 'Jumlah Soal', value: `${essays.length} soal` }]" />

                <PageGuide storage-key="asesor.esai"><GuideEsai /></PageGuide>

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>

                <div v-if="students_data.length === 0" class="alert alert-info">
                    Tidak ada peserta yang ditugaskan di sesi ini.
                </div>

                <!-- Daftar peserta (master) -->
                <SectionCard v-else title="Daftar Peserta" icon="fa-users">
                    <template #meta>
                        Selesai dinilai: {{ fullyScoredCount }} / {{ form.length }} · Rata-rata: {{ sessionAvg() }}
                    </template>
                    <template #actions>
                        <button @click="saveAll" :disabled="saving" class="btn btn-success border-0 shadow">
                            <i class="fa fa-save me-1"></i>
                            {{ saving ? 'Menyimpan...' : 'Simpan Semua Nilai' }}
                        </button>
                    </template>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm table-hover align-middle mb-0">
                                <thead class="table-dark">
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
                                            <span v-if="students_data[i]?.attempt > 1" class="badge bg-warning text-gray-800 ms-1" style="font-size:0.65rem">Remidi</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge"
                                                :class="answeredCount(row) === essays.length ? 'bg-success' : (answeredCount(row) > 0 ? 'bg-warning text-gray-800' : 'bg-secondary')">
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
                </SectionCard>

            </div>
        </div>
    </div>

    <!-- Backdrop + Drawer penilaian (slide dari kanan) -->
    <transition name="fade">
        <div v-if="drawerOpen" class="drawer-backdrop" @click="closeDrawer"></div>
    </transition>

    <transition name="slide">
        <div v-if="drawerOpen && currentRow" class="scoring-drawer shadow-lg">
            <!-- Header drawer: peserta + total -->
            <div class="drawer-header bg-white border-bottom">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="flex-grow-1" style="min-width:0">
                        <h5 class="mb-1 fw-bold text-gray-800 text-truncate">
                            <i class="fa fa-user me-2 text-muted"></i>{{ currentStudent?.student?.name ?? '-' }}
                            <span v-if="currentStudent?.attempt > 1" class="badge bg-warning text-gray-800 ms-1">Remidi</span>
                        </h5>
                        <div class="small text-muted">
                            {{ currentStudent?.student?.no_participant ?? '-' }} · Peserta {{ currentIndex + 1 }} / {{ form.length }}
                            · Dinilai {{ answeredCount(currentRow) }} / {{ essays.length }} soal
                        </div>
                    </div>
                    <div class="text-end me-2">
                        <div class="small text-muted">Total Nilai</div>
                        <div class="fs-4 fw-bold num" :class="currentRow.total !== null ? 'text-success' : 'text-muted'">
                            {{ currentRow.total !== null ? currentRow.total.toFixed(2) : '—' }}
                        </div>
                    </div>
                    <button class="btn btn-gray-100 border" @click="closeDrawer" title="Tutup">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Body drawer (scroll internal): kartu per soal, senada dengan detail jawaban admin -->
            <div class="drawer-body bg-gray-50">
                <!-- Essay Migas: satu berkas untuk seluruh soal -->
                <div v-if="currentStudent?.files?.length || isMigas" class="card border-0 shadow mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="fa fa-paperclip me-2"></i>Berkas jawaban</h6>
                        <div v-for="f in currentStudent?.files ?? []" :key="f.download_url"
                            class="d-flex flex-wrap align-items-center gap-3 p-3 border rounded mb-2">
                            <i :class="fileIcon(f.name)" class="fa-2x"></i>
                            <div class="flex-grow-1 text-break" style="min-width:0">
                                <div class="fw-bolder">{{ f.name }}</div>
                                <div class="small text-muted">Satu berkas untuk semua soal di ujian ini.</div>
                            </div>
                            <div class="d-flex gap-2">
                                <a v-if="f.preview_url" :href="f.preview_url" target="_blank" rel="noopener" class="btn btn-sm btn-gray-100 border">
                                    <i class="fa fa-eye me-1"></i>Pratinjau
                                </a>
                                <a :href="f.download_url" class="btn btn-sm btn-primary">
                                    <i class="fa fa-download me-1"></i>Unduh
                                </a>
                            </div>
                        </div>
                        <div v-if="!currentStudent?.files?.length" class="text-muted small">
                            <i class="fa fa-info-circle me-1"></i>Peserta belum mengunggah berkas jawaban.
                        </div>
                    </div>
                </div>

                <div v-for="(ans, ai) in currentRow.answers" :key="essays[ai]?.id ?? ai" class="card border-0 shadow mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <span class="fw-bolder text-gray-800">Soal {{ ai + 1 }}</span>
                            <StatusBadge v-if="isScored(ans.score)" tone="accent" :label="`Nilai ${Number(ans.score).toFixed(2)}`" class="num" />
                            <StatusBadge v-else tone="neutral" label="Belum dinilai" />
                        </div>

                        <div class="row g-3">
                            <!-- Kiri: pertanyaan + kunci/panduan jawaban -->
                            <div class="col-xl-6">
                                <div class="small text-muted fw-bolder text-uppercase mb-1">Pertanyaan</div>
                                <div class="rich" v-html="essays[ai]?.question || '—'"></div>

                                <template v-if="essays[ai]?.guide">
                                    <button type="button" class="btn btn-link btn-sm px-0 mt-2" @click="toggleGuide(essays[ai].id)">
                                        <i :class="openGuide[essays[ai].id] ? 'fa fa-chevron-down' : 'fa fa-chevron-right'" class="me-1"></i>
                                        {{ openGuide[essays[ai].id] ? 'Sembunyikan' : 'Lihat' }} {{ essays[ai].guide_label.toLowerCase() }}
                                    </button>
                                    <div v-if="openGuide[essays[ai].id]" class="guide-box rich" v-html="essays[ai].guide"></div>
                                </template>
                            </div>

                            <!-- Kanan: jawaban peserta + nilai -->
                            <div class="col-xl-6">
                                <div class="small text-muted fw-bolder text-uppercase mb-1">Jawaban peserta</div>
                                <div v-if="ans.answer_text" class="answer-box rich" v-html="ans.answer_text"></div>
                                <div v-else-if="ans.by_file" class="answer-box text-muted"><i class="fa fa-paperclip me-1"></i>Dijawab lewat berkas di atas.</div>
                                <div v-else class="answer-box text-muted fst-italic">Tidak menjawab.</div>

                                <div class="score-row mt-3">
                                    <label :for="`score-${ai}`" class="fw-bolder small mb-0">Nilai (0–100) <span class="text-danger">*</span></label>
                                    <input :id="`score-${ai}`" type="number" min="0" max="100" step="0.01"
                                        v-model.number="ans.score"
                                        @input="recalcTotal(currentIndex)"
                                        :disabled="!ans.answer_essay_id"
                                        class="form-control score-input"
                                        :placeholder="ans.answer_essay_id ? '0–100' : 'tidak ada jawaban'">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer drawer: navigasi antar peserta -->
            <div class="drawer-footer bg-white border-top">
                <div class="d-flex justify-content-between gap-2">
                    <button class="btn btn-gray-100 border" :disabled="currentIndex === 0" @click="goPrev">
                        <i class="fa fa-chevron-left me-1"></i> Sebelumnya
                    </button>
                    <button @click="saveAll" :disabled="saving" class="btn btn-success border-0 flex-fill">
                        <i class="fa fa-save me-1"></i>
                        {{ saving ? 'Menyimpan...' : 'Simpan Semua Nilai' }}
                    </button>
                    <button class="btn btn-gray-100 border" :disabled="currentIndex >= form.length - 1" @click="goNext">
                        Berikutnya <i class="fa fa-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, router } from '@inertiajs/vue3';
import PageGuide from '../../../Components/PageGuide.vue';
import GuideEsai from '../../../Components/Guide/Asesor/Esai.vue';
import SessionHeader from '../../../Components/Asesor/SessionHeader.vue';
import SectionCard from '../../../Components/SectionCard.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';

export default {
    layout: LayoutAsesor,
    components: { Head, PageGuide, GuideEsai, SessionHeader, SectionCard, StatusBadge },

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
            openGuide: {},
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
        isMigas() {
            return this.exam_session?.exam_esai?.type === 'Essay Migas';
        },
        fullyScoredCount() {
            return this.form.filter(r => this.answeredCount(r) === this.essays.length && this.essays.length > 0).length;
        },
    },

    methods: {
        buildForm() {
            return this.students_data.map(row => {
                // Jawaban dicocokkan ke soalnya lewat essay_id (urutan soal = urutan resmi, sama dengan
                // detail jawaban admin), bukan lewat posisi/essay_order.
                const answers = this.essays.map(essay => {
                    const ans = row.answers.find(a => a.essay_id === essay.id) || null;
                    return {
                        answer_essay_id: ans?.id ?? null,
                        answer_text:     ans?.answer ?? '',
                        by_file:         ans?.by_file ?? false,
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

        isScored(v) {
            return v !== null && v !== '' && !isNaN(v);
        },

        toggleGuide(id) {
            this.openGuide = { ...this.openGuide, [id]: !this.openGuide[id] };
        },

        fileIcon(name) {
            const ext = (name.split('.').pop() || '').toLowerCase();
            if (ext === 'pdf') return 'fa fa-file-pdf text-danger';
            if (['doc', 'docx'].includes(ext)) return 'fa fa-file-word text-primary';
            if (['xls', 'xlsx'].includes(ext)) return 'fa fa-file-excel text-success';
            if (['ppt', 'pptx'].includes(ext)) return 'fa fa-file-powerpoint text-warning';
            if (['jpg', 'jpeg', 'png'].includes(ext)) return 'fa fa-file-image text-info';
            if (['zip', 'rar'].includes(ext)) return 'fa fa-file-archive text-secondary';
            return 'fa fa-file text-muted';
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
                    // Hanya jawaban yang ada barisnya: peserta yang belum mulai ujian belum punya
                    // baris jawaban, dan server menolak answer_essay_id kosong.
                    scores: this.form
                        .map(r => ({
                            student_id: r.student_id,
                            answers: r.answers
                                .filter(a => a.answer_essay_id)
                                .map(a => ({ answer_essay_id: a.answer_essay_id, score: a.score })),
                        }))
                        .filter(r => r.answers.length),
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
    /* Hampir selebar layar: daftar peserta tetap terlihat di sisi kiri */
    width: calc(100vw - 3rem);
    max-width: 1600px;
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

@media (max-width: 767.98px) {
    .scoring-drawer { width: 100vw; }
}

.num { font-variant-numeric: tabular-nums; }

.answer-box {
    background: #f9fafb; border: 1px solid #e5e7eb; border-radius: .5rem;
    padding: .75rem 1rem; overflow-wrap: anywhere; min-height: 70px;
}
.guide-box {
    background: rgba(16, 185, 129, .06); border: 1px dashed rgba(16, 185, 129, .45); border-radius: .5rem;
    padding: .75rem 1rem; margin-top: .25rem; overflow-wrap: anywhere;
}
.rich :deep(p:last-child) { margin-bottom: 0; }
.rich :deep(img) { max-width: 100%; height: auto; }
.rich :deep(ol), .rich :deep(ul) { padding-left: 1.25rem; margin-bottom: .5rem; }

.score-row { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.score-input { width: 140px; }

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
