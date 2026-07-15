<template>
    <Head>
        <title>FR.AK.01 - Pakta Integritas</title>
    </Head>

    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><Link href="/peserta/dashboard">Dashboard</Link></li>
                    <li class="breadcrumb-item"><Link :href="`/peserta/aplikasi/${application.id}/form`">FR.APL.01</Link></li>
                    <li class="breadcrumb-item active">FR.AK.01</li>
                </ol>
            </nav>
            <h5 class="fw-bold">FR.AK.01 — Pakta Integritas</h5>
            <p class="text-muted small">Baca seluruh dokumen, kemudian berikan tanda tangan di bagian bawah.</p>
        </div>
    </div>

    <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
        {{ $page.props.session.success }}
    </div>

    <!-- Dokumen FR.AK.01 -->
    <div class="card border-0 shadow mb-4">
        <div class="card-body px-4 py-4" style="font-size:0.88rem; line-height:1.8; color:#222">

            <!-- Header -->
            <div class="text-center mb-4">
                <div class="fw-bold" style="font-size:1rem">FR.AK.01</div>
                <div class="fw-bold text-uppercase" style="font-size:0.92rem; letter-spacing:.3px">
                    PERSETUJUAN ASESMEN, KETIDAKBERPIHAKAN, KERAHASIAAN DAN KEAMANAN SERTIFIKASI
                </div>
                <div class="mt-2 text-muted" style="font-size:0.82rem">
                    Persetujuan Asesmen ini untuk menjamin bahwa Asesi telah diberi arahan secara rinci tentang perencanaan dan proses asesmen
                </div>
            </div>

            <hr class="my-3">

            <!-- Info skema & sesi -->
            <table class="table table-borderless table-sm mb-3 table-wrap" style="font-size:0.88rem">
                <tr>
                    <td style="width:180px" class="fw-semibold align-top">Skema Sertifikasi</td>
                    <td style="width:10px" class="align-top">:</td>
                    <td>
                        <div>Judul &nbsp;&nbsp;&nbsp;: {{ application.classroom?.title ?? '—' }}</div>
                        <div>Nomor &nbsp;: {{ application.classroom?.kode_skema ?? '—' }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="fw-semibold">TUK</td>
                    <td>:</td>
                    <td>{{ application.tempat_ujian ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Nama Asesor</td>
                    <td>:</td>
                    <td class="text-muted fst-italic">—</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Nama Asesi</td>
                    <td>:</td>
                    <td>{{ participant.name }}</td>
                </tr>
            </table>

            <!-- Bukti yang dikumpulkan -->
            <div class="mb-3">
                <div class="fw-semibold mb-1">Bukti yang akan dikumpulkan :</div>
                <table class="table table-borderless table-sm mb-0 table-wrap" style="font-size:0.85rem">
                    <tr><td style="width:30px">☐</td><td><strong>TL</strong> : Verifikasi Portofolio</td></tr>
                    <tr><td>☐</td><td><strong>L</strong> &nbsp;: Observasi Langsung</td></tr>
                    <tr><td>☐</td><td><strong>T</strong> &nbsp;: Hasil Tes Tulis</td></tr>
                    <tr><td>☐</td><td><strong>T</strong> &nbsp;: Hasil Tes Lisan</td></tr>
                </table>
            </div>

            <!-- Pelaksanaan asesmen -->
            <div class="mb-4">
                <div class="fw-semibold mb-1">Pelaksanaan asesmen disepakati pada :</div>
                <table class="table table-borderless table-sm mb-0 table-wrap" style="font-size:0.85rem">
                    <tr>
                        <td style="width:140px">Hari / Tanggal</td>
                        <td style="width:10px">:</td>
                        <td>{{ formatTanggal(application.exam_session?.start_time) }}</td>
                    </tr>
                    <tr>
                        <td>Waktu</td>
                        <td>:</td>
                        <td>08.00 – 17.00 WIB</td>
                    </tr>
                    <tr>
                        <td>TUK</td>
                        <td>:</td>
                        <td>{{ application.tempat_ujian ?? '—' }}</td>
                    </tr>
                </table>
            </div>

            <hr class="my-3">

            <!-- LSP section -->
            <div class="fw-bold mb-2">LSP Edukasi Global Cendekia :</div>
            <ol style="padding-left:1.2rem">
                <li class="mb-2"><strong>Memastikan Tidak Ada Konflik Kepentingan</strong><br>
                    LSP dan pemohon menyatakan bahwa tidak ada kepentingan pribadi yang dapat memengaruhi hasil sertifikasi.
                </li>
                <li class="mb-2"><strong>Menjaga Objektivitas Proses Sertifikasi</strong>
                    <ol type="1" style="padding-left:1.2rem">
                        <li>LSP Edukasi Global Cendekia berjanji untuk menerapkan standar dan kriteria yang konsisten bagi semua pemohon.</li>
                        <li>Evaluasi akan dilakukan sesuai prosedur tanpa mempertimbangkan afiliasi, status sosial, atau hubungan pribadi pemohon.</li>
                    </ol>
                </li>
                <li class="mb-2"><strong>Menjaga Kerahasiaan Data Pribadi dan Dokumen</strong><br>
                    LSP tidak akan mengungkapkan data pemohon kepada pihak ketiga tanpa izin tertulis dari pemohon, kecuali untuk keperluan audit internal atau regulator yang sah.
                </li>
                <li class="mb-2"><strong>Pengelolaan Data Sertifikasi</strong><br>
                    Data hasil sertifikasi akan disimpan dengan pengamanan sesuai standar dan hanya akan diakses oleh pihak berwenang.
                </li>
                <li class="mb-2"><strong>Perlindungan Informasi Sensitif</strong><br>
                    LSP akan menggunakan sistem pengamanan fisik dan digital untuk melindungi data pemohon.
                </li>
                <li class="mb-2"><strong>Penggunaan Informasi</strong><br>
                    Informasi yang dikumpulkan hanya akan digunakan untuk tujuan sertifikasi.
                </li>
                <li class="mb-2"><strong>Durasi Perjanjian</strong><br>
                    Perjanjian ini berlaku selama proses sertifikasi dan tiga tahun setelah proses selesai. Ketentuan tentang kerahasiaan tetap berlaku tanpa batas waktu kecuali disepakati lain.
                </li>
                <li class="mb-2"><strong>Penyelesaian Sengketa</strong><br>
                    Setiap banding dan keluhan yang muncul akan diselesaikan melalui investigasi proses asesmen dan mediasi terlebih dahulu. Jika mediasi gagal, masalah akan dibawa ke badan hukum yang berwenang.
                </li>
            </ol>

            <hr class="my-3">

            <!-- Asesor section -->
            <div class="fw-bold mb-2">Asesor :</div>
            <ol style="padding-left:1.2rem">
                <li class="mb-2"><strong>Menjalankan Asesmen Secara Objektif</strong><br>
                    Asesor akan menilai pemohon secara adil, menggunakan kriteria yang objektif dan berdasarkan standar kompetensi yang berlaku.
                </li>
                <li class="mb-2"><strong>Menghindari Konflik Kepentingan</strong><br>
                    Jika terdapat potensi konflik kepentingan yang dapat memengaruhi objektivitas asesmen, asesor wajib melaporkannya ke Lembaga Sertifikasi Person (LSP) dan menolak untuk melanjutkan proses asesmen.
                </li>
                <li class="mb-2"><strong>Perlindungan Informasi Pribadi dan Data Sertifikasi</strong><br>
                    Asesor akan menjaga kerahasiaan semua informasi yang diterima dari pemohon dan tidak akan membagikannya kepada pihak ketiga tanpa izin tertulis dari pemohon, kecuali jika diharuskan oleh hukum atau kebijakan LSP.
                </li>
                <li class="mb-2"><strong>Penggunaan Data Sertifikasi</strong><br>
                    Data sertifikasi hanya akan digunakan untuk tujuan penilaian dan tidak untuk kepentingan pribadi atau pihak ketiga yang tidak berkepentingan.
                </li>
                <li class="mb-2"><strong>Keamanan Data dan Dokumen</strong><br>
                    Asesor akan menyimpan dokumen yang berhubungan dengan asesmen secara aman dan hanya mengaksesnya jika diperlukan untuk keperluan sertifikasi.
                </li>
            </ol>

            <hr class="my-3">

            <!-- Asesi section -->
            <div class="fw-bold mb-2">Asesi :</div>
            <ol style="padding-left:1.2rem">
                <li class="mb-2"><strong>Mengikuti Kegiatan Asesmen Berdasarkan Standar LSP Edukasi Global Cendekia</strong>
                    <ol type="1" style="padding-left:1.2rem">
                        <li>Saya setuju mengikuti asesmen dengan pemahaman bahwa informasi yang dikumpulkan hanya digunakan untuk pengembangan profesional dan hanya dapat diakses oleh orang tertentu saja.</li>
                        <li>Saya menyatakan dengan sungguh-sungguh bahwa saya akan merahasiakan semua dokumen dan/atau informasi dalam bentuk apapun sehubungan proses asesmen.</li>
                        <li>Saya menyatakan tidak menggunakan Informasi Rahasia untuk tujuan pribadi atau komersial, termasuk tetapi tidak terbatas pada:
                            <ul style="padding-left:1.2rem">
                                <li>Membocorkan kepada pihak ketiga yang tidak berwenang.</li>
                                <li>Menggandakan, menyalin, atau menyebarkan Informasi Rahasia.</li>
                                <li>Menggunakan Informasi Rahasia untuk mendapatkan keuntungan pribadi.</li>
                            </ul>
                        </li>
                        <li>Pelanggaran atas pernyataan saya di atas, saya bersedia diberi dan tunduk kepada sanksi apapun sesuai ketetapan Lembaga Sertifikasi Person Edukasi Global Cendekia.</li>
                    </ol>
                </li>
                <li class="mb-2"><strong>Memenuhi Standar Kompetensi yang Berlaku</strong>
                    <ol type="1" style="padding-left:1.2rem">
                        <li>Saya akan terus menjaga dan meningkatkan keterampilan serta pengetahuan yang relevan untuk memastikan pemenuhan standar kompetensi sesuai sertifikat yang diterima.</li>
                        <li>Saya menyatakan mematuhi segala aturan dan prosedur yang terkait dengan pemeliharaan kompetensi sebagaimana diatur oleh LSP.</li>
                    </ol>
                </li>
                <li class="mb-2"><strong>Memberikan Informasi Kendala dan Hambatan kepada LSP Edukasi Global Cendekia</strong>
                    <ol type="1" style="padding-left:1.2rem">
                        <li>Saya akan segera menginformasikan kepada LSP Edukasi Global Cendekia apabila mengalami kendala yang dapat memengaruhi kemampuan saya dalam memenuhi standar kompetensi yang berlaku.</li>
                        <li>Laporan mencakup rincian hambatan, alasan, dan perkiraan dampaknya terhadap pemenuhan kompetensi.</li>
                    </ol>
                </li>
                <li class="mb-2"><strong>Menyerahkan Bukti Pendukung Kendala dan Hambatan</strong><br>
                    Saya akan menyediakan dokumen atau bukti pendukung terkait hambatan yang dihadapi.
                </li>
                <li class="mb-2"><strong>Evaluasi Ulang Status Sertifikasi</strong><br>
                    LSP Edukasi Global Cendekia berhak melakukan evaluasi ulang status sertifikasi jika hambatan pemenuhan kompetensi tidak dapat diatasi atau jika saya tidak melaporkan kendala yang ada.
                </li>
                <li class="mb-2"><strong>Pencabutan atau Penangguhan Sertifikasi</strong><br>
                    Jika saya tidak dapat lagi memenuhi kompetensi yang diwajibkan dalam jangka waktu yang ditentukan, sertifikat kompetensi dapat ditangguhkan atau dicabut berdasarkan keputusan LSP Edukasi Global Cendekia.
                </li>
            </ol>

            <hr class="my-3">

            <p>Dengan menandatangani formulir ini, LSP Edukasi Global Cendekia, Asesor dan Pemohon menyatakan setuju untuk mematuhi ketentuan ketidakberpihakan, kerahasiaan, dan keamanan yang disebutkan di atas dan untuk menjaga integritas proses sertifikasi.</p>

            <!-- Area tanda tangan -->
            <div class="row mt-4">
                <!-- TTD LSP -->
                <div class="col-md-4 text-center">
                    <div class="small fw-semibold mb-1">Tanda tangan LSP Edukasi Global Cendekia</div>
                    <div style="height:80px; border-bottom:1px solid #333; margin-bottom:4px"></div>
                    <div class="small text-muted">Tanggal : ___________________</div>
                </div>
                <!-- TTD Asesor -->
                <div class="col-md-4 text-center">
                    <div class="small fw-semibold mb-1">Tanda tangan Asesor</div>
                    <div style="height:80px; border-bottom:1px solid #333; margin-bottom:4px"></div>
                    <div class="small text-muted">Tanggal : ___________________</div>
                </div>
                <!-- TTD Asesi -->
                <div class="col-md-4 text-center">
                    <div class="small fw-semibold mb-1">Tanda tangan Asesi</div>
                    <div v-if="application.signature_path" style="height:80px; display:flex; align-items:center; justify-content:center">
                        <img :src="`/peserta/aplikasi/${application.id}/tanda-tangan/pakta`" style="max-height:74px; max-width:100%; object-fit:contain">
                    </div>
                    <div v-else style="height:80px; border-bottom:1px solid #333; margin-bottom:4px"></div>
                    <div class="small text-muted">Tanggal : {{ formatTanggal(application.pakta_signed_at) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form TTD (hanya jika masih draft) -->
    <div class="card border-0 shadow mb-4" v-if="application.status === 'draft'">
        <div class="card-header bg-gray-800 text-white fw-semibold">
            <i class="fa fa-pen me-2"></i>Tanda Tangan Asesi
            <span v-if="application.pakta_signed_at" class="badge bg-success ms-2">
                <i class="fa fa-check me-1"></i>Sudah Ditandatangani
            </span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Dengan menandatangani di bawah ini, Anda menyatakan telah membaca dan menyetujui seluruh isi dokumen FR.AK.01 di atas.
            </p>

            <!-- Pratinjau TTD tersimpan -->
            <div v-if="application.signature_path" class="mb-3 p-2 border rounded d-flex align-items-center gap-3">
                <img :src="`/peserta/aplikasi/${application.id}/tanda-tangan/pakta`" alt="TTD"
                    style="max-height:80px; max-width:240px; object-fit:contain; border:1px solid #ddd; background:#fff; padding:4px">
                <div>
                    <span class="badge bg-success mb-1"><i class="fa fa-check me-1"></i>TTD Tersimpan</span>
                    <div class="small text-muted">Anda dapat memperbarui tanda tangan.</div>
                </div>
            </div>

            <!-- Toggle mode -->
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

            <!-- Mode: Gambar -->
            <div v-show="sigMode === 'draw'">
                <div class="border rounded mb-2 bg-white" style="touch-action:none">
                    <canvas ref="sigCanvas" style="display:block; width:100%; height:180px; cursor:crosshair"></canvas>
                </div>
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-light border" @click="clearCanvas">
                        <i class="fa fa-eraser me-1"></i> Hapus
                    </button>
                    <button type="button" class="btn btn-sm btn-success" @click="saveDrawn" :disabled="saving">
                        <i class="fa fa-save me-1"></i>{{ saving ? 'Menyimpan...' : 'Simpan & Lanjut ke Dokumen' }}
                    </button>
                </div>
            </div>

            <!-- Mode: Upload -->
            <div v-show="sigMode === 'upload'">
                <div class="mb-2">
                    <label class="fw-semibold small">Pilih file gambar tanda tangan <span class="text-muted">(JPG / PNG, maks. 2 MB)</span></label>
                    <input type="file" class="form-control mt-1" accept="image/png,image/jpeg,image/jpg"
                        ref="sigFileInput" @change="onFileChange">
                </div>
                <div v-if="filePreview" class="mb-2">
                    <img :src="filePreview" alt="preview" style="max-height:100px; border:1px solid #ddd; background:#fff; padding:4px">
                </div>
                <button type="button" class="btn btn-sm btn-success mb-3" @click="saveUpload" :disabled="!sigFile || saving">
                    <i class="fa fa-save me-1"></i>{{ saving ? 'Menyimpan...' : 'Simpan & Lanjut ke Dokumen' }}
                </button>
            </div>

            <!-- Sudah TTD: tombol lanjut -->
            <div v-if="application.pakta_signed_at && !saving" class="mt-1">
                <Link :href="`/peserta/aplikasi/${application.id}/dokumen`" class="btn btn-success">
                    <i class="fa fa-arrow-right me-1"></i> Lanjut ke Upload Dokumen
                </Link>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-5">
        <Link :href="`/peserta/aplikasi/${application.id}/form`" class="btn btn-light border">
            <i class="fa fa-arrow-left me-1"></i> Kembali ke Formulir
        </Link>
        <Link v-if="application.pakta_signed_at" :href="`/peserta/aplikasi/${application.id}/dokumen`" class="btn btn-success">
            <i class="fa fa-arrow-right me-1"></i> Lanjut ke Dokumen
        </Link>
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
        application: Object,
        participant: Object,
    },

    setup(props) {
        const sigMode    = ref('draw');
        const sigCanvas  = ref(null);
        const sigFile    = ref(null);
        const filePreview = ref(null);
        const saving     = ref(false);
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

        const switchMode = async (mode) => {
            sigMode.value = mode;
            if (mode === 'draw') {
                await nextTick();
                initPad();
            }
        };

        const clearCanvas = () => signaturePad?.clear();

        const saveDrawn = () => {
            if (!signaturePad || signaturePad.isEmpty()) {
                alert('Silakan buat tanda tangan terlebih dahulu.');
                return;
            }
            saving.value = true;
            router.post(`/peserta/aplikasi/${props.application.id}/pakta`,
                { signature_data: signaturePad.toDataURL('image/png') },
                { onFinish: () => { saving.value = false; } }
            );
        };

        const onFileChange = (e) => {
            const file = e.target.files[0];
            if (!file) return;
            sigFile.value    = file;
            filePreview.value = URL.createObjectURL(file);
        };

        const saveUpload = () => {
            if (!sigFile.value) return;
            saving.value = true;
            const fd = new FormData();
            fd.append('signature_file', sigFile.value);
            router.post(`/peserta/aplikasi/${props.application.id}/pakta`, fd, {
                forceFormData: true,
                onFinish: () => { saving.value = false; sigFile.value = null; filePreview.value = null; },
            });
        };

        const formatTanggal = (dt) => {
            if (!dt) return '___________________';
            return new Date(dt).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        };

        return {
            sigMode, sigCanvas, sigFile, filePreview, saving,
            switchMode, clearCanvas, saveDrawn, onFileChange, saveUpload,
            formatTanggal,
        };
    },
}
</script>
