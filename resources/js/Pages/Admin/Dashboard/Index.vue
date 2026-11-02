<template>
    <Head>
        <title>Dashboard - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">

        <!-- Judul + total data master -->
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-0">Dashboard</h1>
                <div class="small text-muted">{{ today }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <Link v-for="t in totalItems" :key="t.label" :href="t.url" class="total-chip shadow-sm">
                    <i :class="['fa', t.icon, 'me-1 text-gray-500']"></i>
                    <span class="num fw-bolder text-gray-800">{{ fmtNum(t.value) }}</span>
                    <span class="text-muted ms-1">{{ t.label }}</span>
                </Link>
            </div>
        </div>

        <!-- 1. Perlu Tindakan -->
        <h2 class="h6 text-uppercase text-muted mb-2">Perlu Tindakan</h2>
        <div class="row g-3 mb-4">
            <div v-for="a in actionCards" :key="a.key" class="col-6 col-lg-3">
                <component :is="a.anchor ? 'a' : 'Link'" :href="a.url"
                    class="card border-0 shadow h-100 action-card" :class="{ 'is-clear': !a.value }">
                    <div class="card-body d-flex align-items-start">
                        <div class="action-icon me-3" :class="a.value ? a.tone : 'tone-clear'">
                            <i :class="['fa', a.value ? a.icon : 'fa-check']"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="h3 mb-0 num" :class="a.value ? 'text-gray-800' : 'text-gray-400'">{{ fmtNum(a.value) }}</div>
                            <div class="small fw-bolder text-gray-700">{{ a.label }}</div>
                            <div class="small text-muted">{{ a.value ? a.hint : 'Beres' }}</div>
                        </div>
                    </div>
                </component>
            </div>
        </div>

        <!-- 2. Sesi aktif & terjadwal + 4. Materai -->
        <div class="row g-3 mb-4">
            <div :class="materai ? 'col-xl-8' : 'col-12'" id="sesi">
                <div class="card border-0 shadow h-100">
                    <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
                        <h2 class="h6 mb-0"><i class="fa fa-stopwatch me-2 text-primary"></i>Sesi Berlangsung &amp; Terjadwal</h2>
                        <Link href="/admin/exam_sessions" class="small">Semua sesi <i class="fa fa-angle-right"></i></Link>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-centered mb-0 rounded dash-table">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start">Sesi</th>
                                        <th class="border-0">Waktu</th>
                                        <th class="border-0 text-center">Peserta</th>
                                        <th class="border-0">Kesiapan</th>
                                        <th class="border-0 rounded-end text-center" style="width:1%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="s in activeSessions" :key="s.id">
                                        <td style="min-width:200px">
                                            <div class="d-flex align-items-start gap-2">
                                                <StatusBadge :tone="s.state === 'berlangsung' ? 'accent' : 'secondary'"
                                                    :label="s.state === 'berlangsung' ? 'Berlangsung' : 'Terjadwal'" class="mt-1 flex-shrink-0" />
                                                <div class="text-wrap">
                                                    <strong>{{ s.title }}</strong>
                                                    <div class="text-muted small">{{ s.skema || '—' }} · Batch {{ s.kode_batch || '—' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small text-nowrap">
                                            <div>{{ fmtDate(s.start_time) }}</div>
                                            <div class="text-muted">s.d. {{ fmtDate(s.end_time) }}</div>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bolder num">{{ s.total }}</div>
                                            <div v-if="s.state === 'berlangsung'" class="small text-muted num text-nowrap"
                                                title="Sudah mulai / sudah selesai ujian">
                                                {{ s.progress.started }} mulai · {{ s.progress.finished }} selesai
                                            </div>
                                        </td>
                                        <td class="small">
                                            <div v-for="c in readiness(s)" :key="c.label" class="text-nowrap">
                                                <i :class="['fa me-1', c.ok ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-danger']"></i>
                                                {{ c.label }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <Link :href="`/admin/exam_sessions/${s.id}`" class="btn btn-sm btn-gray-800 border-0 shadow text-nowrap">
                                                Buka
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="!activeSessions.length">
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="fa fa-calendar-check fa-2x d-block mb-2 text-gray-300"></i>
                                            <strong class="d-block">Tidak ada sesi aktif</strong>
                                            <span class="small">Sesi yang sedang berlangsung atau terjadwal akan tampil di sini.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="materai" class="col-xl-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
                        <h2 class="h6 mb-0"><i class="fa fa-stamp me-2 text-primary"></i>E-Meterai</h2>
                        <Link href="/admin/materai" class="small">Log Materai <i class="fa fa-angle-right"></i></Link>
                    </div>
                    <div class="card-body pt-0 d-flex flex-column gap-3">
                        <MateraiSaldo />
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <Link href="/admin/materai?status=stamped" class="materai-stat d-block">
                                    <div class="h4 mb-0 num text-gray-800">{{ fmtNum(materai.stamped_mtd) }}</div>
                                    <div class="small text-muted">Dibubuhkan bulan ini</div>
                                </Link>
                            </div>
                            <div class="col-4">
                                <Link href="/admin/materai?status=proses" class="materai-stat d-block">
                                    <div class="h4 mb-0 num text-gray-800">{{ fmtNum(materai.proses) }}</div>
                                    <div class="small text-muted">Diproses</div>
                                </Link>
                            </div>
                            <div class="col-4">
                                <Link href="/admin/materai?status=failed" class="materai-stat d-block">
                                    <div class="h4 mb-0 num" :class="materai.failed ? 'text-danger' : 'text-gray-800'">{{ fmtNum(materai.failed) }}</div>
                                    <div class="small text-muted">Gagal</div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Pipeline sertifikasi -->
        <div class="card border-0 shadow mb-4" id="pipeline">
            <div class="card-header bg-white border-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="h6 mb-0"><i class="fa fa-stream me-2 text-primary"></i>Pipeline Sertifikasi</h2>
                    <div class="small text-muted">
                        Sesi yang sudah selesai tapi SK &amp; Sertifikat belum terkirim ke semua peserta.
                        <span v-if="pipelineTotal > pipeline.length">Menampilkan {{ pipeline.length }} terbaru dari {{ pipelineTotal }} sesi.</span>
                    </div>
                </div>
                <Link href="/admin/results" class="small">Hasil Penilaian <i class="fa fa-angle-right"></i></Link>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-centered mb-0 rounded dash-table">
                        <thead class="thead-dark">
                            <tr class="border-0">
                                <th class="border-0 rounded-start">Sesi</th>
                                <th v-for="st in stages" :key="st.key" class="border-0 text-center">{{ st.label }}</th>
                                <th class="border-0 rounded-end text-center" style="width:1%">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in pipeline" :key="s.id">
                                <td style="min-width:200px">
                                    <div class="text-wrap">
                                        <strong>{{ s.title }}</strong>
                                        <div class="text-muted small">{{ s.skema || '—' }} · {{ s.total }} peserta · selesai {{ fmtDate(s.end_time, true) }}</div>
                                    </div>
                                </td>
                                <td v-for="st in stages" :key="st.key" class="text-center stage-cell"
                                    :class="{ 'is-stuck': bottleneck(s)?.key === st.key }">
                                    <template v-if="stageApplies(s, st)">
                                        <div class="small num" :class="stageDone(s, st) ? 'text-success fw-bolder' : 'text-gray-800'">
                                            {{ s.progress[st.key] }}/{{ stageTarget(s, st) }}
                                        </div>
                                        <div class="progress stage-bar mt-1">
                                            <div class="progress-bar" :class="stageDone(s, st) ? 'bg-success' : 'bg-primary'"
                                                :style="{ width: pct(s.progress[st.key], stageTarget(s, st)) + '%' }"></div>
                                        </div>
                                        <div v-if="st.key === 'sent' && s.progress.sp" class="small text-muted mt-1">SP {{ s.progress.sp }}</div>
                                    </template>
                                    <span v-else class="text-gray-400 small">—</span>
                                </td>
                                <td class="text-center">
                                    <Link :href="nextStep(s).url" class="btn btn-sm border-0 shadow text-nowrap"
                                        :class="nextStep(s).admin ? 'btn-primary' : 'btn-gray-100'" :title="nextStep(s).hint">
                                        {{ nextStep(s).label }}
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!pipeline.length">
                                <td :colspan="stages.length + 2" class="text-center text-muted py-5">
                                    <i class="fa fa-flag-checkered fa-2x d-block mb-2 text-gray-300"></i>
                                    <strong class="d-block">Semua sesi tuntas</strong>
                                    <span class="small">Tidak ada sesi selesai yang masih menunggu penilaian, keputusan, atau pengiriman.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. Statistik -->
        <h2 class="h6 text-uppercase text-muted mb-2">Statistik Bulan Ini</h2>
        <div class="row g-3 mb-4">
            <div v-for="t in statTiles" :key="t.key" class="col-md-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-body">
                        <div class="small text-muted">{{ t.label }}</div>
                        <div class="d-flex align-items-baseline gap-2">
                            <div class="h2 mb-0 num text-gray-800">{{ fmtNum(t.current) }}</div>
                            <span v-if="t.delta !== null" class="small fw-bolder"
                                :class="t.delta > 0 ? 'text-success' : (t.delta < 0 ? 'text-danger' : 'text-muted')">
                                <i :class="['fa', t.delta > 0 ? 'fa-arrow-up' : (t.delta < 0 ? 'fa-arrow-down' : 'fa-minus')]"></i>
                                {{ Math.abs(t.delta) }}
                            </span>
                        </div>
                        <div class="small text-muted">Bulan lalu: <span class="num">{{ fmtNum(t.previous) }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <!-- Peserta per bulan -->
            <div class="col-xl-7">
                <div class="card border-0 shadow h-100">
                    <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="h6 mb-0">Peserta per Bulan</h2>
                            <div class="small text-muted">12 bulan terakhir, menurut bulan mulai sesi</div>
                        </div>
                        <a href="#" class="small" @click.prevent="showMonthlyTable = !showMonthlyTable">
                            <i :class="['fa me-1', showMonthlyTable ? 'fa-chart-bar' : 'fa-table']"></i>
                            {{ showMonthlyTable ? 'Grafik' : 'Tabel' }}
                        </a>
                    </div>
                    <div class="card-body pt-0">
                        <div v-if="!showMonthlyTable" class="chart-wrap" @mouseleave="hoverMonth = null">
                            <svg :viewBox="`0 0 ${chart.w} ${chart.h}`" class="w-100" role="img"
                                :aria-label="`Grafik peserta per bulan, total ${fmtNum(monthlyTotal)} peserta`">
                                <line v-for="g in chart.grid" :key="g.v" :x1="chart.left" :x2="chart.w" :y1="g.y" :y2="g.y" class="grid-line" />
                                <text v-for="g in chart.grid" :key="'t' + g.v" :x="chart.left - 6" :y="g.y + 4" class="axis-text" text-anchor="end">{{ g.v }}</text>
                                <g v-for="(b, i) in chart.bars" :key="b.month">
                                    <rect :x="b.slotX" :y="0" :width="b.slotW" :height="chart.baseY" fill="transparent"
                                        @mouseenter="hoverMonth = i" />
                                    <path v-if="b.h > 0" :d="b.path" :class="['bar', { 'is-hover': hoverMonth === i }]" pointer-events="none" />
                                    <text v-if="b.labelled" :x="b.cx" :y="b.y - 6" class="value-text" text-anchor="middle">{{ b.count }}</text>
                                    <text :x="b.cx" :y="chart.h - 4" class="axis-text" text-anchor="middle">{{ b.label }}</text>
                                </g>
                                <line :x1="chart.left" :x2="chart.w" :y1="chart.baseY" :y2="chart.baseY" class="base-line" />
                            </svg>
                            <div v-if="hoverMonth !== null" class="chart-tip shadow-sm" :style="tipStyle">
                                <div class="fw-bolder">{{ monthly[hoverMonth].label }}</div>
                                <div><span class="num">{{ fmtNum(monthly[hoverMonth].count) }}</span> peserta</div>
                            </div>
                        </div>
                        <table v-else class="table table-sm mb-0">
                            <thead><tr><th>Bulan</th><th class="text-end">Peserta</th></tr></thead>
                            <tbody>
                                <tr v-for="m in monthly" :key="m.month">
                                    <td>{{ m.label }}</td><td class="text-end num">{{ fmtNum(m.count) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Kelulusan per skema -->
            <div class="col-xl-5">
                <div class="card border-0 shadow h-100">
                    <div class="card-header bg-white border-0">
                        <h2 class="h6 mb-0">Kelulusan per Skema</h2>
                        <div class="small text-muted">Hasil yang difinalisasi 12 bulan terakhir</div>
                    </div>
                    <div class="card-body pt-0">
                        <div v-for="r in passRates" :key="r.skema" class="mb-3"
                            :title="`${r.skema}: ${r.lulus} lulus dari ${r.total} peserta`">
                            <div class="d-flex justify-content-between small gap-2">
                                <span class="text-truncate text-gray-800">{{ r.skema }}</span>
                                <span class="text-nowrap"><strong class="num">{{ fmtPct(r.rate) }}</strong>
                                    <span class="text-muted num"> ({{ r.lulus }}/{{ r.total }})</span></span>
                            </div>
                            <div class="rate-track mt-1"><div class="rate-bar" :style="{ width: r.rate + '%' }"></div></div>
                        </div>
                        <div v-if="!passRates.length" class="text-center text-muted py-4">
                            <i class="fa fa-chart-bar fa-2x d-block mb-2 text-gray-300"></i>
                            <strong class="d-block">Belum ada data</strong>
                            <span class="small">Kelulusan tampil setelah ada sesi yang difinalisasi.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Aktivitas terbaru -->
        <div class="card border-0 shadow">
            <div class="card-header bg-white border-0">
                <h2 class="h6 mb-0"><i class="fa fa-history me-2 text-primary"></i>Aktivitas Terbaru</h2>
            </div>
            <div class="card-body pt-0">
                <ul class="list-unstyled mb-0">
                    <li v-for="(a, i) in activities" :key="i" class="activity-item">
                        <Link :href="a.url" class="d-flex align-items-center gap-3 text-reset">
                            <div class="activity-icon" :class="activityMeta[a.type].tone">
                                <i :class="['fa', activityMeta[a.type].icon]"></i>
                            </div>
                            <div class="flex-grow-1 text-wrap">
                                <div><span class="text-muted">{{ activityMeta[a.type].label }}</span> · <strong>{{ a.title }}</strong></div>
                                <div class="small text-muted">{{ a.meta || '—' }}</div>
                            </div>
                            <div class="small text-muted text-nowrap">{{ fmtAgo(a.at) }}</div>
                        </Link>
                    </li>
                    <li v-if="!activities.length" class="text-center text-muted py-4">
                        <i class="fa fa-history fa-2x d-block mb-2 text-gray-300"></i>
                        <strong class="d-block">Belum ada aktivitas</strong>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</template>

<script>
    import LayoutAdmin from '../../../Layouts/Admin.vue';
    import StatusBadge from '../../../Components/StatusBadge.vue';
    import MateraiSaldo from '../../../Components/MateraiSaldo.vue';
    import { Head, Link } from '@inertiajs/vue3';

    // Tahap pipeline: target = jumlah peserta, kecuali "Dinilai" = peserta yang mulai ujian.
    const STAGES = [
        { key: 'started',  label: 'Ujian' },
        { key: 'assessed', label: 'Dinilai Asesor' },
        { key: 'pk',       label: 'Verifikasi PK' },
        { key: 'ms',       label: 'Verifikasi MS' },
        { key: 'final',    label: 'Final' },
        { key: 'sent',     label: 'SK & Sertifikat' },
    ];

    // Tindakan per tahap macet. admin = bisa dikerjakan admin sendiri (tombol biru).
    const NEXT = {
        assessed: { label: 'Cek Penilaian', hint: 'Asesor belum menilai semua peserta', admin: false, page: 'session' },
        pk:       { label: 'Menunggu PK', hint: 'Menunggu verifikasi Pengambil Keputusan', admin: false, page: 'results' },
        ms:       { label: 'Menunggu MS', hint: 'Menunggu verifikasi & finalisasi Manager Sertifikasi', admin: false, page: 'results' },
        final:    { label: 'Menunggu MS', hint: 'Menunggu finalisasi Manager Sertifikasi', admin: false, page: 'results' },
        sent:     { label: 'Kirim SK', hint: 'Kirim SK & Sertifikat dari Rekap Hasil', admin: true, page: 'results' },
    };

    export default {
        layout: LayoutAdmin,

        components: { Head, Link, StatusBadge, MateraiSaldo },

        props: {
            actions:        Object,
            activeSessions: Array,
            pipeline:       Array,
            pipelineTotal:  Number,
            materai:        Object,
            stats:          Object,
            monthly:        Array,
            passRates:      Array,
            activities:     Array,
            totals:         Object,
        },

        data() {
            return {
                stages: STAGES,
                hoverMonth: null,
                showMonthlyTable: false,
                activityMeta: {
                    submitted:   { label: 'Permohonan masuk',          icon: 'fa-file-import', tone: 'tone-accent' },
                    approved:    { label: 'Permohonan disetujui',      icon: 'fa-check',       tone: 'tone-success' },
                    finalized:   { label: 'Sesi difinalisasi',         icon: 'fa-gavel',       tone: 'tone-warning' },
                    distributed: { label: 'SK & Sertifikat terkirim',  icon: 'fa-paper-plane', tone: 'tone-success' },
                },
            };
        },

        computed: {
            today() {
                return new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            },

            totalItems() {
                return [
                    { label: 'Skema',      value: this.totals.classrooms,    icon: 'fa-layer-group', url: '/admin/classrooms' },
                    { label: 'Peserta',    value: this.totals.students,      icon: 'fa-users',       url: '/admin/students' },
                    { label: 'Ujian',      value: this.totals.exams,         icon: 'fa-edit',        url: '/admin/exams' },
                    { label: 'Sesi Ujian', value: this.totals.exam_sessions, icon: 'fa-stopwatch',   url: '/admin/exam_sessions' },
                ];
            },

            actionCards() {
                const a = this.actions;
                const assign = a.assignment.no_asesor + a.assignment.no_pk;
                const assignHint = [
                    a.assignment.no_asesor ? `${a.assignment.no_asesor} sesi asesor belum lengkap` : null,
                    a.assignment.no_pk ? `${a.assignment.no_pk} sesi tanpa PK` : null,
                ].filter(Boolean).join(' · ');

                const cards = [
                    { key: 'applications', label: 'Permohonan Menunggu', value: a.applications, icon: 'fa-inbox', tone: 'tone-warning',
                      hint: a.documents ? `${a.documents} dokumen belum diverifikasi` : 'Perlu ditinjau', url: '/admin/applications?status=submitted' },
                    { key: 'assignment', label: 'Penugasan Belum Lengkap', value: assign, icon: 'fa-user-tag', tone: 'tone-danger',
                      hint: assignHint, url: '#sesi', anchor: true },
                    { key: 'assessment', label: 'Belum Dinilai Asesor', value: a.assessment, icon: 'fa-pen', tone: 'tone-warning',
                      hint: 'peserta di sesi yang sudah selesai', url: '#pipeline', anchor: true },
                    { key: 'pk', label: 'Menunggu Pengambil Keputusan', value: a.pk, icon: 'fa-user-check', tone: 'tone-accent',
                      hint: 'peserta belum diverifikasi PK', url: '#pipeline', anchor: true },
                    { key: 'ms', label: 'Menunggu Manager Sertifikasi', value: a.ms, icon: 'fa-gavel', tone: 'tone-accent',
                      hint: 'peserta belum difinalisasi', url: '#pipeline', anchor: true },
                    { key: 'send', label: 'SK & Sertifikat Belum Dikirim', value: a.send, icon: 'fa-paper-plane', tone: 'tone-danger',
                      hint: 'peserta final siap dikirim', url: '#pipeline', anchor: true },
                ];

                if (this.materai) {
                    cards.push({ key: 'materai', label: 'Materai Gagal', value: a.materai_failed, icon: 'fa-stamp', tone: 'tone-danger',
                        hint: 'perlu dibubuhkan ulang', url: '/admin/materai?status=failed' });
                }

                return cards;
            },

            statTiles() {
                const c = this.stats.current, p = this.stats.previous;
                const tile = (key, label) => ({ key, label, current: c[key], previous: p[key], delta: c[key] - p[key] });

                return [
                    tile('applications', 'Permohonan masuk'),
                    tile('tested', 'Peserta mengikuti ujian'),
                    tile('certified', 'Sertifikat terkirim (Lulus)'),
                ];
            },

            monthlyTotal() {
                return this.monthly.reduce((s, m) => s + m.count, 0);
            },

            // Geometri grafik batang (satu seri): batang tipis, ujung atas membulat 4px, grid samar.
            chart() {
                const w = 640, h = 220, left = 34, top = 18, bottom = 22;
                const baseY = h - bottom;
                const max = Math.max(...this.monthly.map(m => m.count), 0);
                const step = niceStep(max);
                const ceil = Math.max(step * Math.ceil(max / step), step);
                const scale = v => (v / ceil) * (baseY - top);
                const slotW = (w - left) / this.monthly.length;
                const barW = Math.min(28, slotW * 0.55);
                const maxIdx = this.monthly.findIndex(m => m.count === max);
                const last = this.monthly.length - 1;

                const bars = this.monthly.map((m, i) => {
                    const bh = scale(m.count);
                    const x = left + i * slotW + (slotW - barW) / 2;
                    const y = baseY - bh;
                    const r = Math.min(4, bh, barW / 2);

                    return {
                        ...m,
                        h: bh, y,
                        cx: x + barW / 2,
                        slotX: left + i * slotW,
                        slotW,
                        // Label hanya untuk puncak & bulan terakhir
                        labelled: m.count > 0 && (i === maxIdx || i === last),
                        path: `M${x},${baseY} V${y + r} Q${x},${y} ${x + r},${y} H${x + barW - r} Q${x + barW},${y} ${x + barW},${y + r} V${baseY} Z`,
                    };
                });

                const grid = [];
                for (let v = step; v <= ceil; v += step) grid.push({ v, y: baseY - scale(v) });

                return { w, h, left, baseY, bars, grid };
            },

            tipStyle() {
                const b = this.chart.bars[this.hoverMonth];
                const leftPct = (b.cx / this.chart.w) * 100;

                return { left: `${leftPct}%`, top: `${(b.y / this.chart.h) * 100}%` };
            },
        },

        methods: {
            readiness(s) {
                const items = [];
                if (s.needs_asesor) {
                    items.push({ ok: s.total > 0 && s.assigned >= s.total, label: `Asesor ${s.assigned}/${s.total}` });
                }
                items.push({ ok: !!s.pk_name, label: s.pk_name ? `PK: ${s.pk_name}` : 'PK belum ditunjuk' });
                if (s.tuk !== null) {
                    items.push({ ok: s.total > 0 && s.tuk >= s.total, label: `Verifikasi TUK ${s.tuk}/${s.total}` });
                }
                return items;
            },

            stageApplies(s, st) {
                return st.key !== 'assessed' || s.needs_asesor;
            },

            stageTarget(s, st) {
                return st.key === 'assessed' ? s.progress.started : s.total;
            },

            stageDone(s, st) {
                return s.progress[st.key] >= this.stageTarget(s, st);
            },

            // Tahap pertama yang belum tuntas. "Ujian" tidak dihitung: peserta yang tidak hadir
            // memang tidak pernah mulai.
            bottleneck(s) {
                return this.stages.find(st => st.key !== 'started' && this.stageApplies(s, st) && !this.stageDone(s, st)) || null;
            },

            nextStep(s) {
                const st = this.bottleneck(s);
                const next = NEXT[st?.key] ?? NEXT.sent;
                const url = next.page === 'session' ? `/admin/exam_sessions/${s.id}` : `/admin/results/${s.id}`;

                return { ...next, url };
            },

            pct(v, total) {
                return total > 0 ? Math.min(100, Math.round((v / total) * 100)) : 0;
            },

            fmtNum(n) {
                return Number(n ?? 0).toLocaleString('id-ID');
            },

            fmtPct(n) {
                return `${Number(n).toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`;
            },

            fmtDate(dt, dateOnly = false) {
                if (!dt) return '—';
                return new Date(dt).toLocaleString('id-ID', dateOnly
                    ? { day: 'numeric', month: 'short', year: 'numeric' }
                    : { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
            },

            fmtAgo(dt) {
                if (!dt) return '';
                const diff = (Date.now() - new Date(dt).getTime()) / 1000;
                if (diff < 60) return 'baru saja';
                if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
                if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
                if (diff < 86400 * 7) return `${Math.floor(diff / 86400)} hari lalu`;
                return this.fmtDate(dt, true);
            },
        },
    }

    // Kelipatan sumbu y yang "bulat" (1, 2, 5 × 10^n) untuk ±4 garis grid.
    function niceStep(max) {
        if (max <= 4) return 1;
        const raw = max / 4;
        const mag = Math.pow(10, Math.floor(Math.log10(raw)));
        const n = raw / mag;
        return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * mag;
    }
</script>

<style scoped>
.num { font-variant-numeric: tabular-nums; }

.total-chip {
    display: inline-flex;
    align-items: center;
    background: #fff;
    border-radius: 50rem;
    padding: .3rem .8rem;
    font-size: .875rem;
    text-decoration: none;
}

/* Kartu Perlu Tindakan */
.action-card { text-decoration: none; transition: transform .12s ease, box-shadow .12s ease; }
.action-card:hover { transform: translateY(-2px); }
.action-card.is-clear { opacity: .75; }
.action-icon {
    width: 40px; height: 40px;
    border-radius: .5rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.tone-accent  { background: rgba(35, 97, 206, 0.14);  color: #1c4ea3; }
.tone-warning { background: rgba(240, 188, 116, 0.30); color: #6b4a16; }
.tone-success { background: rgba(16, 185, 129, 0.16); color: #0a6b4a; }
.tone-danger  { background: rgba(225, 29, 72, 0.14);  color: #a01233; }
.tone-clear   { background: #e5e7eb; color: #6b7280; }

/* Tabel: padding Volt 24px dipersempit */
.dash-table th, .dash-table td { padding-left: .75rem; padding-right: .75rem; }

.stage-cell { min-width: 92px; }
.stage-cell.is-stuck { background: rgba(240, 188, 116, 0.18); }
.stage-bar { height: 4px; margin: 0 auto; max-width: 80px; }

.materai-stat { text-decoration: none; padding: .5rem 0; border-radius: .5rem; background: #f9fafb; }
.materai-stat:hover { background: #f3f4f6; }

/* Grafik peserta per bulan */
.chart-wrap { position: relative; }
.grid-line { stroke: #eef0f3; stroke-width: 1; }
.base-line { stroke: #d1d5db; stroke-width: 1; }
.axis-text { fill: #6b7280; font-size: 11px; }
.value-text { fill: #374151; font-size: 11px; font-weight: 600; }
.bar { fill: #2361ce; }
.bar.is-hover { fill: #1c4ea3; }
.chart-tip {
    position: absolute;
    transform: translate(-50%, calc(-100% - 8px));
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: .375rem;
    padding: .3rem .6rem;
    font-size: .8rem;
    pointer-events: none;
    white-space: nowrap;
}

/* Kelulusan per skema */
.rate-track { height: 8px; background: #eef0f3; border-radius: 4px; overflow: hidden; }
.rate-bar { height: 100%; background: #2361ce; border-radius: 0 4px 4px 0; }

/* Aktivitas */
.activity-item { padding: .6rem 0; border-bottom: 1px solid #f1f3f5; }
.activity-item:last-child { border-bottom: 0; }
.activity-item a { text-decoration: none; }
.activity-icon {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .8rem;
}
</style>
