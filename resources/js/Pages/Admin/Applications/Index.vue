<template>
    <Head>
        <title>Permohonan Sertifikasi - Admin</title>
    </Head>

    <div class="container-fluid mb-5 mt-5">
    <!-- Dibuka dari tab sesi: daftar hanya berisi permohonan sesi ini -->
    <template v-if="exam_session">
        <BackBar href="/admin/exam_sessions" />
        <SessionNav :session="exam_session" active="permohonan" />
    </template>

    <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
        {{ $page.props.session.success }}
    </div>

    <ListToolbar v-model="filterForm.q" @search="applyFilter">
        <template #actions>
            <a :href="exportUrl" class="btn btn-success border-0 shadow">
                <i class="fa fa-file-excel me-1"></i> Export Excel
            </a>
            <button type="button" class="btn btn-info border-0 shadow" title="Perlu filter Skema atau dibuka dari tab sesi"
                :disabled="!canExportDokumen || exportingDokumen"
                @click="startExportDokumen">
                <span v-if="exportingDokumen">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Menyiapkan ZIP...
                </span>
                <span v-else>
                    <i class="fa fa-file-archive me-1"></i> Export Dokumen (ZIP)
                </span>
            </button>
        </template>
        <template #filters>
            <select class="form-select border-0 shadow w-auto" v-model="filterForm.status" @change="applyFilter">
                <option value="">Semua Status</option>
                <option value="draft">Draft</option>
                <option value="submitted">Disubmit</option>
                <option value="approved">Disetujui</option>
                <option value="rejected">Ditolak</option>
            </select>
            <select class="form-select border-0 shadow w-auto filter-skema" v-model="filterForm.classroom_id" @change="applyFilter">
                <option value="">Semua Skema</option>
                <option v-for="c in classrooms" :key="c.id" :value="String(c.id)">{{ c.title }}</option>
            </select>
            <input type="text" class="form-control border-0 shadow filter-batch" v-model="filterForm.kode_batch"
                placeholder="Batch" @keyup.enter="applyFilter">
            <button v-if="filterForm.q || filterForm.status || filterForm.classroom_id || filterForm.kode_batch"
                type="button" class="btn btn-gray-100 border" @click="resetFilter">Reset</button>
        </template>
    </ListToolbar>

    <div class="card border-0 shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-centered table-hover table-list mb-0 rounded" style="min-width:700px">
                    <thead class="thead-dark">
                        <tr class="border-0">
                            <th class="border-0 rounded-start text-center" style="width:5%">No.</th>
                            <th class="border-0" style="width:200px">Peserta</th>
                            <th class="border-0">Skema / Sesi</th>
                            <th class="border-0" style="width:110px">Tgl Submit</th>
                            <th class="border-0" style="width:110px">Status</th>
                            <th style="width:70px" class="border-0 rounded-end text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="applications.data.length === 0">
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fa fa-inbox fa-2x d-block mb-2 text-gray-300"></i>
                                <strong class="d-block">Tidak ada permohonan</strong>
                                <span class="small">Belum ada permohonan sertifikasi yang cocok dengan filter Anda.</span>
                            </td>
                        </tr>
                        <tr v-for="(app, i) in applications.data" :key="app.id">
                            <td class="fw-bold text-center">{{ i + 1 + (applications.current_page - 1) * applications.per_page }}</td>
                            <td class="cell-wrap">
                                <div class="fw-bolder small">{{ app.participant?.name }}</div>
                                <div class="text-muted" style="font-size:0.78rem">{{ app.participant?.email }}</div>
                            </td>
                            <td>
                                <div class="small fw-bolder text-truncate" style="max-width:340px" :title="app.classroom?.title">
                                    {{ app.classroom?.title ?? '—' }}
                                </div>
                                <div class="text-muted text-truncate" style="font-size:0.78rem; max-width:340px" :title="app.exam_session?.title">
                                    <i class="fa fa-calendar-alt me-1"></i>{{ app.exam_session?.title ?? '—' }}
                                </div>
                                <div class="text-muted" style="font-size:0.75rem">
                                    <i class="fa fa-hashtag me-1"></i>Batch: {{ app.exam_session?.kode_batch ?? app.kode_batch ?? '—' }}
                                </div>
                            </td>
                            <td class="small">{{ app.submitted_at ? formatDate(app.submitted_at) : '—' }}</td>
                            <td>
                                <StatusBadge :tone="statusTone(app.status)" :label="statusLabel(app.status)" />
                                <div v-if="app.rejected_documents_count > 0" class="mt-1">
                                    <span class="badge bg-danger" style="font-size:0.68rem" title="Ada dokumen yang ditolak, permohonan belum ada keputusan akhir">
                                        <i class="fa fa-exclamation-triangle me-1"></i>{{ app.rejected_documents_count }} dokumen ditolak
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <Link :href="`/admin/applications/${app.id}` + (exam_session ? `?sesi=${exam_session.id}` : '')" class="btn btn-sm btn-info">
                                        <i class="fa fa-eye"></i>
                                    </Link>
                                    <button v-if="app.status !== 'approved'" class="btn btn-sm btn-danger"
                                        :disabled="deletingId === app.id" title="Hapus permohonan"
                                        @click="destroyApplication(app)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="applications.links" align="end" :total="applications.total" :from="applications.from" :to="applications.to" entity="permohonan" />
        </div>
    </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import Pagination from '../../../Components/Pagination.vue';
