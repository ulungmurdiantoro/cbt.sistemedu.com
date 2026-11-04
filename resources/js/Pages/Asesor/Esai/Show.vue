<template>
    <Head><title>Penilaian Esai — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-12">

                <SessionHeader title="Penilaian Esai" icon="fa-pen" :exam-session="exam_session"
                    :extra="[{ label: 'Jumlah Soal', value: `${essay_count} soal` }]" />

                <PageGuide storage-key="asesor.esai"><GuideEsai /></PageGuide>

                <div v-if="students_data.length === 0" class="alert alert-info">
                    Tidak ada peserta yang ditugaskan di sesi ini.
                </div>

                <!-- Daftar peserta: menilai dilakukan di halaman per peserta -->
                <SectionCard v-else title="Daftar Peserta" icon="fa-users">
                    <template #meta>
                        Selesai dinilai: {{ fullyScoredCount }} / {{ students_data.length }} · Rata-rata: {{ sessionAvg }}
                    </template>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width:5%">No.</th>
                                    <th style="width:18%">No. Peserta</th>
                                    <th>Nama</th>
                                    <th class="text-center" style="width:14%">Progress</th>
                                    <th class="text-center" style="width:12%">Total</th>
                                    <th class="text-center" style="width:12%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in students_data" :key="row.student_id"
                                    style="cursor:pointer" @click="open(row)">
                                    <td>{{ i + 1 }}</td>
                                    <td class="fw-bold">{{ row.student?.no_participant ?? '-' }}</td>
                                    <td>
                                        {{ row.student?.name ?? '-' }}
                                        <span v-if="row.attempt > 1" class="badge bg-warning text-gray-800 ms-1" style="font-size:0.65rem">Remidi</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge"
                                            :class="isComplete(row) ? 'bg-success' : (row.scored > 0 ? 'bg-warning text-gray-800' : 'bg-secondary')">
                                            {{ row.scored }} / {{ essay_count }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <span :class="row.total !== null ? 'text-success' : 'text-muted'">
                                            {{ row.total !== null ? Number(row.total).toFixed(2) : '—' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <Link :href="gradeUrl(row)" class="btn btn-sm btn-primary" @click.stop>
                                            <i class="fa fa-pen me-1"></i> Nilai
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </SectionCard>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PageGuide from '../../../Components/PageGuide.vue';
import GuideEsai from '../../../Components/Guide/Asesor/Esai.vue';
import SessionHeader from '../../../Components/Asesor/SessionHeader.vue';
import SectionCard from '../../../Components/SectionCard.vue';

export default {
    layout: LayoutAsesor,
    components: { Head, Link, PageGuide, GuideEsai, SessionHeader, SectionCard },

    props: {
        exam_session: Object,
        essay_count: Number,
        // [{ student_id, student: { id, name, no_participant }, attempt, scored, total }]
        students_data: Array,
    },

    computed: {
        fullyScoredCount() {
            return this.students_data.filter(r => this.isComplete(r)).length;
        },
        sessionAvg() {
            const totals = this.students_data.map(r => r.total).filter(t => t !== null);
            if (!totals.length) return '—';
            return (totals.reduce((a, b) => a + Number(b), 0) / totals.length).toFixed(2);
        },
    },

    methods: {
        isComplete(row) {
            return this.essay_count > 0 && row.scored >= this.essay_count;
        },
        gradeUrl(row) {
            return `/asesor/penilaian/${this.exam_session.id}/esai/peserta/${row.student_id}`;
        },
        open(row) {
            router.visit(this.gradeUrl(row));
        },
    },
}
</script>
