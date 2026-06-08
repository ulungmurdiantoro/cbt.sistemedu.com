<template>
    <Head>
        <title>Panduan Pendaftaran Sertifikasi - Portal Peserta</title>
    </Head>

    <div class="pg-wrap">
        <!-- Topbar -->
        <div class="pg-top">
            <Link href="/peserta/login" class="brand">
                <img src="/assets/images/logo.png" alt="EDUKIA" />
            </Link>
            <div class="pg-top-actions">
                <Link href="/peserta/login" class="pg-link">Masuk</Link>
                <Link href="/peserta/register" class="pg-btn pg-btn-accent">
                    <i class="fa fa-user-plus me-1"></i> Daftar
                </Link>
            </div>
        </div>

        <div class="pg-container">
            <!-- HERO -->
            <div class="pg-hero">
                <span class="pg-eyebrow">Portal Peserta · LSP Edukasi Global Cendekia</span>
                <h1>Panduan Pendaftaran Sertifikasi</h1>
                <p>Ikuti langkah berikut untuk mendaftar asesmen kompetensi — dari membuat akun sampai
                    mengunduh sertifikat. Seluruh proses dilakukan daring melalui portal ini.</p>
                <div class="pg-hero-cta">
                    <Link href="/peserta/register" class="pg-btn pg-btn-accent lg">
                        <i class="fa fa-user-plus me-1"></i> Mulai Pendaftaran
                    </Link>
                    <a href="#langkah" class="pg-btn pg-btn-ghost lg">
                        <i class="fa fa-list-ol me-1"></i> Lihat Langkah
                    </a>
                </div>
            </div>

            <!-- SIAPKAN DULU -->
            <section>
                <div class="pg-sechead"><span class="num">01</span><h2>Yang Perlu Disiapkan</h2></div>
                <p class="pg-lead">Siapkan hal-hal ini sebelum mulai agar pengisian berjalan lancar tanpa terputus.</p>
                <div class="pg-grid g3">
                    <div class="pg-prep" v-for="p in prep" :key="p.title">
                        <div class="pg-ico" :class="p.tone"><i :class="p.icon"></i></div>
                        <div>
                            <div class="t">{{ p.title }}</div>
                            <div class="d">{{ p.desc }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- LANGKAH -->
            <section id="langkah">
                <div class="pg-sechead"><span class="num">02</span><h2>Langkah Pendaftaran</h2></div>
                <p class="pg-lead">Sepuluh langkah, berurutan. Permohonan Anda berstatus <strong>Draft</strong> dan bisa
                    dilanjutkan kapan saja sampai Anda menekan <strong>Submit</strong>.</p>

                <div class="pg-timeline">
                    <div class="pg-step" v-for="(s, i) in steps" :key="i">
                        <div class="pg-step-rail">
                            <div class="pg-step-num" :class="s.phase">{{ i + 1 }}</div>
                            <div v-if="i < steps.length - 1" class="pg-step-line"></div>
                        </div>
                        <div class="pg-step-card">
                            <div class="pg-step-head">
                                <i :class="s.icon"></i>
                                <h3>{{ s.title }}</h3>
                                <span v-if="s.tag" class="pg-tag" :class="s.tagTone">{{ s.tag }}</span>
                            </div>
                            <p class="pg-step-body">{{ s.body }}</p>
                            <ul v-if="s.points" class="pg-step-points">
                                <li v-for="(pt, j) in s.points" :key="j"><i class="fa fa-check"></i><span>{{ pt }}</span></li>
                            </ul>
                            <div v-if="s.result" class="pg-step-result">
                                <i class="fa fa-arrow-right"></i> {{ s.result }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- DOKUMEN & DATA -->
            <section>
                <div class="pg-sechead"><span class="num">03</span><h2>Data &amp; Dokumen Wajib</h2></div>
                <p class="pg-lead">Permohonan hanya bisa di-<em>submit</em> bila data wajib terisi, tanda tangan lengkap,
                    dan seluruh dokumen wajib (bertanda <span class="req">*</span>) terunggah.</p>
                <div class="pg-grid g2">
                    <div class="pg-card">
                        <h4><i class="fa fa-id-card"></i> Data wajib (FR.APL.01)</h4>
                        <ul class="pg-checklist">
                            <li v-for="d in dataWajib" :key="d"><i class="fa fa-check-circle"></i>{{ d }}</li>
                        </ul>
                    </div>
                    <div class="pg-card">
                        <h4><i class="fa fa-folder-open"></i> Dokumen pendukung</h4>
                        <ul class="pg-checklist">
                            <li v-for="d in dokumen" :key="d"><i class="fa fa-file"></i>{{ d }}</li>
                        </ul>
                        <div class="pg-hint">
                            <i class="fa fa-circle-info"></i>
                            Format <strong>PDF / JPG / PNG</strong>, maksimal <strong>5&nbsp;MB</strong> per file.
                            Daftar dokumen persis mengikuti skema yang Anda pilih.
                        </div>
                    </div>
                </div>
            </section>

            <!-- STATUS -->
            <section>
                <div class="pg-sechead"><span class="num">04</span><h2>Arti Status Permohonan</h2></div>
                <p class="pg-lead">Pantau status di Dashboard. Tiap status menentukan langkah Anda berikutnya.</p>
                <div class="pg-statuses">
                    <div class="pg-status" v-for="st in statuses" :key="st.label">
                        <span class="pg-badge" :class="st.tone">{{ st.label }}</span>
                        <span class="pg-status-desc">{{ st.desc }}</span>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section>
                <div class="pg-sechead"><span class="num">05</span><h2>Pertanyaan Umum</h2></div>
                <div class="pg-faq">
                    <details v-for="(f, i) in faq" :key="i" :open="i === 0">
                        <summary>{{ f.q }}</summary>
                        <p>{{ f.a }}</p>
                    </details>
                </div>
            </section>

            <!-- CTA penutup -->
            <div class="pg-final">
                <h3>Siap mendaftar?</h3>
                <p>Buat akun peserta sekarang, lalu ikuti langkah 1–7 untuk mengirim permohonan Anda.</p>
                <div class="pg-final-cta">
                    <Link href="/peserta/register" class="pg-btn pg-btn-light lg">
                        <i class="fa fa-user-plus me-1"></i> Daftar Sekarang
                    </Link>
                    <Link href="/peserta/login" class="pg-btn pg-btn-outline-light lg">
                        <i class="fa fa-sign-in-alt me-1"></i> Sudah punya akun? Masuk
                    </Link>
                </div>
            </div>

            <p class="pg-foot">
                Butuh bantuan? Hubungi panitia LSP Edukasi Global Cendekia. Untuk mengikuti ujian, gunakan
                halaman <Link href="/">login ujian</Link> dengan No. Peserta.
            </p>
        </div>
    </div>
</template>

<script>
import LayoutAuth from '../../../Layouts/Auth.vue';
import { Head, Link } from '@inertiajs/vue3';

export default {
    layout: LayoutAuth,
    components: { Head, Link },

    data() {
        return {
            prep: [
                { title: 'Email aktif', desc: 'Untuk membuat akun & menerima notifikasi permohonan.', icon: 'fa fa-envelope', tone: 'accent' },
                { title: 'Data diri & NIK', desc: 'KTP, tempat/tanggal lahir, pendidikan, jabatan, dan institusi.', icon: 'fa fa-id-card', tone: 'info' },
                { title: 'Tanda tangan', desc: 'Tanda tangan langsung di layar atau unggah gambar (JPG/PNG).', icon: 'fa fa-signature', tone: 'secondary' },
                { title: 'Dokumen scan', desc: 'Berkas persyaratan dalam PDF/JPG/PNG, maks. 5 MB per file.', icon: 'fa fa-folder-open', tone: 'success' },
                { title: 'Skema & sesi', desc: 'Ketahui skema sertifikasi dan jadwal sesi yang ingin diikuti.', icon: 'fa fa-layer-group', tone: 'tertiary' },
                { title: 'Perangkat & internet', desc: 'Komputer/HP dengan koneksi stabil untuk mengunggah berkas.', icon: 'fa fa-wifi', tone: 'danger' },
            ],

            steps: [
                {
                    phase: 'a', icon: 'fa fa-user-plus', title: 'Buat akun peserta', tag: 'Daftar', tagTone: 'accent',
                    body: 'Buka halaman Daftar, lalu isi nama, email, dan password. Akun ini khusus untuk pendaftaran sertifikasi (berbeda dari login ujian).',
                    result: 'Akun peserta aktif dan siap digunakan untuk login.',
                },
                {
                    phase: 'a', icon: 'fa fa-sign-in-alt', title: 'Masuk ke portal', tag: 'Login', tagTone: 'accent',
                    body: 'Login dengan email dan password Anda untuk masuk ke Dashboard Peserta.',
                    result: 'Anda berada di Dashboard "Permohonan Sertifikasi Saya".',
                },
                {
                    phase: 'a', icon: 'fa fa-layer-group', title: 'Pilih skema & sesi', tag: 'Tambah Skema', tagTone: 'accent',
                    body: 'Klik "Tambah Skema", pilih sesi asesmen yang sedang aktif (dikelompokkan per skema), lalu tentukan tujuan asesmen.',
                    points: ['Konteks asesmen, tempat ujian, dan kode batch mengikuti sesi yang dipilih.', 'Satu sesi hanya bisa didaftar satu kali.'],
                    result: 'Permohonan baru dibuat berstatus Draft dengan kode FR.APL.01.',
                },
                {
                    phase: 'b', icon: 'fa fa-file-signature', title: 'Isi Formulir FR.APL.01', tag: 'Bagian 1', tagTone: 'neutral',
                    body: 'Lengkapi data pribadi dan data pekerjaan, lalu bubuhkan tanda tangan pada formulir (gambar di layar atau unggah file).',
                    result: 'Data tersimpan & tanda tangan formulir terekam.',
                },
                {
                    phase: 'b', icon: 'fa fa-handshake', title: 'Tandatangani Pakta Integritas (FR.AK.01)', tag: 'Bagian 2', tagTone: 'neutral',
                    body: 'Baca pernyataan pakta integritas dengan saksama, lalu tandatangani sebagai persetujuan menjalani asesmen secara jujur.',
                    result: 'Pakta integritas tertandatangani.',
                },
                {
                    phase: 'b', icon: 'fa fa-cloud-upload-alt', title: 'Unggah dokumen persyaratan', tag: 'Bagian 3', tagTone: 'neutral',
                    body: 'Unggah seluruh dokumen wajib sesuai daftar persyaratan skema. Selama berstatus Draft, dokumen masih bisa diganti atau dihapus.',
                    points: ['PDF / JPG / PNG, maksimal 5 MB per file.', 'Dokumen bertanda * wajib diunggah.'],
                    result: 'Berkas tersimpan dengan status menunggu pemeriksaan.',
                },
                {
                    phase: 'b', icon: 'fa fa-paper-plane', title: 'Submit permohonan', tag: 'Kirim', tagTone: 'success',
                    body: 'Setelah semua lengkap, tekan Submit. Sistem memeriksa kelengkapan data wajib, tanda tangan formulir & pakta, serta dokumen wajib.',
                    result: 'Status berubah menjadi Disubmit dan email konfirmasi dikirim.',
                },
                {
                    phase: 'c', icon: 'fa fa-user-shield', title: 'Verifikasi oleh admin', tag: 'Proses', tagTone: 'secondary',
                    body: 'Admin LSP memeriksa berkas Anda. Bila ada kekurangan, permohonan dapat ditolak disertai catatan — gunakan tombol "Revisi & Submit Ulang" untuk memperbaiki.',
                    result: 'Status menjadi Disetujui (mendapat No. Peserta Ujian) atau Ditolak.',
                },
                {
                    phase: 'c', icon: 'fa fa-laptop', title: 'Ikuti ujian', tag: 'Ujian', tagTone: 'accent',
                    body: 'Setelah disetujui, Anda menerima No. Peserta Ujian. Pada jadwal sesi, login di halaman ujian memakai No. Peserta tersebut.',
                    result: 'Anda mengerjakan ujian (Pilihan Ganda / Esai) sesuai skema.',
                },
                {
                    phase: 'c', icon: 'fa fa-certificate', title: 'Hasil & sertifikat', tag: 'Selesai', tagTone: 'success',
                    body: 'Setelah hasil difinalisasi, keputusan Kompeten / Belum Kompeten muncul di Dashboard. Unduh SK dan sertifikat bila lulus.',
                    points: ['Belum kompeten? Ikuti remidi bila window remidi dibuka (1 kali kesempatan).'],
                    result: 'Sertifikat kompetensi siap diunduh.',
                },
            ],

            dataWajib: [
                'NIK (sesuai KTP)',
                'Tempat & tanggal lahir',
                'Jenis kelamin',
                'Nomor HP aktif',
                'Kualifikasi pendidikan',
                'Institusi / perusahaan',
                'Jabatan',
            ],

            dokumen: [
                'Identitas diri (mis. KTP)',
                'Pas foto',
                'Ijazah / bukti pendidikan',
                'Bukti pengalaman kerja / portofolio',
                'Dokumen lain sesuai skema',
            ],

            statuses: [
                { label: 'Draft', tone: 'neutral', desc: 'Permohonan dibuat namun belum dikirim. Masih bisa diisi & diubah.' },
                { label: 'Disubmit', tone: 'secondary', desc: 'Sudah dikirim, sedang menunggu verifikasi admin.' },
                { label: 'Disetujui', tone: 'success', desc: 'Diterima. Anda memperoleh No. Peserta untuk ujian.' },
                { label: 'Ditolak', tone: 'danger', desc: 'Ada kekurangan. Baca catatan, lakukan revisi, lalu submit ulang.' },
            ],

            faq: [
                { q: 'Apa beda akun pendaftaran dan login ujian?', a: 'Akun pendaftaran (email + password) dipakai untuk mengurus permohonan di portal ini. Saat ujian, Anda login terpisah menggunakan No. Peserta yang terbit setelah permohonan disetujui.' },
                { q: 'Apakah harus selesai sekali duduk?', a: 'Tidak. Selama berstatus Draft, Anda bisa keluar dan melanjutkan kapan saja. Data, tanda tangan, dan dokumen yang sudah tersimpan tetap ada.' },
                { q: 'Bisakah mengganti dokumen yang salah?', a: 'Bisa, selama permohonan masih Draft. Setelah Submit, dokumen terkunci sampai admin memverifikasi.' },
                { q: 'Permohonan saya ditolak, lalu bagaimana?', a: 'Buka kembali permohonan, baca alasan penolakan dari admin, tekan "Revisi & Submit Ulang", perbaiki, lalu kirim lagi. Berkas lama tetap tersimpan.' },
                { q: 'Bagaimana cara tanda tangan?', a: 'Anda dapat menggambar tanda tangan langsung di layar (mouse/jari) atau mengunggah foto tanda tangan dalam format JPG/PNG (maks. 2 MB).' },
                { q: 'Saya belum lulus, apakah ada kesempatan lain?', a: 'Jika belum kompeten dan panitia membuka window remidi, tombol "Ikut Remidi" akan muncul di Dashboard. Remidi hanya mengulang ujian tulis dan tersedia 1 kali.' },
            ],
        };
    },
}
</script>

<style scoped>
.pg-wrap {
    /* token lokal — palet EDUKIA/Volt */
    --c-primary: #1F2937;
    --c-info: #2361CE;
    --c-success: #10B981;
    --c-danger: #E11D48;
    --c-secondary: #F0BC74;
    --c-tertiary: #31316A;
    --edukia-blue: #449FE5;
    --edukia-orange: #F4891F;
    --surface-page: #F2F4F6;
    --surface-card: #FFFFFF;
    --text-heading: #1F2937;
    --text-body: #374151;
    --text-muted: #6B7280;
    --border-subtle: #E5E7EB;
    --border-default: #D1D5DB;
    --radius: 0.5rem;
    --radius-sm: 0.35rem;
    --shadow: 0 1px 3px 0 rgba(0,0,0,.10), 0 1px 2px 0 rgba(0,0,0,.06);
    --shadow-card: 0 10px 15px -3px rgba(0,0,0,.10), 0 4px 6px -2px rgba(0,0,0,.05);

    min-height: 100vh;
    background: var(--surface-page);
    color: var(--text-body);
    padding-bottom: 64px;
}

/* Topbar */
.pg-top { position: sticky; top: 0; z-index: 30; display: flex; align-items: center; justify-content: space-between; gap: 14px; background: var(--c-primary); padding: 10px 22px; box-shadow: var(--shadow); }
.pg-top .brand img { height: 40px; object-fit: contain; }
.pg-top-actions { display: flex; align-items: center; gap: 10px; }
.pg-link { color: rgba(255,255,255,.85); text-decoration: none; font-weight: 600; font-size: 14px; padding: 6px 10px; }
.pg-link:hover { color: #fff; }

.pg-container { max-width: 920px; margin: 0 auto; padding: 0 20px; }

/* Buttons */
.pg-btn { display: inline-flex; align-items: center; justify-content: center; gap: 4px; font-weight: 600; font-size: 14px; padding: 9px 16px; border-radius: var(--radius); text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: all .15s ease-in-out; white-space: nowrap; }
.pg-btn.lg { padding: 12px 22px; font-size: 15px; }
.pg-btn-accent { background: var(--c-info); color: #fff; }
.pg-btn-accent:hover { background: #1F57B8; color: #fff; }
.pg-btn-ghost { background: transparent; color: var(--text-heading); border-color: var(--border-default); }
.pg-btn-ghost:hover { background: #fff; }
.pg-btn-light { background: #fff; color: var(--c-primary); }
.pg-btn-light:hover { background: #f1f3f6; }
.pg-btn-outline-light { background: transparent; color: #fff; border-color: rgba(255,255,255,.45); }
.pg-btn-outline-light:hover { background: rgba(255,255,255,.1); color: #fff; }

/* Hero */
.pg-hero { padding: 44px 0 30px; text-align: center; }
.pg-eyebrow { display: inline-block; font-size: 13px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--c-info); margin-bottom: 12px; }
.pg-hero h1 { font-size: 2.2rem; font-weight: 700; color: var(--text-heading); margin: 0 0 12px; line-height: 1.15; }
.pg-hero p { font-size: 1.05rem; color: var(--text-muted); max-width: 640px; margin: 0 auto; line-height: 1.6; }
.pg-hero-cta { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-top: 24px; }

/* Section */
section { margin-top: 48px; scroll-margin-top: 70px; }
.pg-sechead { display: flex; align-items: baseline; gap: 12px; margin-bottom: 8px; }
.pg-sechead .num { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-size: 14px; color: var(--c-info); font-weight: 700; }
.pg-sechead h2 { font-size: 1.45rem; font-weight: 700; color: var(--text-heading); margin: 0; }
.pg-lead { color: var(--text-muted); margin: 0 0 20px; line-height: 1.6; }
.pg-lead .req, .req { color: var(--c-danger); font-weight: 700; }

/* Grids */
.pg-grid { display: grid; gap: 14px; }
.pg-grid.g3 { grid-template-columns: repeat(3, 1fr); }
.pg-grid.g2 { grid-template-columns: repeat(2, 1fr); }
@media (max-width: 760px) { .pg-grid.g3, .pg-grid.g2 { grid-template-columns: 1fr; } }

/* Prep cards */
.pg-prep { display: flex; gap: 12px; align-items: flex-start; background: var(--surface-card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 16px; }
.pg-prep .t { font-weight: 700; color: var(--text-heading); font-size: 14px; }
.pg-prep .d { font-size: 12.5px; color: var(--text-muted); line-height: 1.45; margin-top: 2px; }
.pg-ico { width: 38px; height: 38px; flex-shrink: 0; border-radius: var(--radius-sm); display: grid; place-items: center; font-size: 16px; }
.pg-ico.accent { background: rgba(35,97,206,.14); color: #1c4ea3; }
.pg-ico.info { background: rgba(68,159,229,.16); color: #1f6fb0; }
.pg-ico.secondary { background: rgba(240,188,116,.30); color: #6b4a16; }
.pg-ico.success { background: rgba(16,185,129,.16); color: #0a6b4a; }
.pg-ico.danger { background: rgba(225,29,72,.14); color: #a01233; }
.pg-ico.tertiary { background: rgba(49,49,106,.16); color: #31316A; }

/* Timeline */
.pg-timeline { margin-top: 6px; }
.pg-step { display: grid; grid-template-columns: 44px 1fr; gap: 14px; }
.pg-step-rail { display: flex; flex-direction: column; align-items: center; }
.pg-step-num { width: 36px; height: 36px; flex-shrink: 0; border-radius: 50%; display: grid; place-items: center; font-weight: 700; font-size: 15px; color: #fff; box-shadow: var(--shadow); }
.pg-step-num.a { background: var(--c-info); }
.pg-step-num.b { background: var(--c-primary); }
.pg-step-num.c { background: var(--c-tertiary); }
.pg-step-line { flex: 1; width: 2px; background: var(--border-default); margin: 4px 0; min-height: 18px; }
.pg-step-card { background: var(--surface-card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 16px 18px; margin-bottom: 16px; }
.pg-step-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.pg-step-head i { color: var(--c-info); width: 18px; text-align: center; }
.pg-step-head h3 { font-size: 1.02rem; font-weight: 700; color: var(--text-heading); margin: 0; flex: 1; min-width: 60%; }
.pg-step-body { font-size: 14px; color: var(--text-body); line-height: 1.6; margin: 10px 0 0; }
.pg-step-points { list-style: none; margin: 10px 0 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.pg-step-points li { display: flex; gap: 8px; font-size: 13px; color: var(--text-muted); }
.pg-step-points li i { color: var(--c-success); margin-top: 3px; font-size: 11px; }
.pg-step-result { margin-top: 12px; font-size: 13px; font-weight: 600; color: #0a6b4a; background: rgba(16,185,129,.10); border-radius: var(--radius-sm); padding: 8px 12px; }
.pg-step-result i { font-size: 11px; margin-right: 4px; }

/* Tags (soft pills) */
.pg-tag, .pg-badge { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 50rem; white-space: nowrap; }
.pg-tag.accent, .pg-badge.accent { background: rgba(35,97,206,.14); color: #1c4ea3; }
.pg-tag.secondary, .pg-badge.secondary { background: rgba(240,188,116,.30); color: #6b4a16; }
.pg-tag.success, .pg-badge.success { background: rgba(16,185,129,.16); color: #0a6b4a; }
.pg-tag.danger, .pg-badge.danger { background: rgba(225,29,72,.14); color: #a01233; }
.pg-tag.neutral, .pg-badge.neutral { background: #E5E7EB; color: #4B5563; }

/* Cards / checklist */
.pg-card { background: var(--surface-card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 18px 20px; }
.pg-card h4 { font-size: 1rem; font-weight: 700; color: var(--text-heading); margin: 0 0 12px; display: flex; align-items: center; gap: 8px; }
.pg-card h4 i { color: var(--c-info); }
.pg-checklist { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
.pg-checklist li { display: flex; align-items: center; gap: 9px; font-size: 14px; color: var(--text-body); }
.pg-checklist li i { color: var(--c-success); font-size: 13px; width: 16px; text-align: center; }
.pg-card .pg-checklist li i.fa-file { color: var(--c-info); }
.pg-hint { margin-top: 14px; font-size: 12.5px; color: var(--text-muted); background: #f8fafc; border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 10px 12px; line-height: 1.5; }
.pg-hint i { color: var(--c-info); margin-right: 4px; }

/* Statuses */
.pg-statuses { display: flex; flex-direction: column; gap: 10px; }
.pg-status { display: flex; align-items: center; gap: 12px; background: var(--surface-card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 12px 16px; }
.pg-status .pg-badge { flex-shrink: 0; min-width: 88px; text-align: center; }
.pg-status-desc { font-size: 13.5px; color: var(--text-body); }

/* FAQ */
.pg-faq { display: flex; flex-direction: column; gap: 10px; }
.pg-faq details { background: var(--surface-card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 4px 18px; }
.pg-faq summary { cursor: pointer; font-weight: 600; color: var(--text-heading); font-size: 14.5px; padding: 12px 0; list-style: none; display: flex; align-items: center; justify-content: space-between; }
.pg-faq summary::-webkit-details-marker { display: none; }
.pg-faq summary::after { content: "\f078"; font-family: "Font Awesome 5 Free"; font-weight: 900; font-size: 11px; color: var(--text-muted); transition: transform .2s ease; }
.pg-faq details[open] summary::after { transform: rotate(180deg); }
.pg-faq p { margin: 0 0 14px; font-size: 14px; color: var(--text-muted); line-height: 1.6; }

/* Final CTA */
.pg-final { margin-top: 52px; background: var(--c-primary); border-radius: var(--radius); padding: 36px 24px; text-align: center; color: #fff; }
.pg-final h3 { font-size: 1.5rem; font-weight: 700; margin: 0 0 8px; }
.pg-final p { color: rgba(255,255,255,.8); margin: 0 auto 20px; max-width: 480px; line-height: 1.6; }
.pg-final-cta { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

.pg-foot { text-align: center; font-size: 13px; color: var(--text-muted); margin-top: 26px; }
.pg-foot a { color: var(--c-info); text-decoration: none; }
</style>
