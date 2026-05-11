<template>
    <Head>
        <title>Daftar Akun - Portal Sertifikasi</title>
    </Head>

    <div class="min-vh-100 d-flex">

        <!-- Panel kiri: alur pendaftaran (desktop) -->
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

                <h5 class="fw-bold mb-1">Alur Pendaftaran</h5>
                <p class="small opacity-75 mb-4">Selesaikan 6 langkah berikut untuk mengajukan permohonan sertifikasi.</p>

                <div v-for="(step, i) in steps" :key="i" class="d-flex gap-3 mb-3 align-items-start">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                        style="width:26px;height:26px;font-size:0.75rem;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3)">
                        {{ i + 1 }}
                    </div>
                    <div>
                        <div class="fw-semibold small lh-1 mb-1">{{ step.title }}</div>
                        <div class="opacity-60" style="font-size:0.73rem">{{ step.desc }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-4" style="border-top:1px solid rgba(255,255,255,0.15)">
                <p class="small opacity-75 mb-2">Sudah punya akun?</p>
                <Link href="/peserta/login" class="btn btn-sm px-4"
                    style="background:#fff;color:#1f2937;font-weight:600;border:none">
                    <i class="fa fa-sign-in me-1"></i> Login di sini
                </Link>
            </div>
        </div>

        <!-- Panel kanan: form -->
        <div class="flex-fill d-flex align-items-center justify-content-center p-4">
            <div style="max-width:460px;width:100%">

                <!-- Logo mobile -->
                <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
                    <img src="/assets/images/logo.png" alt="Logo" style="height:40px;object-fit:contain">
                    <span class="fw-bold small">LSP Edukasi Global Cendekia</span>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h4 class="fw-bold mb-1">Buat Akun Peserta</h4>
                        <p class="text-muted small mb-4">
                            Akun ini untuk pendaftaran sertifikasi, bukan untuk login ujian.
                        </p>

                        <div v-if="$page.props.session.error" class="alert alert-danger py-2 small border-0">
                            {{ $page.props.session.error }}
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="fw-semibold small">Nama Lengkap</label>
                                <input type="text" class="form-control mt-1" v-model="form.name"
                                    placeholder="Sesuai KTP / identitas resmi"
                                    :class="{ 'is-invalid': errors.name }">
                                <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="fw-semibold small">Email</label>
                                <input type="email" class="form-control mt-1" v-model="form.email"
                                    placeholder="email@contoh.com"
                                    :class="{ 'is-invalid': errors.email }">
                                <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="fw-semibold small">Password</label>
                                <div class="input-group mt-1">
                                    <input :type="showPass ? 'text' : 'password'" class="form-control"
                                        v-model="form.password" placeholder="Minimal 8 karakter"
                                        :class="{ 'is-invalid': errors.password }">
                                    <button type="button" class="btn btn-outline-secondary" @click="showPass = !showPass">
                                        <i :class="showPass ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                    </button>
                                    <div v-if="errors.password" class="invalid-feedback">{{ errors.password }}</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="fw-semibold small">Konfirmasi Password</label>
                                <input type="password" class="form-control mt-1"
                                    v-model="form.password_confirmation" placeholder="Ulangi password">
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-dark" :disabled="processing">
                                    <i class="fa fa-user-plus me-1"></i>
                                    {{ processing ? 'Memproses...' : 'Buat Akun' }}
                                </button>
                            </div>

                            <p class="text-center small text-muted mb-0">
                                Sudah punya akun?
                                <Link href="/peserta/login" class="text-decoration-none fw-semibold">Login di sini</Link>
                            </p>
                        </form>
                    </div>
                </div>

                <p class="text-center small text-muted mt-3">
                    <i class="fa fa-desktop me-1"></i>
                    Peserta ujian?
                    <Link href="/" class="text-decoration-none">Login dengan No. Peserta</Link>
                </p>

                <!-- Alur ringkas mobile -->
                <div class="d-lg-none mt-4 p-3 rounded" style="background:#f8f9fa;border:1px solid #e9ecef">
                    <div class="small fw-bold mb-2 text-muted">ALUR PENDAFTARAN</div>
                    <div v-for="(step, i) in steps" :key="i" class="d-flex gap-2 align-items-start mb-2">
                        <span class="badge bg-dark rounded-circle" style="width:20px;height:20px;font-size:0.65rem;display:flex;align-items:center;justify-content:center;flex-shrink:0">{{ i+1 }}</span>
                        <span class="small">{{ step.title }}</span>
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
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
        });

        const steps = [
            { title: 'Buat akun peserta',          desc: 'Daftar dengan email dan password' },
            { title: 'Pilih skema & sesi ujian',   desc: 'Pilih skema sertifikasi yang sesuai' },
            { title: 'Isi formulir FR.APL.01',     desc: 'Data pribadi dan riwayat pekerjaan' },
            { title: 'Pakta integritas FR.AK.01',  desc: 'Tanda tangani secara digital' },
            { title: 'Upload dokumen persyaratan', desc: 'Ijazah, SK, dan dokumen pendukung' },
            { title: 'Submit & tunggu verifikasi', desc: 'Admin memverifikasi permohonan Anda' },
        ];

        const submit = () => {
            processing.value = true;
            router.post('/peserta/register', form, {
                onFinish: () => { processing.value = false; },
            });
        };

        return { form, processing, showPass, steps, submit };
    },
}
</script>
