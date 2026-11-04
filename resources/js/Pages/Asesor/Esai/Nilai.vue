<template>
    <Head><title>Nilai Esai — {{ student.name }}</title></Head>
    <div class="container-fluid mb-5 mt-5 page-pad">
        <div class="row">
            <div class="col-12">

                <SessionHeader title="Penilaian Esai" icon="fa-pen" :exam-session="exam_session" :back-href="listUrl"
                    :extra="[{ label: 'Jumlah Soal', value: `${essays.length} soal` }]" />

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>

                <SectionCard :title="student.name" icon="fa-user">
                    <template #meta>
                        {{ student.no_participant ?? '-' }} · Peserta {{ nav.position }} / {{ nav.count }}
                        · Dinilai {{ scoredCount }} / {{ essays.length }} soal
                        <span v-if="student.attempt > 1" class="badge bg-warning text-gray-800 ms-1">Remidi</span>
                    </template>
                    <template #actions>
                        <div class="text-end me-2">
                            <div class="small text-muted">Total Nilai</div>
                            <div class="fs-4 fw-bold num lh-1" :class="total !== null ? 'text-success' : 'text-muted'">
                                {{ total !== null ? total.toFixed(2) : '—' }}
                            </div>
                        </div>
                    </template>

                    <div class="p-3 p-lg-4 bg-gray-50">
                        <div v-if="!essays.length" class="text-center text-muted py-5">
                            <i class="fa fa-file-alt fa-2x d-block mb-2 text-gray-300"></i>
                            <strong class="d-block">Ujian ini belum punya soal esai</strong>
                        </div>

                        <!-- Essay Migas: satu berkas untuk seluruh soal -->
                        <div v-if="files.length || isMigas" class="card border-0 shadow mb-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3"><i class="fa fa-paperclip me-2"></i>Berkas jawaban</h6>
                                <div v-for="f in files" :key="f.download_url"
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
                                <div v-if="!files.length" class="text-muted small">
                                    <i class="fa fa-info-circle me-1"></i>Peserta belum mengunggah berkas jawaban.
                                </div>
                            </div>
                        </div>

                        <div v-for="(item, ai) in form" :key="item.essay.id" class="card border-0 shadow mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                    <span class="fw-bolder text-gray-800">Soal {{ ai + 1 }}</span>
                                    <StatusBadge v-if="isScored(item.score)" tone="accent" :label="`Nilai ${Number(item.score).toFixed(2)}`" class="num" />
                                    <StatusBadge v-else tone="neutral" label="Belum dinilai" />
                                </div>

                                <div class="row g-3">
                                    <!-- Kiri: pertanyaan + kunci/panduan jawaban -->
                                    <div class="col-xl-6">
                                        <div class="small text-muted fw-bolder text-uppercase mb-1">Pertanyaan</div>
                                        <div class="rich" v-html="item.essay.question || '—'"></div>

                                        <template v-if="item.essay.guide">
                                            <button type="button" class="btn btn-link btn-sm px-0 mt-2" @click="item.openGuide = !item.openGuide">
                                                <i :class="item.openGuide ? 'fa fa-chevron-down' : 'fa fa-chevron-right'" class="me-1"></i>
                                                {{ item.openGuide ? 'Sembunyikan' : 'Lihat' }} {{ item.essay.guide_label.toLowerCase() }}
                                            </button>
                                            <div v-if="item.openGuide" class="guide-box rich" v-html="item.essay.guide"></div>
                                        </template>
                                    </div>

                                    <!-- Kanan: jawaban peserta + nilai -->
                                    <div class="col-xl-6">
                                        <div class="small text-muted fw-bolder text-uppercase mb-1">Jawaban peserta</div>
                                        <div v-if="item.answer_text" class="answer-box rich" v-html="item.answer_text"></div>
                                        <div v-else-if="item.by_file" class="answer-box text-muted"><i class="fa fa-paperclip me-1"></i>Dijawab lewat berkas di atas.</div>
                                        <div v-else class="answer-box text-muted fst-italic">Tidak menjawab.</div>

                                        <div class="score-row mt-3">
                                            <label :for="`score-${ai}`" class="fw-bolder small mb-0">Nilai (0–100) <span class="text-danger">*</span></label>
                                            <input :id="`score-${ai}`" type="number" min="0" max="100" step="0.01"
                                                v-model.number="item.score"
                                                :disabled="!item.answer_essay_id"
                                                class="form-control score-input"
                                                :placeholder="item.answer_essay_id ? '0–100' : 'tidak ada jawaban'">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </SectionCard>

            </div>
        </div>
    </div>

    <!-- Bar navigasi melayang (sticky tidak menempel di main.content Volt) -->
    <div class="grade-bar d-flex flex-wrap align-items-center gap-2 bg-gray-800 text-white rounded shadow px-3 py-2">
        <span class="small me-1">
            <template v-if="dirty"><i class="fa fa-exclamation-circle me-1 text-warning"></i>Belum disimpan</template>
            <template v-else>Peserta {{ nav.position }} / {{ nav.count }}</template>
        </span>
        <button class="btn btn-sm btn-gray-100" :disabled="!nav.prev_id || saving" @click="go(nav.prev_id)">
            <i class="fa fa-chevron-left me-1"></i> Sebelumnya
        </button>
        <button class="btn btn-sm btn-success border-0" :disabled="saving || !dirty" @click="save()">
            <i class="fa fa-save me-1"></i> {{ saving ? 'Menyimpan...' : 'Simpan' }}
        </button>
        <button class="btn btn-sm btn-primary border-0" :disabled="!nav.next_id || saving" @click="save(nav.next_id)">
            {{ dirty ? 'Simpan & Berikutnya' : 'Berikutnya' }} <i class="fa fa-chevron-right ms-1"></i>
        </button>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, router } from '@inertiajs/vue3';
