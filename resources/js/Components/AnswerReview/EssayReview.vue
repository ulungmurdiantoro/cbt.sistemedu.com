<template>
    <!-- Detail jawaban Esai / Essay Migas: ringkasan penilaian, berkas (Migas), lalu satu kartu per soal. -->
    <div>
        <div class="card border-0 shadow mb-3">
            <div class="card-body d-flex flex-wrap align-items-center gap-3 small">
                <span><b class="num">{{ scored.length }}/{{ essays.length }}</b> soal dinilai</span>
                <span v-if="scored.length">Rata-rata nilai <b class="num">{{ fmt(average) }}</b></span>
                <span v-if="assessors.length"><i class="fa fa-user-tie me-1 text-muted"></i>Asesor: {{ assessors.join(', ') }}</span>
                <span v-if="!scored.length" class="text-muted">Belum ada jawaban yang dinilai asesor.</span>
            </div>
        </div>

        <!-- Essay Migas: satu berkas untuk seluruh soal -->
        <div v-if="migas" class="card border-0 shadow mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="fa fa-paperclip me-2"></i>Berkas jawaban</h6>
                <div v-for="f in files" :key="f.download_url" class="d-flex flex-wrap align-items-center gap-3 p-3 border rounded mb-2">
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

        <div v-for="e in essays" :key="e.number" class="card border-0 shadow mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <span class="fw-bolder text-gray-800">Soal {{ e.number }}</span>
                    <StatusBadge v-if="e.score !== null" tone="accent" :label="`Nilai ${fmt(e.score)}`" class="num" />
                    <StatusBadge v-else tone="neutral" label="Belum dinilai" />
                </div>
                <div class="rich mb-3" v-html="e.question"></div>

                <div class="small text-muted fw-bolder text-uppercase mb-1">Jawaban peserta</div>
                <div v-if="e.answer" class="answer-box rich" v-html="e.answer"></div>
                <div v-else-if="e.by_file" class="answer-box text-muted"><i class="fa fa-paperclip me-1"></i>Dijawab lewat berkas di atas.</div>
                <div v-else class="answer-box text-muted fst-italic">Tidak menjawab.</div>

                <template v-if="e.guide">
                    <button type="button" class="btn btn-link btn-sm px-0 mt-2" @click="toggle(e.number)">
                        <i :class="open[e.number] ? 'fa fa-chevron-down' : 'fa fa-chevron-right'" class="me-1"></i>
                        {{ open[e.number] ? 'Sembunyikan' : 'Lihat' }} {{ e.guide_label.toLowerCase() }}
                    </button>
                    <div v-if="open[e.number]" class="guide-box rich" v-html="e.guide"></div>
                </template>

                <div v-if="e.assessor || e.assessed_at" class="small text-muted mt-2">
                    <i class="fa fa-user-check me-1"></i>Dinilai {{ e.assessor ?? '—' }}<template v-if="e.assessed_at"> · {{ formatDate(e.assessed_at) }}</template>
                </div>
            </div>
        </div>

        <div v-if="essays.length === 0" class="card border-0 shadow">
            <div class="card-body text-center text-muted py-5">
                <i class="fa fa-file-alt fa-2x d-block mb-2 text-gray-300"></i>
                <strong class="d-block">Ujian ini belum punya soal</strong>
            </div>
        </div>
    </div>
</template>

<script>
import StatusBadge from '../StatusBadge.vue';

export default {
    name: 'EssayReview',
    components: { StatusBadge },
    props: {
        // [{ number, question, guide, guide_label, answer, by_file, score, assessor, assessed_at }]
        essays: { type: Array, required: true },
        // Essay Migas: [{ name, download_url, preview_url }]
        files:  { type: Array, default: () => [] },
        migas:  { type: Boolean, default: false },
    },
    data() {
        return { open: {} };
    },
    computed: {
        scored() {
            return this.essays.filter(e => e.score !== null && e.score !== undefined);
        },
        // Rata-rata jawaban yang sudah dinilai (nilai 0 ikut dihitung), sama dengan Total Nilai di halaman asesor
        average() {
            return this.scored.reduce((sum, e) => sum + Number(e.score), 0) / (this.scored.length || 1);
        },
        assessors() {
            return [...new Set(this.essays.map(e => e.assessor).filter(Boolean))];
        },
    },
    methods: {
        toggle(number) {
            this.open = { ...this.open, [number]: !this.open[number] };
        },
        fmt(v) {
            return Number(v).toFixed(2);
        },
        formatDate(dt) {
            const d = new Date(String(dt).replace(' ', 'T'));
            return isNaN(d) ? dt : d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
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
}
</script>

<style scoped>
.num { font-variant-numeric: tabular-nums; }

.answer-box {
    background: #f9fafb; border: 1px solid #e5e7eb; border-radius: .5rem;
    padding: .75rem 1rem; overflow-wrap: anywhere;
}
.guide-box {
    background: rgba(16, 185, 129, .06); border: 1px dashed rgba(16, 185, 129, .45); border-radius: .5rem;
    padding: .75rem 1rem; margin-top: .25rem; overflow-wrap: anywhere;
}

.rich :deep(p:last-child) { margin-bottom: 0; }
.rich :deep(img) { max-width: 100%; height: auto; }
.rich :deep(ol), .rich :deep(ul) { padding-left: 1.25rem; margin-bottom: .5rem; }
</style>
