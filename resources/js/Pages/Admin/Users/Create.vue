<template>
    <Head>
        <title>Tambah User - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card border-0 shadow">
                    <div class="card-header bg-gray-800 text-white fw-semibold">
                        <i class="fa fa-user-plus me-2"></i> Tambah User Baru
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Kode User <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input v-model="form.users_code" type="text" class="form-control" placeholder="otomatis ter-generate">
                                    <button type="button" class="btn btn-outline-secondary" @click="form.users_code = generateCode(form.role)" title="Generate ulang">
                                        <i class="fa fa-refresh"></i> Generate Ulang
                                    </button>
                                </div>
                                <div class="form-text small">Kode otomatis berdasarkan role. Anda tetap bisa mengubahnya secara manual.</div>
                                <div v-if="errors.users_code" class="text-danger small mt-1">{{ errors.users_code }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                <input v-model="form.name" type="text" class="form-control" placeholder="Nama lengkap">
                                <div v-if="errors.name" class="text-danger small mt-1">{{ errors.name }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input v-model="form.email" type="email" class="form-control" placeholder="email@contoh.com">
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

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                <input v-model="form.password" type="password" class="form-control" placeholder="Minimal 8 karakter">
                                <div v-if="errors.password" class="text-danger small mt-1">{{ errors.password }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input v-model="form.password_confirmation" type="password" class="form-control" placeholder="Ulangi password">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-gray-800" :disabled="processing">
                                    {{ processing ? 'Menyimpan...' : 'Simpan' }}
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
import { reactive, ref, watch } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        errors: Object,
    },

    setup() {
        const processing = ref(false);

        // Random alphanumeric: tanpa karakter ambigu (I, O, 0, 1) supaya tidak salah ketik
        const generateCode = (role) => {
            const prefix = role === 'admin' ? 'ADM' : 'ASR';
            const chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let suffix   = '';
            for (let i = 0; i < 6; i++) {
                suffix += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return `${prefix}-${suffix}`;
        };

        const form = reactive({
            users_code:            generateCode('asesor'),
            name:                  '',
            email:                 '',
            role:                  'asesor',
            password:              '',
            password_confirmation: '',
        });

        // Regenerate kode tiap kali role berubah (kecuali user sudah ubah manual prefix-nya)
        watch(() => form.role, (newRole, oldRole) => {
            const oldPrefix = oldRole === 'admin' ? 'ADM-' : 'ASR-';
            // Hanya regenerate jika kode saat ini masih dalam format auto (prefix sesuai role lama)
            if (form.users_code.startsWith(oldPrefix)) {
                form.users_code = generateCode(newRole);
            }
        });

        const submit = () => {
            processing.value = true;
            router.post('/admin/users', form, {
                onFinish: () => { processing.value = false; },
            });
        };

        return { form, processing, submit, generateCode };
    },
}
</script>
