<template>
    <Head><title>Penilaian Wawancara — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <SessionHeader title="Penilaian Wawancara" icon="fa-comments" :exam-session="exam_session"
                    :extra="[{ label: 'Bobot', value: `${(bobot * 100).toFixed(1)}% dari nilai akhir` }]" />

                <PageGuide storage-key="asesor.wawancara"><GuideWawancara /></PageGuide>

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>

                <div v-if="students.length === 0" class="alert alert-info">
                    Tidak ada peserta yang ditugaskan di sesi ini.
                </div>

                <SectionCard v-else title="Daftar Peserta" icon="fa-users">
                    <template #meta>Selesai dinilai: {{ scoredCount }} / {{ form.length }} · Rata-rata: {{ colAvg('total') }}</template>
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
                                        <th class="text-center" style="min-width:100px">Tugas</th>
                                        <th class="text-center" style="min-width:100px" title="Gaya Wawancara">(A) Gaya</th>
                                        <th class="text-center" style="min-width:110px" title="Penguasaan Materi">(B) Penguasaan</th>
                                        <th class="text-center" style="min-width:110px" title="Kemampuan Menghadapi Pertanyaan">(C) Pertanyaan</th>
                                        <th class="text-center" style="min-width:110px" title="Hasil Pengerjaan Worksheet Ujian Keterampilan">(D) Worksheet</th>
                                        <th class="text-center" style="min-width:100px">Total</th>
                                        <th style="min-width:160px">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in form" :key="row.student_id">
                                        <td class="text-center">{{ i + 1 }}</td>
                                        <td class="text-center fw-bold">
                                            {{ students[i]?.no_participant ?? '-' }}
                                        </td>
                                        <td>{{ students[i]?.name ?? '-' }}</td>

                                        <td class="text-center text-nowrap">
                                            <template v-if="students[i] && tugas[students[i].id]">
                                                <a :href="tugasUrl(students[i])" target="_blank"
                                                    :title="`Lihat ${tugas[students[i].id].original_filename}`"
                                                    class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <!-- <a :href="`${tugasUrl(students[i])}/unduh`"
                                                    :title="`Unduh ${tugas[students[i].id].original_filename}`"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fa fa-download"></i>
                                                </a> -->
                                            </template>
                                            <span v-else class="text-muted">-</span>
                                        </td>

                                        <td class="text-center">
                                            <input type="number" min="0" max="100" step="0.01"
                                                v-model.number="row.gaya_wawancara"
                                                @input="recalcTotal(i)"
                                                class="form-control form-control-sm text-center"
                                                style="width:90px;margin:auto"
                                                placeholder="0–100" />
                                        </td>
                                        <td class="text-center">
                                            <input type="number" min="0" max="100" step="0.01"
                                                v-model.number="row.penguasaan_materi"
                                                @input="recalcTotal(i)"
                                                class="form-control form-control-sm text-center"
                                                style="width:90px;margin:auto"
                                                placeholder="0–100" />
                                        </td>
                                        <td class="text-center">
                                            <input type="number" min="0" max="100" step="0.01"
                                                v-model.number="row.kemampuan_hadapi_pertanyaan"
                                                @input="recalcTotal(i)"
                                                class="form-control form-control-sm text-center"
                                                style="width:90px;margin:auto"
                                                placeholder="0–100" />
                                        </td>
                                        <td class="text-center">
                                            <input type="number" min="0" max="100" step="0.01"
                                                v-model.number="row.hasil_worksheet"
                                                @input="recalcTotal(i)"
                                                class="form-control form-control-sm text-center"
                                                style="width:90px;margin:auto"
                                                placeholder="0–100" />
                                        </td>
                                        <td class="text-center fw-bold">
                                            <span :class="row.total > 0 ? 'text-success' : 'text-muted'">
                                                {{ row.total.toFixed(2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <input type="text" v-model="row.catatan"
                                                class="form-control form-control-sm"
                                                placeholder="Opsional..." />
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-secondary">
                                    <tr>
                                        <td colspan="4" class="fw-bold">Rata-rata Sesi</td>
                                        <td class="text-center fw-bold">{{ colAvg('gaya_wawancara') }}</td>
                                        <td class="text-center fw-bold">{{ colAvg('penguasaan_materi') }}</td>
                                        <td class="text-center fw-bold">{{ colAvg('kemampuan_hadapi_pertanyaan') }}</td>
                                        <td class="text-center fw-bold">{{ colAvg('hasil_worksheet') }}</td>
                                        <td class="text-center fw-bold text-success">{{ colAvg('total') }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-3 small text-muted border-top pt-2">
                            <div class="fw-bolder mb-1">Keterangan Kolom:</div>
                            <div class="row g-1">
                                <div class="col-md-6"><span class="fw-bolder">(A) Gaya</span> — Gaya Wawancara</div>
                                <div class="col-md-6"><span class="fw-bolder">(B) Penguasaan</span> — Penguasaan Materi</div>
                                <div class="col-md-6"><span class="fw-bolder">(C) Pertanyaan</span> — Kemampuan Menghadapi Pertanyaan</div>
                                <div class="col-md-6"><span class="fw-bolder">(D) Worksheet</span> — Hasil Pengerjaan Worksheet Ujian Keterampilan</div>
                            </div>
                            <div class="mt-1">* Total = rata-rata (A + B + C + D) — bobot {{ (bobot * 100).toFixed(1) }}% baru diterapkan saat menghitung Nilai Akhir</div>
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
import GuideWawancara from '../../../Components/Guide/Asesor/Wawancara.vue';
import SessionHeader from '../../../Components/Asesor/SessionHeader.vue';
import SectionCard from '../../../Components/SectionCard.vue';

export default {
    layout: LayoutAsesor,
    components: { Head, PageGuide, GuideWawancara, SessionHeader, SectionCard },

    props: {
        exam_session: Object,
        students: Array,
        assessments: Object,
        tugas: Object,
        bobot: Number,
    },

    data() {
        return {
            saving: false,
            successMsg: '',
            form: this.buildForm(),
        };
    },

    computed: {
        // Peserta yang keempat kriterianya sudah terisi
        scoredCount() {
            const filled = v => v !== null && v !== '' && !isNaN(v);
            return this.form.filter(r => [r.gaya_wawancara, r.penguasaan_materi, r.kemampuan_hadapi_pertanyaan, r.hasil_worksheet].every(filled)).length;
        },
    },

    methods: {
        tugasUrl(student) {
            return `/asesor/penilaian/${this.exam_session.id}/wawancara/tugas/${student.id}`;
        },

        buildForm() {
            return this.students.map(student => {
                const existing = this.assessments[student.id] ?? null;
                const gaya    = existing?.gaya_wawancara              ?? null;
                const peng    = existing?.penguasaan_materi           ?? null;
                const kemp    = existing?.kemampuan_hadapi_pertanyaan ?? null;
                const hasil   = existing?.hasil_worksheet             ?? null;

                return {
                    student_id:                  student.id,
                    gaya_wawancara:              gaya,
                    penguasaan_materi:           peng,
                    kemampuan_hadapi_pertanyaan: kemp,
                    hasil_worksheet:             hasil,
                    catatan:                     existing?.catatan ?? '',
                    total: this.calcTotal(gaya, peng, kemp, hasil),
                };
            });
        },

        calcTotal(g, p, k, h) {
            // Rata-rata 4 kriteria (skala 0-100) — SEJAJAR dengan nilai PG & esai.
            // Bobot wawancara diterapkan sekali saat Nilai Akhir dihitung, bukan
            // di sini (dulu double-weighting lewat faktor_wawancara).
            const vals = [g, p, k, h].filter(v => v !== null && v !== '' && !isNaN(v));
            if (!vals.length) return 0;
            const avg = vals.reduce((acc, v) => acc + Number(v), 0) / vals.length;
            return Math.round(avg * 100) / 100;
        },

        recalcTotal(i) {
            const r = this.form[i];
            r.total = this.calcTotal(
                r.gaya_wawancara,
                r.penguasaan_materi,
                r.kemampuan_hadapi_pertanyaan,
                r.hasil_worksheet,
            );
        },

        colAvg(field) {
            const vals = this.form
                .map(r => r[field])
                .filter(v => v !== null && v !== '' && !isNaN(v));
            if (!vals.length) return '—';
            return (vals.reduce((a, b) => a + Number(b), 0) / vals.length).toFixed(2);
        },

        saveAll() {
            this.saving = true;
            router.post(
                `/asesor/penilaian/${this.exam_session.id}/wawancara`,
                {
                    assessments: this.form.map(r => ({
                        student_id:                  r.student_id,
                        gaya_wawancara:              r.gaya_wawancara,
                        penguasaan_materi:           r.penguasaan_materi,
                        kemampuan_hadapi_pertanyaan: r.kemampuan_hadapi_pertanyaan,
                        hasil_worksheet:             r.hasil_worksheet,
                        catatan:                     r.catatan,
                    })),
                },
                {
                    onSuccess: () => { this.successMsg = 'Nilai wawancara berhasil disimpan.'; },
                    onFinish:  () => { this.saving = false; },
                }
            );
        },
    },
}
</script>
