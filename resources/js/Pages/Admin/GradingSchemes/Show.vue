<template>
    <Head><title>Komposisi Nilai - {{ classroom.title }}</title></Head>

    <div class="container-fluid mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-8">

                <div class="d-flex align-items-center mb-3 gap-2">
                    <Link href="/admin/classrooms" class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-arrow-left"></i>
                    </Link>
                    <div>
                        <h5 class="mb-0 fw-bold">Komposisi Nilai</h5>
                        <p class="mb-0 small text-muted">{{ classroom.title }}</p>
                    </div>
                </div>

                <div v-if="$page.props.flash?.success" class="alert alert-success py-2 small border-0 shadow-sm">
                    <i class="fa fa-check-circle me-1"></i>{{ $page.props.flash.success }}
                </div>
                <div v-if="errors.bobot_ujian_tulis" class="alert alert-danger py-2 small border-0 shadow-sm">
                    <i class="fa fa-exclamation-circle me-1"></i>{{ errors.bobot_ujian_tulis }}
                </div>

                <!-- Tabel a: info dari exam (read-only) -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-header bg-gray-800 text-white fw-semibold">
                        a. Bobot Penilaian Pada Setiap Metode Ujian
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th style="width:25%">Metode Ujian</th>
                                        <th class="text-center" style="width:20%">Jumlah Soal</th>
                                        <th class="text-center" style="width:25%">Lama Pengerjaan</th>
                                        <th class="text-center" style="width:30%">Proporsi Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center fw-semibold bg-light py-2 small text-muted">
                                            Evaluasi per Unit Kompetensi
                                        </td>
                                    </tr>

                                    <!-- PG -->
                                    <tr>
                                        <td class="align-middle fw-semibold">
                                            Pilihan Ganda
                                            <div v-if="exam_info?.pg" class="small text-muted fw-normal">{{ exam_info.pg.title }}</div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span v-if="exam_info?.pg" class="fw-semibold">{{ exam_info.pg.jumlah_soal }}</span>
                                            <span v-else class="text-muted small">—</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span v-if="exam_info?.pg" class="fw-semibold">{{ exam_info.pg.duration }} Menit</span>
                                            <span v-else class="text-muted small">—</span>
                                        </td>
                                        <td class="text-center align-middle fw-semibold">
                                            {{ form.proporsi_pg }}%
                                        </td>
                                    </tr>

                                    <!-- Esai -->
                                    <tr>
                                        <td class="align-middle fw-semibold">
                                            Esai
                                            <div v-if="exam_info?.esai" class="small text-muted fw-normal">{{ exam_info.esai.title }}</div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span v-if="exam_info?.esai" class="fw-semibold">{{ exam_info.esai.jumlah_soal }}</span>
                                            <span v-else class="text-muted small">—</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span v-if="exam_info?.esai" class="fw-semibold">{{ exam_info.esai.duration }} Menit</span>
                                            <span v-else class="text-muted small">—</span>
                                        </td>
                                        <td class="text-center align-middle fw-semibold">
                                            {{ proporsiEsai.toFixed(2) }}%
                                        </td>
                                    </tr>

                                    <tr class="table-light">
                                        <td colspan="3" class="text-end small text-muted pe-3">
                                            <em>Data jumlah soal & durasi diambil dari pengaturan Ujian</em>
                                        </td>
                                        <td class="text-center small text-muted">
                                            Total: {{ (form.proporsi_pg + proporsiEsai).toFixed(2) }}%
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <!-- Tabel b: editable -->
                    <div class="card border-0 shadow mb-4">
                        <div class="card-header bg-gray-800 text-white fw-semibold">
                            b. Rekapitulasi Hasil Pembobotan Penilaian
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th style="width:60%">Metode Ujian</th>
                                            <th class="text-center">Proporsi Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Ujian Tulis -->
                                        <tr>
                                            <td class="align-middle">
                                                <div class="fw-semibold">Ujian Tulis (Pilihan Ganda + Esai)</div>
                                                <div class="small text-muted">
                                                    Proporsi PG dalam tulis:
                                                    <input type="number" v-model.number="form.proporsi_pg"
                                                        min="0" max="100" step="0.01"
                                                        class="form-control form-control-sm d-inline-block text-center ms-1"
                                                        style="width:75px"
                                                        title="Proporsi PG dalam ujian tulis">
                                                    % PG &nbsp;/&nbsp; {{ proporsiEsai.toFixed(2) }}% Esai
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    Bobot efektif → PG: <strong>{{ bobotPgEfektif.toFixed(2) }}%</strong>
                                                    &nbsp; Esai: <strong>{{ bobotEsaiEfektif.toFixed(2) }}%</strong>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <input type="number" v-model.number="form.bobot_ujian_tulis"
                                                        min="0" max="100" step="0.01"
                                                        class="form-control form-control-sm text-center fw-bold"
                                                        style="width:80px"
                                                        @input="syncBobotLisan">
                                                    <span class="small text-muted">%</span>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Ujian Lisan -->
                                        <tr>
                                            <td class="align-middle fw-semibold">
                                                Ujian Lisan + Keterampilan (Wawancara)
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <input type="number" v-model.number="form.bobot_wawancara"
                                                        min="0" max="100" step="0.01"
                                                        class="form-control form-control-sm text-center fw-bold"
                                                        style="width:80px"
                                                        @input="syncBobotTulis">
                                                    <span class="small text-muted">%</span>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Total -->
                                        <tr class="table-secondary">
                                            <td class="fw-semibold text-end pe-3 small">Total</td>
                                            <td class="text-center fw-bold"
                                                :class="totalBobot === 100 ? 'text-success' : 'text-danger'">
                                                {{ totalBobot.toFixed(2) }}%
                                                <i v-if="totalBobot === 100" class="fa fa-check-circle ms-1"></i>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Nilai Kelulusan + Bobot Efektif -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <div class="card border-0 shadow h-100">
                                <div class="card-body">
                                    <label class="fw-semibold small">Nilai Kelulusan (%)</label>
                                    <input type="number" class="form-control mt-1" v-model.number="form.nilai_kelulusan"
                                        min="0" max="100" step="0.01">
                                    <div class="form-text">Nilai akhir ≥ nilai ini → LULUS.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="card border-0 shadow h-100">
                                <div class="card-header bg-light fw-semibold small py-2">
                                    Bobot Efektif (digunakan untuk hitung nilai akhir)
                                </div>
                                <div class="card-body py-2">
                                    <div class="row text-center small">
                                        <div class="col-4 border-end">
                                            <div class="text-muted">PG</div>
                                            <div class="fw-bold text-primary fs-5">{{ bobotPgEfektif.toFixed(1) }}%</div>
                                        </div>
                                        <div class="col-4 border-end">
                                            <div class="text-muted">Esai</div>
                                            <div class="fw-bold text-success fs-5">{{ bobotEsaiEfektif.toFixed(1) }}%</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted">Wawancara</div>
                                            <div class="fw-bold text-warning fs-5">{{ form.bobot_wawancara.toFixed(1) }}%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark shadow" :disabled="processing || totalBobot !== 100">
                            <i class="fa fa-save me-1"></i>
                            {{ processing ? 'Menyimpan...' : 'Simpan Komposisi Nilai' }}
                        </button>
                        <span v-if="totalBobot !== 100" class="text-danger small align-self-center">
                            <i class="fa fa-exclamation-circle me-1"></i>Total harus 100%
                        </span>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        classroom: Object,
        scheme:    Object,
        exam_info: Object,
        errors:    { type: Object, default: () => ({}) },
    },

    setup(props) {
        const processing = ref(false);

        const form = reactive({
            bobot_ujian_tulis: props.scheme?.bobot_ujian_tulis ?? 70,
            proporsi_pg:       props.scheme?.proporsi_pg       ?? 65,
            bobot_wawancara:   props.scheme?.bobot_wawancara   ?? 30,
            nilai_kelulusan:   props.scheme?.nilai_kelulusan   ?? 70,
        });

        const proporsiEsai     = computed(() => +(100 - form.proporsi_pg).toFixed(2));
        const bobotPgEfektif   = computed(() => +(form.bobot_ujian_tulis * form.proporsi_pg / 100).toFixed(2));
        const bobotEsaiEfektif = computed(() => +(form.bobot_ujian_tulis * (100 - form.proporsi_pg) / 100).toFixed(2));
        const totalBobot       = computed(() => +(form.bobot_ujian_tulis + form.bobot_wawancara).toFixed(2));

        const syncBobotLisan = () => { form.bobot_wawancara = +(100 - form.bobot_ujian_tulis).toFixed(2); };
        const syncBobotTulis = () => { form.bobot_ujian_tulis = +(100 - form.bobot_wawancara).toFixed(2); };

        const submit = () => {
            if (totalBobot.value !== 100) return;
            processing.value = true;
            router.post(`/admin/classrooms/${props.classroom.id}/grading-scheme`, form, {
                onFinish: () => { processing.value = false; },
            });
        };

        return {
            form, processing,
            proporsiEsai, bobotPgEfektif, bobotEsaiEfektif, totalBobot,
            syncBobotLisan, syncBobotTulis, submit,
        };
    },
}
</script>
