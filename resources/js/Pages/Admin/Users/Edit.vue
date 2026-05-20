<template>
    <Head>
        <title>Edit User - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card border-0 shadow">
                    <div class="card-header bg-gray-800 text-white fw-semibold">
                        <i class="fa fa-user-edit me-2"></i> Edit User
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Kode User <span class="text-danger">*</span></label>
                                <input v-model="form.users_code" type="text" class="form-control">
                                <div v-if="errors.users_code" class="text-danger small mt-1">{{ errors.users_code }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                <input v-model="form.name" type="text" class="form-control">
                                <div v-if="errors.name" class="text-danger small mt-1">{{ errors.name }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input v-model="form.email" type="email" class="form-control">
                                <div v-if="errors.email" class="text-danger small mt-1">{{ errors.email }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                                <select v-model="form.role" class="form-select">
                                    <option value="admin">Admin</option>
                                    <option value="asesor">Asesor</option>
                                </select>
                                <div v-if="errors.role" class="text-danger small mt-1">{{ errors.role }}</div>
                            </div>

                            <hr>
                            <p class="small text-muted mb-3"><i class="fa fa-info-circle me-1"></i> Kosongkan password jika tidak ingin mengubahnya.</p>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password Baru</label>
                                <input v-model="form.password" type="password" class="form-control" placeholder="Minimal 8 karakter">
                                <div v-if="errors.password" class="text-danger small mt-1">{{ errors.password }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                                <input v-model="form.password_confirmation" type="password" class="form-control" placeholder="Ulangi password baru">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-gray-800" :disabled="processing">
                                    {{ processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                                </button>
                                <Link href="/admin/users" class="btn btn-light border">Batal</Link>
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
import { reactive, ref } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        errors: Object,
        user:   Object,
    },

    setup(props) {
        const processing = ref(false);

        const form = reactive({
            users_code:            props.user.users_code ?? '',
            name:                  props.user.name,
            email:                 props.user.email,
            role:                  props.user.role,
            password:              '',
            password_confirmation: '',
        });

        const submit = () => {
            processing.value = true;
            router.put(`/admin/users/${props.user.id}`, form, {
                onFinish: () => { processing.value = false; },
            });
        };

        return { form, processing, submit };
    },
}
</script>
