<template>
    <!-- Informasi ujian untuk peserta — sama di halaman konfirmasi PG/Esai dan halaman Ujian Praktik/Keterampilan / Upload Tugas -->
    <table class="table table-bordered mb-3 exam-info">
        <tbody>
            <tr v-for="row in visibleRows" :key="row.label">
                <td class="fw-bold">{{ row.label }}</td>
                <td class="text-wrap">{{ row.value }}</td>
            </tr>
        </tbody>
    </table>
</template>

<script>
    export default {
        props: {
            // [{ label, value }] — baris dengan value kosong tidak ditampilkan
            rows: { type: Array, required: true },
        },

        computed: {
            visibleRows() {
                return this.rows.filter((r) => r.value !== null && r.value !== undefined && r.value !== '');
            },
        },
    }
</script>

<style>
    .exam-info td:first-child { width: 35%; }
</style>
