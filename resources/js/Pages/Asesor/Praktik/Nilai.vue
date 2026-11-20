<template>
    <Head><title>Nilai Praktik — {{ student.name }}</title></Head>
    <div class="container-fluid mb-5 mt-5 page-pad">
        <div class="row">
            <div class="col-12">

                <SessionHeader title="Penilaian Ujian Praktik" icon="fa-drafting-compass" :exam-session="exam_session" :back-href="listUrl"
                    :extra="[{ label: 'Bobot', value: parts.map(p => `${p.title}: ${p.bobot}%`).join(' · ') }]" />

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>
                <div v-if="errorMsg" class="alert alert-danger">{{ errorMsg }}</div>

                <SectionCard :title="student.name" icon="fa-user">
                    <template #meta>
                        {{ student.no_participant }} · Peserta {{ nav.position }} / {{ nav.count }}
                        <template v-if="aspekTotal"> · Aspek dinilai {{ aspekFilled }} / {{ aspekTotal }}</template>
                    </template>
                    <template #actions>
                        <div class="text-end me-2">
                            <div class="small text-muted">Nilai Praktik</div>
                            <div class="fs-4 fw-bold num lh-1" :class="total !== null ? 'text-success' : 'text-muted'">
                                {{ total !== null ? total.toFixed(2) : '—' }}
                            </div>
                        </div>
                    </template>

                    <div class="p-3 p-lg-4 bg-gray-50">
                        <div v-for="p in form" :key="p.bagian" class="mb-4">
                            <!-- Judul bagian + hasil unggahan peserta -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0">{{ p.title }}</h6>
                                    <div class="small text-muted">
                                        Bobot {{ p.bobot }}% dari nilai akhir<template v-if="p.durasi"> · {{ p.durasi }} menit</template>
                                        <template v-if="p.aspek.length"> · {{ p.aspek.length }} aspek</template>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a v-if="p.upload" :href="tugasUrl(p.bagian)" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-eye me-1"></i> Lihat hasil unggahan
                                    </a>
                                    <StatusBadge v-else tone="danger">Peserta belum mengunggah</StatusBadge>
                                    <div class="text-end ms-2">
                                        <div class="small text-muted">Nilai bagian</div>
                                        <div class="fw-bold num" :class="partScore(p) !== null ? 'text-success' : 'text-muted'">
                                            {{ partScore(p) !== null ? partScore(p).toFixed(2) : (p.aspek.length ? `${filled(p)}/${p.aspek.length}` : '—') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Per aspek rubrik -->
                            <template v-for="(a, ai) in p.aspek" :key="a.no">
                                <!-- Judul kelompok (mis. HSE: Tugas 2 berisi 2 KUK) -->
                                <div v-if="a.kelompok && a.kelompok !== p.aspek[ai - 1]?.kelompok" class="kelompok-head">{{ a.kelompok }}</div>
                                <div class="card border-0 shadow-sm mb-3 aspek-card"
                                    :class="aspekValue(p, a) !== null ? 'is-filled' : ''">
                                    <div class="card-body">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                            <h6 class="fw-bold mb-0">{{ a.item?.length ? a.nama : `${a.no}. ${a.nama}` }}</h6>
                                            <div class="d-flex align-items-center gap-2">
                                                <span v-if="a.item?.length" class="small fw-bolder num"
                                                    :class="aspekValue(p, a) !== null ? 'text-success' : 'text-muted'">
                                                    {{ itemSum(p, a) }} / 100
                                                    <span v-if="aspekValue(p, a) === null" class="fw-normal">({{ itemsPicked(p, a) }}/{{ a.item.length }} butir)</span>
                                                </span>
                                                <span class="badge bg-gray-200 text-gray-800 border">Bobot {{ a.bobot }}%</span>
                                            </div>
                                        </div>

                                        <!-- Dinilai per butir: pilih Ada & Benar / Ada tapi Salah / Tidak Ada (nilai aspek = jumlah butir) -->
                                        <template v-if="a.item?.length">
                                            <div v-for="it in a.item" :key="it.no" class="item-row">
                                                <div class="item-name small">{{ it.no }}. {{ it.nama }}</div>
                                                <div class="btn-group btn-group-sm flex-wrap" role="group">
                                                    <button v-for="o in a.pilihan" :key="o.nilai" type="button" class="btn opt"
                                                        :class="p.rincian[a.no][it.no] === o.nilai ? optActive(o, a) : 'btn-outline-gray'"
                                                        @click="pick(p, a, it, o)">
                                                        {{ o.label }} <span class="num">({{ o.nilai }})</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <label class="small fw-bolder d-block">Catatan <span class="fw-normal text-muted">(opsional)</span></label>
                                                <input type="text" class="form-control form-control-sm" maxlength="1000"
                                                    v-model="p.catatan[a.no]" placeholder="Temuan pada dokumen/gambar peserta..." />
                                            </div>
                                        </template>

                                        <template v-else>
                                        <!-- Sumber bukti, poin kunci & gerbang kritis (mis. ESG) -->
                                        <div v-if="a.sumber" class="small text-muted mb-2"><i class="fa fa-file-excel me-1"></i>Sumber bukti: {{ a.sumber }}</div>
                                        <div v-if="a.poin_kunci?.length" class="mb-2">
                                            <div class="small fw-bolder mb-1">Poin kunci</div>
                                            <ol class="small mb-0 ps-3">
                                                <li v-for="(pk, i) in a.poin_kunci" :key="i">{{ pk }}</li>
                                            </ol>
                                        </div>
                                        <div v-if="a.gerbang?.length" class="gate-box mb-3" :class="{ failed: p.gerbang[a.no] }">
                                            <div class="small fw-bolder"><i class="fa fa-door-closed me-1"></i>Gerbang kritis — bila gagal, nilai maksimal {{ a.maks_gerbang ?? 50 }}</div>
                                            <ul class="small mb-1 ps-3">
                                                <li v-for="(g, i) in a.gerbang" :key="i">{{ g }}</li>
                                            </ul>
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" :id="`gate-${p.bagian}-${a.no}`"
                                                    v-model="p.gerbang[a.no]" @change="capGate(p, a)">
                                                <label class="form-check-label small fw-bolder" :for="`gate-${p.bagian}-${a.no}`">
                                                    Ada gerbang kritis yang gagal
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Kriteria per rentang skor (FR.IA.04) — klik untuk mengisi nilai tertinggi rentang itu -->
                                        <div v-if="a.kriteria?.length" class="band-grid mb-3" :style="{ '--bands': a.kriteria.length }">
                                            <button v-for="(k, ki) in a.kriteria" :key="k.min" type="button" class="band text-start"
                                                :class="[`band-${bandTone(ki, a.kriteria.length)}`, { active: inBand(p.nilai[a.no], k) }]"
                                                @click="pickBand(p, a, k)">
                                                <div class="fw-bolder small">{{ k.min }}–{{ k.max }} · {{ k.label }}</div>
                                                <div class="band-desc">{{ k.deskripsi }}</div>
                                            </button>
                                        </div>

                                        <div class="row g-2 align-items-center">
                                            <div class="col-auto">
                                                <label class="small fw-bolder d-block">Nilai</label>
                                                <input type="number" min="0" max="100" step="0.01" class="form-control score-input"
                                                    :ref="`input-${p.bagian}-${a.no}`"
                                                    v-model.number="p.nilai[a.no]" placeholder="0–100" />
                                            </div>
                                            <div class="col-auto small text-muted pt-3">
                                                <template v-if="isFilled(p.nilai[a.no])">
                                                    × {{ a.bobot }}% = <strong class="num">{{ (p.nilai[a.no] * a.bobot / 100).toFixed(2) }}</strong>
                                                </template>
                                            </div>
                                            <div class="col-12 col-md">
                                                <label class="small fw-bolder d-block">Catatan <span class="fw-normal text-muted">(opsional)</span></label>
                                                <input type="text" class="form-control form-control-sm" maxlength="1000"
                                                    v-model="p.catatan[a.no]" placeholder="Alasan nilai, temuan pada dokumen..." />
                                            </div>
                                        </div>
                                        <div v-if="outOfRange(p.nilai[a.no])" class="small text-danger mt-1">Nilai harus 0–100.</div>
                                        <div v-else-if="overGate(p, a)" class="small text-danger mt-1">Gerbang kritis gagal: nilai maksimal {{ a.maks_gerbang ?? 50 }}.</div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Bagian tanpa aspek: satu nilai -->
                            <div v-if="!p.aspek.length" class="card border-0 shadow-sm">
                                <div class="card-body d-flex flex-wrap align-items-center gap-3">
                                    <label class="small fw-bolder mb-0">Nilai {{ p.title }}</label>
                                    <input type="number" min="0" max="100" step="0.01" class="form-control score-input"
                                        v-model.number="p.score" placeholder="0–100" />
                                </div>
                            </div>

                            <!-- Rubrik lengkap (rentang skor umum & rekapitulasi) -->
                            <details v-if="p.rubrik" class="mt-2 small">
                                <summary class="text-primary">Lihat rubrik lengkap FR.IA.04</summary>
                                <div class="rubrik mt-2" v-html="p.rubrik"></div>
                            </details>
                        </div>

                        <div class="small text-muted">
                            Nilai bagian = Σ (nilai aspek × bobot aspek), dihitung setelah semua aspek terisi
                            (aspek per butir = jumlah nilai butir, maks. 100). Isian yang belum lengkap tetap tersimpan.
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
        <button class="btn btn-sm btn-success border-0" :disabled="saving || !dirty || invalid" @click="save()">
            <i class="fa fa-save me-1"></i> {{ saving ? 'Menyimpan...' : 'Simpan' }}
        </button>
        <button class="btn btn-sm btn-primary border-0" :disabled="!nav.next_id || saving || invalid" @click="save(nav.next_id)">
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
        student: Object,
        // [{ bagian, title, durasi, bobot, rubrik, aspek: [{no, nama, bobot, kriteria: [{min, max, label, deskripsi}]}],
        //    score, nilai: {no: nilai}, catatan: {no: teks}, upload }]
        parts: Array,
        // { position, count, prev_id, next_id }
        nav: Object,
    },

    data() {
        const form = this.parts.map(p => ({
            ...p,
            nilai: Object.fromEntries(p.aspek.map(a => [a.no, p.nilai?.[a.no] ?? null])),
            catatan: Object.fromEntries(p.aspek.map(a => [a.no, p.catatan?.[a.no] ?? ''])),
            rincian: Object.fromEntries(p.aspek.filter(a => a.item?.length).map(a => [a.no, Object.fromEntries(
                a.item.map(it => [it.no, p.rincian?.[a.no]?.[it.no] ?? null])
            )])),
            // Gerbang kritis gagal per aspek (nilai dibatasi maks_gerbang)
            gerbang: Object.fromEntries(p.aspek.filter(a => a.gerbang?.length).map(a => [a.no, !!p.rincian?.[a.no]?.gerbang])),
            score: p.aspek.length ? null : p.score,
        }));

        return {
            form,
            saved: JSON.stringify(form.map(p => [p.nilai, p.catatan, p.score, p.rincian, p.gerbang])),
            saving: false,
            successMsg: '',
            errorMsg: '',
        };
    },

    computed: {
        listUrl() {
            return `/asesor/penilaian/${this.exam_session.id}/praktik`;
        },
        snapshot() {
            return JSON.stringify(this.form.map(p => [p.nilai, p.catatan, p.score, p.rincian, p.gerbang]));
        },
        dirty() {
            return this.snapshot !== this.saved;
        },
        invalid() {
            return this.form.some(p => p.aspek.some(a => this.outOfRange(p.nilai[a.no]) || this.overGate(p, a)) || this.outOfRange(p.score));
        },
        aspekTotal() {
            return this.form.reduce((n, p) => n + p.aspek.length, 0);
        },
        aspekFilled() {
            return this.form.reduce((n, p) => n + this.filled(p), 0);
        },
        // Sama dengan NilaiPraktik::combine(): rata-rata berbobot bagian yang sudah bernilai
        total() {
            let sum = 0;
            let bobot = 0;
            for (const p of this.form) {
                const v = this.partScore(p);
                if (v !== null) {
                    sum += v * p.bobot;
                    bobot += p.bobot;
                }
            }
            return bobot > 0 ? Math.round((sum / bobot) * 100) / 100 : null;
        },
    },

    mounted() {
        window.addEventListener('beforeunload', this.onBeforeUnload);
        this.removeGuard = router.on('before', (e) => {
            if (this.dirty && !this.saving && e.detail.visit.method === 'get' && !confirm('Nilai belum disimpan. Tinggalkan halaman ini?')) {
                e.preventDefault();
            }
        });
    },

    beforeUnmount() {
        window.removeEventListener('beforeunload', this.onBeforeUnload);
        if (this.removeGuard) this.removeGuard();
    },

    methods: {
        isFilled(v) {
            return v !== null && v !== '' && !isNaN(v);
        },
        outOfRange(v) {
            return this.isFilled(v) && (Number(v) < 0 || Number(v) > 100);
        },
        itemsPicked(p, a) {
            return a.item.filter(it => p.rincian[a.no][it.no] !== null).length;
        },
        itemSum(p, a) {
            return a.item.reduce((acc, it) => acc + (p.rincian[a.no][it.no] ?? 0), 0);
        },
        // Nilai aspek: per butir = jumlah butir (= NilaiPraktik::itemScore, null selama ada butir kosong), selain itu isian
        aspekValue(p, a) {
            if (a.item?.length) return this.itemsPicked(p, a) === a.item.length ? this.itemSum(p, a) : null;
            return this.isFilled(p.nilai[a.no]) ? Number(p.nilai[a.no]) : null;
        },
        filled(p) {
            return p.aspek.filter(a => this.aspekValue(p, a) !== null).length;
        },
        // = NilaiPraktik::aspekScore(): Σ nilai × bobot / Σ bobot, null selama ada aspek kosong
        partScore(p) {
            if (!p.aspek.length) return this.isFilled(p.score) ? Number(p.score) : null;
            if (this.filled(p) < p.aspek.length) return null;
            const sum = p.aspek.reduce((acc, a) => acc + this.aspekValue(p, a) * a.bobot, 0);
            const bobot = p.aspek.reduce((acc, a) => acc + a.bobot, 0);
            return bobot > 0 ? Math.round((sum / bobot) * 100) / 100 : null;
        },
        inBand(v, k) {
            return this.isFilled(v) && Number(v) >= k.min && Number(v) <= k.max;
        },
        // Warna rentang menurut urutan (tertinggi hijau … terendah merah), berapa pun jumlah rentangnya
        bandTone(i, n) {
            return (n <= 4 ? ['a', 'b', 'd', 'e'] : ['a', 'b', 'c', 'd', 'e'])[i] ?? 'e';
        },
        // Klik rentang: isi nilai tertinggi rentang itu (bila nilai sekarang di luar rentang), lalu fokus untuk disesuaikan
        pickBand(p, a, k) {
            if (!this.inBand(p.nilai[a.no], k)) {
                p.nilai[a.no] = p.gerbang?.[a.no] ? Math.min(k.max, a.maks_gerbang ?? 50) : k.max;
            }
            this.$nextTick(() => {
                const el = this.$refs[`input-${p.bagian}-${a.no}`];
                (Array.isArray(el) ? el[0] : el)?.focus();
            });
        },
        overGate(p, a) {
            return !!p.gerbang?.[a.no] && this.isFilled(p.nilai[a.no]) && Number(p.nilai[a.no]) > (a.maks_gerbang ?? 50);
        },
        // Centang gerbang gagal: nilai di atas batas langsung diturunkan ke batas
        capGate(p, a) {
            if (this.overGate(p, a)) p.nilai[a.no] = a.maks_gerbang ?? 50;
        },
        // Klik pilihan yang sama lagi = batalkan pilihan butir itu
        pick(p, a, it, o) {
            p.rincian[a.no][it.no] = p.rincian[a.no][it.no] === o.nilai ? null : o.nilai;
        },
        optActive(o, a) {
            const max = Math.max(...a.pilihan.map(x => x.nilai));
            return o.nilai === max ? 'btn-success' : o.nilai === 0 ? 'btn-danger' : 'btn-warning';
        },
        tugasUrl(bagian) {
            return `/asesor/penilaian/${this.exam_session.id}/wawancara/tugas/${this.student.id}` + (bagian > 1 ? `?bagian=${bagian}` : '');
        },
        onBeforeUnload(e) {
            if (this.dirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        },
        go(id) {
            router.visit(`${this.listUrl}/peserta/${id}`);
        },
        // Simpan nilai peserta ini; bila nextId diisi, lanjut ke peserta berikutnya setelah tersimpan.
        save(nextId = null) {
            if (!this.dirty) {
                if (nextId) this.go(nextId);
                return;
            }

            const item = {
                student_id: this.student.id,
                nilai: Object.fromEntries(this.form.filter(p => !p.aspek.length)
                    .map(p => [p.bagian, this.isFilled(p.score) ? p.score : null])),
                aspek: Object.fromEntries(this.form.filter(p => p.aspek.length)
                    .map(p => [p.bagian, Object.fromEntries(p.aspek.map(a => [a.no, this.isFilled(p.nilai[a.no]) ? p.nilai[a.no] : null]))])),
                rincian: Object.fromEntries(this.form.filter(p => p.aspek.some(a => a.item?.length || a.gerbang?.length))
                    .map(p => [p.bagian, {
                        ...p.rincian,
                        ...Object.fromEntries(Object.entries(p.gerbang).map(([no, gagal]) => [no, { gerbang: gagal }])),
                    }])),
                catatan: Object.fromEntries(this.form.filter(p => p.aspek.length)
                    .map(p => [p.bagian, Object.fromEntries(p.aspek.map(a => [a.no, p.catatan[a.no] || null]))])),
            };

            this.saving = true;
            this.errorMsg = '';
            router.post(`${this.listUrl}`, { scores: [item] }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.saved = this.snapshot;
                    this.successMsg = 'Nilai praktik berhasil disimpan.';
                    if (nextId) this.go(nextId);
                },
                onError: (errors) => { this.errorMsg = Object.values(errors)[0] ?? 'Gagal menyimpan nilai praktik.'; },
                onFinish: () => { this.saving = false; },
            });
        },
    },
};
</script>

