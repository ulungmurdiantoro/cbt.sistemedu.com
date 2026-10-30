<template>
    <Head>
        <title>Log Materai - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <MateraiSaldo class="mb-3" />

        <div class="row g-3 mb-4">
            <div v-for="c in cards" :key="c.label" class="col-6 col-md-3">
                <div class="card border-0 shadow h-100">
                    <div class="card-body d-flex align-items-center">
                        <i :class="['fa', c.icon, 'fa-lg me-3', c.color]"></i>
                        <div>
                            <div class="h4 mb-0 num">{{ c.value }}</div>
                            <div class="small text-muted">{{ c.label }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3 g-2">
            <div class="col-md-5 d-flex gap-2">
                <select v-model="dokumen" class="form-select border-0 shadow" @change="apply">
                    <option value="">Semua dokumen</option>
                    <option value="FR.AK.14">FR.AK.14</option>
                    <option value="FR.AK.01">FR.AK.01</option>
                </select>
                <select v-model="status" class="form-select border-0 shadow" @change="apply">
                    <option value="">Semua status</option>
                    <option value="stamped">Berhasil</option>
                    <option value="failed">Gagal</option>
                    <option value="proses">Diproses</option>
                </select>
            </div>
            <div class="col-md-4 offset-md-3">
                <form @submit.prevent="apply">
                    <div class="input-group">
                        <input type="text" class="form-control border-0 shadow" v-model="search"
                            placeholder="masukkan kata kunci...">
                        <button type="submit" class="btn btn-gray-800 border-0 shadow">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-centered mb-0 rounded">
                        <thead class="thead-dark">
                            <tr class="border-0">
                                <th class="border-0 rounded-start" style="width:4%">No.</th>
                                <th class="border-0" style="width:14%">Waktu</th>
                                <th class="border-0" style="width:9%">Dokumen</th>
                                <th class="border-0">Peserta</th>
                                <th class="border-0">Sesi</th>
                                <th class="border-0" style="width:9%">Status</th>
                                <th class="border-0">Keterangan</th>
                                <th class="border-0 rounded-end" style="width:6%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(r, index) in rows.data" :key="r.dokumen + r.ref_id">
                                <td class="fw-bold text-center">
                                    {{ index + 1 + (rows.current_page - 1) * rows.per_page }}
                                </td>
                                <td class="small num">{{ fmtTime(r.waktu) }}</td>
                                <td class="small fw-bolder">{{ r.dokumen }}</td>
                                <td>
                                    <span class="fw-bolder">{{ r.nama || '—' }}</span>
                                    <div v-if="r.no_peserta" class="small text-muted">{{ r.no_peserta }}</div>
                                </td>
                                <td class="small">
                                    <Link v-if="r.sesi_id" :href="`/admin/exam_sessions/${r.sesi_id}`">{{ r.sesi || `Sesi ${r.sesi_id}` }}</Link>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-center">
                                    <StatusBadge :tone="statusTone(r.status)" :label="statusLabel(r.status)" />
                                </td>
                                <td class="small text-danger" style="max-width:280px; white-space:normal;">
                                    {{ r.status === 'failed' ? r.alasan : '' }}
                                </td>
                                <td class="text-center">
                                    <a :href="docUrl(r)" target="_blank" class="btn btn-sm btn-info border-0 shadow" title="Lihat dokumen">
                                        <i class="fa fa-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="rows.data.length === 0">
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fa fa-stamp fa-2x d-block mb-2 text-gray-300"></i>
                                    <strong class="d-block">Belum ada pembubuhan materai</strong>
                                    <span class="small">Riwayat akan muncul setelah FR.AK.14 / FR.AK.01 dibubuhi e-meterai.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="rows.links" align="end" :total="rows.total" :from="rows.from" :to="rows.to" entity="pembubuhan" />
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import MateraiSaldo from '../../../Components/MateraiSaldo.vue';
import { Head, Link, router } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination, StatusBadge, MateraiSaldo },
    props: {
        rows:    Object,
        summary: Object,
        filters: Object,
    },

    data() {
        return {
            search:  this.filters?.q ?? '',
            dokumen: this.filters?.dokumen ?? '',
            status:  this.filters?.status ?? '',
        };
    },

    computed: {
        cards() {
            return [
                { label: 'Berhasil hari ini', value: this.summary.today,   icon: 'fa-calendar-check', color: 'text-success' },
                { label: 'Total berhasil',    value: this.summary.stamped, icon: 'fa-stamp',          color: 'text-primary' },
                { label: 'Sedang diproses',   value: this.summary.proses,  icon: 'fa-hourglass-half', color: 'text-warning' },
                { label: 'Gagal',             value: this.summary.failed,  icon: 'fa-exclamation-triangle', color: 'text-danger' },
            ];
        },
    },

    methods: {
        apply() {
            router.get('/admin/materai', { q: this.search, dokumen: this.dokumen, status: this.status }, { preserveState: true });
        },
        statusLabel(s) {
            return { stamped: 'Berhasil', failed: 'Gagal' }[s] ?? 'Diproses';
        },
        statusTone(s) {
            return { stamped: 'success', failed: 'danger' }[s] ?? 'secondary';
        },
        fmtTime(t) {
            if (!t) return '—';
            return new Date(t.replace(' ', 'T')).toLocaleString('id-ID', {
                day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
            });
        },
        docUrl(r) {
            if (r.dokumen === 'FR.AK.14') return `/admin/results/${r.sesi_id}/fr-ak-14/${r.student_id}`;
            return r.status === 'stamped'
                ? `/admin/applications/${r.ref_id}/materai/download`
                : `/admin/applications/${r.ref_id}`;
        },
    },
}
</script>
