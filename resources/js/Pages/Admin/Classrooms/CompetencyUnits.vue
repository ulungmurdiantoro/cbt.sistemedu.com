<template>
    <Head>
        <title>Unit Kompetensi - {{ classroom.title }}</title>
    </Head>

    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0">Unit Kompetensi</h5>
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
        <!-- Daftar unit kompetensi -->
        <div class="col-md-7">
            <div class="card border-0 shadow mb-4">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="bg-gray-100">
                            <tr>
                                <th style="width:40px">No.</th>
                                <th style="width:130px">Kode Unit</th>
                                <th>Judul Unit Kompetensi</th>
                                <th style="width:80px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="units.length === 0">
                                <td colspan="4" class="text-center text-muted py-4">Belum ada unit kompetensi</td>
                            </tr>
                            <tr v-for="(unit, i) in units" :key="unit.id">
                                <td class="align-middle">{{ unit.order || i + 1 }}</td>
                                <td class="align-middle">
                                    <span class="badge bg-secondary text-wrap" style="font-size:0.8rem">{{ unit.kode_unit }}</span>
                                </td>
                                <td class="align-middle">
                                    <div class="fw-semibold small">{{ unit.judul_unit }}</div>
                                    <div v-if="unit.judul_unit_en" class="text-muted fst-italic" style="font-size:0.78rem">{{ unit.judul_unit_en }}</div>
                                </td>
                                <td class="align-middle">
                                    <button class="btn btn-sm btn-warning me-1" @click="editUnit(unit)">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" @click="deleteUnit(unit.id)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Form tambah/edit -->
        <div class="col-md-5">
            <div class="card border-0 shadow">
                <div class="card-header bg-gray-800 text-white fw-semibold">
                    {{ editMode ? 'Edit Unit Kompetensi' : 'Tambah Unit Kompetensi' }}
                </div>
                <div class="card-body">
                    <form @submit.prevent="saveUnit">
                        <div class="mb-3">
                            <label class="fw-semibold small">Kode Unit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.kode_unit" placeholder="contoh: TIK.OP02.001.01">
                            <div v-if="errors.kode_unit" class="text-danger small mt-1">{{ errors.kode_unit }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold small">Judul Unit Kompetensi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.judul_unit" placeholder="Masukkan judul unit kompetensi">
                            <div v-if="errors.judul_unit" class="text-danger small mt-1">{{ errors.judul_unit }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold small">Judul Unit Kompetensi (Bahasa Inggris)</label>
                            <input type="text" class="form-control" v-model="form.judul_unit_en" placeholder="Enter competency unit title in English">
                            <div v-if="errors.judul_unit_en" class="text-danger small mt-1">{{ errors.judul_unit_en }}</div>
                        </div>
                        <div class="mb-4">
                            <label class="fw-semibold small">Urutan</label>
                            <input type="number" class="form-control" v-model="form.order" min="0">
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
        classroom: Object,
        units:     Array,
        errors:    Object,
    },

    setup(props) {
        const editMode = ref(false);
        const editId   = ref(null);
        const saving   = ref(false);

        const form = reactive({
            kode_unit:    '',
            judul_unit:   '',
            judul_unit_en:'',
            order:        0,
        });

        const resetForm = () => {
            form.kode_unit = ''; form.judul_unit = ''; form.judul_unit_en = ''; form.order = 0;
            editMode.value = false; editId.value = null;
        };

        const editUnit = (unit) => {
            editMode.value        = true;
            editId.value          = unit.id;
            form.kode_unit        = unit.kode_unit;
            form.judul_unit       = unit.judul_unit;
            form.judul_unit_en    = unit.judul_unit_en ?? '';
            form.order            = unit.order;
        };

        const cancelEdit = () => resetForm();

        const saveUnit = () => {
            saving.value = true;
            const url = editMode.value
                ? `/admin/classrooms/${props.classroom.id}/competency-units/${editId.value}`
                : `/admin/classrooms/${props.classroom.id}/competency-units`;
            const method = editMode.value ? 'put' : 'post';

            router[method](url, form, {
                onSuccess: () => resetForm(),
                onFinish:  () => { saving.value = false; },
            });
        };

        const deleteUnit = (id) => {
            if (!confirm('Hapus unit kompetensi ini?')) return;
            router.delete(`/admin/classrooms/${props.classroom.id}/competency-units/${id}`);
        };

        return { form, editMode, saving, editUnit, cancelEdit, saveUnit, deleteUnit };
    },
}
</script>
