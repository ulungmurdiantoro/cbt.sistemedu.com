<template>
    <div>
        <!-- Baris atas halaman per sesi: hanya tombol Kembali. Tombol aksi (Simpan dll.) selalu di
             header kartu tabel (Components/Asesor/TableCard.vue), bukan di sini. -->
        <Link href="/asesor/dashboard" class="btn btn-md btn-primary border-0 shadow mb-3">
            <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
        </Link>

        <div class="card border-0 shadow mb-4">
            <div class="card-body">
                <h5 class="mb-3"><i :class="['fa', icon, 'me-2']"></i>{{ title }}</h5>
                <div class="row g-2 small">
                    <div v-for="item in items" :key="item.label" class="col-sm-6 col-lg-3">
                        <div class="text-muted">{{ item.label }}</div>
                        <div class="fw-bolder text-gray-800 info-value">{{ item.value || '-' }}</div>
                    </div>
                </div>
                <div v-if="$slots.default" class="small text-muted mt-3"><slot /></div>
            </div>
        </div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';

export default {
    components: { Link },
    props: {
        title:       { type: String, required: true },
        icon:        { type: String, default: 'fa-clipboard-check' },
        examSession: { type: Object, required: true },
        // Tampilkan baris "Ujian" (judul ujian PG/esai sesi ini)
        showExam:    { type: Boolean, default: true },
        // Info tambahan khas halaman: [{ label, value }]
        extra:       { type: Array, default: () => [] },
    },
    computed: {
        // Relasi dikirim Laravel dalam snake_case (exam_pg / exam_esai), bukan examPg.
        examPg()   { return this.examSession.exam_pg ?? null; },
        examEsai() { return this.examSession.exam_esai ?? null; },
        items() {
            const s = this.examSession;
            const sesi = s.kode_batch ? `${s.title} (Batch ${s.kode_batch})` : s.title;
            const skema = this.examPg?.classroom?.title ?? this.examEsai?.classroom?.title;
            const ujian = [this.examPg?.title, this.examEsai?.title].filter(Boolean).join(' · ');
            return [
                { label: 'Sesi', value: sesi },
                { label: 'Skema', value: skema },
                ...(this.showExam ? [{ label: 'Ujian', value: ujian }] : []),
                ...this.extra,
            ];
        },
    },
};
</script>

<style scoped>
/* Volt memasang nowrap di beberapa elemen; judul sesi/ujian panjang harus turun baris. */
.info-value {
    white-space: normal;
    overflow-wrap: anywhere;
}
</style>
