<template>
    <Head>
        <title>Blueprint UI/UX - Aplikasi Ujian Online</title>
    </Head>

    <div class="bp-wrap">
        <!-- HERO -->
        <div class="bp-hero">
            <span class="bp-eyebrow">Blueprint UI / UX · Admin Console</span>
            <h1>Satu cetak biru, semua menu senada.</h1>
            <p>Panduan dan <em>master prompt</em> untuk membangun &amp; merawat seluruh halaman admin
                <strong>Sistem Edu / EDUKIA</strong> agar tampil dan terasa identik — dari Dashboard sampai
                Laporan Nilai.</p>
        </div>

        <!-- 1 · MASTER PROMPT -->
        <section id="bp-prompt">
            <div class="bp-sechead"><span class="bp-secnum">01</span>
                <h2>Master Prompt</h2>
            </div>
            <p class="bp-lead">Tempel blok ini sebagai instruksi setiap kali membuat atau mengubah halaman admin.
                Ia mengunci kerangka, anatomi, token, dan tata bahasa agar tiap menu konsisten.</p>
            <div class="bp-prompt-card">
                <div class="bp-prompt-head">
                    <span class="lbl"><i class="fa fa-terminal"></i> prompt — tempel apa adanya</span>
                    <button class="bp-copy-btn" :class="{ ok: copied }" @click="copyPrompt">
                        <i :class="copied ? 'fa fa-check' : 'far fa-copy'"></i> {{ copied ? 'Tersalin' : 'Salin' }}
                    </button>
                </div>
                <pre class="bp-prompt">{{ promptText }}</pre>
            </div>
        </section>

        <!-- 2 · FONDASI -->
        <section id="bp-fondasi">
            <div class="bp-sechead"><span class="bp-secnum">02</span>
                <h2>Fondasi Visual</h2>
            </div>
            <p class="bp-lead">Palet Volt yang dipakai aplikasi. Selalu rujuk variabel — jangan menyalin nilai mentah.</p>

            <div class="bp-grid g4" style="margin-bottom: 18px;">
                <div class="bp-foundation" v-for="c in colors" :key="c.name">
                    <div class="bp-swatch" :style="{ background: c.value, border: c.border ? '1px solid var(--border-subtle)' : 'none' }">
                        <span>{{ c.hex }}</span>
                    </div>
                    <div class="name">{{ c.name }}</div>
                    <div class="desc">{{ c.desc }}</div>
                </div>
            </div>

            <div class="bp-grid g2">
                <div class="bp-foundation">
                    <h4>Tipografi · Quicksand</h4>
                    <div class="bp-typerow"><span class="meta">display · 700</span><span style="font-size: 30px; font-weight: 700; color: var(--text-heading); line-height: 1;">Aa Skema</span></div>
                    <div class="bp-typerow"><span class="meta">h2 · 24 · 700</span><span style="font-size: 22px; font-weight: 700; color: var(--text-heading);">Judul Halaman</span></div>
                    <div class="bp-typerow"><span class="meta">body · 16 · 400</span><span style="font-size: 16px;">Teks isi &amp; tabel</span></div>
                    <div class="bp-typerow"><span class="meta">mono · nilai</span><span style="font-family: var(--font-mono); font-size: 15px;">87.50 · 00:42:15</span></div>
                </div>
                <div class="bp-foundation">
                    <h4>Bentuk &amp; Elevasi</h4>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px;">
                        <div class="bp-shape" style="box-shadow: var(--shadow);">shadow</div>
                        <div class="bp-shape" style="box-shadow: var(--shadow-card);">card</div>
                        <div class="bp-shape" style="border-radius: var(--radius-sm); border: 1px solid var(--border-default);">0.35rem</div>
                        <div class="bp-shape" style="width: 64px; background: var(--c-info); border-radius: 50%; color: #fff;">circle</div>
                    </div>
                    <div class="desc" style="margin-top: 14px;">Radius default 0.5rem · modal 1rem · pill untuk badge · lingkaran untuk avatar. Bayangan pendek &amp; netral.</div>
                </div>
            </div>
        </section>

        <!-- 3 · SHELL -->
        <section id="bp-shell">
            <div class="bp-sechead"><span class="bp-secnum">03</span>
                <h2>Kerangka (Shell)</h2>
            </div>
            <p class="bp-lead">Sama persis di setiap menu: rail sidebar gelap, topbar, area konten. Urutan &amp; grup nav tidak boleh diacak.</p>
            <div class="bp-grid g4">
                <div class="bp-foundation"><h4>Sidebar</h4><div class="name">Rail gelap 260px</div><div class="desc">Sticky, brand + PRO, nav berkelompok, item aktif royal blue.</div></div>
                <div class="bp-foundation"><h4>Topbar</h4><div class="name">Judul + Avatar</div><div class="desc">Judul kiri; avatar bulat, nama, peran, menu Logout kanan.</div></div>
                <div class="bp-foundation"><h4>Konten</h4><div class="name">Area konten</div><div class="desc">Padding 28px, latar off-white, lebar mengikuti sisa ruang.</div></div>
                <div class="bp-foundation"><h4>Grup Nav</h4><div class="name">4 seksi</div><div class="desc">Main · Manajemen · Ujian Online · Administrasi.</div></div>
            </div>
        </section>

        <!-- 4 · ANATOMI -->
        <section id="bp-anatomi">
            <div class="bp-sechead"><span class="bp-secnum">04</span>
                <h2>Anatomi Halaman — Pola Wajib</h2>
            </div>
            <p class="bp-lead">Cetakan yang dipakai ulang oleh <strong>setiap</strong> menu daftar. Yang berubah antar menu
                hanya kolom tabel &amp; label tombol.</p>

            <div class="bp-anat">
                <div class="bp-anat-side">
                    <div class="b"><span class="dot"><i class="fa fa-graduation-cap" style="font-size: 11px;"></i></span> UJIAN ONLINE</div>
                    <div class="grp">Manajemen</div>
                    <div class="link"><i class="fa fa-layer-group"></i> Skema</div>
                    <div class="link on"><i class="fa fa-users"></i> Peserta</div>
                    <div class="grp">Ujian Online</div>
                    <div class="link"><i class="fa fa-pencil-alt"></i> Ujian</div>
                    <div class="link"><i class="fa fa-stopwatch"></i> Sesi Ujian</div>
                    <div class="link"><i class="fa fa-user-check"></i> Penugasan Asesor</div>
                </div>
                <div class="bp-anat-body">
                    <div class="bp-anat-top">
                        <h3>Peserta</h3>
                        <div class="user">
                            <div class="avatar">AD</div>
                            <div style="line-height: 1.2;">
                                <div style="font-size: 13px; font-weight: 600; color: var(--text-heading);">Admin EDUKIA</div>
                                <div style="font-size: 11px; color: var(--text-muted);">Administrator</div>
                            </div>
                        </div>
                    </div>
                    <div class="bp-anat-content">
                        <div class="annot" style="margin-bottom: 26px;">
                            <span class="tag">1 · Toolbar</span>
                            <div class="demo-toolbar">
                                <div class="demo-actions">
                                    <button class="dbtn p"><i class="fa fa-plus-circle"></i> Tambah</button>
                                    <button class="dbtn s"><i class="fa fa-file-excel"></i> Import</button>
                                </div>
                                <div class="dsearch"><i class="fa fa-search"></i> masukkan kata kunci...</div>
                            </div>
                        </div>

                        <div class="annot" style="margin-bottom: 22px;">
                            <span class="tag">2 · Card → Tabel</span>
                            <div style="background: #fff; border-radius: var(--radius); box-shadow: var(--shadow-card); padding: 14px; overflow-x:auto;">
                                <table class="bp-table">
                                    <thead>
                                        <tr><th style="width: 5%;">No.</th><th>No. Peserta</th><th>Nama</th><th>Skema</th><th>Jabatan</th><th style="width: 96px;">Aksi</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="r in demoRows" :key="r.no">
                                            <td class="center strong">{{ r.no }}</td>
                                            <td>{{ r.kode }}</td>
                                            <td class="strong">{{ r.nama }}</td>
                                            <td class="center"><span class="bp-badge accent">{{ r.skema }}</span></td>
                                            <td>{{ r.jabatan }}</td>
                                            <td class="center">
                                                <span class="iaction e"><i class="fa fa-pencil-alt"></i></span>
                                                <span class="iaction d"><i class="fa fa-trash"></i></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="annot">
                            <span class="tag">3 · Pagination</span>
                            <div class="demo-pag">
                                <span>Menampilkan 3 dari 128 peserta</span>
                                <div style="display: flex; gap: 6px;">
                                    <span class="pg muted">‹</span>
                                    <span class="pg on">1</span>
                                    <span class="pg">2</span>
                                    <span class="pg">›</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p class="bp-note"><strong>Pengecualian terdokumentasi:</strong> Dashboard memakai baris 4× StatCard lalu Card tabel
                "terbaru" — tetap pada shell &amp; token yang sama.</p>
        </section>

        <!-- 5 · MATRIKS -->
        <section id="bp-matriks">
            <div class="bp-sechead"><span class="bp-secnum">05</span>
                <h2>Matriks Konsistensi Menu</h2>
            </div>
            <p class="bp-lead">Kesepuluh menu memakai <strong>Anatomi §04 yang identik</strong>. Yang berbeda hanya
                kolom tabel &amp; label tombol.</p>
            <div class="bp-matrix-wrap">
                <table class="bp-matrix">
                    <thead>
                        <tr><th>Menu</th><th>Tipe</th><th>Tombol Toolbar</th><th>Kolom Tabel Inti</th><th>Badge</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in matrix" :key="m.menu">
                            <td class="menu"><i :class="m.icon"></i>{{ m.menu }}</td>
                            <td><span class="bp-pill" :class="m.typeClass">{{ m.type }}</span></td>
                            <td>{{ m.buttons }}</td>
                            <td>{{ m.columns }}</td>
                            <td>{{ m.badge }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bp-grid g2" style="margin-top: 22px;">
                <div class="bp-foundation">
                    <h4>Aturan turunan</h4>
                    <ul class="bp-rules">
                        <li>Kolom <strong>No.</strong> selalu pertama (center); kolom <strong>Aksi</strong> selalu terakhir (icon-only: edit accent + hapus danger).</li>
                        <li>Nilai dua desimal <span class="bp-kbd">87.50</span> rata kanan, <span class="bp-kbd">tabular-nums</span>; durasi mono <span class="bp-kbd">00:42:15</span>.</li>
                        <li>Pencarian selalu di kanan toolbar, placeholder <span class="bp-kbd">masukkan kata kunci...</span></li>
                        <li>Tombol = verba imperatif: Tambah, Import, Simpan, Hapus, Setujui, Tolak, Export, Detail.</li>
                    </ul>
                </div>
                <div class="bp-foundation">
                    <h4>Tata bahasa badge</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 9px; margin-top: 12px;">
                        <span class="bp-badge accent">Berlangsung</span>
                        <span class="bp-badge secondary">Terjadwal</span>
                        <span class="bp-badge success">Lulus</span>
                        <span class="bp-badge danger">Tidak Lulus</span>
                        <span class="bp-badge neutral">Draft</span>
                    </div>
                    <div class="desc" style="margin-top: 14px;">Selalu <em>soft</em> (isian tint), bentuk pill, huruf
                        kecil-tebal. Tone mengikuti makna, bukan selera.</div>
                </div>
            </div>
        </section>

        <!-- 6 · DO / DONT -->
        <section id="bp-dodont">
            <div class="bp-sechead"><span class="bp-secnum">06</span>
                <h2>Do &amp; Don't</h2>
            </div>
            <div class="bp-dd">
                <div class="bp-dd-card do">
                    <h4><i class="fa fa-check-circle"></i> Lakukan</h4>
                    <ul>
                        <li><i class="fa fa-check"></i> Latar off-white; kartu putih borderless + shadow.</li>
                        <li><i class="fa fa-check"></i> Header tabel gelap, teks putih, ujung membulat.</li>
                        <li><i class="fa fa-check"></i> Badge soft bertone; ikon stat tint 30%.</li>
                        <li><i class="fa fa-check"></i> Bahasa Indonesia formal "Anda"; tombol verba imperatif.</li>
                        <li><i class="fa fa-check"></i> Komponen StatusBadge &amp; Pagination yang sudah ada.</li>
                    </ul>
                </div>
                <div class="bp-dd-card dont">
                    <h4><i class="fa fa-times-circle"></i> Hindari</h4>
                    <ul>
                        <li><i class="fa fa-times"></i> Putih murni sebagai latar halaman.</li>
                        <li><i class="fa fa-times"></i> Gradien, glow, glassmorphism, atau emoji.</li>
                        <li><i class="fa fa-times"></i> Mengubah urutan toolbar/kolom antar menu.</li>
                        <li><i class="fa fa-times"></i> Hex/px mentah atau font lain di luar Quicksand.</li>
                        <li><i class="fa fa-times"></i> Tombol/badge buatan sendiri di luar komponen baku.</li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head },

    data() {
        return {
            copied: false,

            promptText:
`PERAN. Anda membangun antarmuka admin untuk Sistem Edu / EDUKIA — platform ujian berbasis komputer (CBT) & sertifikasi. Bahasa UI: Bahasa Indonesia formal-ramah ("Anda"). Tanpa emoji.

ATURAN EMAS.
1) Satu kerangka untuk semua menu: Sidebar gelap (#1F2937, 260px) + Topbar (judul kiri, avatar kanan) + Area konten.
2) Setiap halaman daftar = Toolbar (aksi kiri • Cari kanan) -> Card berisi Tabel -> Pagination. Urutan tetap.
3) Pakai token, bukan angka mentah: var(--color-info), var(--radius), var(--shadow), dst.
4) Pakai komponen baku: StatusBadge, Pagination, kartu Volt (card border-0 shadow). Jangan bikin sendiri.
5) Tanpa gradien, glow, glassmorphism, atau emoji.

VISUAL. Font Quicksand (300-700); mono hanya untuk angka/timer. Latar off-white #F2F4F6; kartu putih borderless + shadow, radius 0.5rem. Primary=slate #1F2937, Accent/Info=royal blue #2361CE, Success=#10B981, Danger=#E11D48, Secondary=gold #F0BC74. Header tabel GELAP teks putih (thead-dark), ujung membulat. Badge soft pill. Hover menggelapkan isian; fokus ring lembut 0.18rem; transisi 0.15s.

ANATOMI HALAMAN DAFTAR (wajib).
- Toolbar: kiri [+ Tambah] (primary, elevated) [Import] (success) bila relevan; kanan kotak Cari (ikon search, placeholder "masukkan kata kunci...").
- Card -> Tabel (table-bordered): kolom "No." center pertama; kolom data sesuai menu; kolom "Aksi" terakhir (ikon Edit accent + Hapus danger).
- Pagination: kiri "Menampilkan X dari N <entitas>"; kanan < [1] 2 > (aktif=accent).
- Form Tambah/Edit = modal (radius 1rem, shadow-lg); footer [Batal][Simpan]. Hapus = SweetAlert "Apakah Anda yakin? Anda tidak akan dapat mengembalikan ini!".

BADGE STATUS. Berlangsung/Aktif=accent | Terjadwal/Menunggu=secondary | Selesai/Lulus=success | Tidak Lulus/Ditolak/Waktu Habis=danger | Draft/Nonaktif=neutral.

NAV BAKU (jangan diacak).
MAIN: Dashboard | MANAJEMEN: Skema, Peserta | UJIAN ONLINE: Ujian, Sesi Ujian, Permohonan, Penugasan Asesor, Hasil Penilaian | ADMINISTRASI: Kelola User, Laporan Nilai.

DETAIL SENADA. Kolom No. selalu pertama (center), Aksi selalu terakhir (icon-only). Nilai dua desimal (87.50) rata kanan tabular-nums; durasi mono 00:42:15. Tombol = verba imperatif (Tambah, Simpan, Hapus, Setujui, Tolak, Export, Detail). Empty state: ikon abu + judul + kalimat ramah.

KELUARAN. Gunakan LayoutAdmin (Sidebar + Navbar), kelas Volt, komponen StatusBadge & Pagination. Bila menu baru, ikuti baris yang sesuai pada Matriks Konsistensi Menu.`,

            colors: [
                { name: 'Primary · Slate', hex: '#1F2937', value: '#1F2937', desc: 'Tombol utama, sidebar' },
                { name: 'Accent · Royal Blue', hex: '#2361CE', value: '#2361CE', desc: 'Edit, link, item aktif, badge' },
                { name: 'Success · Emerald', hex: '#10B981', value: '#10B981', desc: 'Lulus, tersimpan, import' },
                { name: 'Danger · Rose', hex: '#E11D48', value: '#E11D48', desc: 'Hapus, tidak lulus, waktu habis' },
                { name: 'Secondary · Gold', hex: '#F0BC74', value: '#F0BC74', desc: 'Badge PRO, status terjadwal' },
                { name: 'Tertiary · Indigo', hex: '#31316A', value: '#31316A', desc: 'Aksen stat sekunder' },
                { name: 'Surface · Off-white', hex: '#F2F4F6', value: '#F2F4F6', desc: 'Latar halaman (bukan putih murni)', border: true },
                { name: 'Brand-only', hex: 'EDUKIA', value: 'linear-gradient(90deg, #449FE5 50%, #F4891F 50%)', desc: 'Logo/sertifikat saja — bukan UI' },
            ],

            demoRows: [
                { no: 1, kode: 'PST-0427', nama: 'Budi Santoso', skema: 'Teknisi K3 Migas', jabatan: 'Operator' },
                { no: 2, kode: 'PST-0428', nama: 'Sri Wahyuni', skema: 'Ahli Muda K3 Umum', jabatan: 'Safety Officer' },
                { no: 3, kode: 'PST-0429', nama: 'Andi Pratama', skema: 'Juru Las SMAW', jabatan: 'Welder' },
            ],

            matrix: [
                { menu: 'Dashboard', icon: 'fa fa-tachometer-alt', type: 'Ringkasan', typeClass: 'secondary', buttons: '—', columns: 'StatCard ×4 + tabel "Sesi Terbaru"', badge: 'Status sesi' },
                { menu: 'Skema', icon: 'fa fa-layer-group', type: 'Daftar', typeClass: 'accent', buttons: '+ Tambah · Import', columns: 'Nama Skema · Kode · Jml Unit · TUK', badge: 'Aktif/Draft' },
                { menu: 'Peserta', icon: 'fa fa-users', type: 'Daftar', typeClass: 'accent', buttons: '+ Tambah · Import', columns: 'No. Peserta · Nama · Skema · J.Kelamin · Jabatan · Institusi', badge: 'Skema' },
                { menu: 'Ujian', icon: 'fa fa-pencil-alt', type: 'Daftar', typeClass: 'accent', buttons: '+ Tambah', columns: 'Judul · Skema · Tipe · Jml Soal · Durasi · KKM', badge: 'Tipe ujian' },
                { menu: 'Sesi Ujian', icon: 'fa fa-stopwatch', type: 'Daftar', typeClass: 'accent', buttons: '+ Tambah', columns: 'Nama Sesi · Tanggal · Peserta · Status', badge: 'Berlangsung/Terjadwal/Selesai' },
                { menu: 'Permohonan', icon: 'fa fa-inbox', type: 'Approval', typeClass: 'secondary', buttons: '—', columns: 'No. · Pemohon · Skema · Tanggal · Status', badge: 'Menunggu/Disetujui/Ditolak' },
                { menu: 'Penugasan Asesor', icon: 'fa fa-user-check', type: 'Daftar', typeClass: 'accent', buttons: '+ Tugaskan', columns: 'Asesor · Skema · Jml Peserta · Sesi', badge: '—' },
                { menu: 'Hasil Penilaian', icon: 'fa fa-award', type: 'Daftar', typeClass: 'accent', buttons: 'Export', columns: 'Peserta · Skema · Nilai (87.50) · KKM (70.00) · Hasil', badge: 'Lulus/Tidak Lulus' },
                { menu: 'Kelola User', icon: 'fa fa-user-cog', type: 'Daftar', typeClass: 'accent', buttons: '+ Tambah', columns: 'Nama · Email · Peran · Status', badge: 'Aktif/Nonaktif' },
                { menu: 'Laporan Nilai', icon: 'fa fa-chart-line', type: 'Laporan', typeClass: 'secondary', buttons: 'Export Excel · PDF', columns: 'filter (Skema/Sesi) -> rekap nilai', badge: 'per nilai' },
            ],
        };
    },

    methods: {
        async copyPrompt() {
            try {
                await navigator.clipboard.writeText(this.promptText);
            } catch (e) {
                const ta = document.createElement('textarea');
                ta.value = this.promptText;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                ta.remove();
            }
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 1800);
        },
    },
}
</script>