<style scoped>
.num { font-variant-numeric: tabular-nums; }
.page-pad { padding-bottom: 4rem; }
.score-input { width: 120px; }

.aspek-card { border-left: 4px solid #e5e7eb !important; }
.aspek-card.is-filled { border-left-color: #10b981 !important; }

.kelompok-head { font-weight: 700; font-size: .85rem; color: #1f2937; border-bottom: 2px solid #e5e7eb; padding: .25rem 0; margin: .75rem 0 .5rem; }
.gate-box { border: 1px dashed #f59e0b; background: rgba(245, 158, 11, .06); border-radius: .5rem; padding: .5rem .75rem; }
.gate-box.failed { border-color: #ef4444; background: rgba(239, 68, 68, .06); }
.item-row {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem;
    padding: .4rem 0; border-bottom: 1px dashed #e5e7eb;
}
.item-row:last-of-type { border-bottom: 0; }
.item-name { flex: 1 1 220px; }
.opt { font-size: .75rem; }
.btn-outline-gray { border: 1px solid #d1d5db; color: #374151; background: #fff; }
.btn-outline-gray:hover { background: #f3f4f6; }
.band-grid { display: grid; grid-template-columns: repeat(var(--bands, 5), minmax(0, 1fr)); gap: .5rem; }
@media (max-width: 991.98px) { .band-grid { grid-template-columns: 1fr; } }
.band {
    background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; padding: .5rem .65rem;
    color: #1f2937; transition: border-color .15s, box-shadow .15s;
}
.band:hover { border-color: #9ca3af; }
.band-desc { font-size: .78rem; line-height: 1.35; color: #4b5563; margin-top: .2rem; }
.band.active { box-shadow: 0 0 0 2px currentColor inset; }
.band-a.active { background: rgba(16, 185, 129, .08); color: #047857; }
.band-b.active { background: rgba(59, 130, 246, .08); color: #1d4ed8; }
.band-c.active { background: rgba(245, 158, 11, .1); color: #b45309; }
.band-d.active { background: rgba(249, 115, 22, .1); color: #c2410c; }
.band-e.active { background: rgba(239, 68, 68, .08); color: #b91c1c; }
.band.active .band-desc { color: inherit; }

.rubrik :deep(table) { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
.rubrik :deep(th), .rubrik :deep(td) { border: 1px solid #d1d5db; padding: 4px 6px; vertical-align: top; }
.rubrik :deep(tr:first-child) { background: #1f2937; color: #fff; font-weight: 600; }

/* position: sticky tidak menempel di dalam main.content (Volt memberi overflow: hidden), jadi bar ini fixed */
.grade-bar {
    position: fixed;
    right: 1.5rem;
    bottom: 1.5rem;
    z-index: 1030;
    max-width: calc(100vw - 3rem);
}
</style>
