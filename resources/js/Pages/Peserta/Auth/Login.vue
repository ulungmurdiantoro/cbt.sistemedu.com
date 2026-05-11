<template>
    <Head>
        <title>Login Peserta - Portal Sertifikasi</title>
    </Head>

    <div class="min-vh-100 d-flex">

        <!-- Panel kiri: welcome (desktop) -->
        <div class="d-none d-lg-flex flex-column p-5 text-white" style="width:420px;flex-shrink:0;background:#1f2937">
            <div>
                <div class="text-center mb-4">
                    <img src="/assets/images/logo.png" alt="Logo"
                        style="max-height:80px;max-width:160px;object-fit:contain">
                </div>
                <div class="text-center mb-5">
                    <div class="fw-bold lh-1">LSP Edukasi Global</div>
                    <div class="small opacity-60">Cendekia</div>
                </div>

                <h4 class="fw-bold mb-2">Selamat Datang</h4>
                <p class="opacity-75 mb-5" style="font-size:0.9rem;line-height:1.7">
                    Portal pendaftaran sertifikasi kompetensi. Login untuk melanjutkan proses pengajuan
                    atau memantau status permohonan Anda.
                </p>

                <div class="p-3 rounded mb-4" style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12)">
                    <div class="small fw-semibold mb-2"><i class="fa fa-info-circle me-1 opacity-75"></i>Informasi</div>
                    <p class="small opacity-75 mb-0">
                        Halaman ini khusus untuk <strong>pendaftaran sertifikasi</strong>.
                        Jika Anda ingin mengikuti ujian, gunakan halaman login ujian dengan No. Peserta.
                    </p>
                </div>
            </div>

            <div class="mt-4 pt-4" style="border-top:1px solid rgba(255,255,255,0.15)">
                <p class="small opacity-75 mb-2">Belum punya akun?</p>
                <Link href="/peserta/register" class="btn btn-sm px-4"
                    style="background:#fff;color:#1f2937;font-weight:600;border:none">
                    <i class="fa fa-user-plus me-1"></i> Daftar Sekarang
                </Link>
            </div>
        </div>

        <!-- Panel kanan: form -->
        <div class="flex-fill d-flex align-items-center justify-content-center p-4">
            <div style="max-width:440px;width:100%">

                <!-- Logo mobile -->
                <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
                    <img src="/assets/images/logo.png" alt="Logo" style="height:40px;object-fit:contain">
                    <span class="fw-bold small">LSP Edukasi Global Cendekia</span>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h4 class="fw-bold mb-1">Login Portal Peserta</h4>
                        <p class="text-muted small mb-4">
                            Masukkan email dan password akun sertifikasi Anda.
                        </p>

                        <div v-if="$page.props.session.error" class="alert alert-danger py-2 small border-0">
                            <i class="fa fa-exclamation-circle me-1"></i>{{ $page.props.session.error }}
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="fw-semibold small">Email</label>
                                <div class="input-group mt-1">
                                    <span class="input-group-text"><i class="fa fa-envelope text-muted"></i></span>
                                    <input type="email" class="form-control" v-model="form.email"
                                        placeholder="email@contoh.com"
                                        :class="{ 'is-invalid': errors.email }">
                                    <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="fw-semibold small">Password</label>
                                <div class="input-group mt-1">
                                    <span class="input-group-text"><i class="fa fa-lock text-muted"></i></span>
                                    <input :type="showPass ? 'text' : 'password'" class="form-control"
                                        v-model="form.password" placeholder="Password"
                                        :class="{ 'is-invalid': errors.password }">
                                    <button type="button" class="btn btn-outline-secondary" @click="showPass = !showPass">
                                        <i :class="showPass ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                    </button>
                                    <div v-if="errors.password" class="invalid-feedback">{{ errors.password }}</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="remember" v-model="form.remember">
                                    <label class="form-check-label small" for="remember">Ingat saya</label>
                                </div>
                                <Link href="/peserta/lupa-password" class="text-decoration-none small">Lupa password?</Link>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-dark" :disabled="processing">
                                    <i class="fa fa-sign-in me-1"></i>
                                    {{ processing ? 'Memproses...' : 'Login' }}
                                </button>
                            </div>

                            <p class="text-center small text-muted mb-0">
                                Belum punya akun?
                                <Link href="/peserta/register" class="text-decoration-none fw-semibold">Daftar di sini</Link>
                            </p>
                        </form>
                    </div>
                </div>

                <p class="text-center small text-muted mt-3">
                    <i class="fa fa-desktop me-1"></i>
                    Peserta ujian?
                    <Link href="/" class="text-decoration-none">Login dengan No. Peserta</Link>
                </p>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAuth from '../../../Layouts/Auth.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

export default {
    layout: LayoutAuth,
    components: { Head, Link },
    props: { errors: Object },

    setup() {
        const processing = ref(false);
        const showPass   = ref(false);
        const form = reactive({
            email: '',
            password: '',
            remember: false,
        });

        const submit = () => {
            processing.value = true;
            router.post('/peserta/login', form, {
                onFinish: () => { processing.value = false; },
            });
        };

        return { form, processing, showPass, submit };
    },
}
</script>
