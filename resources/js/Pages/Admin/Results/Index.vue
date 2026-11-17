<template>
    <Head><title>Rekap Hasil - Aplikasi Ujian Online</title></Head>

    <div class="container-fluid mb-5 mt-5">
        <ListToolbar v-model="search" @search="handleSearch" />

        <div class="card border-0 shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-centered table-list mb-0 rounded">
                        <thead class="thead-dark">
                            <tr>
                                <th class="border-0 rounded-start" style="width:4%">No.</th>
                                <th class="border-0">Sesi Ujian</th>
                                <th class="border-0">Kode Batch</th>
                                <th class="border-0 text-center" style="width:8%">Peserta</th>
                                <th class="border-0 text-center" style="width:9%">Status</th>
                                <th class="border-0">Progres</th>
                                <th class="border-0">Perlu Tindakan</th>
                                <th class="border-0 rounded-end" style="width:10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(s, i) in sessions.data" :key="s.id">
                                <td class="text-center fw-bold">{{ i + 1 + (sessions.current_page - 1) * sessions.per_page }}</td>
                                <td class="cell-wrap">
                                    <div class="fw-bolder">{{ s.title }}</div>
                                    <div class="text-muted small">{{ s.start_time }} – {{ s.end_time }}</div>
                                </td>
                                <td class="small">{{ s.kode_batch }}</td>
                                <td class="text-center">{{ s.students_count }}</td>
                                <td class="text-center">
                                    <StatusBadge v-if="new Date(s.end_time) > new Date()" tone="accent" label="Aktif" />
                                    <StatusBadge v-else tone="success" label="Selesai" />
                                </td>
                                <!-- Progres per tahap (sama dengan Pipeline di dashboard): angka = peserta yang sudah melewati tahap -->
                                <td class="pipeline-cell">
                                    <div v-if="s.pipeline && s.pipeline.total" class="stage-chips">
                                        <span v-for="st in stagesFor(s)" :key="st.key" class="stage-chip" :class="`is-${stageState(s, st)}`"
                                            :title="`${st.title}: ${s.pipeline.progress[st.key]} dari ${stageTarget(s, st)} peserta`">
                                            {{ st.label }} <span class="num">{{ s.pipeline.progress[st.key] }}/{{ stageTarget(s, st) }}</span>
                                        </span>
                                    </div>
                                    <span v-else class="text-muted small">Belum ada peserta</span>
                                </td>
                                <td class="small action-cell">
                                    <span v-if="nextAction(s)" :class="nextAction(s).tone">
                                        <i class="fa me-1" :class="nextAction(s).icon"></i>{{ nextAction(s).label }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <Link :href="`/admin/results/${s.id}`" class="btn btn-sm btn-primary border-0 shadow">
                                        <i class="fa fa-chart-bar me-1"></i> Rekap
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="sessions.data.length === 0">
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fa fa-award fa-2x d-block mb-2 text-gray-300"></i>
                                    <strong class="d-block">Belum ada hasil penilaian</strong>
                                    <span class="small">Rekap hasil akan muncul setelah sesi ujian berjalan.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="sessions.links" align="end" :total="sessions.total" :from="sessions.from" :to="sessions.to" entity="sesi" />
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import ListToolbar from '../../../Components/Admin/ListToolbar.vue';
import { Head, Link, router } from '@inertiajs/vue3';

// Tahap pipeline (AdminDashboardService::sessionRow -> progress). admin = dikerjakan admin sendiri.
const STAGES = [
    { key: 'started',  label: 'Ujian', title: 'Mulai ujian' },
    { key: 'assessed', label: 'Nilai', title: 'Dinilai asesor', waiting: 'Menunggu nilai asesor' },
    { key: 'pk',       label: 'PK',    title: 'Diverifikasi Pengambil Keputusan', waiting: 'Menunggu verifikasi PK' },
    { key: 'ms',       label: 'MS',    title: 'Diverifikasi Manajer Sertifikasi', waiting: 'Menunggu verifikasi MS' },
    { key: 'final',    label: 'Final', title: 'Difinalisasi', waiting: 'Menunggu finalisasi MS' },
    { key: 'sp',       label: 'SP',    title: 'SP terkirim', waiting: 'Siap kirim SP', admin: true },
    { key: 'sent',     label: 'SK',    title: 'SK & Sertifikat terkirim', waiting: 'Siap kirim SK & Sertifikat', admin: true },
];

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination, StatusBadge, ListToolbar },
    props: { sessions: Object },
    data() {
        return { search: new URL(document.location).searchParams.get('q') ?? '' };
    },
    methods: {
        handleSearch() {
            router.get('/admin/results', { q: this.search });
        },
        // Tahap yang berlaku untuk sesi: "Nilai" hanya bila sesi punya esai/wawancara.
        stagesFor(s) {
            return STAGES.filter(st => st.key !== 'assessed' || s.pipeline.needs_asesor);
        },
        // Target "Nilai" = peserta yang mulai ujian; tahap lain = semua peserta.
        stageTarget(s, st) {
            return st.key === 'assessed' ? s.pipeline.progress.started : s.pipeline.total;
        },
        // Tahap dianggap tuntas juga bila tahap sesudahnya sudah tuntas (sesi lama sebelum verifikasi
        // PK/MS ada tetap tercatat final & terkirim) — supaya tidak terlihat "macet" di tahap itu.
        stageDone(s, st) {
            const list = this.stagesFor(s);
            return list.slice(list.indexOf(st)).some(x => this.stageTarget(s, x) > 0 && s.pipeline.progress[x.key] >= this.stageTarget(s, x));
        },
        stageState(s, st) {
            if (this.stageDone(s, st)) return 'done';
            return s.pipeline.progress[st.key] > 0 ? 'partial' : 'empty';
        },
        // Tahap pertama yang belum tuntas (tanpa "Ujian": yang tidak hadir memang tidak pernah mulai)
        nextAction(s) {
            const p = s.pipeline;
            if (!p || !p.total) return null;
            if (p.state === 'terjadwal') return { label: 'Belum dimulai', icon: 'fa-calendar', tone: 'text-muted' };
            const st = this.stagesFor(s).find(x => x.key !== 'started' && !this.stageDone(s, x));
            if (!st) return { label: 'Selesai', icon: 'fa-check-circle', tone: 'text-success' };
            if (p.state === 'berlangsung' && st.key === 'assessed') return { label: 'Ujian berlangsung', icon: 'fa-play-circle', tone: 'text-primary' };
            const before = { assessed: p.progress.started, pk: p.total, ms: p.total, final: p.total, sp: p.progress.final, sent: p.progress.final }[st.key];
            const n = Math.max(0, before - p.progress[st.key]);
            return {
                label: n ? `${st.waiting} (${n})` : st.waiting,
                icon: st.admin ? 'fa-hand-point-right' : 'fa-hourglass-half',
                tone: st.admin ? 'text-primary fw-bolder' : 'text-gray-800',
            };
        },
    },
}
</script>

<style scoped>
.pipeline-cell { min-width: 300px; white-space: normal; }
.stage-chips { display: flex; flex-wrap: wrap; gap: 3px; }
.stage-chip {
    font-size: .68rem; font-weight: 600; line-height: 1.1; padding: .22rem .4rem; border-radius: .25rem;
    border: 1px solid #d1d5db; color: #6b7280; background: #fff; white-space: nowrap;
}
.stage-chip.is-done    { background: #10b981; border-color: #10b981; color: #fff; }
.stage-chip.is-partial { background: #dbeafe; border-color: #3b82f6; color: #1d4ed8; }
.action-cell { min-width: 170px; white-space: normal; line-height: 1.3; }
.num { font-variant-numeric: tabular-nums; }
</style>
