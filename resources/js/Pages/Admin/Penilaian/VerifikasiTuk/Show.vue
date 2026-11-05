<template>
    <Head><title>FR.TUK.06 — {{ student.name }}</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="col-12">

            <BackBar :href="`/admin/penilaian/${exam_session.id}/verifikasi-tuk`">
                <a v-if="verification" :href="`/admin/penilaian/${exam_session.id}/verifikasi-tuk/${student.id}/pdf`"
                    target="_blank" class="btn btn-outline-danger border shadow-sm">
                    <i class="fa fa-file-pdf me-2"></i> Unduh FR.TUK.06
                </a>
            </BackBar>

            <div class="card border-0 shadow mb-3">
                <div class="card-body py-3">
                    <h6 class="fw-bold mb-1">
                        <i class="fa fa-clipboard-check me-2"></i>Checklist Verifikasi TUK Online (FR.TUK.06 {{ options.revision }})
                    </h6>
                    <div class="small text-muted">{{ student.no_participant }} — {{ student.name }} · {{ exam_session.title }}</div>
                </div>
            </div>

            <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
                {{ $page.props.session.success }}
            </div>
            <div v-if="Object.keys(errors || {}).length" class="alert alert-danger border-0 shadow mb-3">
                <div v-for="(msg, key) in errors" :key="key">{{ msg }}</div>
            </div>

            <!-- A. Identitas -->
            <div class="card border-0 shadow mb-3">
                <div class="card-header bg-white border-bottom fw-bolder py-3">A. Identitas Pelaksanaan</div>
                <div class="card-body">
                    <table class="table table-sm table-bordered mb-0 table-wrap">
                        <tbody>
                            <tr><td class="fw-bolder" style="width:30%">Nama Peserta</td><td>{{ student.name }}</td></tr>
                            <tr><td class="fw-bolder">Skema Sertifikasi</td><td>{{ skema || '-' }}</td></tr>
                            <tr>
                                <td class="fw-bolder align-middle">Tanggal Asesmen</td>
                                <td><input type="date" class="form-control form-control-sm" style="max-width:220px" v-model="form.tanggal_asesmen"></td>
                            </tr>
                            <tr>
                                <td class="fw-bolder align-middle">Waktu</td>
                                <td><input type="text" class="form-control form-control-sm" style="max-width:220px" v-model="form.waktu_asesmen" placeholder="mis. 08.30 – 10.00 WIB"></td>
                            </tr>
                            <tr><td class="fw-bolder">Metode Asesmen</td><td>{{ exam_session.tempat_ujian || 'Online (Zoom Meeting)' }}</td></tr>
                            <tr>
                                <td class="fw-bolder align-middle">Lokasi Peserta</td>
                                <td v-if="isRev1">
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <div v-for="opt in options.lokasi_options" :key="opt" class="form-check mb-0">
                                            <input class="form-check-input" type="radio" name="lokasi_peserta" :id="`lokasi_${opt}`" :value="opt" v-model="form.lokasi_peserta">
                                            <label class="form-check-label" :for="`lokasi_${opt}`">{{ opt }}{{ opt === 'Lainnya' ? ':' : '' }}</label>
                                        </div>
                                        <input v-if="form.lokasi_peserta === 'Lainnya'" type="text" class="form-control form-control-sm" style="max-width:260px"
                                            maxlength="255" v-model="form.lokasi_lainnya" placeholder="sebutkan lokasinya">
                                    </div>
                                </td>
                                <td v-else><input type="text" class="form-control form-control-sm" v-model="form.lokasi_peserta" placeholder="mis. Rumah, Kota Semarang"></td>
                            </tr>
                            <tr>
                                <td class="fw-bolder align-middle">Nama Pengawas Ujian</td>
                                <td>
                                    <select class="form-select form-select-sm" style="max-width:360px" v-model="form.pengawas_id">
                                        <option :value="null" disabled>— Pilih Pengawas Ujian —</option>
                                        <option v-for="p in pengawas_options" :key="p.id" :value="p.id">
                                            {{ p.name }}{{ p.has_signature ? '' : ' (belum ada TTD)' }}
                                        </option>
                                    </select>
                                    <div class="small text-muted mt-1">
                                        Nama &amp; TTD di FR.TUK.06 ikut user yang dipilih. Pengawas baru: tambahkan user role Admin
                                        beserta TTD-nya di menu <Link href="/admin/users">Users</Link>.
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="options.petunjuk" class="small text-muted mt-3">
                        <strong>Petunjuk pengisian:</strong> {{ options.petunjuk }}
                    </div>
                </div>
            </div>

            <!-- B–F. Kriteria -->
            <div v-for="section in sections" :key="section.key" class="card border-0 shadow mb-3">
                <div class="card-header bg-white border-bottom fw-bolder py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>
                        {{ section.key }}. {{ titleCase(section.title) }}
                        <span class="badge bg-gray-200 text-gray-800 ms-2">{{ section.key === 'F' ? 'Selama ujian' : 'Sebelum ujian' }}</span>
                        <span class="small fw-normal ms-2 text-gray-300">{{ filled(section) }}/{{ section.items.length }}</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-secondary" @click="setAll(section, 'sesuai')">
                        <i class="fa fa-check-double me-1"></i>Tandai semua {{ options.status_labels.sesuai }}
                    </button>
                </div>
                <div class="card-body p-0">
                    <!-- Dokumen identitas dari permohonan peserta — pembanding untuk Verifikasi Identitas -->
                    <div v-if="section.key === 'B'" class="d-flex flex-wrap align-items-start gap-3 p-3 border-bottom bg-gray-100">
                        <a v-if="identity_document.document?.is_image" :href="identityUrl" target="_blank" rel="noopener" title="Buka dokumen">
                            <img :src="identityUrl" alt="Dokumen identitas peserta" class="border rounded bg-white d-block"
                                style="max-height:140px;max-width:220px">
                        </a>
                        <div>
                            <div class="fw-bolder small">
                                {{ identity_document.label }} <span v-if="identity_document.is_required" class="text-danger">*</span>
                            </div>
                            <div v-if="identity_document.document" class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                <StatusBadge :tone="docStatus.tone">{{ docStatus.label }}</StatusBadge>
                                <a :href="identityUrl" target="_blank" rel="noopener" class="btn btn-sm btn-gray-100 border">
                                    <i class="fa fa-eye me-1"></i>Lihat Dokumen
                                </a>
                            </div>
                            <div v-if="identity_document.document?.reviewer_notes" class="small text-danger mt-1">
                                {{ identity_document.document.reviewer_notes }}
                            </div>
                            <div class="small text-muted mt-1">
                                <template v-if="!identity_document.application_id">
                                    Peserta ini tidak terdaftar lewat permohonan sertifikasi, jadi tidak ada dokumen identitas yang diunggah.
                                </template>
                                <template v-else-if="!identity_document.has_requirement">
                                    Skema ini belum punya persyaratan dokumen identitas. Tambahkan di
                                    <Link :href="`/admin/classrooms/${identity_document.classroom_id}/requirements`">Persyaratan Dokumen skema</Link>.
                                </template>
                                <template v-else-if="!identity_document.document">
                                    <StatusBadge tone="danger" class="me-1">Belum diunggah</StatusBadge>
                                    Peserta belum mengunggah dokumen ini di permohonannya.
                                </template>
                                <template v-else>
                                    Cocokkan nama &amp; wajah di dokumen dengan peserta di Zoom (butir 1–3).
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width:5%" class="text-center">No.</th>
                                    <th>{{ options.item_header }}</th>
                                    <th style="width:9%" class="text-center">{{ options.status_labels.sesuai }}</th>
                                    <th style="width:9%" class="text-center">{{ options.status_labels.tidak_sesuai }}</th>
                                    <th style="width:28%">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in section.items" :key="item.key"
                                    :class="{ 'table-danger': form.items[item.key].status === 'tidak_sesuai' }">
                                    <td class="text-center">{{ i + 1 }}</td>
                                    <td class="text-wrap">{{ item.label }}</td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="radio" :name="item.key" value="sesuai"
                                            v-model="form.items[item.key].status" :aria-label="`${item.key} sesuai`">
                                    </td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="radio" :name="item.key" value="tidak_sesuai"
                                            v-model="form.items[item.key].status" :aria-label="`${item.key} tidak sesuai`">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" maxlength="500"
                                            v-model="form.items[item.key].catatan">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- G. Kesimpulan verifikasi awal -->
            <div class="card border-0 shadow mb-3">
                <div class="card-header bg-white border-bottom fw-bolder py-3">
                    G. Kesimpulan Verifikasi Awal
                    <span class="badge bg-gray-200 text-gray-800 ms-2">Sebelum ujian</span>
                </div>
                <div class="card-body">
                    <div v-for="(label, key) in options.kesimpulan_awal" :key="key" class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="kesimpulan_awal" :id="`awal_${key}`" :value="key" v-model="form.kesimpulan_awal">
                        <label class="form-check-label" :for="`awal_${key}`">
                            <strong>{{ label }}</strong> — {{ options.kesimpulan_awal_keterangan[key] }}
                        </label>
                    </div>
                    <div v-if="['layak', 'memenuhi'].includes(form.kesimpulan_awal) && tidakSesuaiSebelumUjian > 0" class="alert alert-warning border-0 small py-2 mt-2 mb-2">
                        <i class="fa fa-exclamation-triangle me-1"></i>
                        Ada {{ tidakSesuaiSebelumUjian }} butir (B–E) berstatus {{ options.status_labels.tidak_sesuai }}.
                        <template v-if="isRev1">Periksa lagi apakah kesimpulannya seharusnya <strong>Tidak Memenuhi</strong>.</template>
                        <template v-else>Periksa lagi apakah kesimpulannya seharusnya <strong>Layak dengan Perbaikan</strong> atau <strong>Tidak Layak</strong>.</template>
                    </div>
                    <label class="small fw-bolder mt-2">{{ isRev1 ? 'Catatan' : 'Catatan ketidaksesuaian / tindakan perbaikan' }}</label>
                    <textarea class="form-control" rows="2" maxlength="2000" v-model="form.catatan_awal"></textarea>
                </div>
            </div>

            <!-- H (Rev.1). Ketidaksesuaian, perbaikan & verifikasi ulang -->
            <div v-if="isRev1" class="card border-0 shadow mb-3">
                <div class="card-header bg-white border-bottom fw-bolder py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>
                        H. Ketidaksesuaian, Perbaikan dan Verifikasi Ulang
                        <span class="badge bg-gray-200 text-gray-800 ms-2">Sebelum ujian</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-gray-100 border" @click="addKetidaksesuaian">
                        <i class="fa fa-plus me-1"></i>Tambah Baris
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width:5%" class="text-center">No.</th>
                                    <th>Uraian Persyaratan yang Tidak Memenuhi</th>
                                    <th style="width:24%">Tindakan Perbaikan yang Dipersyaratkan</th>
                                    <th style="width:14%">Batas Waktu</th>
                                    <th style="width:16%">Status Verifikasi Ulang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in form.ketidaksesuaian" :key="i">
                                    <td class="text-center">{{ i + 1 }}</td>
                                    <td><input type="text" class="form-control form-control-sm" maxlength="500" v-model="row.uraian"></td>
                                    <td><input type="text" class="form-control form-control-sm" maxlength="500" v-model="row.tindakan"></td>
                                    <td><input type="text" class="form-control form-control-sm" maxlength="100" v-model="row.batas_waktu" placeholder="mis. 08.15 WIB"></td>
                                    <td>
                                        <div v-for="(label, key) in options.status_ulang" :key="key" class="form-check mb-0">
                                            <input class="form-check-input" type="radio" :name="`ulang_${i}`" :id="`ulang_${i}_${key}`" :value="key" v-model="row.status">
                                            <label class="form-check-label small" :for="`ulang_${i}_${key}`">{{ label }}</label>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3 p-3 border-top">
                        <span class="fw-bolder small">Kesimpulan setelah verifikasi ulang:</span>
                        <div v-for="(label, key) in options.status_ulang" :key="key" class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="kesimpulan_ulang" :id="`kulang_${key}`" :value="key" v-model="form.kesimpulan_ulang">
                            <label class="form-check-label" :for="`kulang_${key}`">{{ label.toUpperCase() }}</label>
                        </div>
                        <button v-if="form.kesimpulan_ulang" type="button" class="btn btn-sm btn-link p-0" @click="form.kesimpulan_ulang = null">kosongkan</button>
                    </div>
                </div>
            </div>

            <!-- H. Hasil pemantauan -->
            <div class="card border-0 shadow mb-3">
                <div class="card-header bg-white border-bottom fw-bolder py-3">
                    {{ isRev1 ? 'I' : 'H' }}. Hasil Pemantauan Selama Asesmen
                    <span class="badge bg-gray-200 text-gray-800 ms-2">Selama ujian</span>
                </div>
                <div class="card-body">
                    <div v-for="(label, key) in options.hasil_pemantauan" :key="key" class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="hasil_pemantauan" :id="`pantau_${key}`" :value="key" v-model="form.hasil_pemantauan">
                        <label class="form-check-label" :for="`pantau_${key}`">{{ label }}</label>
                    </div>
                    <template v-if="isRev1">
                        <label class="small fw-bolder mt-2">Uraian kejadian/ketidaksesuaian</label>
                        <textarea class="form-control" rows="2" maxlength="2000" v-model="form.uraian_pemantauan"></textarea>
                        <label class="small fw-bolder mt-2">Tindakan yang dilakukan</label>
                        <textarea class="form-control" rows="2" maxlength="2000" v-model="form.tindakan_pemantauan"></textarea>
                        <label class="small fw-bolder mt-2 d-block">Dampak terhadap kelanjutan asesmen</label>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div v-for="(label, key) in options.dampak_options" :key="key" class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="dampak_pemantauan" :id="`dampak_${key}`" :value="key" v-model="form.dampak_pemantauan">
                                <label class="form-check-label" :for="`dampak_${key}`">{{ label }}{{ key === 'lainnya' ? ':' : '' }}</label>
                            </div>
                            <input v-if="form.dampak_pemantauan === 'lainnya'" type="text" class="form-control form-control-sm" style="max-width:260px"
                                maxlength="255" v-model="form.dampak_lainnya" placeholder="sebutkan">
                        </div>
                    </template>
                    <template v-else>
                        <label class="small fw-bolder mt-2">Uraian kejadian dan tindak lanjut</label>
                        <textarea class="form-control" rows="2" maxlength="2000" v-model="form.uraian_pemantauan"></textarea>
                    </template>
                </div>
            </div>

            <!-- I. Validasi -->
            <div class="card border-0 shadow mb-3">
                <div class="card-header bg-white border-bottom fw-bolder py-3">{{ isRev1 ? 'J' : 'I' }}. Validasi Pengawas Ujian</div>
                <div class="card-body">
                    <table class="table table-sm table-bordered mb-0 table-wrap">
                        <tbody>
                            <tr>
                                <td class="fw-bolder" style="width:30%">Nama Pengawas Ujian</td>
                                <td>{{ pengawas?.name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bolder">Tanggal/Waktu Verifikasi</td>
                                <td>
                                    <span v-if="verification?.verified_at">{{ formatDateTime(verification.verified_at) }}</span>
                                    <span v-else class="text-muted small">Otomatis saat kesimpulan verifikasi awal pertama kali disimpan.</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bolder align-middle">Kesimpulan Akhir</td>
                                <td>
                                    <div v-for="(label, key) in options.kesimpulan_akhir" :key="key" class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="kesimpulan_akhir" :id="`akhir_${key}`" :value="key" v-model="form.kesimpulan_akhir">
                                        <label class="form-check-label" :for="`akhir_${key}`">{{ label }}</label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bolder">Tanda Tangan/Validasi</td>
                                <td class="small">
                                    <span v-if="!pengawas" class="text-muted">Pilih Nama Pengawas Ujian di bagian A.</span>
                                    <template v-else-if="pengawas.has_signature">
                                        <img :src="`/admin/users/${pengawas.id}/tanda-tangan`" alt="TTD Pengawas" class="border rounded bg-white d-block mb-1"
                                            style="max-height:60px;max-width:200px">
                                        <span class="text-muted">TTD tersimpan milik {{ pengawas.name }}.</span>
                                    </template>
                                    <span v-else class="text-danger">
                                        <i class="fa fa-exclamation-circle me-1"></i>{{ pengawas.name }} belum punya TTD tersimpan, jadi PDF tercetak tanpa TTD.
                                        Tambahkan TTD di menu <Link :href="`/admin/users/${pengawas.id}/edit`">Users → Edit</Link>, lalu simpan ulang checklist ini.
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Aksi -->
            <div class="card border-0 shadow position-sticky" style="bottom:0; z-index:1020">
                <div class="card-body py-2 d-flex flex-wrap justify-content-end gap-2">
                    <button type="button" class="btn btn-success border-0" :disabled="saving" @click="save(false)">
                        <i class="fa fa-save me-1"></i>{{ saving ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                    <button v-if="next_student_id" type="button" class="btn btn-primary border-0" :disabled="saving" @click="save(true)">
                        Simpan &amp; Peserta Berikutnya <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
import BackBar from '../../../../Components/Admin/BackBar.vue';
import LayoutAdmin from '../../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import StatusBadge from '../../../../Components/StatusBadge.vue';

export default {
    layout: LayoutAdmin,
    components: { BackBar, Head, Link, StatusBadge },
    props: {
        errors:          Object,
        exam_session:    Object,
        student:         Object,
        skema:           String,
        verification:    Object,
        identity_document: Object,
        sections:        Array,
        options:             Object,
        pengawas_options:    Array,
        default_pengawas_id: Number,
        next_student_id:     Number,
    },

    data() {
        const v = this.verification ?? {};

        const rev1 = this.options.revision === 'Rev.1';
        const blankRow = () => ({ uraian: '', tindakan: '', batas_waktu: '', status: null });
        const ketidaksesuaian = (v.ketidaksesuaian ?? []).map(r => ({ ...blankRow(), ...r }));

        const items = {};
        this.sections.forEach(section => section.items.forEach(item => {
            const saved = v.items?.[item.key] ?? {};
            items[item.key] = { status: saved.status ?? null, catatan: saved.catatan ?? '' };
        }));

        return {
            saving: false,
            form: {
                // tanggal lokal (bukan UTC) dalam format YYYY-MM-DD
                tanggal_asesmen:   v.tanggal_asesmen ?? new Date().toLocaleDateString('sv-SE'),
                waktu_asesmen:     v.waktu_asesmen ?? '',
                lokasi_peserta:    v.lokasi_peserta ?? (rev1 ? null : ''),
                lokasi_lainnya:    v.lokasi_lainnya ?? '',
                items,
                kesimpulan_awal:   v.kesimpulan_awal ?? null,
                catatan_awal:      v.catatan_awal ?? '',
                // Rev.1 bagian H: minimal 4 baris seperti di dokumen
                ketidaksesuaian:   [...ketidaksesuaian, ...Array.from({ length: Math.max(0, 4 - ketidaksesuaian.length) }, blankRow)],
                kesimpulan_ulang:  v.kesimpulan_ulang ?? null,
                hasil_pemantauan:  v.hasil_pemantauan ?? null,
                uraian_pemantauan: v.uraian_pemantauan ?? '',
                tindakan_pemantauan: v.tindakan_pemantauan ?? '',
                dampak_pemantauan: v.dampak_pemantauan ?? null,
                dampak_lainnya:    v.dampak_lainnya ?? '',
                kesimpulan_akhir:  v.kesimpulan_akhir ?? null,
                pengawas_id:       this.default_pengawas_id ?? null,
            },
        };
    },

    computed: {
        isRev1() {
            return this.options.revision === 'Rev.1';
        },
        pengawas() {
            return this.pengawas_options.find(p => p.id === this.form.pengawas_id) ?? null;
        },
        identityUrl() {
            const d = this.identity_document;
            return d.document ? `/admin/applications/${d.application_id}/documents/${d.document.id}/preview` : null;
        },
        docStatus() {
            return {
                verified: { tone: 'success', label: 'Terverifikasi' },
                rejected: { tone: 'danger', label: 'Ditolak' },
            }[this.identity_document.document?.status] ?? { tone: 'secondary', label: 'Menunggu verifikasi' };
        },
        tidakSesuaiSebelumUjian() {
            return this.sections
                .filter(s => s.key !== 'F')
                .flatMap(s => s.items)
                .filter(item => this.form.items[item.key].status === 'tidak_sesuai')
                .length;
        },
    },

    methods: {
        filled(section) {
            return section.items.filter(item => this.form.items[item.key].status).length;
        },
        addKetidaksesuaian() {
            this.form.ketidaksesuaian.push({ uraian: '', tindakan: '', batas_waktu: '', status: null });
        },
        setAll(section, status) {
            section.items.forEach(item => { this.form.items[item.key].status = status; });
        },
        titleCase(text) {
            return text.toLowerCase().replace(/\b\w/g, c => c.toUpperCase()).replace(/\bTuk\b/, 'TUK');
        },
        formatDateTime(value) {
            return new Date(value).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });
        },
        save(next) {
            this.saving = true;
            router.post(
                `/admin/penilaian/${this.exam_session.id}/verifikasi-tuk/${this.student.id}`,
                { ...this.form, next },
                {
                    preserveScroll: !next,
                    // router.post mempertahankan state komponen secara default — saat pindah
                    // ke peserta berikutnya form harus dibangun ulang dari props peserta itu.
                    preserveState: next ? 'errors' : true,
                    onFinish: () => { this.saving = false; },
                }
            );
        },
    },
}
</script>
