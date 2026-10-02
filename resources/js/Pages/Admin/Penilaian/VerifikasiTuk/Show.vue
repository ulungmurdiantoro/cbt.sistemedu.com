<template>
    <Head><title>FR.TUK.06 — {{ student.name }}</title></Head>
    <div class="container-fluid mb-5 mt-4">
        <div class="col-12">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <Link :href="`/admin/penilaian/${exam_session.id}/verifikasi-tuk`" class="btn btn-primary border-0 shadow">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali ke Daftar Peserta
                </Link>
                <a v-if="verification" :href="`/admin/penilaian/${exam_session.id}/verifikasi-tuk/${student.id}/pdf`"
                    target="_blank" class="btn btn-outline-danger border shadow-sm">
                    <i class="fa fa-file-pdf me-2"></i> Unduh FR.TUK.06
                </a>
            </div>

            <div class="card border-0 shadow mb-3">
                <div class="card-body py-3">
                    <h6 class="fw-bold mb-1"><i class="fa fa-clipboard-check me-2"></i>Checklist Verifikasi TUK Online (FR.TUK.06)</h6>
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
                <div class="card-header bg-gray-800 text-white fw-semibold">A. Identitas Pelaksanaan</div>
                <div class="card-body">
                    <table class="table table-sm table-bordered mb-0 table-wrap">
                        <tbody>
                            <tr><td class="fw-semibold" style="width:30%">Nama Peserta</td><td>{{ student.name }}</td></tr>
                            <tr><td class="fw-semibold">Skema Sertifikasi</td><td>{{ skema || '-' }}</td></tr>
                            <tr>
                                <td class="fw-semibold align-middle">Tanggal Asesmen</td>
                                <td><input type="date" class="form-control form-control-sm" style="max-width:220px" v-model="form.tanggal_asesmen"></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold align-middle">Waktu</td>
                                <td><input type="text" class="form-control form-control-sm" style="max-width:220px" v-model="form.waktu_asesmen" placeholder="mis. 08.30 – 10.00 WIB"></td>
                            </tr>
                            <tr><td class="fw-semibold">Metode Asesmen</td><td>{{ exam_session.tempat_ujian || 'Online (Zoom Meeting)' }}</td></tr>
                            <tr>
                                <td class="fw-semibold align-middle">Lokasi Peserta</td>
                                <td><input type="text" class="form-control form-control-sm" v-model="form.lokasi_peserta" placeholder="mis. Rumah, Kota Semarang"></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold align-middle">Nama Pengawas Ujian</td>
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
                </div>
            </div>

            <!-- B–F. Kriteria -->
            <div v-for="section in sections" :key="section.key" class="card border-0 shadow mb-3">
                <div class="card-header bg-gray-800 text-white fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>
                        {{ section.key }}. {{ titleCase(section.title) }}
                        <span class="badge bg-gray-200 text-gray-800 ms-2">{{ section.key === 'F' ? 'Selama ujian' : 'Sebelum ujian' }}</span>
                        <span class="small fw-normal ms-2 text-gray-300">{{ filled(section) }}/{{ section.items.length }}</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-secondary" @click="setAll(section, 'sesuai')">
                        <i class="fa fa-check-double me-1"></i>Tandai semua Sesuai
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th style="width:5%" class="text-center">No.</th>
                                    <th>Kriteria Verifikasi</th>
                                    <th style="width:9%" class="text-center">Sesuai</th>
                                    <th style="width:9%" class="text-center">Tidak Sesuai</th>
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
                <div class="card-header bg-gray-800 text-white fw-semibold">
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
                    <div v-if="form.kesimpulan_awal === 'layak' && tidakSesuaiSebelumUjian > 0" class="alert alert-warning border-0 small py-2 mt-2 mb-2">
                        <i class="fa fa-exclamation-triangle me-1"></i>
                        Ada {{ tidakSesuaiSebelumUjian }} kriteria (B–E) berstatus Tidak Sesuai. Periksa lagi apakah kesimpulannya
                        seharusnya <strong>Layak dengan Perbaikan</strong> atau <strong>Tidak Layak</strong>.
                    </div>
                    <label class="small fw-semibold mt-2">Catatan ketidaksesuaian / tindakan perbaikan</label>
                    <textarea class="form-control" rows="2" maxlength="2000" v-model="form.catatan_awal"></textarea>
                </div>
            </div>

            <!-- H. Hasil pemantauan -->
            <div class="card border-0 shadow mb-3">
                <div class="card-header bg-gray-800 text-white fw-semibold">
                    H. Hasil Pemantauan Selama Asesmen
                    <span class="badge bg-gray-200 text-gray-800 ms-2">Selama ujian</span>
                </div>
                <div class="card-body">
                    <div v-for="(label, key) in options.hasil_pemantauan" :key="key" class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="hasil_pemantauan" :id="`pantau_${key}`" :value="key" v-model="form.hasil_pemantauan">
                        <label class="form-check-label" :for="`pantau_${key}`">{{ label }}</label>
                    </div>
                    <label class="small fw-semibold mt-2">Uraian kejadian dan tindak lanjut</label>
                    <textarea class="form-control" rows="2" maxlength="2000" v-model="form.uraian_pemantauan"></textarea>
                </div>
            </div>

            <!-- I. Validasi -->
            <div class="card border-0 shadow mb-3">
                <div class="card-header bg-gray-800 text-white fw-semibold">I. Validasi Pengawas Ujian</div>
                <div class="card-body">
                    <table class="table table-sm table-bordered mb-0 table-wrap">
                        <tbody>
                            <tr>
                                <td class="fw-semibold" style="width:30%">Nama Pengawas Ujian</td>
                                <td>{{ pengawas?.name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Tanggal/Waktu Verifikasi</td>
                                <td>
                                    <span v-if="verification?.verified_at">{{ formatDateTime(verification.verified_at) }}</span>
                                    <span v-else class="text-muted small">Otomatis saat kesimpulan verifikasi awal pertama kali disimpan.</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold align-middle">Kesimpulan Akhir</td>
                                <td>
                                    <div v-for="(label, key) in options.kesimpulan_akhir" :key="key" class="form-check form-check-inline mb-0">
                                        <input class="form-check-input" type="radio" name="kesimpulan_akhir" :id="`akhir_${key}`" :value="key" v-model="form.kesimpulan_akhir">
                                        <label class="form-check-label" :for="`akhir_${key}`">{{ label }}</label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Tanda Tangan/Validasi</td>
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
            <div class="card border-0 shadow sticky-bottom">
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
import LayoutAdmin from '../../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        errors:          Object,
        exam_session:    Object,
        student:         Object,
        skema:           String,
        verification:    Object,
        sections:        Array,
        options:             Object,
        pengawas_options:    Array,
        default_pengawas_id: Number,
        next_student_id:     Number,
    },

    data() {
        const v = this.verification ?? {};

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
                lokasi_peserta:    v.lokasi_peserta ?? '',
                items,
                kesimpulan_awal:   v.kesimpulan_awal ?? null,
                catatan_awal:      v.catatan_awal ?? '',
                hasil_pemantauan:  v.hasil_pemantauan ?? null,
                uraian_pemantauan: v.uraian_pemantauan ?? '',
                kesimpulan_akhir:  v.kesimpulan_akhir ?? null,
                pengawas_id:       this.default_pengawas_id ?? null,
            },
        };
    },

    computed: {
        pengawas() {
            return this.pengawas_options.find(p => p.id === this.form.pengawas_id) ?? null;
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
