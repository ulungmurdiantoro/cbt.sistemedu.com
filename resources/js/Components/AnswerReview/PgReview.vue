<template>
    <!-- Detail jawaban Pilihan Ganda: ringkasan benar/salah/kosong, peta nomor, lalu satu kartu per soal. -->
    <div>
        <div class="card border-0 shadow mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="stat stat-benar"><i class="fa fa-check me-1"></i>Benar <b class="num">{{ count.benar }}</b></span>
                        <span class="stat stat-salah"><i class="fa fa-times me-1"></i>Salah <b class="num">{{ count.salah }}</b></span>
                        <span class="stat stat-kosong"><i class="far fa-circle me-1"></i>Kosong <b class="num">{{ count.kosong }}</b></span>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Saring soal">
                        <button v-for="f in filters" :key="f.key" type="button" class="btn"
                            :class="filter === f.key ? 'btn-gray-800' : 'btn-gray-100 border'"
                            @click="filter = f.key">
                            {{ f.label }} ({{ f.key === 'semua' ? questions.length : count[f.key] }})
                        </button>
                    </div>
                </div>

                <div class="small text-muted mb-2">Peta jawaban — klik nomor untuk loncat ke soalnya.</div>
                <div class="answer-map">
                    <button v-for="q in questions" :key="q.number" type="button" class="map-cell" :class="`map-${q.status}`"
                        :title="`Soal ${q.number}: ${statusLabel[q.status]}`" @click="jump(q.number)">
                        {{ q.number }}
                    </button>
                </div>
            </div>
        </div>

        <div v-for="q in visible" :id="`soal-${q.number}`" :key="q.number" class="card border-0 shadow mb-3 question-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <span class="fw-bolder text-gray-800">Soal {{ q.number }}</span>
                    <StatusBadge :tone="statusTone[q.status]" :label="statusLabel[q.status]" />
                </div>
                <div class="rich mb-3" v-html="q.question"></div>

                <ul class="list-unstyled mb-0">
                    <li v-for="o in q.options" :key="o.key" class="opt" :class="optionClass(q, o)">
                        <span class="opt-letter">{{ letter(o.key) }}</span>
                        <span class="opt-text rich" v-html="o.text"></span>
                        <span class="opt-tags">
                            <span v-if="o.key === q.correct" class="opt-tag tag-key"><i class="fa fa-key me-1"></i>Kunci</span>
                            <span v-if="o.key === q.chosen" class="opt-tag" :class="q.status === 'benar' ? 'tag-right' : 'tag-wrong'">
                                <i :class="q.status === 'benar' ? 'fa fa-check' : 'fa fa-times'" class="me-1"></i>Jawaban peserta
                            </span>
                        </span>
                    </li>
                </ul>
                <div v-if="q.status === 'kosong'" class="small text-muted mt-2">
                    <i class="far fa-circle me-1"></i>Peserta tidak menjawab soal ini.
                </div>
            </div>
        </div>

        <div v-if="visible.length === 0" class="card border-0 shadow">
            <div class="card-body text-center text-muted py-5">
                <i class="fa fa-check-circle fa-2x d-block mb-2 text-gray-300"></i>
                <strong class="d-block">{{ questions.length ? `Tidak ada soal ${statusLabel[filter].toLowerCase()}` : 'Ujian ini belum punya soal' }}</strong>
                <span v-if="questions.length" class="small">Pilih "Semua" untuk melihat seluruh soal.</span>
            </div>
        </div>
    </div>
</template>

<script>
import StatusBadge from '../StatusBadge.vue';

