<template>
    <Head>
        <title>Login Peserta - Portal Sertifikasi</title>
    </Head>

    <div class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-9">
                <div class="row g-0 shadow-sm rounded-3 overflow-hidden bg-white">

                    <!-- Panel kiri -->
                    <div class="col-md-5 d-flex flex-column p-4 p-md-5 text-white"
                        style="background:#1f2937">

                        <div class="text-center mb-4">
                            <img src="/assets/images/logo.png" alt="Logo"
                                style="max-height:90px;max-width:180px;object-fit:contain">
                        </div>

                        <div class="text-center mb-4">
                            <h5 class="fw-bold mb-1">Portal Sertifikasi</h5>
                            <p class="small mb-0" style="opacity:.65">LSP Edukasi Global Cendekia</p>
                        </div>

                        <hr style="border-color:rgba(255,255,255,.15)">

                        <div class="mb-4">
                            <p class="small fw-semibold mb-3">
                                <i class="fa fa-info-circle me-1"></i>Tentang Portal Ini
                            </p>
                            <p class="small mb-0" style="opacity:.8;line-height:1.6">
                                Halaman ini khusus untuk <strong>pendaftaran sertifikasi</strong>.
                                Jika Anda ingin mengikuti ujian, gunakan halaman login ujian dengan No. Peserta.
                            </p>
                        </div>

                        <div class="mt-auto">
                            <Link href="/peserta/register" class="btn btn-sm w-100 mb-2"
                                style="background:#fff;color:#1f2937;font-weight:600;border:none">
                                <i class="fa fa-user-plus me-1"></i> Daftar Sekarang
                            </Link>
                            <Link href="/peserta/panduan" class="btn btn-sm w-100 mb-2"
                                style="background:transparent;color:#fff;font-weight:600;border:1px solid rgba(255,255,255,.45)">
                                <i class="fa fa-book-open me-1"></i> Panduan Pendaftaran
                            </Link>
                            <Link href="/" class="btn btn-sm w-100 text-decoration-none"
                                style="font-size:0.8rem;color:rgba(255,255,255,0.6)">
                                Login ujian dengan No. Peserta
                            </Link>
                        </div>
                    </div>

                    <!-- Panel kanan: form -->
                    <div class="col-md-7 p-4 p-md-5 d-flex flex-column justify-content-center">

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
                                    <span class="input-group-text bg-light">
                                        <i class="fa fa-envelope text-muted"></i>
                                    </span>
                                    <input type="email" class="form-control" v-model="form.email"
                                        placeholder="email@contoh.com"
                                        :class="{ 'is-invalid': errors.email }">
                                    <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="fw-semibold small">Password</label>
                                <div class="input-group mt-1">
                                    <span class="input-group-text bg-light">
                                        <i class="fa fa-lock text-muted"></i>
                                    </span>
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

                        <!-- Info ujian di mobile -->
                        <div class="d-md-none mt-4 p-3 rounded" style="background:#f8f9fa;border:1px solid #e9ecef">
                            <p class="small fw-semibold mb-1">
                                <i class="fa fa-desktop me-1 text-muted"></i>Ingin ikut ujian?
                            </p>
                            <Link href="/" class="btn btn-sm btn-dark mt-1">Login dengan No. Peserta</Link>
                        </div>
                    </div>

                </div>
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
            email:    '',
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
