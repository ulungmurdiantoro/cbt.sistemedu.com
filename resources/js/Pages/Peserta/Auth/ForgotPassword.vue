<template>
    <Head>
        <title>Lupa Password - Portal Peserta Sertifikasi</title>
    </Head>
    <div class="row justify-content-center mt-5">
        <div class="col-12 col-xl-10 col-xxl-9">
            <div class="bg-white shadow border-0 rounded border-light p-4 p-lg-5 w-100">
                <h4 class="mb-1 fw-bold">Lupa Password</h4>
                <p class="text-muted small mb-4">Masukkan email Anda dan kami akan mengirimkan link untuk mereset password.</p>

                <div v-if="$page.props.session.success" class="alert alert-success">
                    {{ $page.props.session.success }}
                </div>

                <form @submit.prevent="submit">
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                            <input type="email" class="form-control" v-model="email"
                                placeholder="email@contoh.com" autofocus>
                        </div>
                        <div v-if="errors.email" class="text-danger small mt-1">{{ errors.email }}</div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gray-800" :disabled="processing">
                            {{ processing ? 'Mengirim...' : 'Kirim Link Reset Password' }}
                        </button>
                    </div>

                    <p class="text-center small text-muted">
                        Sudah ingat password?
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
import { ref } from 'vue';

export default {
    layout: LayoutAuth,
    components: { Head, Link },
    props: { errors: Object },

    setup() {
        const processing = ref(false);
        const email      = ref('');

        const submit = () => {
            processing.value = true;
            router.post('/peserta/lupa-password', { email: email.value }, {
                onFinish: () => { processing.value = false; },
            });
        };

        return { email, processing, submit };
    },
}
</script>
