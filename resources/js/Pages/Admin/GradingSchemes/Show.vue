<template>
    <Head><title>Komposisi Nilai - {{ classroom.title }}</title></Head>

    <div class="container-fluid mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">

                <div class="d-flex align-items-center mb-3 gap-2">
                    <Link href="/admin/classrooms" class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-arrow-left"></i>
                    </Link>
                    <div>
                        <h5 class="mb-0 fw-bold">Komposisi Nilai</h5>
                        <p class="mb-0 small text-muted">{{ classroom.title }}</p>
                    </div>
                </div>

                <div v-if="$page.props.flash?.success" class="alert alert-success py-2 small">
                    {{ $page.props.flash.success }}
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        <p class="small text-muted mb-3">
                            Total bobot harus <strong>100%</strong>.
                            Nilai akhir = (PG × bobot PG) + (Esai × bobot Esai) + (Wawancara × bobot Wawancara).
                        </p>

                        <form @submit.prevent="submit">
                            <div class="row g-3 mb-3">
                                <div class="col-4">
                                    <label class="fw-semibold small">Bobot PG (%)</label>
                                    <input type="number" class="form-control mt-1" v-model.number="form.bobot_pg"
                                        min="0" max="100" step="0.01"
                                        :class="{ 'is-invalid': errors.bobot_pg }">
                                    <div v-if="errors.bobot_pg" class="invalid-feedback">{{ errors.bobot_pg }}</div>
                                </div>
                                <div class="col-4">
                                    <label class="fw-semibold small">Bobot Esai (%)</label>
                                    <input type="number" class="form-control mt-1" v-model.number="form.bobot_esai"
                                        min="0" max="100" step="0.01">
                                </div>
                                <div class="col-4">
                                    <label class="fw-semibold small">Bobot Wawancara (%)</label>
                                    <input type="number" class="form-control mt-1" v-model.number="form.bobot_wawancara"
                                        min="0" max="100" step="0.01">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="fw-semibold small">Nilai Kelulusan (%)</label>
                                <input type="number" class="form-control mt-1 w-50" v-model.number="form.nilai_kelulusan"
                                    min="0" max="100" step="0.01">
                                <div class="form-text">Peserta dengan nilai akhir ≥ nilai ini dinyatakan LULUS.</div>
                            </div>

                            <div class="d-flex align-items-center gap-3">
                                <button type="submit" class="btn btn-dark" :disabled="processing">
                                    <i class="fa fa-save me-1"></i>
                                    {{ processing ? 'Menyimpan...' : 'Simpan' }}
                                </button>
                                <span :class="totalColor" class="fw-semibold small">
                                    Total: {{ total }}%
                                    <i v-if="total === 100" class="fa fa-check-circle text-success ms-1"></i>
                                    <i v-else class="fa fa-exclamation-circle text-danger ms-1"></i>
                                </span>
                            </div>
                        </form>

                    </div>
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
        const form = reactive({
            bobot_pg:        props.scheme?.bobot_pg        ?? 40,
            bobot_esai:      props.scheme?.bobot_esai      ?? 35,
            bobot_wawancara: props.scheme?.bobot_wawancara ?? 25,
            nilai_kelulusan: props.scheme?.nilai_kelulusan ?? 70,
        });

        const total      = computed(() => +(form.bobot_pg + form.bobot_esai + form.bobot_wawancara).toFixed(2));
        const totalColor = computed(() => total.value === 100 ? 'text-success' : 'text-danger');

        const submit = () => {
            processing.value = true;
            router.post(`/admin/classrooms/${props.classroom.id}/grading-scheme`, form, {
                onFinish: () => { processing.value = false; },
            });
        };

        return { form, processing, total, totalColor, submit };
    },
}
</script>
