<template>
    <!-- Kartu bagian seragam (admin & asesor): judul + ringkasan di kiri,
         tombol aksi (Simpan dll.) selalu di kanan header kartu. -->
    <div class="card border-0 shadow mb-4">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
            <div class="card-title-block">
                <h6 class="mb-0"><i :class="['fa', icon, 'me-2']"></i>{{ title }}</h6>
                <div v-if="$slots.meta" class="small text-muted mt-1 meta"><slot name="meta" /></div>
            </div>
            <div v-if="$slots.actions" class="d-flex align-items-center gap-2 ms-auto"><slot name="actions" /></div>
        </div>
        <div class="card-body">
            <slot />
        </div>
    </div>
</template>

<script>
export default {
    props: {
        title: { type: String, required: true },
        icon:  { type: String, default: 'fa-table' },
    },
};
</script>

<style scoped>
/* Judul/ringkasan mengisi sisa lebar dan boleh turun baris, supaya tombol aksi tetap di kanan;
   baru pindah ke baris bawah (tetap rata kanan) bila layar sempit. */
.card-title-block {
    flex: 1 1 320px;
    min-width: 0;
}
.meta {
    white-space: normal;
}
</style>
