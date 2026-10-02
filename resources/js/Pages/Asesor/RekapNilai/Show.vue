<template>
    <Head><title>Rekap Nilai — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link href="/asesor/dashboard" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-chart-bar me-2"></i>Rekap Nilai</h5>
                        <hr>
                        <table class="table table-bordered mb-0 table-wrap" style="max-width:560px">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" style="width:40%">Sesi</td>
                                    <td>{{ exam_session.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Skema</td>
                                    <td>{{ exam_session.examPg?.classroom?.title ?? exam_session.examEsai?.classroom?.title ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Bobot</td>
                                    <td>{{ bobotText }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Nilai Kelulusan</td>
                                    <td>{{ fmt(scheme.nilai_kelulusan) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <PageGuide storage-key="asesor.rekap"><GuideRekapNilai /></PageGuide>

                <div v-if="rows.length === 0" class="alert alert-info">
                    Tidak ada peserta yang ditugaskan di sesi ini.
                </div>

                <div v-else class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h6 class="mb-0"><i class="fa fa-table me-2"></i>Nilai Peserta yang Ditugaskan kepada Anda</h6>
                            <div class="small text-muted">
                                {{ rows.length }} peserta · {{ lengkapCount }} nilai lengkap · {{ finalCount }} sudah final
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center" style="width:4%">No.</th>
                                        <th class="text-center" style="min-width:100px">No Peserta</th>
                                        <th style="min-width:150px">Nama</th>
                                        <th v-if="exam_session.exam_id_pg" class="text-center" style="min-width:80px">PG</th>
                                        <th v-if="exam_session.exam_id_esai" class="text-center" style="min-width:100px">Esai</th>
                                        <th v-if="exam_session.has_wawancara" class="text-center" style="min-width:100px">Wawancara</th>
                                        <th class="text-center" style="min-width:95px">Nilai Akhir</th>
                                        <th class="text-center" style="min-width:140px">Keputusan</th>
                                        <th class="text-center" style="min-width:80px" title="Rekomendasi Kompeten / Belum Kompeten Anda di Laporan Asesmen">K/BK</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in rows" :key="row.student_id">
                                        <td class="text-center">{{ i + 1 }}</td>
                                        <td class="text-center fw-bold">{{ row.no_participant ?? '-' }}</td>
                                        <td>{{ row.name ?? '-' }}</td>

                                        <td v-if="exam_session.exam_id_pg" class="text-end num">
                                            {{ row.nilai_pg !== null ? fmt(row.nilai_pg) : '—' }}
                                        </td>
                                        <td v-if="exam_session.exam_id_esai" class="text-end num">
                                            <template v-if="row.nilai_esai !== null">{{ fmt(row.nilai_esai) }}</template>
                                            <span v-else-if="row.esai_belum_dinilai" class="badge bg-warning text-gray-800">belum dinilai</span>
                                            <template v-else>—</template>
                                            <div v-if="row.nilai_esai !== null && row.esai_belum_dinilai" class="small text-gray-600">
                                                <i class="fa fa-exclamation-circle text-warning me-1"></i>
                                                {{ row.esai_belum_dinilai }} belum dinilai
                                            </div>
                                        </td>
                                        <td v-if="exam_session.has_wawancara" class="text-end num">
                                            <template v-if="row.nilai_wawancara !== null">{{ fmt(row.nilai_wawancara) }}</template>
                                            <span v-else class="badge bg-warning text-gray-800">belum dinilai</span>
                                        </td>

                                        <td class="text-end num fw-bold">{{ row.nilai_akhir !== null ? fmt(row.nilai_akhir) : '—' }}</td>
                                        <td class="text-center">
                                            <StatusBadge :tone="keputusan(row).tone">{{ keputusan(row).label }}</StatusBadge>
                                        </td>
                                        <td class="text-center">
                                            <StatusBadge v-if="row.rekomendasi === 'K'" tone="success" title="Kompeten">K</StatusBadge>
                                            <StatusBadge v-else-if="row.rekomendasi === 'BK'" tone="danger" title="Belum Kompeten">BK</StatusBadge>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-secondary">
                                    <tr>
                                        <td colspan="3" class="fw-bold">Rata-rata</td>
                                        <td v-if="exam_session.exam_id_pg" class="text-end num fw-bold">{{ avg('nilai_pg') }}</td>
                                        <td v-if="exam_session.exam_id_esai" class="text-end num fw-bold">{{ avg('nilai_esai') }}</td>
                                        <td v-if="exam_session.has_wawancara" class="text-end num fw-bold">{{ avg('nilai_wawancara') }}</td>
                                        <td class="text-end num fw-bold">{{ avg('nilai_akhir') }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-3 small text-muted border-top pt-2">
                            * Nilai Akhir = rata-rata berbobot komponen yang sudah bernilai; bila ada komponen yang belum
                            dinilai, bobotnya dibagi ke komponen yang ada sehingga nilai masih bisa berubah.
                            Keputusan bertanda <strong>sementara</strong> dihitung otomatis dari nilai kelulusan — keputusan
                            resmi ditetapkan Pengambil Keputusan saat finalisasi.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, Link } from '@inertiajs/vue3';
import PageGuide from '../../../Components/PageGuide.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import GuideRekapNilai from '../../../Components/Guide/Asesor/RekapNilai.vue';

export default {
    layout: LayoutAsesor,
    components: { Head, Link, PageGuide, StatusBadge, GuideRekapNilai },

    props: {
        exam_session: Object,
        rows: Array,
        scheme: Object,
    },

    computed: {
        bobotText() {
            const parts = [];
            if (this.exam_session.exam_id_pg) parts.push(`PG ${this.fmtPct(this.scheme.bobot_pg)}`);
            if (this.exam_session.exam_id_esai) parts.push(`Esai ${this.fmtPct(this.scheme.bobot_esai)}`);
            if (this.exam_session.has_wawancara) parts.push(`Wawancara ${this.fmtPct(this.scheme.bobot_wawancara)}`);
            return parts.join(' · ') || '-';
        },
        lengkapCount() {
            return this.rows.filter(r => this.isLengkap(r)).length;
        },
        finalCount() {
            return this.rows.filter(r => r.is_finalized).length;
        },
    },

    methods: {
        fmt(v) {
            return Number(v).toFixed(2);
        },
        fmtPct(v) {
            return `${Number(v ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })}%`;
        },
        isLengkap(row) {
            return (!this.exam_session.exam_id_pg || row.nilai_pg !== null)
                && (!this.exam_session.exam_id_esai || (row.nilai_esai !== null && !row.esai_belum_dinilai))
                && (!this.exam_session.has_wawancara || row.nilai_wawancara !== null);
        },
        keputusan(row) {
            if (!row.keputusan) return { tone: 'neutral', label: 'Belum ada nilai' };
            const lulus = row.keputusan === 'LULUS';
            if (row.is_finalized) return { tone: lulus ? 'success' : 'danger', label: lulus ? 'Lulus' : 'Tidak Lulus' };
            return { tone: 'secondary', label: `${lulus ? 'Lulus' : 'Tidak Lulus'} (sementara)` };
        },
        avg(field) {
            const vals = this.rows.map(r => r[field]).filter(v => v !== null && v !== undefined);
            if (!vals.length) return '—';
            return (vals.reduce((a, b) => a + Number(b), 0) / vals.length).toFixed(2);
        },
    },
}
</script>

<style scoped>
.num {
    font-variant-numeric: tabular-nums;
}
</style>
