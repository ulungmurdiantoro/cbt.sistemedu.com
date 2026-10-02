<template>
    <Head><title>Penugasan Asesor — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <Link href="/admin/penilaian" class="btn btn-md btn-primary border-0 shadow">
                        <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                    </Link>
                    <div class="d-flex flex-wrap gap-2">
                        <Link v-if="exam_session.verifikasi_tuk" :href="`/admin/penilaian/${exam_session.id}/verifikasi-tuk`"
                            class="btn btn-md btn-outline-primary border shadow-sm">
                            <i class="fa fa-clipboard-check me-2"></i> Verifikasi TUK (FR.TUK.06)
                        </Link>
                        <Link :href="`/admin/penilaian/${exam_session.id}/dokumen`" class="btn btn-md btn-outline-primary border shadow-sm">
                            <i class="fa fa-signature me-2"></i> TTD AK.01 Asesor
                        </Link>
                    </div>
                </div>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-user-tie me-2"></i>Penugasan Asesor — {{ exam_session.title }}</h5>
                        <hr>
                        <table class="table table-bordered mb-0 table-wrap" style="max-width:500px">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" style="width:35%">Sesi</td>
                                    <td>{{ exam_session.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Ujian</td>
                                    <td>{{ exam_session.exam_pg?.title ?? exam_session.exam_esai?.title ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Skema</td>
                                    <td>{{ exam_session.exam_pg?.classroom?.title ?? exam_session.exam_esai?.classroom?.title ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="students.length === 0" class="alert alert-info">
                    Belum ada peserta yang terdaftar di sesi ini.
                </div>

                <div v-else class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0"><i class="fa fa-table me-2"></i>Penugasan Asesor per Peserta</h6>
                            <button @click="save" :disabled="saving" class="btn btn-success border-0 shadow">
                                <i class="fa fa-save me-1"></i>
                                {{ saving ? 'Menyimpan...' : 'Simpan Penugasan' }}
                            </button>
                        </div>

                        <div v-if="successMsg" class="alert alert-success alert-dismissible">
                            {{ successMsg }}
                            <button type="button" class="btn-close" @click="successMsg = ''"></button>
                        </div>

                        <p class="small text-muted mb-2">
                            <i class="fa fa-search me-1"></i>Klik kolom asesor lalu ketik namanya untuk mencari ({{ asesors.length }} asesor).
                            Angka di samping nama = jumlah peserta sesi ini yang sudah ditugaskan ke asesor itu.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width:5%">No</th>
                                        <th>No Peserta</th>
                                        <th>Nama Peserta</th>
                                        <th style="min-width:220px">Asesor yang Ditugaskan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in form" :key="row.student_id">
                                        <td>{{ i + 1 }}</td>
                                        <td>{{ students[i]?.no_participant }}</td>
                                        <td>{{ students[i]?.name }}</td>
                                        <td>
                                            <SearchSelect v-model="row.user_id" :options="asesorOptions"
                                                empty-label="— Belum ditugaskan —" search-placeholder="Ketik nama asesor..." />
                                            <a v-if="savedAssignMap[row.student_id]"
                                                :href="`/dokumen/laporan-asesmen/${exam_session.id}/${savedAssignMap[row.student_id]}/download`"
                                                target="_blank" class="d-inline-block small mt-1" title="Download FR.AK.05">
                                                <i class="fa fa-file-pdf me-1"></i>FR.AK.05
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SearchSelect from '../../../Components/SearchSelect.vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, SearchSelect },

    props: {
        exam_session: Object,
        students: Array,
        asesors: Array,
        assignments: Array,
    },

    data() {
        // Index assignments by student_id for quick lookup
        const assignMap = {};
        this.assignments.forEach(a => { assignMap[a.student_id] = a.user_id; });

        return {
            saving: false,
            successMsg: '',
            savedAssignMap: assignMap,
            form: this.students.map(s => ({
                student_id: s.id,
                user_id: assignMap[s.id] ?? null,
            })),
        };
    },

    computed: {
        // Pilihan asesor + jumlah peserta sesi ini yang (di form, termasuk yang belum disimpan) ditugaskan kepadanya.
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

    methods: {
        save() {
            this.saving = true;
            router.post(
                `/admin/penilaian/${this.exam_session.id}/penugasan`,
                { assignments: this.form },
                {
                    onSuccess: () => { this.successMsg = 'Penugasan berhasil disimpan.'; },
                    onFinish:  () => { this.saving = false; },
                }
            );
        },
    },
}
</script>
