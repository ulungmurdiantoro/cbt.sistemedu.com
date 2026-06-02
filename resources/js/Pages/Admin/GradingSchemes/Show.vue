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

                <form @submit.prevent="submit">

                    <!-- Tabel a -->
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
                                        <!-- Baris PG -->
                                        <tr>
                                            <td class="align-middle fw-semibold">Pilihan Ganda</td>
                                            <td class="text-center align-middle">
                                                <input type="number" v-model.number="form.jumlah_soal_pg"
                                                    min="1" class="form-control form-control-sm text-center"
                                                    style="width:80px; margin:auto">
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <input type="number" v-model.number="form.durasi_pg_menit"
                                                        min="1" class="form-control form-control-sm text-center"
                                                        style="width:70px">
                                                    <span class="small text-muted">Menit</span>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <input type="number" v-model.number="form.proporsi_pg"
                                                        min="0" max="100" step="0.01"
                                                        class="form-control form-control-sm text-center"
                                                        style="width:75px"
                                                        @input="syncProporsiEsai">
                                                    <span class="small text-muted">%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Baris Esai -->
                                        <tr>
                                            <td class="align-middle fw-semibold">Esai</td>
                                            <td class="text-center align-middle">
                                                <input type="number" v-model.number="form.jumlah_soal_esai"
                                                    min="1" class="form-control form-control-sm text-center"
                                                    style="width:80px; margin:auto">
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <input type="number" v-model.number="form.durasi_esai_menit"
                                                        min="1" class="form-control form-control-sm text-center"
                                                        style="width:70px">
                                                    <span class="small text-muted">Menit</span>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <span class="fw-semibold">{{ proporsiEsai.toFixed(2) }}</span>
                                                    <span class="small text-muted">%</span>
                                                </div>
                                                <div class="small text-muted">(otomatis)</div>
                                            </td>
                                        </tr>
                                        <!-- Total dalam tulis -->
                                        <tr class="table-secondary">
                                            <td colspan="3" class="fw-semibold small text-end pe-3">
                                                Total Proporsi dalam Ujian Tulis
                                            </td>
                                            <td class="text-center fw-bold"
                                                :class="totalProporsiTulis === 100 ? 'text-success' : 'text-danger'">
                                                {{ totalProporsiTulis.toFixed(2) }}%
                                                <i v-if="totalProporsiTulis === 100" class="fa fa-check-circle ms-1"></i>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel b -->
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
                                            <td class="align-middle fw-semibold">
                                                Ujian Tulis (Pilihan Ganda + Esai)
                                                <div class="small text-muted fw-normal">
                                                    PG: {{ bobotPgEfektif.toFixed(2) }}% &nbsp;|&nbsp;
                                                    Esai: {{ bobotEsaiEfektif.toFixed(2) }}%
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

                    <!-- Nilai Kelulusan + Ringkasan Bobot Efektif -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <div class="card border-0 shadow h-100">
                                <div class="card-body">
                                    <label class="fw-semibold small">Nilai Kelulusan (%)</label>
                                    <input type="number" class="form-control mt-1" v-model.number="form.nilai_kelulusan"
                                        min="0" max="100" step="0.01">
                                    <div class="form-text">Peserta dengan nilai akhir ≥ nilai ini dinyatakan LULUS.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="card border-0 shadow h-100">
                                <div class="card-header bg-light fw-semibold small py-2">
                                    Bobot Efektif (digunakan perhitungan nilai akhir)
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
                            <i class="fa fa-exclamation-circle me-1"></i>Total bobot harus 100%
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
        errors:    { type: Object, default: () => ({}) },
    },

    setup(props) {
        const processing = ref(false);

        const form = reactive({
            bobot_ujian_tulis: props.scheme?.bobot_ujian_tulis ?? 70,
            proporsi_pg:       props.scheme?.proporsi_pg       ?? 65,
            bobot_wawancara:   props.scheme?.bobot_wawancara   ?? 30,
            nilai_kelulusan:   props.scheme?.nilai_kelulusan   ?? 70,
            jumlah_soal_pg:    props.scheme?.jumlah_soal_pg    ?? 100,
            durasi_pg_menit:   props.scheme?.durasi_pg_menit   ?? 120,
            jumlah_soal_esai:  props.scheme?.jumlah_soal_esai  ?? 10,
            durasi_esai_menit: props.scheme?.durasi_esai_menit ?? 90,
        });

        // Proporsi esai = 100 - pg (dalam ujian tulis)
        const proporsiEsai = computed(() =>
            Math.max(0, +(100 - form.proporsi_pg).toFixed(2))
        );

        // Bobot efektif
        const bobotPgEfektif   = computed(() => +(form.bobot_ujian_tulis * form.proporsi_pg / 100).toFixed(2));
        const bobotEsaiEfektif = computed(() => +(form.bobot_ujian_tulis * (100 - form.proporsi_pg) / 100).toFixed(2));

        // Total checks
        const totalProporsiTulis = computed(() => +(form.proporsi_pg + proporsiEsai.value).toFixed(2));
        const totalBobot         = computed(() => +(form.bobot_ujian_tulis + form.bobot_wawancara).toFixed(2));

        // Saat ubah bobot tulis → update wawancara otomatis
        const syncBobotLisan = () => {
            form.bobot_wawancara = +(100 - form.bobot_ujian_tulis).toFixed(2);
        };

        // Saat ubah wawancara → update tulis otomatis
        const syncBobotTulis = () => {
            form.bobot_ujian_tulis = +(100 - form.bobot_wawancara).toFixed(2);
        };

        // Proporsi Esai otomatis
        const syncProporsiEsai = () => { /* computed handles it */ };

        const submit = () => {
            if (totalBobot.value !== 100) return;
            processing.value = true;
            router.post(`/admin/classrooms/${props.classroom.id}/grading-scheme`, form, {
                onFinish: () => { processing.value = false; },
            });
        };

        return {
            form, processing,
            proporsiEsai, bobotPgEfektif, bobotEsaiEfektif,
            totalProporsiTulis, totalBobot,
            syncBobotLisan, syncBobotTulis, syncProporsiEsai,
            submit,
        };
    },
}
</script>
