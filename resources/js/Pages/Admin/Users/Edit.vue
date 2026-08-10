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

                <!-- Tanda Tangan Asesor -->
                <div class="card border-0 shadow mt-4" v-if="form.role === 'asesor'">
                    <div class="card-header bg-gray-800 text-white fw-semibold">
                        <i class="fa fa-signature me-2"></i>Tanda Tangan Asesor
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            TTD ini akan otomatis dipakai asesor saat menandatangani Verifikasi Akhir dokumen peserta.
                        </p>

                        <div v-if="user.signature_path && !editingSig" class="mb-3 p-2 border rounded bg-white d-flex align-items-center gap-3">
                            <img :src="`/admin/users/${user.id}/tanda-tangan`" alt="TTD Tersimpan"
                                style="max-height:70px; max-width:220px; object-fit:contain">
                            <div class="flex-fill">
                                <span class="badge bg-success small"><i class="fa fa-check me-1"></i>TTD Tersimpan</span>
                                <div class="small text-muted mt-1">{{ user.signature_name }}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="editingSig = true">
                                <i class="fa fa-pen me-1"></i>Ganti
                            </button>
                        </div>

                        <div v-if="!user.signature_path || editingSig">
                            <div class="mb-2">
                                <label class="fw-semibold small">Nama Penandatangan</label>
                                <input type="text" class="form-control form-control-sm mt-1" v-model="sigName"
                                    :placeholder="user.name">
                            </div>

                            <div class="d-flex gap-1 mt-1 mb-2">
                                <button type="button" class="btn btn-sm flex-fill"
                                    :class="sigMode === 'draw' ? 'btn-gray-800' : 'btn-light border'"
                                    @click="switchSigMode('draw')">
                                    <i class="fa fa-pen me-1"></i>Gambar
                                </button>
                                <button type="button" class="btn btn-sm flex-fill"
                                    :class="sigMode === 'upload' ? 'btn-gray-800' : 'btn-light border'"
                                    @click="switchSigMode('upload')">
                                    <i class="fa fa-upload me-1"></i>Upload
                                </button>
                                <button v-if="user.signature_path" type="button"
                                    class="btn btn-sm btn-light border" @click="editingSig = false">
                                    Batal
                                </button>
                            </div>

                            <div v-show="sigMode === 'draw'">
                                <div class="border rounded bg-white" style="touch-action:none">
                                    <canvas ref="sigCanvas" style="display:block; width:100%; height:140px; cursor:crosshair"></canvas>
                                </div>
                                <button type="button" class="btn btn-sm btn-light border mt-1" @click="clearSig">
                                    <i class="fa fa-eraser me-1"></i>Hapus
                                </button>
                            </div>

                            <div v-show="sigMode === 'upload'">
                                <input type="file" class="form-control form-control-sm"
                                    accept="image/png,image/jpeg,image/jpg" @change="onSigFileChange">
                                <div v-if="sigFilePreview" class="mt-2">
                                    <img :src="sigFilePreview" style="max-height:80px; border:1px solid #ddd; background:#fff; padding:4px">
                                </div>
                            </div>

                            <div class="d-grid mt-3">
                                <button type="button" class="btn btn-success" :disabled="sigSaving" @click="submitSignature">
                                    <i class="fa fa-save me-1"></i>{{ sigSaving ? 'Menyimpan...' : 'Simpan Tanda Tangan' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref, nextTick, onMounted, onUnmounted, watch } from 'vue';
import SignaturePad from 'signature_pad';

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

        // Tanda Tangan Asesor
        const editingSig     = ref(false);
        const sigMode        = ref('draw');
        const sigCanvas      = ref(null);
        const sigFile        = ref(null);
        const sigFilePreview = ref(null);
        const sigName        = ref(props.user.signature_name ?? '');
        const sigSaving      = ref(false);
        let   sigPad         = null;
        let   resizeTimer    = null;

        const initSigPad = () => {
            if (!sigCanvas.value) return;
            const canvas    = sigCanvas.value;
            const container = canvas.parentElement;
            const ratio     = Math.max(window.devicePixelRatio || 1, 1);
            const savedData = sigPad?.toData() ?? [];
            canvas.width    = (container?.clientWidth || canvas.offsetWidth) * ratio;
            canvas.height   = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            sigPad          = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
            if (savedData.length) sigPad.fromData(savedData);
        };

        const handleResize = () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(initSigPad, 200);
        };

        const sigPadVisible = () => form.role === 'asesor' && (!props.user.signature_path || editingSig.value);

        onMounted(async () => {
            if (sigPadVisible()) {
                await nextTick();
                initSigPad();
            }
            window.addEventListener('resize', handleResize);
        });

        onUnmounted(() => {
            window.removeEventListener('resize', handleResize);
            clearTimeout(resizeTimer);
        });

        watch([() => form.role, editingSig], async () => {
            if (sigPadVisible()) {
                await nextTick();
                initSigPad();
            }
        });

        const switchSigMode = async (mode) => {
            sigMode.value = mode;
            if (mode === 'draw') { await nextTick(); initSigPad(); }
        };

        const clearSig = () => sigPad?.clear();

        const onSigFileChange = (e) => {
            const file = e.target.files[0];
            if (!file) return;
            sigFile.value        = file;
            sigFilePreview.value = URL.createObjectURL(file);
        };

        const submitSignature = () => {
            const fd = new FormData();
            fd.append('signature_name', sigName.value);

            if (sigMode.value === 'draw') {
                if (!sigPad || sigPad.isEmpty()) {
                    alert('Silakan buat tanda tangan terlebih dahulu.');
                    return;
                }
                fd.append('signature_data', sigPad.toDataURL('image/png'));
            } else {
                if (!sigFile.value) {
                    alert('Pilih file tanda tangan terlebih dahulu.');
                    return;
                }
                fd.append('signature_file', sigFile.value);
            }

            sigSaving.value = true;
            router.post(`/admin/users/${props.user.id}/tanda-tangan`, fd, {
                forceFormData:  true,
                preserveScroll: true,
                onSuccess: () => { editingSig.value = false; },
                onFinish:  () => { sigSaving.value = false; },
            });
        };

        return {
            form, processing, submit,
            editingSig, sigMode, sigCanvas, sigFile, sigFilePreview, sigName, sigSaving,
            switchSigMode, clearSig, onSigFileChange, submitSignature,
        };
    },
}
</script>