import SessionNav from '../../../Components/SessionNav.vue';
import ListToolbar from '../../../Components/Admin/ListToolbar.vue';
import BackBar from '../../../Components/Admin/BackBar.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, computed, ref, onBeforeUnmount } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, StatusBadge, Pagination, SessionNav, ListToolbar, BackBar },
    props: {
        applications: Object,
        filters:      Object,
        classrooms:   Array,
        exam_session: Object,
    },

    setup(props) {
        const filterForm = reactive({
            q:            props.filters?.q ?? '',
            status:       props.filters?.status ?? '',
            classroom_id: props.filters?.classroom_id ?? '',
            kode_batch:   props.filters?.kode_batch ?? '',
            // Dari tab sesi; tidak ada isian di form, hanya ikut terbawa saat filter/export
            exam_session_id: props.filters?.exam_session_id ?? '',
        });

        const canExportDokumen = computed(() => !!(filterForm.classroom_id || filterForm.exam_session_id));

        const applyFilter = () => {
            router.get('/admin/applications', filterForm, { preserveState: true });
        };

        // Reset tidak keluar dari sesi; untuk itu ada tautan "Lihat semua permohonan"
        const resetFilter = () => {
            filterForm.q = ''; filterForm.status = ''; filterForm.classroom_id = ''; filterForm.kode_batch = '';
            router.get('/admin/applications', filterForm.exam_session_id ? { exam_session_id: filterForm.exam_session_id } : {});
        };

        const deletingId = ref(null);
        const destroyApplication = (app) => {
            const label = { draft:'Draft', submitted:'Disubmit', approved:'Disetujui', rejected:'Ditolak' }[app.status] ?? app.status;
            if (!confirm(`Hapus permohonan "${app.participant?.name ?? '-'}" (${label})?\n\nDokumen yang diunggah ikut terhapus permanen dan tidak bisa dikembalikan.`)) return;
            deletingId.value = app.id;
            router.delete(`/admin/applications/${app.id}`, {
                preserveScroll: true,
                onError: (e) => alert(e.delete ?? 'Gagal menghapus permohonan.'),
                onFinish: () => { deletingId.value = null; },
            });
        };

        const exportUrl = computed(() => {
            const params = new URLSearchParams();
            Object.entries(filterForm).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });
            const qs = params.toString();
            return '/admin/applications/export' + (qs ? `?${qs}` : '');
        });

        // ZIP dibangun sinkron di server (mPDF per peserta) jadi bisa lama untuk
        // banyak peserta. Karena unduhannya lewat navigasi biasa (bukan XHR), tidak
        // ada event "selesai" yang bisa didengar JS — jadi kita titip token lewat
        // cookie: server men-set cookie ini di respons SETELAH ZIP selesai dibuat,
        // dan di sini kita polling document.cookie untuk tahu kapan berhenti loading.
        const exportingDokumen = ref(false);
        let exportPollTimer   = null;
        let exportSafetyTimer = null;

        const clearExportTimers = () => {
            clearInterval(exportPollTimer);
            clearTimeout(exportSafetyTimer);
            exportPollTimer = null;
            exportSafetyTimer = null;
        };

        const startExportDokumen = () => {
            if (!canExportDokumen.value || exportingDokumen.value) return;

            const token = 'dl' + Date.now() + Math.random().toString(36).slice(2);
            const params = new URLSearchParams();
            Object.entries(filterForm).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });
            params.append('download_token', token);

            exportingDokumen.value = true;
            window.location.href = '/admin/applications/export-dokumen?' + params.toString();

            exportPollTimer = setInterval(() => {
                if (document.cookie.includes('fileDownloadToken=' + token)) {
                    document.cookie = 'fileDownloadToken=; Max-Age=0; path=/';
                    exportingDokumen.value = false;
                    clearExportTimers();
                }
            }, 500);

            // Jaga-jaga kalau cookie gagal terdeteksi (mis. koneksi terputus) —
            // jangan biarkan tombol terkunci loading selamanya.
            exportSafetyTimer = setTimeout(() => {
                exportingDokumen.value = false;
                clearExportTimers();
            }, 10 * 60 * 1000);
        };

        onBeforeUnmount(clearExportTimers);

        const formatDate = (dt) => new Date(dt).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });

        const statusLabel = (s) => ({ draft:'Draft', submitted:'Disubmit', approved:'Disetujui', rejected:'Ditolak' }[s] ?? s);
        // tone badge senada dengan Blueprint §4
        const statusTone = (s) => ({ draft:'neutral', submitted:'secondary', approved:'success', rejected:'danger' }[s] ?? 'neutral');

        return {
            filterForm, applyFilter, resetFilter, exportUrl,
            exportingDokumen, startExportDokumen, canExportDokumen,
            deletingId, destroyApplication,
            formatDate, statusLabel, statusTone,
        };
    },
}
</script>

<style scoped>
.filter-skema { max-width: 240px; }
.filter-batch { width: 110px; }
</style>
