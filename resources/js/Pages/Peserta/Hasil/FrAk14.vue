<template>
    <Head>
        <title>FR.AK.14 - Surat Pernyataan Pemegang Sertifikat</title>
    </Head>

    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><Link href="/peserta/dashboard">Dashboard</Link></li>
                    <li class="breadcrumb-item active">FR.AK.14</li>
                </ol>
            </nav>
            <h5 class="fw-bold">FR.AK.14 — Surat Pernyataan Pemegang Sertifikat</h5>
            <p class="text-muted small">
                Baca seluruh pernyataan di bawah, lalu tandatangani. Materai elektronik dibubuhkan otomatis
                (gratis) setelah tanda tangan tersimpan. SK dan Sertifikat baru bisa diunduh setelah langkah ini selesai.
            </p>
        </div>
    </div>

    <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
        {{ $page.props.session.success }}
    </div>

    <!-- Dokumen FR.AK.14 -->
    <div class="card border-0 shadow mb-4">
        <div class="card-body px-4 py-4" style="font-size:0.88rem; line-height:1.8; color:#222">

            <div class="text-center mb-4">
                <div class="fw-bold text-uppercase" style="font-size:1rem">Surat Pernyataan Pemegang Sertifikat Kompetensi</div>
            </div>

            <p>Yang bertanda tangan dibawah ini:</p>
            <table class="table table-borderless table-sm mb-3 table-wrap" style="font-size:0.88rem">
                <tr><td style="width:140px" class="fw-semibold">Nama</td><td style="width:10px">:</td><td>{{ namaPeserta }}</td></tr>
                <tr><td class="fw-semibold">NIK</td><td>:</td><td>{{ nik }}</td></tr>
                <tr><td class="fw-semibold">Skema</td><td>:</td><td>{{ namaSkema }}</td></tr>
                <tr><td class="fw-semibold">No. Sertifikat</td><td>:</td><td>{{ noSertifikat }}</td></tr>
            </table>

            <p>Menyatakan bersedia memenuhi ketentuan-ketentuan yang dipersyaratkan dalam Skema Sertifikasi LSP Edukasi Global Cendekia, sebagai berikut:</p>

            <ol style="padding-left:1.2rem" class="mt-2">
                <li class="mb-2">Sanggup menjaga kerahasiaan seluruh proses pelaksanaan uji kompetensi.</li>
                <li class="mb-2">Sanggup menjamin terpeliharannya kompetensi yang sesuai pada sertifikat kompetensi.</li>
                <li class="mb-2">Sertifikat kompetensi hanya berlaku untuk ruang lingkup sesuai dengan skema sertifikasi.</li>
                <li class="mb-2"><strong>Penggunaan Sertifikat, Logo dan Penanda</strong>
                    <ol type="a" style="padding-left:1.2rem">
                        <li>Pemegang Sertifikat dapat menggunakan sertifikat, logo dan penanda dalam bidang pekerjaan dan kualifikasi/kompetensi yang tercantum pada sertifikat.</li>
                        <li>Pemegang Sertifikat dapat menggandakan dan memberikan salinan sertifikat kepada pihak lain untuk kepentingan yang berkaitan dengan bidang pekerjaan dan kualifikasi/kompetensi yang tercantum pada sertifikat.</li>
                        <li>Pemegang Sertifikat tidak diperkenankan menggunakan sertifikat, logo, dan penanda dengan cara yang tidak sesuai, tidak dapat dipertanggungjawabkan, atau dapat mencemarkan nama baik KAN dan LSP Edukasi Global Cendekia (Edukia), serta apabila sertifikat telah berakhir masa berlakunya, sedang dibekukan atau telah dicabut.</li>
                    </ol>
                </li>
                <li class="mb-2"><strong>Pemeliharaan Sertifikasi dan Sertifikasi Ulang</strong>
                    <ol type="a" style="padding-left:1.2rem">
                        <li>Untuk memastikan terpeliharanya kompetensi, Pemegang Sertifikat wajib mengikuti program pemeliharaan sertifikasi yang ditetapkan LSP Edukasi Global Cendekia yang akan dimulai pada bulan ke-36 setelah tanggal terbit sertifikat.</li>
                        <li>Untuk memperpanjang masa berlaku sertifikat, Pemegang Sertifikat dapat mengajukan permohonan kepada LSP Edukasi Global Cendekia, minimal 2 (dua) bulan sebelum berakhirnya masa berlaku sertifikat.</li>
                    </ol>
                </li>
                <li class="mb-2"><strong>Pembekuan dan Pencabutan Sertifikat</strong>
                    <ol type="a" style="padding-left:1.2rem">
                        <li>Apabila Pemegang Sertifikat tidak memenuhi ketentuan-ketentuan yang disebutkan pada bagian 1 sampai 5 maka LSP Edukasi Global Cendekia dapat menerbitkan surat pembekuan sertifikat.</li>
                        <li>LSP Edukasi Global Cendekia akan menerbitkan surat pengaktifan kembali sertifikat setelah Pemegang Sertifikat dinilai berhasil melakukan tindakan perbaikan dalam batas waktu yang ditetapkan LSP Edukasi Global Cendekia.</li>
                        <li>Apabila Pemegang Sertifikat dinilai gagal dalam melakukan tindakan perbaikan, maka LSP Edukasi Global Cendekia dapat menerbitkan surat pencabutan Sertifikat. Pemegang Sertifikat diwajibkan mengembalikan sertifikat yang dicabut kepada LSP Edukasi Global Cendekia.</li>
                    </ol>
                </li>
            </ol>

            <hr class="my-3">

            <p>Demikian pernyataan ini saya buat dengan sebenar-benarnya dengan penuh tanggung jawab. Apabila saya melanggar pasal-pasal dalam perjanjian diatas, maka saya bersedia menanggung semua tindakan yang diambil oleh LSP Edukasi Global Cendekia yang dapat berupa penundaan atau pencabutan sertifikat, pengumuman pelanggaran, dan jika perlu tindakan hukum lainnya.</p>

            <!-- Area tanda tangan -->
            <div class="row mt-4">
                <div class="col-md-6 offset-md-6 text-center">
                    <div class="small text-muted mb-1">................................., {{ formatTanggal(result.fr_ak_14_signed_at) }}</div>
                    <div class="small fw-semibold mb-1">Yang menyatakan,</div>
                    <div v-if="result.fr_ak_14_signed_at" style="height:80px; display:flex; align-items:center; justify-content:center">
                        <img :src="`/peserta/hasil/${sessionId}/${studentId}/fr-ak-14/tanda-tangan`" style="max-height:74px; max-width:100%; object-fit:contain">
                    </div>
                    <div v-else style="height:80px; border-bottom:1px solid #333; margin-bottom:4px"></div>
                    <div class="small">{{ namaPeserta }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form TTD (hanya jika belum tanda tangan) -->
    <div class="card border-0 shadow mb-4" v-if="!result.fr_ak_14_signed_at">
        <div class="card-header bg-gray-800 text-white fw-semibold">
            <i class="fa fa-pen me-2"></i>Tanda Tangan
        </div>
        <div class="card-body">
            <div class="d-flex gap-2 mb-3">
                <button type="button" class="btn btn-sm"
                    :class="sigMode === 'draw' ? 'btn-gray-800' : 'btn-light border'"
                    @click="switchMode('draw')">
                    <i class="fa fa-pen me-1"></i> Gambar TTD
                </button>
                <button type="button" class="btn btn-sm"
                    :class="sigMode === 'upload' ? 'btn-gray-800' : 'btn-light border'"
                    @click="switchMode('upload')">
                    <i class="fa fa-upload me-1"></i> Upload Gambar TTD
                </button>
            </div>

            <div v-show="sigMode === 'draw'">
                <div class="border rounded mb-2 bg-white" style="touch-action:none">
                    <canvas ref="sigCanvas" style="display:block; width:100%; height:180px; cursor:crosshair"></canvas>
                </div>
                <div class="d-flex gap-2 mb-1">
                    <button type="button" class="btn btn-sm btn-light border" @click="clearCanvas">
                        <i class="fa fa-eraser me-1"></i> Hapus
                    </button>
                    <button type="button" class="btn btn-sm btn-success" @click="saveDrawn" :disabled="saving">
                        <i class="fa fa-save me-1"></i>{{ saving ? 'Menyimpan...' : 'Simpan Tanda Tangan' }}
                    </button>
                </div>
            </div>

            <div v-show="sigMode === 'upload'">
                <div class="mb-2">
                    <label class="fw-semibold small">Pilih file gambar tanda tangan <span class="text-muted">(JPG / PNG, maks. 2 MB)</span></label>
                    <input type="file" class="form-control mt-1" accept="image/png,image/jpeg,image/jpg"
                        ref="sigFileInput" @change="onFileChange">
                </div>
                <div v-if="filePreview" class="mb-2">
                    <img :src="filePreview" alt="preview" style="max-height:100px; border:1px solid #ddd; background:#fff; padding:4px">
                </div>
                <button type="button" class="btn btn-sm btn-success mb-1" @click="saveUpload" :disabled="!sigFile || saving">
                    <i class="fa fa-save me-1"></i>{{ saving ? 'Menyimpan...' : 'Simpan Tanda Tangan' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Status materai (setelah tanda tangan) -->
    <div class="card border-0 shadow mb-5" v-else>
        <div class="card-body p-4 text-center">
            <template v-if="result.materai_status === 'stamped'">
                <i class="fa fa-check-circle text-success" style="font-size:3rem"></i>
                <h6 class="fw-bold mt-3">Materai Sudah Dibubuhkan</h6>
                <p class="text-muted small mb-3">
                    Dibubuhkan pada {{ formatDate(result.materai_stamped_at) }}. SK dan Sertifikat sudah bisa diunduh dari Dashboard.
                </p>
                <Link href="/peserta/dashboard" class="btn btn-success"><i class="fa fa-arrow-left me-1"></i>Kembali ke Dashboard</Link>
            </template>
            <template v-else-if="result.materai_status === 'failed'">
                <i class="fa fa-exclamation-triangle text-danger" style="font-size:3rem"></i>
                <h6 class="fw-bold mt-3">Pembubuhan Materai Gagal</h6>
                <p class="text-muted small mb-3">{{ result.materai_failure_reason }}</p>
                <button class="btn btn-primary px-4" :disabled="retrying" @click="retry">
                    <span v-if="retrying"><span class="spinner-border spinner-border-sm me-1"></span>Memproses...</span>
                    <span v-else><i class="fa fa-redo me-1"></i>Coba Lagi</span>
                </button>
            </template>
            <template v-else>
                <i class="fa fa-hourglass-half text-warning" style="font-size:3rem"></i>
                <h6 class="fw-bold mt-3">Materai Sedang Diproses</h6>
                <p class="text-muted small mb-0">Silakan cek kembali beberapa saat lagi.</p>
                <button class="btn btn-sm btn-light border mt-3" @click="reload">
                    <i class="fa fa-sync me-1"></i> Muat Ulang Status
                </button>
            </template>
        </div>
    </div>
</template>

<script>
import LayoutPeserta from '../../../Layouts/Peserta.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import SignaturePad from 'signature_pad';

export default {
    layout: LayoutPeserta,
    components: { Head, Link },
    props: {
        sessionId:    [Number, String],
        studentId:    [Number, String],
        namaPeserta:  String,
        nik:          String,
        namaSkema:    String,
        noSertifikat: String,
        result:       Object,
    },

    setup(props) {
        const sigMode     = ref('draw');
        const sigCanvas   = ref(null);
        const sigFile     = ref(null);
        const filePreview = ref(null);
        const saving      = ref(false);
        const retrying    = ref(false);
        let   signaturePad = null;
        let   resizeTimer  = null;

        const baseUrl = `/peserta/hasil/${props.sessionId}/${props.studentId}/fr-ak-14`;

        const initPad = () => {
            if (!sigCanvas.value) return;
            const canvas    = sigCanvas.value;
            const container = canvas.parentElement;
            const ratio     = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width    = (container?.clientWidth || canvas.offsetWidth) * ratio;
            canvas.height   = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            signaturePad    = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
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

        const switchMode = async (mode) => {
            sigMode.value = mode;
            if (mode === 'draw') { await nextTick(); initPad(); }
        };

        const clearCanvas = () => signaturePad?.clear();

        const saveDrawn = () => {
            if (!signaturePad || signaturePad.isEmpty()) {
                alert('Silakan buat tanda tangan terlebih dahulu.');
                return;
            }
            saving.value = true;
            router.post(baseUrl, { signature_data: signaturePad.toDataURL('image/png') }, {
                onFinish: () => { saving.value = false; },
            });
        };

        const onFileChange = (e) => {
            const file = e.target.files[0];
            if (!file) return;
            sigFile.value     = file;
            filePreview.value = URL.createObjectURL(file);
        };

        const saveUpload = () => {
            if (!sigFile.value) return;
            saving.value = true;
            const fd = new FormData();
            fd.append('signature_file', sigFile.value);
            router.post(baseUrl, fd, {
                forceFormData: true,
                onFinish: () => { saving.value = false; sigFile.value = null; filePreview.value = null; },
            });
        };

        const retry = () => {
            retrying.value = true;
            router.post(`${baseUrl}/retry`, {}, { onFinish: () => { retrying.value = false; } });
        };

        const reload = () => router.reload({ preserveScroll: true });

        const formatTanggal = (dt) => dt
            ? new Date(dt).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
            : '___________________';

        const formatDate = (dt) => dt
            ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
            : '—';

        return {
            sigMode, sigCanvas, sigFile, filePreview, saving, retrying,
            switchMode, clearCanvas, saveDrawn, onFileChange, saveUpload, retry, reload,
            formatTanggal, formatDate,
        };
    },
}
</script>