<style scoped>
.bp-wrap {
    /* ── Token lokal (palet Volt / EDUKIA — sumber kebenaran yang didokumentasikan) ── */
    --c-primary: #1F2937;
    --c-info: #2361CE;
    --c-success: #10B981;
    --c-danger: #E11D48;
    --c-secondary: #F0BC74;
    --c-tertiary: #31316A;
    --surface-page: #F2F4F6;
    --surface-card: #FFFFFF;
    --surface-sunken: #F9FAFB;
    --surface-sidebar: #1F2937;
    --text-heading: #1F2937;
    --text-body: #374151;
    --text-muted: #6B7280;
    --gray-200: #E5E7EB;
    --gray-300: #D1D5DB;
    --gray-500: #6B7280;
    --gray-600: #4B5563;
    --border-subtle: #E5E7EB;
    --border-default: #D1D5DB;
    --radius: 0.5rem;
    --radius-sm: 0.35rem;
    --shadow: 0 1px 3px 0 rgba(0,0,0,.10), 0 1px 2px 0 rgba(0,0,0,.06);
    --shadow-card: 0 10px 15px -3px rgba(0,0,0,.10), 0 4px 6px -2px rgba(0,0,0,.05);
    --shadow-sm: 0 2px 5px rgba(140,152,164,.20);
    --tint-info: rgba(35,97,206,.14);
    --tint-secondary: rgba(240,188,116,.30);
    --tint-success: rgba(16,185,129,.16);
    --tint-danger: rgba(225,29,72,.14);
    --edukia-blue: #449FE5;
    --edukia-orange: #F4891F;
    --font-mono: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;

    max-width: 1180px;
    margin: 0 auto;
    padding: 8px 8px 80px;
    color: var(--text-body);
}

