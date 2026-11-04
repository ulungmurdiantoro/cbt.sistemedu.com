<template>
    <Head><title>Laporan Asesmen — {{ exam_session.title }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <SessionHeader title="FR.AK.05 — Laporan Asesmen" icon="fa-file-alt" :exam-session="exam_session" :show-exam="false" />

                <PageGuide storage-key="asesor.laporan"><GuideLaporan /></PageGuide>

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>

                <OwnSignatureCard :has-signature="has_signature"
                    usage="Dipakai otomatis untuk membubuhkan TTD Anda di FR.AK.05, TTD AK.01, dan dokumen lain."
                    missing-hint="Isi di bawah ini supaya bisa dipakai otomatis untuk FR.AK.05 dan dokumen lain yang memerlukan TTD Anda."
                    @saved="successMsg = 'Tanda tangan berhasil disimpan.'" />

                <SectionCard title="Rekomendasi Asesi">
                    <template v-if="rows.length" #actions>
                        <button @click="saveRekomendasi" :disabled="savingRekomendasi" class="btn btn-success border-0 shadow">
                            <i class="fa fa-save me-1"></i>
                            {{ savingRekomendasi ? 'Menyimpan...' : 'Simpan Rekomendasi' }}
                        </button>
                    </template>
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
                                setelah nilai esai/wawancara selesai dinilai, sampai sesi ini difinalisasi Manajer Sertifikasi.
                            </div>
                        </template>
                </SectionCard>

                <SectionCard title="Catatan Laporan Asesmen" icon="fa-pen">
                    <template #meta>
                        Kolom di bawah sudah diisi teks contoh yang umum dipakai — silakan diedit sesuai kondisi sesi ini, atau langsung disimpan apa adanya.
                        <span v-if="!report"> Simpan laporan terlebih dahulu untuk bisa mengunduh PDF.</span>
                    </template>
                    <template #actions>
                        <a v-if="report" :href="downloadUrl" target="_blank" class="btn btn-outline-dark border">
                            <i class="fa fa-file-pdf me-1"></i> Download PDF
                        </a>
                        <button @click="save" :disabled="saving" class="btn btn-success border-0 shadow">
                            <i class="fa fa-save me-1"></i>
                            {{ saving ? 'Menyimpan...' : 'Simpan Laporan' }}
                        </button>
                    </template>

                        <div class="mb-3">
                            <label class="form-label fw-bolder">Tanggal Pelaksanaan Asesmen</label>
                            <input type="date" v-model="form.tanggal_asesmen" class="form-control" style="max-width:220px">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bolder">Aspek Negatif dan Positif dalam Asesmen</label>
                            <textarea v-model="form.aspek_negatif_positif" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bolder">Pencatatan Penolakan Hasil Asesmen</label>
                            <textarea v-model="form.pencatatan_penolakan" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bolder">Saran Perbaikan (Asesor/Personil Terkait)</label>
                            <textarea v-model="form.saran_perbaikan" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-bolder">Catatan</label>
                            <textarea v-model="form.catatan" class="form-control" rows="3"></textarea>
                        </div>
                </SectionCard>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, router } from '@inertiajs/vue3';
import PageGuide from '../../../Components/PageGuide.vue';
import GuideLaporan from '../../../Components/Guide/Asesor/LaporanAsesmen.vue';
import OwnSignatureCard from '../../../Components/OwnSignatureCard.vue';
import SessionHeader from '../../../Components/Asesor/SessionHeader.vue';
import SectionCard from '../../../Components/SectionCard.vue';

export default {
    layout: LayoutAsesor,
    components: { Head, PageGuide, GuideLaporan, OwnSignatureCard, SessionHeader, SectionCard },

    props: {
        exam_session:  Object,
        rows:          Array,
        report:        Object,
        has_signature: Boolean,
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
