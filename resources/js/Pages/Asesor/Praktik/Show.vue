<template>
    <Head><title>Penilaian Praktik/Keterampilan — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <SessionHeader title="Penilaian Ujian Praktik/Keterampilan" icon="fa-drafting-compass" :exam-session="exam_session"
                    :extra="[{ label: 'Bobot', value: parts.map(p => `${p.title}: ${p.bobot}%`).join(' · ') }]" />

                <PageGuide storage-key="asesor.praktik"><GuidePraktik /></PageGuide>

                <div v-if="rows.length === 0" class="alert alert-info">
                    Tidak ada peserta yang ditugaskan di sesi ini.
                </div>

                <SectionCard v-else title="Daftar Peserta" icon="fa-users">
                    <template #meta>Selesai dinilai: {{ doneCount }} / {{ rows.length }} · Rata-rata: {{ avgTotal }}</template>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center" style="width:4%">No.</th>
                                    <th class="text-center" style="min-width:150px">No Peserta</th>
                                    <th style="min-width:200px">Nama</th>
                                    <th v-for="p in parts" :key="p.bagian" class="text-center" style="min-width:180px">
                                        {{ multi ? p.title : 'Hasil & Nilai' }}
                                        <div v-if="multi" class="small fw-normal">Bobot {{ p.bobot }}%</div>
                                    </th>
                                    <th v-if="multi" class="text-center" style="min-width:110px">Total Praktik/Keterampilan</th>
                                    <th class="text-center" style="width:110px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in rows" :key="row.id">
                                    <td class="text-center">{{ i + 1 }}</td>
                                    <td class="text-center fw-bold">{{ row.no_participant }}</td>
                                    <td>{{ row.name }}</td>
                                    <td v-for="(rp, j) in row.parts" :key="rp.bagian" class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                            <a v-if="rp.upload" :href="tugasUrl(row.id, rp.bagian)" target="_blank"
                                                :title="`Lihat ${rp.upload}`" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <span v-else class="small text-muted" title="Peserta belum mengunggah">Belum unggah</span>

                                            <span v-if="rp.score !== null" class="fw-bold text-success num">{{ fmt(rp.score) }}</span>
                                            <StatusBadge v-else-if="parts[j].aspek_count && rp.filled" tone="secondary">
                                                {{ rp.filled }}/{{ parts[j].aspek_count }} aspek
                                            </StatusBadge>
                                            <StatusBadge v-else tone="neutral">Belum dinilai</StatusBadge>
                                        </div>
                                    </td>
                                    <td v-if="multi" class="text-center fw-bold num">
                                        <span v-if="row.total !== null" class="text-success">{{ fmt(row.total) }}</span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td class="text-center">
                                        <Link :href="gradeUrl(row.id)" class="btn btn-sm btn-primary border-0 shadow">
                                            <i class="fa fa-pen me-1"></i> Nilai
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 small text-muted border-top pt-2">
                        Klik <strong>Nilai</strong> untuk menilai satu peserta per aspek rubrik.
                        <template v-if="multi">Total Praktik/Keterampilan = rata-rata berbobot bagian ({{ parts.map(p => `${p.title} ${p.bobot}%`).join(', ') }}).</template>
                        Bobot diterapkan sekali saat Nilai Akhir dihitung.
                    </div>
                </SectionCard>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, Link } from '@inertiajs/vue3';
import PageGuide from '../../../Components/PageGuide.vue';
import GuidePraktik from '../../../Components/Guide/Asesor/Praktik.vue';
import SessionHeader from '../../../Components/Asesor/SessionHeader.vue';
import SectionCard from '../../../Components/SectionCard.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';

export default {
    layout: LayoutAsesor,
    components: { Head, Link, PageGuide, GuidePraktik, SessionHeader, SectionCard, StatusBadge },

    props: {
        exam_session: Object,
        // [{ bagian, title, durasi, bobot, aspek_count }]
        parts: Array,
        // [{ id, no_participant, name, parts: [{ bagian, score, filled, upload }], total }]
        rows: Array,
    },

    computed: {
        multi() {
            return this.parts.length > 1;
        },
        doneCount() {
            return this.rows.filter(r => r.parts.every(p => p.score !== null)).length;
        },
        avgTotal() {
            const totals = this.rows.map(r => r.total).filter(t => t !== null);
            return totals.length ? this.fmt(totals.reduce((a, b) => a + b, 0) / totals.length) : '—';
        },
    },

    methods: {
        fmt(v) {
            return Number(v).toFixed(2);
        },
        gradeUrl(studentId) {
            return `/asesor/penilaian/${this.exam_session.id}/praktik/peserta/${studentId}`;
        },
        tugasUrl(studentId, bagian) {
            return `/asesor/penilaian/${this.exam_session.id}/wawancara/tugas/${studentId}` + (bagian > 1 ? `?bagian=${bagian}` : '');
        },
    },
}
</script>

<style scoped>
.num { font-variant-numeric: tabular-nums; }
</style>
