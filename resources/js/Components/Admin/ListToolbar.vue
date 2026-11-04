<template>
    <!-- Toolbar halaman daftar admin (Blueprint): tombol aksi di kiri • filter + kotak Cari di kanan.
         Semua menu daftar memakai komponen ini supaya posisi & ukuran tombol sama. -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div v-if="$slots.actions" class="d-flex flex-wrap align-items-center gap-2"><slot name="actions" /></div>
        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto toolbar-right">
            <slot name="filters" />
            <form v-if="searchable" class="search-box" @submit.prevent="$emit('search')">
                <div class="input-group">
                    <input type="text" class="form-control border-0 shadow" :value="modelValue"
                        @input="$emit('update:modelValue', $event.target.value)" :placeholder="placeholder">
                    <button type="submit" class="input-group-text border-0 shadow" title="Cari">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        modelValue:  { type: String, default: '' },
        placeholder: { type: String, default: 'masukkan kata kunci...' },
        searchable:  { type: Boolean, default: true },
    },
    emits: ['update:modelValue', 'search'],
};
</script>

<style scoped>
.search-box {
    width: 320px;
    max-width: 100%;
}
@media (max-width: 575.98px) {
    .toolbar-right, .search-box { width: 100%; }
}
</style>
