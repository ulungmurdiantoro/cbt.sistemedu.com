<template>
    <Head><title>Penilaian Praktik — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <SessionHeader title="Penilaian Ujian Praktik" icon="fa-drafting-compass" :exam-session="exam_session"
                    :extra="[{ label: 'Bobot', value: parts.map(p => `${p.title}: ${p.bobot}%`).join(' · ') }]" />

                <PageGuide storage-key="asesor.praktik"><GuidePraktik /></PageGuide>

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>
                <div v-if="errorMsg" class="alert alert-danger">{{ errorMsg }}</div>

                <div v-if="students.length === 0" class="alert alert-info">
                    Tidak ada peserta yang ditugaskan di sesi ini.
                </div>

                <SectionCard v-else title="Daftar Peserta" icon="fa-users">
                    <template #meta>Selesai dinilai: {{ scoredCount }} / {{ form.length }} · Rata-rata: {{ avgTotal }}</template>
                    <template #actions>
                        <button @click="saveAll" :disabled="saving" class="btn btn-success border-0 shadow">
                            <i class="fa fa-save me-1"></i>
                            {{ saving ? 'Menyimpan...' : 'Simpan Semua Nilai' }}
                        </button>
                    </template>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center" style="width:4%">No.</th>
                                    <th class="text-center" style="min-width:100px">No Peserta</th>
                                    <th style="min-width:160px">Nama</th>
                                    <th v-for="p in parts" :key="p.bagian" class="text-center" style="min-width:170px">
                                        {{ p.title }}
                                        <div class="small fw-normal">Bobot {{ p.bobot }}%<template v-if="p.durasi"> · {{ p.durasi }} menit</template></div>
                                    </th>
                                    <th class="text-center" style="min-width:110px">Total Praktik</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in form" :key="row.student_id">
                                    <td class="text-center">{{ i + 1 }}</td>
                                    <td class="text-center fw-bold">{{ students[i].no_participant }}</td>
                                    <td>{{ students[i].name }}</td>
                                    <td v-for="p in parts" :key="p.bagian" class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <a v-if="tugas[row.student_id]?.[p.bagian]" :href="tugasUrl(row.student_id, p.bagian)" target="_blank"
                                                :title="`Lihat ${tugas[row.student_id][p.bagian]}`" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <span v-else class="text-muted px-2" title="Belum mengunggah">-</span>
                                            <input type="number" min="0" max="100" step="0.01"
                                                v-model.number="row.nilai[p.bagian]"
                                                class="form-control form-control-sm text-center"
                                                style="width:90px" placeholder="0–100" />
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <span v-if="total(row) !== null" class="text-success">{{ total(row).toFixed(2) }}</span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 small text-muted border-top pt-2">
                        * Total Praktik = rata-rata berbobot bagian yang sudah dinilai
                        ({{ parts.map(p => `${p.title} ${p.bobot}%`).join(', ') }}). Bobot diterapkan sekali saat Nilai Akhir dihitung.
                    </div>
                </SectionCard>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, router } from '@inertiajs/vue3';
import PageGuide from '../../../Components/PageGuide.vue';
import GuidePraktik from '../../../Components/Guide/Asesor/Praktik.vue';
import SessionHeader from '../../../Components/Asesor/SessionHeader.vue';
import SectionCard from '../../../Components/SectionCard.vue';

export default {
    layout: LayoutAsesor,
    components: { Head, PageGuide, GuidePraktik, SessionHeader, SectionCard },

    props: {
        exam_session: Object,
        students: Array,
        // [{ bagian, title, durasi, bobot }] — bagian praktik yang dinilai terpisah
        parts: Array,
        // { [student_id]: { [bagian]: nilai } }
        scores: { type: Object, default: () => ({}) },
        // { [student_id]: { [bagian]: nama file } }
        tugas: { type: Object, default: () => ({}) },
    },

    data() {
        return {
            saving: false,
            successMsg: '',
            errorMsg: '',
            form: this.students.map(s => ({
                student_id: s.id,
                nilai: Object.fromEntries(this.parts.map(p => [p.bagian, this.scores[s.id]?.[p.bagian] ?? null])),
            })),
        };
    },

    computed: {
        // Peserta yang semua bagiannya sudah bernilai
        scoredCount() {
            return this.form.filter(r => this.parts.every(p => this.filled(r.nilai[p.bagian]))).length;
        },
        avgTotal() {
            const totals = this.form.map(r => this.total(r)).filter(t => t !== null);
            return totals.length ? (totals.reduce((a, b) => a + b, 0) / totals.length).toFixed(2) : '—';
        },
    },

    methods: {
        filled(v) {
            return v !== null && v !== '' && !isNaN(v);
        },

        // Sama dengan NilaiPraktik::combine() di server: rata-rata berbobot bagian yang terisi
        total(row) {
            let sum = 0;
            let bobot = 0;
            for (const p of this.parts) {
                if (this.filled(row.nilai[p.bagian])) {
                    sum += Number(row.nilai[p.bagian]) * p.bobot;
                    bobot += p.bobot;
                }
            }
            return bobot > 0 ? Math.round((sum / bobot) * 100) / 100 : null;
        },

        tugasUrl(studentId, bagian) {
            return `/asesor/penilaian/${this.exam_session.id}/wawancara/tugas/${studentId}` + (bagian > 1 ? `?bagian=${bagian}` : '');
        },

        saveAll() {
            this.saving = true;
            this.errorMsg = '';
            router.post(`/asesor/penilaian/${this.exam_session.id}/praktik`, {
                scores: this.form.map(r => ({
                    student_id: r.student_id,
                    nilai: Object.fromEntries(this.parts.map(p => [p.bagian, this.filled(r.nilai[p.bagian]) ? r.nilai[p.bagian] : null])),
                })),
            }, {
                preserveScroll: true,
                onSuccess: () => { this.successMsg = 'Nilai praktik berhasil disimpan.'; },
                onError: (errors) => { this.errorMsg = Object.values(errors)[0] ?? 'Gagal menyimpan nilai praktik.'; },
                onFinish: () => { this.saving = false; },
            });
        },
    },
}
</script>
