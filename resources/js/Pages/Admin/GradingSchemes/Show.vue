<template>
    <Head><title>Komposisi Nilai - {{ classroom.title }}</title></Head>

    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0">Komposisi Nilai</h5>
                    <p class="text-muted small mb-0">Skema: <strong>{{ classroom.title }}</strong></p>
                </div>
                <Link href="/admin/classrooms" class="btn btn-sm btn-light border">
                    <i class="fa fa-arrow-left me-1"></i> Kembali
                </Link>
            </div>
        </div>
    </div>

    <div v-if="$page.props.session?.success" class="alert alert-success border-0 shadow mb-3">
        {{ $page.props.session.success }}
    </div>
    <div v-if="errors.bobot_ujian_tulis" class="alert alert-danger border-0 shadow mb-3">
        <i class="fa fa-exclamation-circle me-1"></i>{{ errors.bobot_ujian_tulis }}
    </div>

    <div class="row">
        <!-- Tabel rekapitulasi (kiri) -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th>Metode Ujian</th>
                                    <th class="text-center" style="width:130px">Proporsi Nilai</th>
                                    <th class="text-center" style="width:120px">Bobot Efektif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Ujian Tulis -->
                                <tr>
                                    <td>
                                        <div class="fw-semibold small">Ujian Tulis</div>
                                        <div class="text-muted" style="font-size:0.78rem">
                                            PG {{ form.proporsi_pg }}% &nbsp;+&nbsp; Esai {{ proporsiEsai.toFixed(2) }}%
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold">{{ form.bobot_ujian_tulis }}%</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary me-1">PG {{ bobotPgEfektif.toFixed(1) }}%</span>
                                        <span class="badge bg-success">Esai {{ bobotEsaiEfektif.toFixed(1) }}%</span>
                                    </td>
                                </tr>
                                <!-- Ujian Lisan -->
                                <tr>
                                    <td>
                                        <div class="fw-semibold small">Ujian Lisan + Keterampilan</div>
                                        <div class="text-muted" style="font-size:0.78rem">Wawancara</div>
                                    </td>
                                    <td class="text-center fw-bold">{{ form.bobot_wawancara }}%</td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">{{ form.bobot_wawancara }}%</span>
                                    </td>
                                </tr>
                                <!-- Total -->
                                <tr class="table-secondary">
                                    <td class="fw-semibold small">Total</td>
                                    <td class="text-center fw-bold"
                                        :class="totalBobot === 100 ? 'text-success' : 'text-danger'">
                                        {{ totalBobot.toFixed(2) }}%
                                        <i v-if="totalBobot === 100" class="fa fa-check-circle ms-1"></i>
                                        <i v-else class="fa fa-exclamation-circle ms-1"></i>
                                    </td>
                                    <td class="text-center small text-muted">Harus 100%</td>
                                </tr>
                                <!-- Nilai Kelulusan -->
                                <tr>
                                    <td class="fw-semibold small">Nilai Kelulusan</td>
                                    <td class="text-center fw-bold" colspan="2">{{ form.nilai_kelulusan }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form edit (kanan) -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow">
                <div class="card-header bg-gray-800 text-white fw-semibold">
                    Rekapitulasi Hasil Pembobotan Penilaian
                </div>
                <div class="card-body">
                    <form @submit.prevent="submit">

                        <div class="mb-3">
                            <label class="fw-semibold small">Bobot Ujian Tulis (PG + Esai) <span class="text-danger">*</span></label>
                            <div class="input-group mt-1">
                                <input type="number" class="form-control" v-model.number="form.bobot_ujian_tulis"
                                    min="0" max="100" step="0.01" @input="syncBobotLisan">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-semibold small">Proporsi PG dalam Ujian Tulis <span class="text-danger">*</span></label>
                            <div class="input-group mt-1">
                                <input type="number" class="form-control" v-model.number="form.proporsi_pg"
                                    min="0" max="100" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Esai otomatis = {{ proporsiEsai.toFixed(2) }}%</div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-semibold small">Bobot Ujian Lisan + Keterampilan <span class="text-danger">*</span></label>
                            <div class="input-group mt-1">
                                <input type="number" class="form-control" v-model.number="form.bobot_wawancara"
                                    min="0" max="100" step="0.01" @input="syncBobotTulis">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-semibold small">Faktor Wawancara <span class="text-danger">*</span></label>
                            <div class="input-group mt-1">
                                <input type="number" class="form-control" v-model.number="form.faktor_wawancara"
                                    min="0" max="1" step="0.001">
                                <span class="input-group-text">×</span>
                            </div>
                            <div class="form-text">
                                Faktor pengali skor mentah wawancara.
                                Contoh: 0.075 → 4 kriteria × maks 100 × 0.075 = maks <strong>{{ (400 * form.faktor_wawancara).toFixed(1) }} poin</strong>.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-semibold small">Nilai Kelulusan <span class="text-danger">*</span></label>
                            <div class="input-group mt-1">
                                <input type="number" class="form-control" v-model.number="form.nilai_kelulusan"
                                    min="0" max="100" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Nilai akhir ≥ nilai ini → LULUS.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-gray-800 btn-sm"
                                :disabled="processing || totalBobot !== 100">
                                {{ processing ? 'Menyimpan...' : (editMode ? 'Update' : 'Simpan') }}
                            </button>
                            <span v-if="totalBobot !== 100" class="text-danger small align-self-center">
                                Total harus 100%
                            </span>
                        </div>
                    </form>
                </div>
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
        const editMode   = computed(() => !!props.scheme?.classroom_id);

        const form = reactive({
            bobot_ujian_tulis: props.scheme?.bobot_ujian_tulis ?? 70,
            proporsi_pg:       props.scheme?.proporsi_pg       ?? 65,
            bobot_wawancara:   props.scheme?.bobot_wawancara   ?? 30,
            faktor_wawancara:  props.scheme?.faktor_wawancara  ?? 0.075,
            nilai_kelulusan:   props.scheme?.nilai_kelulusan   ?? 60,
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
            form, processing, editMode,
            proporsiEsai, bobotPgEfektif, bobotEsaiEfektif, totalBobot,
            syncBobotLisan, syncBobotTulis, submit,
        };
    },
}
</script>
