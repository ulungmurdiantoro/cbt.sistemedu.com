<template>
    <Head>
        <title>{{ exam_session.title }} - Sesi Ujian</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <Link href="/admin/exam_sessions" class="btn btn-md btn-primary border-0 shadow" type="button">
                        <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                    </Link>
                    <Link :href="`/admin/exam_sessions/${exam_session.id}/edit`" class="btn btn-md btn-info border-0 shadow">
                        <i class="fa fa-pencil-alt me-2"></i> Edit Sesi
                    </Link>
                </div>

                <SessionNav :session="exam_session" active="peserta" />

                <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
                    {{ $page.props.session.success }}
                </div>
                <div v-if="Object.keys(errors ?? {}).length" class="alert alert-danger border-0 shadow mb-3">
                    Penugasan gagal disimpan. Muat ulang halaman lalu coba lagi.
                </div>

                <!-- Detail sesi -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="fa fa-stopwatch me-2"></i>Detail Sesi</h6>
                        <div class="row g-3 small">
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Skema</div>
                                <div class="fw-bolder">{{ skema ?? '—' }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Ujian Pilihan Ganda</div>
                                <div class="fw-bolder">{{ exam_session.exam_pg?.title ?? '—' }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Ujian Esai</div>
                                <div class="fw-bolder">{{ exam_session.exam_esai?.title ?? '—' }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Wawancara · Verifikasi TUK</div>
                                <div>
                                    <StatusBadge :tone="exam_session.has_wawancara ? 'accent' : 'neutral'" :label="exam_session.has_wawancara ? 'Wawancara' : 'Tanpa wawancara'" />
                                    <StatusBadge v-if="exam_session.verifikasi_tuk" tone="accent" label="FR.TUK.06" class="ms-1" />
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Mulai</div>
                                <div class="fw-bolder">{{ formatDate(exam_session.start_time) }}</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Selesai</div>
                                <div class="fw-bolder">{{ formatDate(exam_session.end_time) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Peserta & penugasan asesor -->
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <h6 class="fw-bold mb-0"><i class="fa fa-users me-2"></i>Peserta &amp; Asesor ({{ students.length }})</h6>
                            <div class="d-flex gap-2">
                                <Link :href="`/admin/exam_sessions/${exam_session.id}/enrolle/create`" class="btn btn-md btn-primary border-0 shadow">
                                    <i class="fa fa-user-plus me-1"></i> Enroll Peserta
                                </Link>
                                <button v-if="students.length" @click="save" :disabled="saving" class="btn btn-md btn-success border-0 shadow">
                                    <i class="fa fa-save me-1"></i> {{ saving ? 'Menyimpan...' : 'Simpan Penugasan' }}
                                </button>
                            </div>
                        </div>

                        <p v-if="students.length" class="small text-muted mb-2">
                            <i class="fa fa-search me-1"></i>Klik kolom asesor lalu ketik namanya untuk mencari ({{ asesors.length }} asesor).
                            Angka di samping nama = jumlah peserta sesi ini yang sudah ditugaskan ke asesor itu.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-bordered table-centered mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start" style="width:4%">No.</th>
                                        <th class="border-0">No Peserta</th>
                                        <th class="border-0">Nama</th>
                                        <th class="border-0">Permohonan</th>
                                        <th class="border-0" style="min-width:240px">Asesor</th>
                                        <th class="border-0 rounded-end text-center" style="width:6%">Aksi</th>
                                    </tr>
                                </thead>
                                <div class="mt-2"></div>
                                <tbody>
                                    <tr v-for="(row, i) in form" :key="row.student_id">
                                        <td class="fw-bold text-center">{{ i + 1 }}</td>
                                        <td class="text-nowrap">{{ students[i].no_participant }}</td>
                                        <td>{{ students[i].name }}</td>
                                        <td>
                                            <Link v-if="students[i].application_id"
                                                :href="`/admin/applications/${students[i].application_id}?sesi=${exam_session.id}`"
                                                title="Buka permohonan">
                                                <StatusBadge :tone="statusTone(students[i].application_status)" :label="statusLabel(students[i].application_status)" />
                                            </Link>
                                            <span v-else class="small text-muted" title="Peserta di-enroll manual, tanpa permohonan di sesi ini">— enroll manual</span>
                                        </td>
                                        <td>
                                            <SearchSelect v-model="row.user_id" :options="asesorOptions"
                                                empty-label="— Belum ditugaskan —" search-placeholder="Ketik nama asesor..." />
                                            <a v-if="students[i].asesor_id"
                                                :href="`/dokumen/laporan-asesmen/${exam_session.id}/${students[i].asesor_id}/download`"
                                                target="_blank" class="d-inline-block small mt-1" title="Download FR.AK.05">
                                                <i class="fa fa-file-pdf me-1"></i>FR.AK.05
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <button @click.prevent="destroy(students[i])" class="btn btn-sm btn-danger border-0" title="Keluarkan dari sesi">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="students.length === 0">
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="fa fa-users fa-2x d-block mb-2 text-gray-300"></i>
                                            <strong class="d-block">Belum ada peserta di sesi ini</strong>
                                            <span class="small">Peserta masuk otomatis saat permohonannya disetujui, atau lewat tombol Enroll Peserta.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p v-if="inactive_count" class="small text-muted mt-2 mb-0">
                            <i class="fa fa-info-circle me-1"></i>{{ inactive_count }} akun nonaktif (akun lama hasil re-issue / gabung duplikat) tidak ditampilkan.
                        </p>

                        <!-- Muncul selama ada pilihan asesor yang belum disimpan -->
                        <div v-if="dirty" class="position-sticky d-flex flex-wrap justify-content-between align-items-center gap-2 bg-gray-800 text-white rounded shadow px-3 py-2 mt-3"
                            style="bottom:0;z-index:1020">
                            <span class="small"><i class="fa fa-exclamation-circle me-1"></i>Ada {{ changedCount }} perubahan penugasan yang belum disimpan.</span>
                            <button @click="save" :disabled="saving" class="btn btn-sm btn-success border-0">
                                <i class="fa fa-save me-1"></i> {{ saving ? 'Menyimpan...' : 'Simpan Penugasan' }}
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import SessionNav from '../../../Components/SessionNav.vue';
import SearchSelect from '../../../Components/SearchSelect.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const UNSAVED_MESSAGE = 'Perubahan penugasan asesor belum disimpan. Tinggalkan halaman ini?';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, StatusBadge, SessionNav, SearchSelect },
    props: {
        errors:         Object,
        exam_session:   Object,
        // [{ id, no_participant, name, application_id, application_status, asesor_id }]
        students:       Array,
        inactive_count: Number,
        asesors:        Array,
    },

    data() {
        return {
            saving: false,
            form:   this.buildForm(),
        };
    },

    computed: {
        skema() {
            return this.exam_session.exam_pg?.classroom?.title ?? this.exam_session.exam_esai?.classroom?.title;
        },
        changedCount() {
            return this.form.filter((row, i) => row.user_id !== (this.students[i]?.asesor_id ?? null)).length;
        },
        dirty() {
            return this.changedCount > 0;
        },
        // Pilihan asesor + jumlah peserta sesi ini (di form, termasuk yang belum disimpan) yang ditugaskan kepadanya.
        asesorOptions() {
            const load = {};
            this.form.forEach(r => { if (r.user_id) load[r.user_id] = (load[r.user_id] ?? 0) + 1; });

            return this.asesors.map(a => ({
                value: a.id,
                label: a.name,
                hint:  load[a.id] ? `${load[a.id]} peserta` : '',
            }));
        },
    },

    watch: {
        students() {
            this.form = this.buildForm();
        },
    },

    mounted() {
        // Pindah tab / halaman lain saat masih ada pilihan asesor yang belum disimpan → tanya dulu.
        this.removeBeforeVisit = router.on('before', (event) => {
            if (this.dirty && event.detail.visit.method === 'get' && !window.confirm(UNSAVED_MESSAGE)) {
                event.preventDefault();
            }
        });
        window.addEventListener('beforeunload', this.onBeforeUnload);
    },

    beforeUnmount() {
        this.removeBeforeVisit?.();
        window.removeEventListener('beforeunload', this.onBeforeUnload);
    },

    methods: {
        buildForm() {
            return this.students.map(s => ({ student_id: s.id, user_id: s.asesor_id ?? null }));
        },

        onBeforeUnload(event) {
            if (this.dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        },

        save() {
            this.saving = true;
            router.post(
                `/admin/penilaian/${this.exam_session.id}/penugasan`,
                { assignments: this.form },
                {
                    preserveScroll: true,
                    onFinish: () => { this.saving = false; },
                }
            );
        },

        destroy(student) {
            Swal.fire({
                title: 'Keluarkan dari sesi?',
                html: `<b>${this.escape(student.name)}</b> akan dihapus dari semua ujian di sesi ini.`
                    + (this.dirty ? '<br><br>Perubahan penugasan asesor yang belum disimpan akan hilang.' : ''),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, keluarkan',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (!result.isConfirmed) return;

                router.delete(`/admin/exam_sessions/${this.exam_session.id}/enrolle/${student.id}/destroy`, {
                    preserveScroll: true,
                    onSuccess: () => Swal.fire({ title: 'Dihapus', text: 'Peserta dikeluarkan dari sesi.', icon: 'success', timer: 2000, showConfirmButton: false }),
                });
            });
        },

        escape(text) {
            const el = document.createElement('div');
            el.textContent = text ?? '';
            return el.innerHTML;
        },

        formatDate(dt) {
            return dt
                ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
                : '—';
        },

        statusLabel(s) {
            return { draft: 'Draft', submitted: 'Disubmit', approved: 'Disetujui', rejected: 'Ditolak' }[s] ?? s;
        },

        // tone badge senada dengan daftar Permohonan
        statusTone(s) {
            return { draft: 'neutral', submitted: 'secondary', approved: 'success', rejected: 'danger' }[s] ?? 'neutral';
        },
    },
}
</script>
