<template>
    <Head>
        <title>Persyaratan Dokumen - {{ classroom.title }}</title>
    </Head>

    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0">Persyaratan Dokumen</h5>
                    <p class="text-muted small mb-0">Skema: <strong>{{ classroom.title }}</strong></p>
                </div>
                <Link href="/admin/classrooms" class="btn btn-sm btn-light border">
                    <i class="fa fa-arrow-left me-1"></i> Kembali
                </Link>
            </div>
        </div>
    </div>

    <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
        {{ $page.props.session.success }}
    </div>

    <div class="row">
        <!-- Daftar requirement -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th style="width:40px">No.</th>
                                    <th style="min-width:200px">Dokumen</th>
                                    <th style="width:100px">Wajib</th>
                                    <th style="width:110px; white-space:nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="requirements.length === 0">
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada persyaratan</td>
                                </tr>
                                <tr v-for="(req, i) in requirements" :key="req.id">
                                    <td>{{ req.order || i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold small text-break">{{ req.label }}</div>
                                        <div class="text-muted text-break" style="font-size:0.78rem">{{ req.code }}</div>
                                        <div v-if="req.description" class="text-muted text-break" style="font-size:0.78rem">{{ req.description }}</div>
                                    </td>
                                    <td>
                                        <span :class="req.is_required ? 'badge bg-danger' : 'badge bg-secondary'">
                                            {{ req.is_required ? 'Wajib' : 'Opsional' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-warning" @click="editReq(req)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" @click="deleteReq(req.id)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form tambah/edit -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow">
                <div class="card-header bg-gray-800 text-white fw-semibold">
                    {{ editMode ? 'Edit Persyaratan' : 'Tambah Persyaratan' }}
                </div>
                <div class="card-body">
                    <form @submit.prevent="saveReq">
                        <div class="mb-3">
                            <label class="fw-semibold small">Kode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.code" placeholder="contoh: ijazah">
                            <div v-if="errors.code" class="text-danger small mt-1">{{ errors.code }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold small">Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.label" placeholder="contoh: Ijazah Pendidikan">
                            <div v-if="errors.label" class="text-danger small mt-1">{{ errors.label }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold small">Deskripsi</label>
                            <textarea class="form-control" rows="2" v-model="form.description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold small">Urutan</label>
                            <input type="number" class="form-control" v-model="form.order" min="0">
                        </div>
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="is_required" v-model="form.is_required">
                            <label class="form-check-label fw-semibold small" for="is_required">Dokumen Wajib</label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-gray-800 btn-sm" :disabled="saving">
                                {{ saving ? 'Menyimpan...' : (editMode ? 'Update' : 'Tambah') }}
                            </button>
                            <button v-if="editMode" type="button" class="btn btn-light btn-sm border" @click="cancelEdit">
                                Batal
                            </button>
                        </div>
                    </form>
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
        classroom:    Object,
        requirements: Array,
        errors:       Object,
    },

    setup(props) {
        const editMode  = ref(false);
        const editId    = ref(null);
        const saving    = ref(false);

        const form = reactive({
            code:        '',
            label:       '',
            description: '',
            is_required: true,
            order:       0,
        });

        const resetForm = () => {
            form.code = ''; form.label = ''; form.description = '';
            form.is_required = true; form.order = 0;
            editMode.value = false; editId.value = null;
        };

        const editReq = (req) => {
            editMode.value   = true;
            editId.value     = req.id;
            form.code        = req.code;
            form.label       = req.label;
            form.description = req.description ?? '';
            form.is_required = req.is_required;
            form.order       = req.order;
        };

        const cancelEdit = () => resetForm();

        const saveReq = () => {
            saving.value = true;
            const url = editMode.value
                ? `/admin/classrooms/${props.classroom.id}/requirements/${editId.value}`
                : `/admin/classrooms/${props.classroom.id}/requirements`;
            const method = editMode.value ? 'put' : 'post';

            router[method](url, form, {
                onSuccess: () => resetForm(),
                onFinish: () => { saving.value = false; },
            });
        };

        const deleteReq = (id) => {
            if (!confirm('Hapus persyaratan ini?')) return;
            router.delete(`/admin/classrooms/${props.classroom.id}/requirements/${id}`);
        };

        return { form, editMode, saving, editReq, cancelEdit, saveReq, deleteReq };
    },
}
</script>
