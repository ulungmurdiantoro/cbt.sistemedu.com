<template>
    <div v-if="file" class="d-flex align-items-center gap-1 small">
        <i class="fa fa-paperclip text-gray-500"></i>
        <span class="text-truncate" style="max-width:150px" :title="file.name">{{ file.name }}</span>
        <span class="badge bg-gray-200 text-gray-800 border">baru</span>
        <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1" title="Batal" @click="$emit('update:file', null)">
            <i class="fa fa-times"></i>
        </button>
    </div>
    <div v-else-if="bukti" class="d-flex align-items-center gap-1 small">
        <a :href="`/asesor/cv/bukti/${bukti.id}`" target="_blank" class="text-truncate" style="max-width:170px" :title="bukti.name">
            <i class="fa fa-file-alt me-1"></i>{{ bukti.name }}
        </a>
        <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1" title="Hapus bukti" @click="$emit('update:bukti', null)">
            <i class="fa fa-times"></i>
        </button>
    </div>
    <template v-else>
        <input type="file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" @change="onChange">
        <div v-if="error" class="text-danger small mt-1">{{ error }}</div>
    </template>
</template>

<script>
const MAX_MB = 5;
const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];

// Bukti dokumen (opsional) untuk satu baris CV asesor: bukti tersimpan {id, name}
// atau file baru yang menunggu Simpan CV. Batas sama dengan CvController.
export default {
    props: {
        bukti: { type: Object, default: null },
        file:  { type: Object, default: null },
    },

    emits: ['update:bukti', 'update:file'],

    data() {
        return { error: '' };
    },

    methods: {
        onChange(e) {
            const file = e.target.files[0];
            this.error = '';
            if (!file) return;

            const ext = file.name.split('.').pop().toLowerCase();
            if (!EXTENSIONS.includes(ext)) {
                this.error = 'Format harus PDF, JPG atau PNG.';
            } else if (file.size > MAX_MB * 1024 * 1024) {
                this.error = `Ukuran maksimal ${MAX_MB} MB.`;
            }

            if (this.error) {
                e.target.value = '';
                return;
            }
            this.$emit('update:file', file);
        },
    },
}
</script>
