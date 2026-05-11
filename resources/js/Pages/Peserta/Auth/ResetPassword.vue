<template>
    <Head>
        <title>Reset Password - Portal Peserta Sertifikasi</title>
    </Head>
    <div class="row justify-content-center mt-5">
        <div class="col-12 col-xl-10 col-xxl-9">
            <div class="bg-white shadow border-0 rounded border-light p-4 p-lg-5 w-100">
                <h4 class="mb-1 fw-bold">Reset Password</h4>
                <p class="text-muted small mb-4">Masukkan password baru Anda di bawah ini.</p>

                <form @submit.prevent="submit">
                    <div class="form-group mb-3">
                        <label class="fw-semibold">Email</label>
                        <input type="email" class="form-control" :value="form.email" disabled>
                    </div>

                    <div class="form-group mb-3">
                        <label class="fw-semibold">Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-lock"></i></span>
                            <input :type="showPassword ? 'text' : 'password'" class="form-control"
                                v-model="form.password" placeholder="Minimal 8 karakter">
                            <button type="button" class="btn btn-light border" @click="showPassword = !showPassword">
                                <i :class="showPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                            </button>
                        </div>
                        <div v-if="errors.password" class="text-danger small mt-1">{{ errors.password }}</div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-lock"></i></span>
                            <input :type="showPassword ? 'text' : 'password'" class="form-control"
                                v-model="form.password_confirmation" placeholder="Ulangi password">
                        </div>
                    </div>

                    <div v-if="errors.email" class="alert alert-danger small py-2">{{ errors.email }}</div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-gray-800" :disabled="processing">
                            {{ processing ? 'Menyimpan...' : 'Simpan Password Baru' }}
                        </button>
                    </div>
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
    props: {
        token: String,
        email: String,
        errors: Object,
    },

    setup(props) {
        const processing  = ref(false);
        const showPassword = ref(false);

        const form = reactive({
            token:                 props.token,
            email:                 props.email,
            password:              '',
            password_confirmation: '',
        });

        const submit = () => {
            processing.value = true;
            router.post('/peserta/reset-password', form, {
                onFinish: () => { processing.value = false; },
            });
        };

        return { form, processing, showPassword, submit };
    },
}
</script>