import SessionHeader from '../../../Components/Asesor/SessionHeader.vue';
import SectionCard from '../../../Components/SectionCard.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';

export default {
    layout: LayoutAsesor,
    components: { Head, SessionHeader, SectionCard, StatusBadge },

    props: {
        exam_session: Object,
        essays: Array,      // [{ id, number, question, guide, guide_label }] — urutan resmi (id naik)
        student: Object,    // { id, name, no_participant, attempt }
        answers: Array,     // [{ id, essay_id, by_file, answer, score }]
        files: Array,       // Essay Migas: [{ name, download_url, preview_url }]
        nav: Object,        // { position, count, prev_id, next_id }
    },

    data() {
        return {
            form: this.buildForm(),
            saved: null,
            saving: false,
            successMsg: '',
        };
    },

    computed: {
        listUrl() {
            return `/asesor/penilaian/${this.exam_session.id}/esai`;
        },
        isMigas() {
            return this.exam_session?.exam_esai?.type === 'Essay Migas';
        },
        scored() {
            return this.form.filter(f => this.isScored(f.score));
        },
        scoredCount() {
            return this.scored.length;
        },
        // Rata-rata nilai soal yang sudah diisi (nilai 0 ikut dihitung) — server menghitung ulang saat simpan
        total() {
            if (!this.scored.length) return null;
            return Math.round(this.scored.reduce((a, f) => a + Number(f.score), 0) / this.scored.length * 100) / 100;
        },
        snapshot() {
            return JSON.stringify(this.form.map(f => (this.isScored(f.score) ? Number(f.score) : null)));
        },
        dirty() {
            return this.saved !== null && this.snapshot !== this.saved;
        },
    },

    created() {
        this.saved = this.snapshot;
    },

    mounted() {
        // Peringatan bila meninggalkan halaman dengan nilai yang belum disimpan
        this.removeBefore = router.on('before', (event) => {
            if (this.dirty && !this.saving && event.detail.visit.method === 'get') {
                return window.confirm('Ada nilai yang belum disimpan. Tinggalkan halaman ini?');
            }
        });
        window.addEventListener('beforeunload', this.onBeforeUnload);
    },

    beforeUnmount() {
        this.removeBefore?.();
        window.removeEventListener('beforeunload', this.onBeforeUnload);
    },

    methods: {
        buildForm() {
            // Jawaban dicocokkan ke soalnya lewat essay_id, bukan posisi
            return this.essays.map(essay => {
                const ans = this.answers.find(a => a.essay_id === essay.id) || null;
                return {
                    essay,
                    answer_essay_id: ans?.id ?? null,
                    answer_text:     ans?.answer ?? '',
                    by_file:         ans?.by_file ?? false,
                    score:           ans?.score ?? null,
                    openGuide:       false,
                };
            });
        },

        isScored(v) {
            return v !== null && v !== '' && v !== undefined && !isNaN(v);
        },

        onBeforeUnload(e) {
            if (this.dirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        },

        studentUrl(id) {
            return `${this.listUrl}/peserta/${id}`;
        },

        go(id) {
            router.visit(this.studentUrl(id));
        },

        // Simpan nilai peserta ini; bila nextId diisi, lanjut ke peserta berikutnya setelah tersimpan.
        save(nextId = null) {
            if (!this.dirty) {
                if (nextId) this.go(nextId);
                return;
            }

            const answers = this.form
                .filter(f => f.answer_essay_id)
                .map(f => ({ answer_essay_id: f.answer_essay_id, score: this.isScored(f.score) ? f.score : null }));

            if (!answers.length) return;

            this.saving = true;
            router.post(
                `/asesor/penilaian/${this.exam_session.id}/esai`,
                { scores: [{ student_id: this.student.id, answers }] },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.saved = this.snapshot;
                        this.successMsg = 'Nilai berhasil disimpan.';
                        if (nextId) this.go(nextId);
                    },
                    onFinish: () => { this.saving = false; },
                },
            );
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
    },
};
</script>

<style scoped>
.num { font-variant-numeric: tabular-nums; }

/* Ruang untuk bar navigasi melayang di bawah */
.page-pad { padding-bottom: 4rem; }

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

/* position: sticky tidak menempel di dalam main.content (Volt memberi overflow: hidden), jadi bar ini fixed */
.grade-bar {
    position: fixed;
    right: 1.5rem;
    bottom: 1.5rem;
    z-index: 1030;
    max-width: calc(100vw - 3rem);
}
</style>