/* ── Hero ── */
.bp-hero { padding: 24px 0 26px; border-bottom: 1px solid var(--border-subtle); margin-bottom: 38px; }
.bp-eyebrow { display: inline-block; font-size: 14px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--c-info); margin-bottom: 12px; }
.bp-hero h1 { font-size: 2.3rem; font-weight: 700; color: var(--text-heading); margin: 0 0 12px; letter-spacing: -0.01em; line-height: 1.15; }
.bp-hero p { font-size: 1.1rem; color: var(--text-muted); max-width: 760px; margin: 0; }

/* ── Section ── */
section { margin-bottom: 52px; }
.bp-sechead { display: flex; align-items: baseline; gap: 14px; margin-bottom: 18px; }
.bp-secnum { font-family: var(--font-mono); font-size: 14px; color: var(--c-info); font-weight: 600; }
.bp-sechead h2 { font-size: 1.5rem; font-weight: 700; color: var(--text-heading); margin: 0; }
.bp-lead { color: var(--text-muted); margin: -4px 0 22px; max-width: 760px; }

/* ── Master prompt ── */
.bp-prompt-card { border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-card); }
.bp-prompt-head { display: flex; align-items: center; justify-content: space-between; background: var(--surface-sidebar); color: #fff; padding: 13px 18px; }
.bp-prompt-head .lbl { display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 14px; }
.bp-prompt-head .lbl i { color: var(--c-secondary); }
.bp-copy-btn { display: inline-flex; align-items: center; gap: 8px; background: var(--c-info); color: #fff; border: 0; cursor: pointer; font-weight: 600; font-size: 14px; padding: 8px 16px; border-radius: var(--radius); transition: background .15s ease-in-out; }
.bp-copy-btn:hover { background: #1F57B8; }
.bp-copy-btn.ok { background: var(--c-success); }
.bp-prompt { margin: 0; background: #0f1722; color: #d7dee8; padding: 22px; max-height: 460px; overflow: auto; font-family: var(--font-mono); font-size: 12.5px; line-height: 1.7; white-space: pre-wrap; word-break: break-word; }

/* ── Foundations grid ── */
.bp-grid { display: grid; gap: 18px; }
.bp-grid.g4 { grid-template-columns: repeat(4, 1fr); }
.bp-grid.g2 { grid-template-columns: repeat(2, 1fr); }
@media (max-width: 880px) { .bp-grid.g4, .bp-grid.g2 { grid-template-columns: repeat(2, 1fr); } }
.bp-foundation { background: var(--surface-card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 18px; }
.bp-foundation h4 { margin: 0 0 10px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); font-weight: 700; }
.bp-swatch { height: 56px; border-radius: var(--radius-sm); margin-bottom: 10px; display: flex; align-items: flex-end; padding: 8px; }
.bp-swatch span { font-family: var(--font-mono); font-size: 11px; background: rgba(255,255,255,.85); padding: 2px 6px; border-radius: 4px; color: #1f2937; }
.bp-foundation .name { font-weight: 600; color: var(--text-heading); font-size: 14px; }
.bp-foundation .desc { font-size: 12px; color: var(--text-muted); }

.bp-typerow { display: flex; align-items: baseline; gap: 14px; padding: 10px 0; border-bottom: 1px solid var(--border-subtle); }
.bp-typerow:last-child { border: 0; }
.bp-typerow .meta { font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); width: 110px; flex: none; }
.bp-shape { width: 96px; height: 64px; background: #fff; border-radius: var(--radius); display: grid; place-items: center; font-size: 11px; color: var(--text-muted); font-family: var(--font-mono); }

/* ── Anatomy diagram ── */
.bp-anat { display: grid; grid-template-columns: 240px 1fr; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-card); border: 1px solid var(--border-subtle); }
@media (max-width: 820px) { .bp-anat { grid-template-columns: 1fr; } }
.bp-anat-side { background: var(--surface-sidebar); color: var(--gray-300); padding: 18px 14px; }
.bp-anat-side .b { display: flex; align-items: center; gap: 8px; color: #fff; font-weight: 700; font-size: 12px; letter-spacing: 0.04em; margin-bottom: 14px; }
.bp-anat-side .b .dot { width: 22px; height: 22px; border-radius: var(--radius-sm); background: var(--c-info); display: grid; place-items: center; color: #fff; }
.bp-anat-side .grp { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--gray-500); margin: 14px 0 6px; }
.bp-anat-side .link { display: flex; align-items: center; gap: 9px; font-size: 12.5px; padding: 7px 9px; border-radius: var(--radius); color: var(--gray-300); }
.bp-anat-side .link.on { background: var(--c-info); color: #fff; }
.bp-anat-side .link i { width: 15px; text-align: center; opacity: .9; }
.bp-anat-body { background: var(--surface-page); }
.bp-anat-top { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; }
.bp-anat-top h3 { margin: 0; font-size: 1.3rem; font-weight: 700; color: var(--text-heading); }
.bp-anat-top .user { display: flex; align-items: center; gap: 8px; }
.bp-anat-top .avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--c-info); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: 13px; }
.bp-anat-content { padding: 4px 20px 22px; }

.annot { position: relative; }
.annot .tag { position: absolute; right: 14px; top: -10px; background: var(--c-info); color: #fff; font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 50rem; box-shadow: var(--shadow-sm); }
.demo-toolbar { display: flex; justify-content: space-between; gap: 14px; align-items: center; }
.demo-actions { display: flex; gap: 8px; }
.dbtn { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 600; padding: 8px 14px; border-radius: var(--radius); border: 0; color: #fff; box-shadow: var(--shadow-sm); }
.dbtn.p { background: var(--c-primary); }
.dbtn.s { background: var(--c-success); }
.dsearch { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-default); border-radius: var(--radius); padding: 8px 12px; width: 240px; color: var(--text-muted); font-size: 12.5px; }
.iaction { width: 28px; height: 28px; border-radius: var(--radius-sm); border: 0; color: #fff; box-shadow: var(--shadow-sm); display: inline-grid; place-items: center; font-size: 11px; margin: 0 2px; }
.iaction.e { background: var(--c-info); }
.iaction.d { background: var(--c-danger); }

.bp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.bp-table thead th { background: var(--surface-sidebar); color: #fff; text-align: left; padding: 9px 11px; font-weight: 600; white-space: nowrap; }
.bp-table thead th:first-child { border-radius: var(--radius) 0 0 var(--radius); }
.bp-table thead th:last-child { border-radius: 0 var(--radius) var(--radius) 0; }
.bp-table tbody td { padding: 9px 11px; border-bottom: 1px solid var(--border-subtle); color: var(--text-body); }
.bp-table .center { text-align: center; }
.bp-table .strong { font-weight: 600; color: var(--text-heading); }

.demo-pag { display: flex; align-items: center; justify-content: space-between; font-size: 12.5px; color: var(--text-muted); padding: 6px 4px; }
.pg { background: var(--gray-200); color: var(--gray-600); padding: 5px 12px; border-radius: var(--radius); font-size: 12.5px; font-weight: 600; }
.pg.on { background: var(--c-info); color: #fff; }
.pg.muted { color: var(--gray-500); }

/* ── Matrix ── */
.bp-matrix-wrap { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow-card); overflow: auto; }
.bp-matrix { width: 100%; border-collapse: collapse; font-size: 14px; }
.bp-matrix thead th { background: var(--surface-sidebar); color: #fff; text-align: left; padding: 12px 14px; font-weight: 600; white-space: nowrap; }
.bp-matrix tbody td { padding: 12px 14px; border-bottom: 1px solid var(--border-subtle); vertical-align: top; color: var(--text-body); }
.bp-matrix tbody tr:last-child td { border-bottom: 0; }
.bp-matrix tbody tr:hover td { background: var(--surface-sunken); }
.bp-matrix .menu { font-weight: 600; color: var(--text-heading); white-space: nowrap; }
.bp-matrix .menu i { color: var(--c-info); margin-right: 8px; width: 16px; text-align: center; }
.bp-pill { display: inline-block; font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 50rem; }

.bp-rules { margin: 8px 0 0; padding-left: 18px; font-size: 14px; color: var(--text-body); display: flex; flex-direction: column; gap: 7px; }
.bp-kbd { font-family: var(--font-mono); font-size: 12px; background: #F2F4F6; border: 1px solid var(--border-subtle); border-radius: 5px; padding: 1px 6px; color: var(--text-body); }

/* ── Soft badges ── */
.bp-badge { display: inline-block; font-size: 12px; font-weight: 700; padding: 4px 11px; border-radius: 50rem; }
.bp-badge.accent { background: var(--tint-info); color: #1c4ea3; }
.bp-badge.secondary { background: var(--tint-secondary); color: #6b4a16; }
.bp-badge.success { background: var(--tint-success); color: #0a6b4a; }
.bp-badge.danger { background: var(--tint-danger); color: #a01233; }
.bp-badge.neutral { background: var(--gray-200); color: var(--gray-600); }
.bp-pill.accent { background: var(--tint-info); color: #1c4ea3; }
.bp-pill.secondary { background: var(--tint-secondary); color: #6b4a16; }

/* ── Do / Don't ── */
.bp-dd { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
@media (max-width: 820px) { .bp-dd { grid-template-columns: 1fr; } }
.bp-dd-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); padding: 22px; border-top: 3px solid var(--c); }
.bp-dd-card.do { --c: var(--c-success); }
.bp-dd-card.dont { --c: var(--c-danger); }
.bp-dd-card h4 { margin: 0 0 14px; display: flex; align-items: center; gap: 9px; font-size: 1rem; color: var(--text-heading); }
.bp-dd-card.do h4 i { color: var(--c-success); }
.bp-dd-card.dont h4 i { color: var(--c-danger); }
.bp-dd-card ul { margin: 0; padding-left: 0; list-style: none; display: flex; flex-direction: column; gap: 10px; }
.bp-dd-card li { display: flex; gap: 10px; font-size: 14px; color: var(--text-body); align-items: flex-start; }
.bp-dd-card li i { margin-top: 3px; font-size: 12px; }
.bp-dd-card.do li i { color: var(--c-success); }
.bp-dd-card.dont li i { color: var(--c-danger); }

.bp-note { font-size: 14px; color: var(--text-muted); margin-top: 14px; }
</style>
