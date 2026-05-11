<template>
    <Head>
        <title>Login Peserta Ujian - Aplikasi Ujian Online</title>
    </Head>

    <div class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-9">
                <div class="row g-0 shadow-sm rounded-3 overflow-hidden bg-white">

                    <!-- Panel kiri: logo + info pendaftaran -->
                    <div class="col-md-5 d-flex flex-column p-4 p-md-5 text-white"
                        style="background:#1f2937">

                        <!-- Logo -->
                        <div class="text-center mb-4">
                            <img src="/assets/images/logo.png" alt="Logo"
                                style="max-height:90px;max-width:180px;object-fit:contain">
                        </div>

                        <div class="text-center mb-4">
                            <h5 class="fw-bold mb-1">Aplikasi Ujian Online</h5>
                            <p class="small mb-0" style="opacity:.65">LSP Edukasi Global Cendekia</p>
                        </div>

                        <hr style="border-color:rgba(255,255,255,.15)">

                        <!-- Info cara mendapat No. Peserta -->
                        <div class="mb-4">
                            <p class="small fw-semibold mb-3">
                                <i class="fa fa-question-circle me-1"></i>Belum punya No. Peserta?
                            </p>
                            <div v-for="(step, i) in steps" :key="i" class="d-flex gap-2 mb-2 align-items-start">
                                <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width:20px;height:20px;font-size:0.65rem;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25)">
                                    {{ i + 1 }}
                                </span>
                                <span class="small" style="opacity:.8;line-height:1.4">{{ step }}</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="/peserta/register" class="btn btn-sm w-100 mb-2"
                                style="background:#fff;color:#1f2937;font-weight:600;border:none">
                                <i class="fa fa-user-plus me-1"></i> Daftar Sertifikasi
                            </a>
                            <a href="/peserta/login" class="btn btn-sm w-100 text-decoration-none"
                                style="font-size:0.8rem;color:rgba(255,255,255,0.6)">
                                Sudah punya akun peserta? Login
                            </a>
                        </div>
                    </div>

                    <!-- Panel kanan: form login -->
                    <div class="col-md-7 p-4 p-md-5 d-flex flex-column justify-content-center">

                        <h4 class="fw-bold mb-1">Login Peserta Ujian</h4>
                        <p class="text-muted small mb-4">
                            Masukkan No. Peserta yang dikirim ke email Anda setelah permohonan sertifikasi disetujui.
                        </p>

                        <div v-if="errors.message" class="alert alert-danger py-2 small border-0">
                            <i class="fa fa-exclamation-circle me-1"></i>{{ errors.message }}
                        </div>
                        <div v-if="$page.props.session.error" class="alert alert-danger py-2 small border-0">
                            <i class="fa fa-exclamation-circle me-1"></i>{{ $page.props.session.error }}
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label class="fw-semibold small">No. Peserta</label>
                                <div class="input-group mt-1">
                                    <span class="input-group-text bg-light">
                                        <i class="fa fa-id-card text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control"
                                        v-model="form.no_participant"
                                        placeholder="Contoh: TOT.10.2026.00001"
                                        autocomplete="off"
                                        :class="{ 'is-invalid': errors.no_participant }">
                                    <div v-if="errors.no_participant" class="invalid-feedback">
                                        {{ errors.no_participant }}
                                    </div>
                                </div>
                                <div class="form-text">
                                    No. Peserta dikirim via email setelah admin menyetujui permohonan Anda.
                                </div>
                            </div>

                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-dark" :disabled="processing">
                                    <i class="fa fa-sign-in me-1"></i>
                                    {{ processing ? 'Memproses...' : 'Masuk ke Ujian' }}
                                </button>
                            </div>
                        </form>

                        <!-- Info box mobile: tampil hanya di layar kecil -->
                        <div class="d-md-none p-3 rounded" style="background:#f8f9fa;border:1px solid #e9ecef">
                            <p class="small fw-semibold mb-2">
                                <i class="fa fa-info-circle me-1 text-muted"></i>Belum punya No. Peserta?
                            </p>
                            <p class="small text-muted mb-2">
                                Daftar melalui portal sertifikasi untuk mengajukan permohonan ujian kompetensi.
                            </p>
                            <a href="/peserta/register" class="btn btn-sm btn-dark">
                                <i class="fa fa-user-plus me-1"></i> Daftar Sertifikasi
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutStudent from '../../../Layouts/Student.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

export default {
    layout: LayoutStudent,
    components: { Head },
    props: { errors: Object },

    setup() {
        const processing = ref(false);
        const form = reactive({ no_participant: '' });

        const steps = [
            'Daftar akun di portal sertifikasi',
            'Pilih skema & sesi ujian',
            'Isi formulir dan upload dokumen',
            'Submit permohonan',
            'No. Peserta dikirim ke email Anda',
        ];

        const submit = () => {
            processing.value = true;
            router.post('/students/login', { no_participant: form.no_participant }, {
                onFinish: () => { processing.value = false; },
            });
        };

        return { form, processing, steps, submit };
    },
}
</script>
