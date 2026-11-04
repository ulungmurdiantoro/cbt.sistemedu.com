<template>
    <!-- Baris atas halaman detail / form / per sesi admin: tombol Kembali selalu di kiri,
         aksi tingkat halaman (Edit, Laporan, dll.) di kanan. -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <Link :href="href" class="btn btn-md btn-primary border-0 shadow">
            <i class="fa fa-long-arrow-alt-left me-2"></i>{{ label }}
        </Link>
        <div v-if="$slots.default" class="d-flex flex-wrap align-items-center gap-2 ms-auto"><slot /></div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';

export default {
    components: { Link },
    props: {
        href:  { type: String, required: true },
        label: { type: String, default: 'Kembali' },
    },
};
</script>
