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
                                    <button type="button" class="btn btn-outline-secondary" @click="form.users_code = generateCode(form.roles)" title="Generate ulang">
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
                                <div class="form-text small mb-1">Satu user bisa punya lebih dari satu role.</div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="admin" v-model="form.roles" id="role-admin">
                                    <label class="form-check-label" for="role-admin">Admin</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="asesor" v-model="form.roles" id="role-asesor">
                                    <label class="form-check-label" for="role-asesor">Asesor</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="manager_sertifikasi" v-model="form.roles" id="role-manager">
                                    <label class="form-check-label" for="role-manager">Pengambil Keputusan</label>
                                </div>
                                <div v-if="errors.roles" class="text-danger small mt-1">{{ errors.roles }}</div>
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
        // Prefix kode mengikuti role dengan prioritas tertinggi: admin > manager_sertifikasi > asesor
        const prefixForRoles = (roles) => {
            if (roles.includes('admin')) return 'ADM';
            if (roles.includes('manager_sertifikasi')) return 'MGR';
            return 'ASR';
        };
        const generateCode = (roles) => {
            const prefix = prefixForRoles(roles);
            const chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let suffix   = '';
            for (let i = 0; i < 6; i++) {
                suffix += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return `${prefix}-${suffix}`;
        };

        const form = reactive({
            users_code:            generateCode(['asesor']),
            name:                  '',
            email:                 '',
            roles:                 ['asesor'],
            password:              '',
            password_confirmation: '',
        });

        // Regenerate kode tiap kali role berubah (kecuali user sudah ubah manual prefix-nya)
        watch(() => [...form.roles], (newRoles, oldRoles) => {
            const oldPrefix = prefixForRoles(oldRoles) + '-';
            // Hanya regenerate jika kode saat ini masih dalam format auto (prefix sesuai role lama)
            if (form.users_code.startsWith(oldPrefix)) {
                form.users_code = generateCode(newRoles);
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
