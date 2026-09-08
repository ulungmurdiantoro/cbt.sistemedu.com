<template>
    <Head><title>CV Saya</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <div class="card border-0 shadow mb-4">
                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1"><i class="fa fa-id-badge me-2"></i>CV Saya</h5>
                            <p class="text-muted mb-0 small">Isi &amp; perbarui CV Anda kapan saja. Data ini dipakai untuk menerbitkan CV resmi LSP dalam format PDF.</p>
                        </div>
                        <a :href="'/asesor/cv/pdf'" target="_blank" class="btn btn-outline-dark border">
                            <i class="fa fa-file-pdf me-1"></i> Unduh PDF
                        </a>
                    </div>
                </div>

                <div v-if="successMsg" class="alert alert-success alert-dismissible">
                    {{ successMsg }}
                    <button type="button" class="btn-close" @click="successMsg = ''"></button>
                </div>

                <!-- Data Pribadi -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-user me-2"></i>Data Pribadi</h6>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Foto Profil</label>
                                <div class="d-flex align-items-center gap-3">
                                    <img v-if="photoPreview" :src="photoPreview" alt="Foto"
                                        style="width:80px;height:100px;object-fit:cover;border:1px solid #ddd;background:#fff">
                                    <div v-else class="d-flex align-items-center justify-content-center text-muted small"
                                        style="width:80px;height:100px;border:1px dashed #ccc;background:#f8f9fa">Belum ada</div>
                                    <input type="file" class="form-control form-control-sm" accept="image/png,image/jpeg,image/jpg" @change="onPhotoChange">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Tempat, Tanggal Lahir</label>
                                <input type="text" class="form-control" v-model="form.tempat_tanggal_lahir" placeholder="mis. Surakarta, 7 Juli 1974">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Jenis Kelamin</label>
                                <select class="form-select" v-model="form.jenis_kelamin">
                                    <option value="">-- Pilih --</option>
                                    <option value="Laki-Laki">Laki-Laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Alamat Rumah</label>
                                <textarea class="form-control" rows="2" v-model="form.alamat_rumah"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Alamat Institusi/Kantor</label>
                                <textarea class="form-control" rows="2" v-model="form.alamat_institusi"></textarea>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Nama Institusi/Kantor</label>
                                <input type="text" class="form-control" v-model="form.nama_institusi">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Nomor Handphone</label>
                                <input type="text" class="form-control" v-model="form.no_handphone">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Email</label>
                                <input type="text" class="form-control" :value="$page.props.auth.user.email" disabled>
                                <div class="form-text">Email login — ubah lewat pengaturan akun.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pendidikan Formal -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-graduation-cap me-2"></i>Pendidikan Formal</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-2">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:15%">Jenjang</th>
                                        <th>Sekolah/Institusi</th>
                                        <th>Bidang Ilmu</th>
                                        <th style="width:12%">Tahun Lulus</th>
                                        <th style="width:36px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in form.pendidikan_formal" :key="i">
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.jenjang"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.sekolah"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.bidang_ilmu"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.tahun_lulus"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="removeRow('pendidikan_formal', i)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-light border" @click="addRow('pendidikan_formal', { jenjang:'', sekolah:'', bidang_ilmu:'', tahun_lulus:'' })">
                            <i class="fa fa-plus me-1"></i> Tambah Baris
                        </button>
                    </div>
                </div>

                <!-- Pelatihan -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-chalkboard-teacher me-2"></i>Pelatihan</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-2">
                                <thead class="table-light">
                                    <tr>
                                        <th>Judul Kegiatan</th>
                                        <th style="width:22%">Penyelenggara</th>
                                        <th style="width:12%">Tahun</th>
                                        <th style="width:16%">Lokasi</th>
                                        <th style="width:36px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in form.pelatihan" :key="i">
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.judul_kegiatan"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.penyelenggara"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.tahun"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.lokasi"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="removeRow('pelatihan', i)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-light border" @click="addRow('pelatihan', { judul_kegiatan:'', penyelenggara:'', tahun:'', lokasi:'' })">
                            <i class="fa fa-plus me-1"></i> Tambah Baris
                        </button>
                    </div>
                </div>

                <!-- Pengalaman Kerja -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-briefcase me-2"></i>Pengalaman Kerja</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-2">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jabatan</th>
                                        <th style="width:14%">Tahun</th>
                                        <th style="width:26%">Perusahaan/Institusi</th>
                                        <th style="width:16%">Lokasi</th>
                                        <th style="width:36px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in form.pengalaman_kerja" :key="i">
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.jabatan"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.tahun"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.perusahaan"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.lokasi"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="removeRow('pengalaman_kerja', i)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-light border" @click="addRow('pengalaman_kerja', { jabatan:'', tahun:'', perusahaan:'', lokasi:'' })">
                            <i class="fa fa-plus me-1"></i> Tambah Baris
                        </button>
                    </div>
                </div>

                <!-- Keahlian -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-star me-2"></i>Keahlian</h6>
                        <div v-for="(item, i) in form.keahlian" :key="i" class="d-flex gap-2 mb-2">
                            <input type="text" class="form-control form-control-sm" v-model="form.keahlian[i]" :placeholder="`Keahlian ${i + 1}`">
                            <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="removeKeahlian(i)">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-light border" @click="form.keahlian.push('')">
                            <i class="fa fa-plus me-1"></i> Tambah Keahlian
                        </button>
                    </div>
                </div>

                <!-- Pengalaman Profesional -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-award me-2"></i>Pengalaman Profesional</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-2">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pengalaman</th>
                                        <th style="width:24%">Penyelenggara</th>
                                        <th style="width:14%">Tahun</th>
                                        <th style="width:36px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in form.pengalaman_profesional" :key="i">
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.pengalaman"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.penyelenggara"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.tahun"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="removeRow('pengalaman_profesional', i)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-light border" @click="addRow('pengalaman_profesional', { pengalaman:'', penyelenggara:'', tahun:'' })">
                            <i class="fa fa-plus me-1"></i> Tambah Baris
                        </button>
                    </div>
                </div>

                <!-- Sertifikasi Kompetensi -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fa fa-certificate me-2"></i>Sertifikasi Kompetensi</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-2">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jenis Sertifikasi</th>
                                        <th style="width:18%">Bidang Ilmu</th>
                                        <th style="width:18%">Penyelenggara</th>
                                        <th style="width:10%">Tahun</th>
                                        <th style="width:12%">Masa Berlaku</th>
                                        <th style="width:36px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in form.sertifikasi_kompetensi" :key="i">
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.jenis_sertifikasi"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.bidang_ilmu"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.penyelenggara"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.tahun"></td>
                                        <td><input type="text" class="form-control form-control-sm" v-model="row.masa_berlaku"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="removeRow('sertifikasi_kompetensi', i)">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-light border" @click="addRow('sertifikasi_kompetensi', { jenis_sertifikasi:'', bidang_ilmu:'', penyelenggara:'', tahun:'', masa_berlaku:'' })">
                            <i class="fa fa-plus me-1"></i> Tambah Baris
                        </button>
                    </div>
                </div>

                <div class="d-flex gap-2 mb-5">
                    <button @click="save" :disabled="saving" class="btn btn-success border-0 shadow">
                        <i class="fa fa-save me-1"></i>
                        {{ saving ? 'Menyimpan...' : 'Simpan CV' }}
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import { Head, router } from '@inertiajs/vue3';

