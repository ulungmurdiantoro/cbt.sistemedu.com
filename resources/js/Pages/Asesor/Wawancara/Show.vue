<template>
    <Head><title>Penilaian Wawancara — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link href="/asesor/dashboard" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-comments me-2"></i>Penilaian Wawancara</h5>
                        <hr>
                        <table class="table table-bordered mb-0" style="max-width:500px">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" style="width:40%">Sesi</td>
                                    <td>{{ exam_session.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Ujian</td>
                                    <td>{{ exam_session.exam.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Skema</td>
                                    <td>{{ exam_session.exam.classroom.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Bobot</td>
                                    <td>{{ (bobot * 100).toFixed(1) }}% dari total nilai ujian</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="students.length === 0" class="alert alert-info">
                    Tidak ada peserta yang ditugaskan di sesi ini.
                </div>

                <div v-else class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0"><i class="fa fa-table me-2"></i>Tabel Penilaian Wawancara</h6>
                            <button @click="saveAll" :disabled="saving"
                                class="btn btn-success border-0 shadow">
                                <i class="fa fa-save me-1"></i>
                                {{ saving ? 'Menyimpan...' : 'Simpan Semua Nilai' }}
                            </button>
                        </div>

                        <div v-if="successMsg" class="alert alert-success alert-dismissible">
                            {{ successMsg }}
                            <button type="button" class="btn-close" @click="successMsg = ''"></button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center" style="min-width:100px">No Peserta</th>
                                        <th style="min-width:150px">Nama</th>
                                        <th class="text-center" style="min-width:130px">Gaya Wawancara</th>
                                        <th class="text-center" style="min-width:150px">Penguasaan Materi</th>
                                        <th class="text-center" style="min-width:200px">Kemampuan Menghadapi Pertanyaan</th>
                                        <th class="text-center" style="min-width:220px">Hasil Pengerjaan Worksheet Ujian Keterampilan</th>
                                        <th class="text-center" style="min-width:120px">Total Nilai</th>
                                        <th style="min-width:180px">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in form" :key="row.student_id">
                                        <td class="text-center fw-bold">
                                            {{ students[i]?.no_participant ?? '-' }}
                                        </td>
                                        <td>{{ students[i]?.name ?? '-' }}</td>

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
                                        <td colspan="2" class="fw-bold">Rata-rata Sesi</td>
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

                        <p class="text-muted small mt-2">
                            * Total Nilai = (Gaya Wawancara + Penguasaan Materi + Kemampuan Hadapi Pertanyaan + Hasil Worksheet) × {{ bobot }}
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, Link, router } from '@inertiajs/vue3';

export default {
    layout: LayoutAsesor,
    components: { Head, Link },

    props: {
        exam_session: Object,
        students: Array,
        assessments: Object,
        bobot: Number,
    },

    data() {
        return {
            saving: false,
            successMsg: '',
            form: this.buildForm(),
        };
    },

    methods: {
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
            const vals = [g, p, k, h].filter(v => v !== null && v !== '' && !isNaN(v));
            const sum  = vals.reduce((acc, v) => acc + Number(v), 0);
            return Math.round(sum * this.bobot * 100) / 100;
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
