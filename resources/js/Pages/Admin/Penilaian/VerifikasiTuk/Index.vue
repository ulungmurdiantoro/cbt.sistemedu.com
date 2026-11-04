<template>
    <Head><title>Verifikasi TUK — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="col-12">

            <BackBar href="/admin/exam_sessions" />

            <SessionNav :session="exam_session" active="tuk" />

            <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
                {{ $page.props.session.success }}
            </div>

            <div class="alert alert-info border-0 shadow-sm small mb-3">
                <i class="fa fa-info-circle me-1"></i>
                Isi checklist tiap peserta sebagai <strong>Pengawas Ujian</strong>: bagian A–E &amp; G sebelum ujian dimulai,
                bagian F &amp; H selama/setelah ujian, lalu kesimpulan akhir. Checklist ini hanya pencatatan —
                peserta tetap bisa memulai ujian.
            </div>

            <!-- Summary -->
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow text-center py-2">
                        <div class="fs-4 fw-bold text-primary">{{ count(r => r.kesimpulan_awal) }}</div>
                        <div class="small text-muted">Sudah Verifikasi Awal</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow text-center py-2">
                        <div class="fs-4 fw-bold text-secondary">{{ count(r => !r.kesimpulan_awal) }}</div>
                        <div class="small text-muted">Belum Verifikasi Awal</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow text-center py-2">
                        <div class="fs-4 fw-bold text-success">{{ count(r => r.kesimpulan_akhir) }}</div>
                        <div class="small text-muted">Sudah Kesimpulan Akhir</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow text-center py-2">
                        <div class="fs-4 fw-bold text-danger">{{ count(r => r.kesimpulan_awal === 'tidak_layak' || r.kesimpulan_akhir === 'tidak_layak') }}</div>
                        <div class="small text-muted">Tidak Layak</div>
                    </div>
                </div>
            </div>

            <!-- Tabel peserta -->
            <SectionCard title="Daftar Peserta" icon="fa-users">
                <template #meta>{{ rows.length }} peserta</template>
                    <div class="table-responsive">
                        <table class="table table-bordered table-centered table-hover table-list mb-0 rounded">
                            <thead class="thead-dark">
                                <tr>
                                    <th class="text-center" style="width:5%">No.</th>
                                    <th style="width:12%">No. Peserta</th>
                                    <th>Nama</th>
                                    <th class="text-center" style="width:18%">Verifikasi Awal</th>
                                    <th class="text-center" style="width:14%">Kesimpulan Akhir</th>
                                    <th style="width:16%">Pengawas</th>
                                    <th class="text-center" style="width:12%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in rows" :key="row.student_id">
                                    <td>{{ i + 1 }}</td>
                                    <td class="fw-bold">{{ row.no_participant }}</td>
                                    <td>{{ row.name }}</td>
                                    <td class="text-center">
                                        <StatusBadge v-if="row.kesimpulan_awal" :tone="tone(row.kesimpulan_awal)" :title="formatDateTime(row.verified_at)">
                                            {{ awalLabel[row.kesimpulan_awal] }}
                                        </StatusBadge>
                                        <StatusBadge v-else-if="row.has_record" tone="secondary">Sedang Diisi</StatusBadge>
                                        <StatusBadge v-else tone="neutral">Belum</StatusBadge>
                                    </td>
                                    <td class="text-center">
                                        <StatusBadge v-if="row.kesimpulan_akhir" :tone="tone(row.kesimpulan_akhir)">
                                            {{ akhirLabel[row.kesimpulan_akhir] }}
                                        </StatusBadge>
                                        <span v-else class="text-muted small">—</span>
                                    </td>
                                    <td class="small">
                                        <span v-if="row.pengawas_name">{{ row.pengawas_name }}</span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <Link :href="`/admin/penilaian/${exam_session.id}/verifikasi-tuk/${row.student_id}`"
                                            class="btn btn-sm border-0"
                                            :class="row.kesimpulan_akhir ? 'btn-outline-secondary' : 'btn-primary'">
                                            <i class="fa fa-clipboard-check me-1"></i>{{ row.has_record ? (row.kesimpulan_akhir ? 'Lihat' : 'Lanjutkan') : 'Isi' }}
                                        </Link>
                                        <a v-if="row.has_record" :href="`/admin/penilaian/${exam_session.id}/verifikasi-tuk/${row.student_id}/pdf`"
                                            target="_blank" class="btn btn-sm btn-outline-danger border-0 ms-1" title="Unduh FR.TUK.06">
                                            <i class="fa fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                                <tr v-if="rows.length === 0">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fa fa-users fa-2x text-gray-300 d-block mb-2"></i>
                                        <strong>Belum ada peserta.</strong> Peserta muncul di sini setelah terdaftar di sesi ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
            </SectionCard>

        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../../Layouts/Admin.vue';
import StatusBadge from '../../../../Components/StatusBadge.vue';
import SessionNav from '../../../../Components/SessionNav.vue';
import BackBar from '../../../../Components/Admin/BackBar.vue';
import SectionCard from '../../../../Components/SectionCard.vue';
import { Head, Link } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, StatusBadge, SessionNav, BackBar, SectionCard },
    props: {
        exam_session: Object,
        rows: Array,
    },

    data() {
        return {
            awalLabel:  { layak: 'Layak', layak_perbaikan: 'Layak dgn Perbaikan', tidak_layak: 'Tidak Layak' },
            akhirLabel: { layak: 'Layak', tidak_layak: 'Tidak Layak' },
        };
    },

    methods: {
        count(predicate) {
            return this.rows.filter(predicate).length;
        },
        tone(kesimpulan) {
            return { layak: 'success', layak_perbaikan: 'secondary', tidak_layak: 'danger' }[kesimpulan] ?? 'neutral';
        },
        formatDateTime(value) {
            if (!value) return '';
            return new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
        },
    },
}
</script>
