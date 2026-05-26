<template>
    <Head>
        <title>FR.APL.01 - Permohonan Sertifikasi</title>
    </Head>

    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><Link href="/peserta/dashboard">Dashboard</Link></li>
                    <li class="breadcrumb-item active">FR.APL.01</li>
                </ol>
            </nav>
            <h5 class="fw-bold">FR.APL.01 — Permohonan Sertifikasi Kompetensi</h5>
            <p class="text-muted small">
                Skema: <strong>{{ application.classroom.title }}</strong> &bull;
                Sesi: <strong>{{ application.exam_session?.title }}</strong>
            </p>
        </div>
    </div>

    <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
        {{ $page.props.session.success }}
    </div>
    <div v-if="$page.props.session.error" class="alert alert-danger border-0 shadow mb-3">
        {{ $page.props.session.error }}
    </div>

    <form @submit.prevent="submit">
        <!-- Bagian 1a: Data Pribadi -->
        <div class="card border-0 shadow mb-4">
            <div class="card-header bg-gray-800 text-white fw-semibold">
                Bagian 1a — Data Pribadi
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="fw-semibold small">Nama Lengkap Beserta Gelar <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="form.name" placeholder="contoh: Dr. Budi Santoso, M.Si.">
                        <div v-if="errors.name" class="text-danger small mt-1">{{ errors.name }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold small">No. KTP / NIK / Paspor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="form.nik" placeholder="16 digit NIK">
                        <div v-if="errors.nik" class="text-danger small mt-1">{{ errors.nik }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="fw-semibold small">Tempat Lahir <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="form.tempat_lahir">
                        <div v-if="errors.tempat_lahir" class="text-danger small mt-1">{{ errors.tempat_lahir }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="fw-semibold small">Tanggal Lahir <span class="text-danger">*</span></label>
                        <Datepicker
                            v-model="tanggalLahirDate"
                            :format="'dd MMMM yyyy'"
                            :preview-format="'dd MMMM yyyy'"
                            :enable-time-picker="false"
                            auto-apply
                            :year-range="[1940, currentYear]"
                            :max-date="new Date()"
                            :start-date="new Date(1970, 0, 1)"
                            text-input
                            locale="id"
                            placeholder="Pilih tanggal lahir"
                            input-class-name="form-control"
                            month-name-format="long"
                        />
                        <div class="form-text small">Klik nama bulan/tahun di kalender untuk navigasi cepat ke tahun lama.</div>
                        <div v-if="errors.tanggal_lahir" class="text-danger small mt-1">{{ errors.tanggal_lahir }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="fw-semibold small">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select class="form-select" v-model="form.jenis_kelamin">
                            <option value="">Pilih</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                        <div v-if="errors.jenis_kelamin" class="text-danger small mt-1">{{ errors.jenis_kelamin }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold small">Kebangsaan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="form.kebangsaan">
                        <div v-if="errors.kebangsaan" class="text-danger small mt-1">{{ errors.kebangsaan }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold small">Kualifikasi Pendidikan <span class="text-danger">*</span></label>
                        <select class="form-select" v-model="form.kualifikasi_pendidikan">
                            <option value="">Pilih</option>
                            <option v-for="q in ['SD','SMP','SMA','D3','S1','S2','S3']" :key="q" :value="q">{{ q }}</option>
                        </select>
                        <div v-if="errors.kualifikasi_pendidikan" class="text-danger small mt-1">{{ errors.kualifikasi_pendidikan }}</div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="fw-semibold small">Alamat Rumah <span class="text-danger">*</span></label>
                        <textarea class="form-control" rows="2" v-model="form.alamat_rumah"></textarea>
                        <div v-if="errors.alamat_rumah" class="text-danger small mt-1">{{ errors.alamat_rumah }}</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-semibold small">Kode Pos</label>
                        <input type="text" class="form-control" v-model="form.kode_pos_rumah" placeholder="12345">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-semibold small">Telp Rumah</label>
                        <input type="text" class="form-control" v-model="form.telp_rumah">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-semibold small">HP / WA <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="form.hp">
                        <div v-if="errors.hp" class="text-danger small mt-1">{{ errors.hp }}</div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-semibold small">Email Alt.</label>
                        <input type="email" class="form-control" v-model="form.email_alt">
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian 1b: Data Pekerjaan -->
        <div class="card border-0 shadow mb-4">
            <div class="card-header bg-gray-800 text-white fw-semibold">
                Bagian 1b — Data Pekerjaan Sekarang
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold small">Nama Institusi / Perusahaan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="form.institusi">
                        <div v-if="errors.institusi" class="text-danger small mt-1">{{ errors.institusi }}</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold small">Jabatan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="form.jabatan">
                        <div v-if="errors.jabatan" class="text-danger small mt-1">{{ errors.jabatan }}</div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="fw-semibold small">Alamat Kantor</label>
                        <textarea class="form-control" rows="2" v-model="form.alamat_kantor"></textarea>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-semibold small">Kode Pos Kantor</label>
                        <input type="text" class="form-control" v-model="form.kode_pos_kantor">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-semibold small">Telp Kantor</label>
                        <input type="text" class="form-control" v-model="form.telp_kantor">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-semibold small">Fax</label>
                        <input type="text" class="form-control" v-model="form.fax_kantor">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="fw-semibold small">Email Kantor</label>
                        <input type="email" class="form-control" v-model="form.email_kantor">
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian 2: Data Sertifikasi (read-only dari skema) -->
        <div class="card border-0 shadow mb-4">
            <div class="card-header bg-gray-800 text-white fw-semibold">
                Bagian 2 — Data Sertifikasi
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold small">Skema Sertifikasi</label>
                        <input type="text" class="form-control" :value="application.classroom.title" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold small">Tujuan Asesmen</label>
                        <input type="text" class="form-control" :value="application.tujuan_asesmen" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold small">Konteks Asesmen</label>
                        <input type="text" class="form-control" :value="application.konteks_asesmen" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-semibold small">Tempat Ujian</label>
                        <input type="text" class="form-control" :value="application.tempat_ujian" disabled>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tanda Tangan FR.APL.01 -->
        <div class="card border-0 shadow mb-4">
            <div class="card-header bg-gray-800 text-white fw-semibold">
                <i class="fa fa-pen me-2"></i>Tanda Tangan Pemohon (FR.APL.01)
                <span v-if="application.signature_form_path" class="badge bg-success ms-2">
                    <i class="fa fa-check me-1"></i>Sudah Ditandatangani
                </span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Dengan menandatangani di bawah ini, saya menyatakan bahwa data yang saya isi dalam formulir ini adalah benar dan dapat dipertanggungjawabkan.
                </p>

                <div v-if="application.signature_form_path" class="mb-3 p-2 border rounded d-flex align-items-center gap-3">
                    <img :src="`/peserta/aplikasi/${application.id}/tanda-tangan/form`" alt="TTD"
                        style="max-height:80px; max-width:240px; object-fit:contain; border:1px solid #ddd; background:#fff; padding:4px">
                    <div>
                        <span class="badge bg-success mb-1"><i class="fa fa-check me-1"></i>TTD Tersimpan</span>
                        <div class="small text-muted">Anda dapat memperbarui tanda tangan.</div>
                    </div>
                </div>

                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-sm"
                        :class="sigMode === 'draw' ? 'btn-gray-800' : 'btn-light border'"
                        @click="switchSigMode('draw')">
                        <i class="fa fa-pen me-1"></i> Gambar TTD
                    </button>
                    <button type="button" class="btn btn-sm"
                        :class="sigMode === 'upload' ? 'btn-gray-800' : 'btn-light border'"
                        @click="switchSigMode('upload')">
                        <i class="fa fa-upload me-1"></i> Upload Gambar TTD
                    </button>
                </div>

                <div v-show="sigMode === 'draw'">
                    <div class="border rounded mb-2 bg-white" style="touch-action:none">
                        <canvas ref="sigCanvas" style="display:block; width:100%; height:160px; cursor:crosshair"></canvas>
                    </div>
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" class="btn btn-sm btn-light border" @click="clearSig">
                            <i class="fa fa-eraser me-1"></i> Hapus
                        </button>
                        <button type="button" class="btn btn-sm btn-success" @click="saveSigDrawn" :disabled="savingSig">
                            <i class="fa fa-save me-1"></i>{{ savingSig ? 'Menyimpan...' : 'Simpan TTD' }}
                        </button>
                    </div>
                </div>

                <div v-show="sigMode === 'upload'">
                    <div class="mb-2">
                        <label class="fw-semibold small">Pilih file gambar tanda tangan <span class="text-muted">(JPG / PNG, maks. 2 MB)</span></label>
                        <input type="file" class="form-control mt-1" accept="image/png,image/jpeg,image/jpg"
                            @change="onSigFileChange">
                    </div>
                    <div v-if="sigFilePreview" class="mb-2">
                        <img :src="sigFilePreview" style="max-height:100px; border:1px solid #ddd; background:#fff; padding:4px">
                    </div>
                    <button type="button" class="btn btn-sm btn-success mb-2" @click="saveSigUpload" :disabled="!sigFile || savingSig">
                        <i class="fa fa-save me-1"></i>{{ savingSig ? 'Menyimpan...' : 'Simpan TTD' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-5">
            <button type="submit" class="btn btn-gray-800" :disabled="processing">
                {{ processing ? 'Menyimpan...' : 'Simpan & Lanjut ke Pakta Integritas' }}
            </button>
            <Link href="/peserta/dashboard" class="btn btn-light border">Kembali</Link>
        </div>
    </form>
</template>

<script>
import LayoutPeserta from '../../../Layouts/Peserta.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import SignaturePad from 'signature_pad';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

export default {
    layout: LayoutPeserta,
    components: { Head, Link, Datepicker },
    props: {
        application: Object,
        participant: Object,
        errors: Object,
    },

    setup(props) {
        const processing  = ref(false);
        const sigMode     = ref('draw');
        const sigCanvas   = ref(null);
        const sigFile     = ref(null);
        const sigFilePreview = ref(null);
        const savingSig   = ref(false);
        let   signaturePad = null;

        let resizeTimer = null;

        const initPad = () => {
            if (!sigCanvas.value) return;
            const canvas    = sigCanvas.value;
            const container = canvas.parentElement;
            const ratio     = Math.max(window.devicePixelRatio || 1, 1);
            const savedData = signaturePad?.toData() ?? [];
            canvas.width    = (container?.clientWidth || canvas.offsetWidth) * ratio;
            canvas.height   = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            signaturePad    = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
            if (savedData.length) signaturePad.fromData(savedData);
        };

        const handleResize = () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(initPad, 200);
        };

        onMounted(async () => {
            await nextTick();
            initPad();
            window.addEventListener('resize', handleResize);
        });

        onUnmounted(() => {
            window.removeEventListener('resize', handleResize);
            clearTimeout(resizeTimer);
        });

        const switchSigMode = async (mode) => {
            sigMode.value = mode;
            if (mode === 'draw') { await nextTick(); initPad(); }
        };

        const clearSig = () => signaturePad?.clear();

        const saveSigDrawn = () => {
            if (!signaturePad || signaturePad.isEmpty()) {
                alert('Silakan buat tanda tangan terlebih dahulu.');
                return;
            }
            savingSig.value = true;
            router.post(`/peserta/aplikasi/${props.application.id}/form/tanda-tangan`,
                { signature_data: signaturePad.toDataURL('image/png') },
                {
                    // Pertahankan state form (field yang sudah diisi user tapi belum di-save)
                    preserveState:  true,
                    preserveScroll: true,
                    onFinish: () => { savingSig.value = false; },
                }
            );
        };

        const onSigFileChange = (e) => {
            const file = e.target.files[0];
            if (!file) return;
            sigFile.value = file;
            sigFilePreview.value = URL.createObjectURL(file);
        };

        const saveSigUpload = () => {
            if (!sigFile.value) return;
            savingSig.value = true;
            const fd = new FormData();
            fd.append('signature_file', sigFile.value);
            router.post(`/peserta/aplikasi/${props.application.id}/form/tanda-tangan`, fd, {
                forceFormData:  true,
                preserveState:  true,
                preserveScroll: true,
                onFinish: () => { savingSig.value = false; sigFile.value = null; sigFilePreview.value = null; },
            });
        };

        const currentYear = new Date().getFullYear();

        const form = reactive({
            name:                   props.participant.name ?? '',
            nik:                    props.participant.nik ?? '',
            tempat_lahir:           props.participant.tempat_lahir ?? '',
            tanggal_lahir:          props.participant.tanggal_lahir
                ? String(props.participant.tanggal_lahir).substring(0, 10)
                : '',
            jenis_kelamin:          props.participant.jenis_kelamin ?? '',
            kebangsaan:             props.participant.kebangsaan ?? 'Indonesia',
            alamat_rumah:           props.participant.alamat_rumah ?? '',
            kode_pos_rumah:         props.participant.kode_pos_rumah ?? '',
            telp_rumah:             props.participant.telp_rumah ?? '',
            hp:                     props.participant.hp ?? '',
            email_alt:              props.participant.email_alt ?? '',
            kualifikasi_pendidikan: props.participant.kualifikasi_pendidikan ?? '',
            institusi:              props.participant.institusi ?? '',
            jabatan:                props.participant.jabatan ?? '',
            alamat_kantor:          props.participant.alamat_kantor ?? '',
            kode_pos_kantor:        props.participant.kode_pos_kantor ?? '',
            telp_kantor:            props.participant.telp_kantor ?? '',
            fax_kantor:             props.participant.fax_kantor ?? '',
            email_kantor:           props.participant.email_kantor ?? '',
        });

        // Two-way binding antara Datepicker (Date object) dan form.tanggal_lahir (string YYYY-MM-DD)
        const tanggalLahirDate = computed({
            get: () => form.tanggal_lahir ? new Date(form.tanggal_lahir) : null,
            set: (val) => {
                if (!val) { form.tanggal_lahir = ''; return; }
                const d = val instanceof Date ? val : new Date(val);
                const yyyy = d.getFullYear();
                const mm   = String(d.getMonth() + 1).padStart(2, '0');
                const dd   = String(d.getDate()).padStart(2, '0');
                form.tanggal_lahir = `${yyyy}-${mm}-${dd}`;
            },
        });

        const submit = () => {
            processing.value = true;
            router.put(`/peserta/aplikasi/${props.application.id}/form`, form, {
                onFinish: () => { processing.value = false; },
            });
        };

        return {
            form, processing, submit, currentYear, tanggalLahirDate,
            sigMode, sigCanvas, sigFile, sigFilePreview, savingSig,
            switchSigMode, clearSig, saveSigDrawn, onSigFileChange, saveSigUpload,
        };
    },
}
</script>
