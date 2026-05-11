<template>
    <Head>
        <title>Daftar Akun - Portal Peserta Sertifikasi</title>
    </Head>
    <div class="row justify-content-center mt-5">
        <div class="col-12 col-xl-10 col-xxl-9">
            <div class="bg-white shadow border-0 rounded border-light p-4 p-lg-5 w-100">
                <h4 class="mb-1 fw-bold">Daftar Akun Peserta</h4>
                <p class="text-muted small mb-4">Akun ini digunakan untuk pendaftaran sertifikasi, bukan untuk login ujian.</p>

                <div v-if="$page.props.session.error" class="alert alert-danger">
                    {{ $page.props.session.error }}
                </div>

                <form @submit.prevent="submit">
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Nama Lengkap</label>
                        <input type="text" class="form-control" v-model="form.name" placeholder="Nama lengkap sesuai KTP">
                        <div v-if="errors.name" class="text-danger small mt-1">{{ errors.name }}</div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="fw-semibold">Email</label>
                        <input type="email" class="form-control" v-model="form.email" placeholder="email@contoh.com">
                        <div v-if="errors.email" class="text-danger small mt-1">{{ errors.email }}</div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="fw-semibold">Password</label>
                        <input type="password" class="form-control" v-model="form.password" placeholder="Minimal 8 karakter">
                        <div v-if="errors.password" class="text-danger small mt-1">{{ errors.password }}</div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="fw-semibold">Konfirmasi Password</label>
                        <input type="password" class="form-control" v-model="form.password_confirmation" placeholder="Ulangi password">
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gray-800" :disabled="processing">
                            {{ processing ? 'Memproses...' : 'Daftar' }}
                        </button>
                    </div>

                    <p class="text-center small text-muted">
                        Sudah punya akun?
                        <Link href="/peserta/login" class="text-decoration-none fw-semibold">Login di sini</Link>
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
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
        });

        const submit = () => {
            processing.value = true;
            router.post('/peserta/register', form, {
                onFinish: () => { processing.value = false; },
            });
        };

        return { form, processing, submit };
    },
}
</script>
