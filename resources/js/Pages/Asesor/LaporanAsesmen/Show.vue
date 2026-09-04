<template>
    <Head><title>Laporan Asesmen — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link href="/asesor/dashboard" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-file-alt me-2"></i>FR.AK.05 — Laporan Asesmen</h5>
                        <hr>
                        <table class="table table-bordered mb-0 table-wrap" style="max-width:500px">
                            <tbody>
                                <tr>
                                    <td class="fw-bold" style="width:40%">Sesi</td>
                                    <td>{{ exam_session.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Skema</td>
                                    <td>{{ exam_session.examPg?.classroom?.title ?? exam_session.examEsai?.classroom?.title ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0"><i class="fa fa-table me-2"></i>Rekomendasi Asesi</h6>
                            <button v-if="rows.length" @click="saveRekomendasi" :disabled="savingRekomendasi"
                                class="btn btn-sm btn-success border-0 shadow">
                                <i class="fa fa-save me-1"></i>
                                {{ savingRekomendasi ? 'Menyimpan...' : 'Simpan Rekomendasi' }}
                            </button>
                        </div>
                        <div v-if="rows.length === 0" class="alert alert-info mb-0">
                            Tidak ada peserta yang ditugaskan di sesi ini.
                        </div>
                        <template v-else>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="text-center" style="width:5%">No.</th>
                                            <th>Nama Asesi</th>
                                            <th class="text-center" style="width:16%">Rekomendasi</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, i) in rows" :key="row.student_id">
                                            <td class="text-center">{{ i + 1 }}</td>
                                            <td>{{ row.name }}</td>
                                            <td class="text-center">
                                                <div v-if="row.is_finalized">
                                                    <span v-if="row.rekomendasi === 'K'" class="badge bg-success">Kompeten</span>
                                                    <span v-else-if="row.rekomendasi === 'BK'" class="badge bg-danger">Belum Kompeten</span>
                                                    <span v-else class="badge bg-secondary">Belum diisi</span>
                                                    <div class="small text-muted mt-1"><i class="fa fa-lock me-1"></i>Terkunci</div>
                                                </div>
                                                <div v-else-if="!row.application_id" class="text-muted small fst-italic">
                                                    Tidak ada aplikasi
                                                </div>
                                                <div v-else class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn"
                                                        :class="rekForm[row.student_id] === 'K' ? 'btn-success' : 'btn-outline-success'"
                                                        @click="rekForm[row.student_id] = 'K'">K</button>
                                                    <button type="button" class="btn"
                                                        :class="rekForm[row.student_id] === 'BK' ? 'btn-danger' : 'btn-outline-danger'"
                                                        @click="rekForm[row.student_id] = 'BK'">BK</button>
                                                </div>
                                            </td>
                                            <td class="small">{{ row.keterangan }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="small text-muted mt-2">
                                Rekomendasi K/BK terpisah dari Verifikasi Akhir dokumen — bisa diisi/diubah kapan saja
                                setelah nilai esai/wawancara selesai dinilai, sampai sesi ini difinalisasi Pengambil Keputusan.
                            </div>
                        </template>
                    </div>
                </div>

                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-pen me-2"></i>Catatan Laporan Asesmen</h6>
                        <div class="small text-muted mb-3">
                            Kolom di bawah sudah diisi teks contoh yang umum dipakai — silakan diedit sesuai kondisi sesi ini, atau langsung disimpan apa adanya.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal Pelaksanaan Asesmen</label>
                            <input type="date" v-model="form.tanggal_asesmen" class="form-control" style="max-width:220px">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Aspek Negatif dan Positif dalam Asesmen</label>
                            <textarea v-model="form.aspek_negatif_positif" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pencatatan Penolakan Hasil Asesmen</label>
                            <textarea v-model="form.pencatatan_penolakan" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Saran Perbaikan (Asesor/Personil Terkait)</label>
                            <textarea v-model="form.saran_perbaikan" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea v-model="form.catatan" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button @click="save" :disabled="saving" class="btn btn-success border-0 shadow">
                                <i class="fa fa-save me-1"></i>
                                {{ saving ? 'Menyimpan...' : 'Simpan Laporan' }}
                            </button>
                            <a v-if="report" :href="downloadUrl" target="_blank" class="btn btn-outline-dark border">
                                <i class="fa fa-file-pdf me-1"></i> Download PDF
                            </a>
                            <span v-else class="align-self-center small text-muted">
                                Simpan laporan terlebih dahulu untuk bisa mengunduh PDF.
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, Link, router } from '@inertiajs/vue3';

export default {
    layout: LayoutAsesor,
    components: { Head, Link },

    props: {
        exam_session: Object,
        rows:         Array,
        report:       Object,
    },

    data() {
        const rekForm = {};
        this.rows.forEach(r => { rekForm[r.student_id] = r.rekomendasi; });

        // Teks contoh diambil dari format resmi FR.AK.05 — dipakai sebagai isian awal
        // supaya asesor tidak mulai dari kosong, tinggal disesuaikan atau langsung disimpan.
        const defaultAspek = 'Peserta mampu mengerjakan semua ujian tulis dengan lancar, mempresentasikan penugasan sesuai ketentuan yang diinformasikan dan menjawab pertanyaan asesor sesuai dengan pemahaman dan pengalaman.';
        const defaultCatatan = 'Seluruh peserta dapat mengikuti semua rangkaian kegiatan dan mengerjakan ujian, sehingga direkomendasikan untuk mendapatkan sertifikat kegiatan sertifikasi.';

        return {
            saving: false,
            savingRekomendasi: false,
            successMsg: '',
            rekForm,
            form: {
                tanggal_asesmen:       this.report?.tanggal_asesmen ?? this.exam_session.start_time?.slice(0, 10) ?? '',
                aspek_negatif_positif: this.report?.aspek_negatif_positif ?? defaultAspek,
                pencatatan_penolakan:  this.report?.pencatatan_penolakan ?? '-',
                saran_perbaikan:       this.report?.saran_perbaikan ?? '-',
                catatan:               this.report?.catatan ?? defaultCatatan,
            },
        };
    },

    computed: {
        downloadUrl() {
            return `/dokumen/laporan-asesmen/${this.exam_session.id}/${this.$page.props.auth.user.id}/download`;
        },
    },

    methods: {
        save() {
            this.saving = true;
            router.post(
                `/asesor/penilaian/${this.exam_session.id}/laporan-asesmen`,
                this.form,
                {
                    preserveScroll: true,
                    onSuccess: () => { this.successMsg = 'Laporan Asesmen berhasil disimpan.'; },
                    onFinish:  () => { this.saving = false; },
                }
            );
        },

        saveRekomendasi() {
            this.savingRekomendasi = true;
            const rekomendasi = this.rows.map(r => ({
                student_id: r.student_id,
                value:      this.rekForm[r.student_id] ?? null,
            }));
            router.post(
                `/asesor/penilaian/${this.exam_session.id}/laporan-asesmen/rekomendasi`,
                { rekomendasi },
                {
                    preserveScroll: true,
                    onSuccess: () => { this.successMsg = 'Rekomendasi berhasil disimpan.'; },
                    onFinish:  () => { this.savingRekomendasi = false; },
                }
            );
        },
    },
}
</script>
