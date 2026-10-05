<template>
    <!--
      Tabel daftar sesi ujian (aktif di atas, lalu pemisah "Sesi Selesai").
      Dipakai menu Sesi Ujian; kolom Aksi diisi lewat slot #actions.
      Server sudah mengurutkan: sesi aktif dulu, lalu yang selesai (terbaru dulu).
    -->
    <div class="table-responsive">
        <table class="table table-bordered table-centered mb-0 rounded session-table">
            <thead class="thead-dark">
                <tr class="border-0">
                    <th class="border-0 rounded-start" style="width:4%">No.</th>
                    <th class="border-0">Sesi Ujian</th>
                    <th class="border-0">Jenis Ujian</th>
                    <th class="border-0 text-center" style="width:1%">Peserta</th>
                    <th class="border-0">Waktu</th>
                    <th class="border-0 rounded-end text-center" :style="{ width: actionsWidth }">Aksi</th>
                </tr>
            </thead>
            <div class="mt-2"></div>
            <tbody>
                <template v-for="(s, index) in sessions" :key="s.id">
                    <tr v-if="isFirstFinished(index)" class="separator-row">
                        <td colspan="6" class="py-1 px-3 text-muted small fw-bolder border-0" style="background:#f8f9fa;border-top:2px dashed #dee2e6 !important;">
                            <i class="fa fa-check-circle me-1 text-secondary"></i> Sesi Selesai
                        </td>
                    </tr>

                    <tr :class="isActive(s) ? 'table-active-session' : 'table-finished-session'">
                        <td class="fw-bold text-center">{{ index + 1 + offset }}</td>
                        <!-- Volt memberi nowrap ke semua sel; judul panjang dibungkus supaya tabel tidak perlu digeser -->
                        <td style="min-width:220px">
                            <div class="d-flex align-items-start gap-2">
                                <StatusBadge v-if="isActive(s)" tone="accent" label="Aktif" class="mt-1 flex-shrink-0" />
                                <StatusBadge v-else tone="success" label="Selesai" class="mt-1 flex-shrink-0" />
                                <div class="text-wrap">
                                    <strong>{{ s.title }}</strong>
                                    <div class="text-muted small">Batch {{ s.kode_batch }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="max-width:220px">
                            <ul class="list-unstyled mb-0 small">
                                <li v-if="s.exam_pg" class="text-truncate" :title="s.exam_pg.title">
                                    <span class="badge bg-primary me-1">PG</span>{{ s.exam_pg.title }}
                                </li>
                                <li v-if="s.exam_esai" class="text-truncate" :title="s.exam_esai.title">
                                    <span class="badge bg-success me-1">Esai</span>{{ s.exam_esai.title }}
                                </li>
                                <li v-if="s.has_wawancara">
                                    <span class="badge bg-warning text-gray-800 me-1">Wawancara</span>
                                </li>
                                <li v-if="!s.exam_pg && !s.exam_esai && !s.has_wawancara" class="text-muted">—</li>
                            </ul>
                        </td>
                        <td class="text-center">{{ s[countKey] ?? 0 }}</td>
                        <td class="small text-nowrap">
                            <div><span class="text-muted">Mulai:</span> {{ formatDate(s.start_time) }}</div>
                            <div><span class="text-muted">Selesai:</span> {{ formatDate(s.end_time) }}</div>
                        </td>
                        <td class="text-center text-nowrap">
                            <slot name="actions" :session="s" :active="isActive(s)" />
                        </td>
                    </tr>
                </template>

                <tr v-if="sessions.length === 0">
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="fa fa-stopwatch fa-2x d-block mb-2 text-gray-300"></i>
                        <strong class="d-block">Belum ada sesi ujian</strong>
                        <span class="small">{{ emptyText }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
import StatusBadge from './StatusBadge.vue';

export default {
    name: 'SessionTable',
    components: { StatusBadge },
    props: {
        sessions:     { type: Array, required: true },
        // Nomor urut awal (untuk data berhalaman)
        offset:       { type: Number, default: 0 },
        // Nama kolom jumlah peserta dari server
        countKey:     { type: String, default: 'students_count' },
        emptyText:    { type: String, default: 'Tidak ada sesi yang cocok dengan pencarian Anda.' },
        // 1% = selebar isi (tombol), sisa ruang untuk judul sesi
        actionsWidth: { type: String, default: '1%' },
    },
    data() {
        return { now: new Date() };
    },
    methods: {
        isActive(s) {
            return new Date(s.end_time) > this.now;
        },
        // Pemisah ditampilkan di baris pertama yang sudah selesai
        isFirstFinished(index) {
            if (index === 0) return false;
            return !this.isActive(this.sessions[index]) && this.isActive(this.sessions[index - 1]);
        },
        formatDate(dt) {
            return dt
                ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
                : '—';
        },
    },
}
</script>

<style scoped>
/* Padding sel Volt 24px kiri-kanan → dipersempit supaya tabel muat di layar laptop tanpa digeser */
.session-table th,
.session-table td {
    padding-left: .75rem;
    padding-right: .75rem;
}
.table-active-session td {
    background-color: #fff;
}
.table-finished-session td {
    background-color: #f8f9fa;
    color: #6c757d;
    opacity: 0.85;
}
.table-finished-session .badge {
    opacity: 0.8;
}
</style>
