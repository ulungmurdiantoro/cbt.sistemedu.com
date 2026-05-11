<template>
    <Head>
        <title>Login Peserta - Portal Sertifikasi</title>
    </Head>
    <div class="row justify-content-center mt-5">
        <div class="col-12 col-xl-10 col-xxl-9">
            <div class="bg-white shadow border-0 rounded border-light p-4 p-lg-5 w-100">
                <h4 class="mb-1 fw-bold">Login Portal Peserta</h4>
                <p class="text-muted small mb-4">Untuk login ujian gunakan halaman utama dengan No. Peserta.</p>

                <div v-if="$page.props.session.error" class="alert alert-danger">
                    {{ $page.props.session.error }}
                </div>

                <form @submit.prevent="submit">
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                            <input type="email" class="form-control" v-model="form.email" placeholder="email@contoh.com">
                        </div>
                        <div v-if="errors.email" class="text-danger small mt-1">{{ errors.email }}</div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-lock"></i></span>
                            <input type="password" class="form-control" v-model="form.password" placeholder="Password">
                        </div>
                        <div v-if="errors.password" class="text-danger small mt-1">{{ errors.password }}</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="remember" v-model="form.remember">
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>
                        <Link href="/peserta/lupa-password" class="text-decoration-none small">Lupa password?</Link>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gray-800" :disabled="processing">
                            {{ processing ? 'Memproses...' : 'Login' }}
                        </button>
                    </div>

                    <p class="text-center small text-muted">
                        Belum punya akun?
                        <Link href="/peserta/register" class="text-decoration-none fw-semibold">Daftar di sini</Link>
                    </p>

                    <hr class="my-3">
                    <p class="text-center small text-muted">
                        Peserta ujian? <Link href="/" class="text-decoration-none">Login dengan No. Peserta</Link>
                    </p>
                </form>
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

        return { form, processing, submit };
    },
}
</script>