export default {
    name: 'PgReview',
    components: { StatusBadge },
    props: {
        // [{ number, question, options: [{ key, text }], correct, chosen, status: benar|salah|kosong }]
        questions: { type: Array, required: true },
    },
    data() {
        return {
            filter: 'semua',
            filters: [
                { key: 'semua',  label: 'Semua' },
                { key: 'salah',  label: 'Salah' },
                { key: 'kosong', label: 'Kosong' },
            ],
            statusLabel: { benar: 'Benar', salah: 'Salah', kosong: 'Kosong' },
            statusTone:  { benar: 'success', salah: 'danger', kosong: 'neutral' },
        };
    },
    computed: {
        count() {
            const c = { benar: 0, salah: 0, kosong: 0 };
            this.questions.forEach(q => { c[q.status]++; });
            return c;
        },
        visible() {
            return this.filter === 'semua' ? this.questions : this.questions.filter(q => q.status === this.filter);
        },
    },
    methods: {
        letter(key) {
            return 'ABCDE'[key - 1] ?? '?';
        },
        optionClass(q, o) {
            if (o.key === q.chosen) return q.status === 'benar' ? 'opt-right' : 'opt-wrong';
            return o.key === q.correct ? 'opt-key' : '';
        },
        jump(number) {
            if (!this.visible.some(q => q.number === number)) this.filter = 'semua';
            this.$nextTick(() => {
                document.getElementById(`soal-${number}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },
    },
}
</script>

<style scoped>
.num { font-variant-numeric: tabular-nums; }

.stat { display: inline-flex; align-items: center; gap: .25rem; padding: .35rem .75rem; border-radius: 50rem; font-size: .85rem; }
.stat-benar  { background: rgba(16, 185, 129, .14); color: #0a6b4a; }
.stat-salah  { background: rgba(225, 29, 72, .12);  color: #a01233; }
.stat-kosong { background: #e5e7eb;                 color: #4b5563; }

.answer-map { display: flex; flex-wrap: wrap; gap: .35rem; }
.map-cell {
    width: 2.25rem; height: 2.25rem; border-radius: .4rem; border: 1px solid transparent;
    font-size: .8rem; font-weight: 600; font-variant-numeric: tabular-nums; cursor: pointer;
}
.map-benar  { background: rgba(16, 185, 129, .16); color: #0a6b4a; border-color: rgba(16, 185, 129, .35); }
.map-salah  { background: rgba(225, 29, 72, .14);  color: #a01233; border-color: rgba(225, 29, 72, .35); }
.map-kosong { background: #f3f4f6;                 color: #6b7280; border-color: #e5e7eb; }
.map-cell:hover { filter: brightness(.95); }

/* jarak saat loncat lewat peta (di bawah navbar) */
.question-card { scroll-margin-top: 1rem; }

.opt {
    display: flex; align-items: flex-start; gap: .75rem;
    padding: .55rem .75rem; margin-bottom: .4rem;
    border: 1px solid #e5e7eb; border-radius: .5rem;
}
.opt-letter {
    flex-shrink: 0; width: 1.6rem; height: 1.6rem; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    background: #f3f4f6; font-weight: 700; font-size: .8rem;
}
.opt-text { flex: 1 1 0; min-width: 0; }
.opt-tags { display: flex; flex-wrap: wrap; gap: .25rem; justify-content: flex-end; flex-shrink: 0; }
.opt-tag { font-size: .72rem; font-weight: 700; padding: .15rem .5rem; border-radius: 50rem; white-space: nowrap; }

.opt-key   { border-color: rgba(16, 185, 129, .45); background: rgba(16, 185, 129, .06); }
.opt-right { border-color: rgba(16, 185, 129, .7);  background: rgba(16, 185, 129, .12); }
.opt-wrong { border-color: rgba(225, 29, 72, .6);   background: rgba(225, 29, 72, .08); }
.opt-key .opt-letter, .opt-right .opt-letter { background: #10b981; color: #fff; }
.opt-wrong .opt-letter { background: #e11d48; color: #fff; }

.tag-key   { background: rgba(16, 185, 129, .16); color: #0a6b4a; }
.tag-right { background: #10b981; color: #fff; }
.tag-wrong { background: #e11d48; color: #fff; }

.rich :deep(p:last-child) { margin-bottom: 0; }
.rich :deep(img) { max-width: 100%; height: auto; }

@media (max-width: 575.98px) {
    .opt { flex-wrap: wrap; }
    .opt-tags { width: 100%; justify-content: flex-start; padding-left: 2.35rem; }
}
</style>