export default {
    layout: LayoutAsesor,
    components: { Head },

    props: {
        cv: Object,
    },

    data() {
        // Clone dalam-dalam supaya mengedit baris di form tidak ikut memutasi
        // prop `cv` (array/objek di dalamnya masih referensi ke data server).
        const clone = (v) => JSON.parse(JSON.stringify(v));

        return {
            saving: false,
            successMsg: '',
            photoFile: null,
            photoPreview: this.cv.photo_url,
            form: {
                tempat_tanggal_lahir:   this.cv.tempat_tanggal_lahir,
                jenis_kelamin:          this.cv.jenis_kelamin,
                alamat_rumah:           this.cv.alamat_rumah,
                nama_institusi:         this.cv.nama_institusi,
                alamat_institusi:       this.cv.alamat_institusi,
                no_handphone:           this.cv.no_handphone,
                pendidikan_formal:      this.cv.pendidikan_formal.length ? clone(this.cv.pendidikan_formal) : [{ jenjang:'', sekolah:'', bidang_ilmu:'', tahun_lulus:'' }],
                pelatihan:              this.cv.pelatihan.length ? clone(this.cv.pelatihan) : [{ judul_kegiatan:'', penyelenggara:'', tahun:'', lokasi:'' }],
                pengalaman_kerja:       this.cv.pengalaman_kerja.length ? clone(this.cv.pengalaman_kerja) : [{ jabatan:'', tahun:'', perusahaan:'', lokasi:'' }],
                keahlian:               this.cv.keahlian.length ? clone(this.cv.keahlian) : [''],
                pengalaman_profesional: this.cv.pengalaman_profesional.length ? clone(this.cv.pengalaman_profesional) : [{ pengalaman:'', penyelenggara:'', tahun:'' }],
                sertifikasi_kompetensi: this.cv.sertifikasi_kompetensi.length ? clone(this.cv.sertifikasi_kompetensi) : [{ jenis_sertifikasi:'', bidang_ilmu:'', penyelenggara:'', tahun:'', masa_berlaku:'' }],
            },
        };
    },

    methods: {
        addRow(section, template) {
            this.form[section].push({ ...template });
        },
        removeRow(section, index) {
            this.form[section].splice(index, 1);
        },
        removeKeahlian(index) {
            this.form.keahlian.splice(index, 1);
        },
        onPhotoChange(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.photoFile = file;
            this.photoPreview = URL.createObjectURL(file);
        },
        save() {
            this.saving = true;
            const payload = { ...this.form };
            if (this.photoFile) payload.photo_file = this.photoFile;

            router.post('/asesor/cv', payload, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => { this.successMsg = 'CV berhasil disimpan.'; this.photoFile = null; },
                onFinish:  () => { this.saving = false; },
            });
        },
    },
}
</script>
