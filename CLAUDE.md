# CLAUDE.md — cbt.sistemedu.com

Dokumen ini menjelaskan arsitektur, konvensi, dan fitur yang telah dibangun pada proyek ini.

---

## Stack Teknologi

| Layer | Teknologi |
|---|---|
| Backend | Laravel 11 (PHP) |
| Frontend | Inertia.js + Vue 3 (Options API) |
| Auth | Laravel Fortify (multi-guard) |
| CSS | Bootstrap **5.1.3** (Volt theme, versi lama — lihat *CSS & Ikon: Versi Lama*) + Font Awesome **5.15.4** |
| DB | MySQL (production) / SQLite (lokal) |
| Excel export | Maatwebsite Laravel Excel |
| PDF export | mPDF |

---

## Struktur Direktori Kunci

```
app/
  Http/
    Controllers/
      Admin/         ← semua controller admin
      Asesor/        ← controller portal asesor
      Manager/       ← controller portal pengambil keputusan sertifikasi
      Student/       ← controller ujian siswa (BaseExamController: timer & penjaga jawaban)
      Peserta/       ← controller portal peserta sertifikasi
    Middleware/
      AuthStudent.php
      AuthParticipant.php
      EnsureAdmin.php / EnsureAsesor.php / EnsureManagerSertifikasi.php
    Responses/
      LoginResponse.php   ← redirect post-login sesuai role
      LogoutResponse.php
  Services/
    DocumentGeneratorService.php  ← semua PDF (FR.APL/FR.AK, SK, sertifikat) + ZIP export dokumen
    StudentEnrollmentService.php  ← buat akun ujian, enrollment ExamGroup, reissue, pindah batch
    ResultCalculatorService.php   ← nilai akhir (PG + esai + wawancara, berbobot)
    PeruriService.php / MidtransService.php  ← e-meterai
  Support/
    AnswerFile.php        ← upload file jawaban peserta (tipe diizinkan, disk private)
  Models/
    User.php              ← role lewat tabel user_roles (hasRole())
    Student.php
    Exam.php
    ExamSession.php
    ExamGroup.php         ← enrollment siswa ke sesi
    Essay.php             ← soal esai
    AnswerEssay.php       ← jawaban siswa (memiliki kolom score baru)
    Grade.php             ← nilai akhir per siswa per ujian
    InterviewAssessment.php  ← (baru) penilaian wawancara
    AsesorAssignment.php     ← (baru) penugasan asesor ke peserta
  Exports/
    GradesEssayExport.php    ← export Excel esai (sudah pakai score nyata)

resources/js/
  Layouts/
    Admin.vue
    Asesor.vue    ← (baru) layout portal asesor
    Student.vue
    Peserta.vue
  Pages/
    Admin/
      Penilaian/
        Index.vue   ← (baru) daftar sesi untuk penugasan asesor
        Show.vue    ← (baru) atur asesor per peserta di sesi tertentu
      Reports/      ← laporan nilai (existing)
      ...
    Asesor/
      Dashboard.vue          ← (baru) dashboard asesor
      Esai/Show.vue          ← (baru) tabel penilaian jawaban esai
      Wawancara/Show.vue     ← (baru) tabel penilaian wawancara
    Student/
      EssaysMigas/   ← ujian esai dengan file upload
      Essays/
      Exams/
    Peserta/
      Application/   ← form pendaftaran sertifikasi
```

---

## Autentikasi & Guard

| Guard | Model | Login URL | Redirect setelah login |
|---|---|---|---|
| `web` (admin) | `User` (role admin) | `/login` (Fortify) | `/admin/dashboard` |
| `web` (asesor) | `User` (role asesor) | `/login` (Fortify) | `/asesor/dashboard` |
| `web` (manager) | `User` (role manager_sertifikasi) | `/login` (Fortify) | `/manager/dashboard` |
| `student` | `Student` | `/` (hanya No. Peserta, tanpa password) | `/student/dashboard` |
| `participant` | `Participant` | `/peserta/login` | `/peserta/dashboard` |

Middleware alias (di `bootstrap/app.php`):
- `auth` → Fortify default (guard web)
- `admin` → `EnsureAdmin`, `asesor` → `EnsureAsesor`, `manager` → `EnsureManagerSertifikasi`
- `student` → `AuthStudent`
- `participant` → `AuthParticipant`

Role disimpan di tabel **`user_roles`** (`user_id`, `role` enum `admin|asesor|manager_sertifikasi`) —
satu user bisa punya beberapa role. Cek dengan `$user->hasRole(UserRole::Asesor)` (enum `App\Enums\UserRole`).
Kolom `users.role` sudah dihapus (migrasi `2026_08_25_000001`).

Login siswa (`Student\LoginController`) membatasi 10 percobaan **gagal** per IP per menit
(login yang berhasil tidak dihitung — satu ruang ujian sering di balik satu IP/NAT).

---

## Tipe Ujian (Exam Type)

| Type | Keterangan |
|---|---|
| `Pilihan Ganda` | Multiple choice, dinilai otomatis |
| `Essay` | Esai teks, dinilai asesor |
| `Essay Migas` | Esai dengan upload file, dinilai asesor |

---

## Skema Database Kunci

### Tabel yang Dimodifikasi (fitur asesor)

**`answer_essays`** — tambah kolom:
```
score        decimal(5,2)  nullable   ← nilai per jawaban, diisi asesor
assessed_by  bigint        nullable   FK users.id
assessed_at  timestamp     nullable
```

### Tabel Baru

**`interview_assessments`**:
```
id, exam_session_id, student_id, asesor_id (FK users),
gaya_wawancara, penguasaan_materi, kemampuan_hadapi_pertanyaan,
hasil_worksheet, total_nilai, catatan, timestamps
UNIQUE (exam_session_id, student_id)
```

**`asesor_assignments`**:
```
id, user_id (FK users — asesor), exam_session_id, student_id, timestamps
UNIQUE (user_id, exam_session_id, student_id)
```

---

## Fitur Penilaian Asesor (Diimplementasikan)

### Alur Admin
1. Admin masuk ke **Penugasan Asesor** di sidebar (`/admin/penilaian`)
2. Pilih sesi ujian → pilih asesor per peserta → simpan
3. Admin dapat melihat hasil nilai di Laporan Nilai (existing)

### Alur Asesor
1. Asesor login di URL yang sama dengan admin (`/login`)
2. Otomatis diarahkan ke `/asesor/dashboard`
3. Dashboard menampilkan sesi ujian yang ditugaskan beserta jumlah peserta
4. Dari setiap sesi, asesor bisa memilih:
   - **Nilai Esai** → `GET /asesor/penilaian/{session_id}/esai`
   - **Nilai Wawancara** → `GET /asesor/penilaian/{session_id}/wawancara`

### Tampilan Penilaian Esai
Tabel horizontal, satu baris per peserta:
```
No Peserta | Nama | Jawaban 1 | Nilai 1 | Jawaban 2 | Nilai 2 | ... | Total Nilai
```
- **Total Nilai** = rata-rata nilai jawaban yang sudah dinilai (nilai 0 ikut dihitung). Frontend
  hanya menampilkan; server menghitung ulang dari `answer_essays.score`
  (`EssayAssessmentController::averageScore`) lalu menyimpan ke `grades.grade`
- Baris footer: rata-rata per kolom nilai + rata-rata sesi
- Tombol **Simpan Semua Nilai** mengirim semua nilai sekaligus (POST)

### Tampilan Penilaian Wawancara
Tabel kriteria tetap, satu baris per peserta:
```
No Peserta | Nama | Gaya Wawancara | Penguasaan Materi | Kemampuan Menghadapi Pertanyaan | Hasil Pengerjaan Worksheet Ujian Keterampilan | Total Nilai | Catatan
```
- **Total Nilai** = rata-rata 4 kriteria (skala 0–100), sejajar dengan nilai PG & esai
  - Contoh: (88 + 86 + 86 + 88) / 4 = **87**
- Bobot wawancara diterapkan **sekali** di `ResultCalculatorService` (bukan di controller)

---

## Keamanan Ujian Siswa

Aturan di `Student\BaseExamController` (dipakai PG, Essay, Essay Migas):
- **Kunci jawaban tidak boleh sampai ke browser.** Relasi soal dimuat dengan kolom terbatas
  (`Question::STUDENT_COLUMNS`, `Essay::STUDENT_COLUMNS`); `is_correct`/`score` disembunyikan.
  Setiap query baru di halaman siswa wajib memakai `questionForStudent()` / `essayForStudent()`.
- **Timer dijaga server.** `grades.duration` (sisa ms) hanya boleh berkurang (`syncDuration`), dan dibatasi
  jam dinding `start_time + durasi ujian + GRACE_MINUTES (60)` (`remainingMs`). Timer klien berhenti saat
  peserta offline, jadi toleransi ini menampung gangguan koneksi.
- Mulai ujian hanya sekali (tidak reset `start_time` / acak ulang soal); jawaban ditolak setelah
  `end_time` terisi atau waktu habis (`acceptsAnswers`); mengakhiri dua kali tidak menimpa nilai.
- Jendela sesi (`exam_sessions.start_time/end_time`) **tidak** dicek di server: `APP_TIMEZONE` default UTC
  sedangkan jam sesi diinput waktu lokal.

**Jawaban esai (HTML dari Quill 2) dibersihkan saat disimpan**: cast `App\Casts\SanitizedHtml` pada
`AnswerEssay::answer` → `App\Support\RichText::clean()` (HTMLPurifier). Jawaban ini dirender `v-html` di
halaman asesor/admin dan `{!! !!}` di PDF laporan, jadi script, event handler, iframe, `javascript:` dan
gambar non-`data:` dibuang. Menulis kolom ini lewat Query Builder `->update()` melewati cast — jangan.
Ubah daftar tag di `RichText` → naikkan `HTML.DefinitionRev`.

File upload peserta (Essay Migas & tugas) lewat `App\Support\AnswerFile`: tipe dibatasi
(pdf, office, gambar, zip/rar; maks 20 MB) dan disimpan di disk **`private`**, diunduh lewat controller
(`student.essaysmigas.download`, `admin.essay_migas.download`). File lama di disk `public`
dipindah dengan `php artisan answer-files:move-private` (`--dry-run` untuk cek dulu).

Bukti dokumen CV asesor (opsional, per baris Pendidikan/Pelatihan/Pengalaman Kerja/Pengalaman Profesional/Sertifikasi)
disimpan di baris JSON-nya sendiri (`bukti => [id, path, name]`, disk `private`). Browser hanya menerima `id` + `name`
dan mengirim balik `bukti_id` — jangan kirim/terima `path` dari browser.

---

## Penomoran SK / SP

`NumberingService` memakai counter global per tahun di `numbering_counters` (lintas sesi). Nomor dibagikan
saat Pengambil Keputusan klik Finalisasi, urut No. Peserta. Aturan supaya nomor tidak loncat:
- Peserta remidi **memakai ulang** nomor SK/SP lamanya (`RemidiService` tidak mengosongkannya).
- Sesi / peserta / skema yang sudah memegang nomor tidak bisa dihapus (`ParticipantResult::preventLosingNumbers`).
- Nomor disimpan tanpa spasi (mutator di `ParticipantResult`).
- Jangan ubah nomor/counter lewat SQL manual. Pakai `numbering:audit` (cek celah), `numbering:renumber-session`
  (nomori ulang satu sesi + sesuaikan counter) dan `numbering:normalize` (buang spasi) — semua punya `--dry-run`.

---

## Unit Kompetensi per Skema

Daftar unit (kode, judul, urutan) ada di `CompetencyUnitsSeeder::skemas()` dan mengikuti tabel Kemasan pada
dokumen skema. Untuk database yang sudah berisi data, jangan jalankan seeder (kelas dicocokkan lewat `title`,
yang di produksi sudah berbeda → kelas dobel). Pakai `php artisan competency-units:sync` (`--dry-run` untuk cek
dulu): mencocokkan kelas lewat `classrooms_code` (alias kode lama FSI→FMO, ISL→LQO), hanya mengubah kode/judul/urutan
unit, tidak membuat kelas dan tidak menyentuh `judul_unit_en`/`kode_unit_asli`. Unit dibaca langsung saat PDF
dibuat — sertifikat/SK yang sudah ter-cache tetap, PDF yang dibuat ulang memakai unit terbaru.

---

## Verifikasi TUK Online (FR.TUK.06)

Checklist per peserta per sesi (`tuk_verifications`), diisi **admin sebagai Pengawas Ujian** di
`/admin/penilaian/{sesi}/verifikasi-tuk` (tombol muncul di halaman Penugasan Asesor bila saklar sesi
`exam_sessions.verifikasi_tuk` aktif). **Hanya pencatatan — tidak mengunci ujian peserta.**
- Daftar kriteria B–F ditanam di `App\Support\TukChecklist`; jawaban disimpan per kunci butir (`B1`…`F6`)
  di kolom JSON `items`. Jangan ubah urutan/kunci butir yang sudah ada.
- Nama Pengawas Ujian dipilih dari dropdown user ber-role admin (bisa lebih dari satu pengawas). Default:
  pengawas checklist itu, lalu pilihan terakhir admin tsb. di sesi yang sama (session `tuk_pengawas.{sesi}`), lalu
  admin yang login. Nama + TTD (`users.signature_path`, dibuat di Kelola User atau saat menyetujui permohonan)
  disalin ke `pengawas_name`/`pengawas_signature_path` setiap kali disimpan. `verified_at` diisi saat
  kesimpulan verifikasi awal pertama kali disimpan dan tidak bergeser saat F/H/I dilengkapi setelah ujian.
- PDF: `DocumentGeneratorService::generateFrTuk06()` → view `documents/fr_tuk_06`.

---

## Routes Baru

```php
// Admin — penugasan asesor (middleware: auth)
GET   /admin/penilaian                              → admin.penilaian.index
GET   /admin/penilaian/{exam_session_id}            → admin.penilaian.show
POST  /admin/penilaian/{exam_session_id}/penugasan  → admin.penilaian.saveAssignments

// Asesor — portal penilaian (middleware: auth + asesor)
GET   /asesor/dashboard                                    → asesor.dashboard
GET   /asesor/penilaian/{exam_session_id}/esai            → asesor.esai.show
POST  /asesor/penilaian/{exam_session_id}/esai            → asesor.esai.store
GET   /asesor/penilaian/{exam_session_id}/wawancara       → asesor.wawancara.show
POST  /asesor/penilaian/{exam_session_id}/wawancara       → asesor.wawancara.store
GET   /asesor/penilaian/{exam_session_id}/rekap           → asesor.rekap.show (Rekap Nilai, baca saja)
```

Rekap Nilai asesor memakai `ResultCalculatorService::calculateForSession()` (hitung tanpa menyimpan, hanya
peserta yang ditugaskan). `recalcForSession()` = hitung + simpan, dipakai halaman admin/Pengambil Keputusan.

---

## Konvensi Kode

- Controller mengembalikan `inertia('Path/To/Page', ['key' => $value])`
- Vue pages menggunakan Options API (`export default { layout, components, props, data, methods }`)
- Layout di-set lewat `layout: LayoutAdmin` (bukan `<Layout>` wrapper)
- Inertia router: `router.post(url, data, { onSuccess, onFinish })`
- Nama route: `admin.resource.action`, `asesor.resource.action`
- Migrasi: timestamp `YYYY_MM_DD_NNNNNN_deskripsi.php`
- Panduan portal Asesor & Pengambil Keputusan: isi per halaman ada di `resources/js/Components/Guide/{Asesor,Manager}/*.vue`,
  ditampilkan lewat `<PageGuide storage-key="...">` di atas halaman **dan** dirangkai di `Pages/*/Guide/Index.vue`.
  Ubah perilaku/tombol halaman → perbarui komponen Guide-nya juga.

---

## CSS & Ikon: Versi Lama (jangan pakai kelas baru)

Tampilan memakai file jadi di `public/assets` (dimuat di `resources/views/app.blade.php`), **bukan** paket npm:
`assets/css/volt.css` = tema Volt (Themesberg 2021) hasil build **Bootstrap 5.1**, `assets/js/bootstrap.bundle.min.js` =
**v5.1.3**, dan Font Awesome **5.15.4** dari CDN. Sumber SCSS Volt tidak ada di repo. Upgrade sudah dipertimbangkan
dan **sengaja tidak dilakukan** (harus build ulang Volt + cek ulang semua halaman). Jangan memasang Bootstrap/FA kedua
lewat CDN/npm. Kelas yang tidak ada **tidak menimbulkan error**, elemennya cuma tampil polos/tak terlihat, jadi
gampang lolos.

**Kelas yang TIDAK ada → penggantinya:**

| Jangan pakai | Kenapa | Pakai |
|---|---|---|
| `bg-light`, `text-dark`, `bg-dark`, `border-light` | dibuang Volt (`$theme-colors` tanpa light/dark) | `bg-gray-100`, `text-gray-800`, `bg-gray-800 text-white`, `border-gray-200` |
| `btn-light`, `btn-dark` | sama | `btn-gray-100`, `btn-gray-800` |
| `fw-semibold` | baru ada di Bootstrap 5.2 | `fw-bolder` (= 600 di Volt; `fw-bold` di Volt hanya 500) |
| `sticky-bottom` (`sticky-top` ada) | baru di 5.2 | `position-sticky` + `style="bottom:0;z-index:1020"` |
| `text-bg-*` (5.2), `*-subtle` mis. `bg-success-subtle` (5.3), `focus-ring` (5.3) | belum ada di 5.1 | badge lembut → `<StatusBadge tone="...">`; selain itu `bg-gray-100` / CSS sendiri |
| Accordion tanpa warna sendiri | tampil transparan di tema ini (tanpa `--bs-accordion-*`) | beri warna lewat CSS scoped (lihat `Pages/*/Guide/Index.vue`) |
| Kelas Tailwind (`flex`, `w-full`, `rounded-lg`, …) | Tailwind tidak terpasang | utilitas Bootstrap (`d-flex`, `w-100`, `rounded`) |

Catatan warna Volt: `bg-secondary`/`btn-secondary` berwarna **oranye muda (amber)**, bukan abu-abu. Badge terang:
`badge bg-gray-200 text-gray-800 border`; badge gelap: `badge bg-gray-800 text-white`; badge kuning:
`badge bg-warning text-gray-800`. Pemakaian lama di `resources/js` sudah diganti semua (Okt 2026); `welcome.blade.php`
memang Tailwind bawaan Laravel (CSS sendiri), biarkan.

**Ikon Font Awesome 5:** nama FA6 dan FA4 tidak tampil. Contoh: `fa-right-left` → `fa-exchange-alt`, `fa-circle-dot` →
`fa-dot-circle`, `fa-circle-info` → `fa-info-circle`, `fa-xmark` → `fa-times`, `fa-magnifying-glass` → `fa-search`,
`fa-refresh` → `fa-sync`, `fa-sign-in` → `fa-sign-in-alt`.

**Cek sebelum memakai kelas yang ragu:**
`grep -cE "\.nama-kelas([^a-zA-Z0-9_-]|$)" public/assets/css/volt.css resources/css/app.css` (0 di kedua file = tidak ada). Kelas tambahan/override proyek ditaruh di `resources/css/app.css` (dimuat setelah `volt.css`).

---

## Setup Lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
npm run build   # atau npm run dev
```

Untuk membuat akun asesor pertama (via tinker):
```php
php artisan tinker
$u = User::create(['users_code' => 'ASR001', 'name' => 'Nama Asesor', 'email' => 'asesor@contoh.com', 'password' => bcrypt('password')]);
$u->roleAssignments()->create(['role' => 'asesor']);
```

### Test

```bash
php artisan test   # SQLite in-memory (phpunit.xml), tidak menyentuh DB lokal
```
- Fixture bersama: `tests/Feature/Concerns/CreatesExamFixtures.php`. Login peserta di test pakai
  `actingAsStudent()` — bukan `actingAs($s, 'student')`, karena itu mengganti guard default.
- Migrasi harus bisa jalan di SQLite: SQL khusus MySQL dibungkus `if (DB::getDriverName() !== 'mysql') return;`.
- CI (`.github/workflows/ci.yml`) menjalankan build frontend + test. Pint **tidak** dipaksakan — codebase
  memakai perataan `=>` yang tidak sesuai preset Pint.

---

## File Penting Lainnya

| File | Keterangan |
|---|---|
| `app/Providers/FortifyServiceProvider.php` | Konfigurasi login view & redirect |
| `app/Exports/GradesEssayExport.php` | Export Excel nilai esai (sudah pakai `score` nyata) |
| `resources/js/Components/Sidebar.vue` | Navigasi sidebar admin (ada menu Penugasan Asesor) |
| `config/auth.php` | Definisi guards: web, student, participant |
| `config/materai.php` | E-meterai Peruri: `MATERAI_ENABLED` (saklar utama), `MATERAI_AUTO_STAMP`, `MATERAI_FIRST_SESSION_ID` (pembebasan FR.AK.14) |
| `app/Jobs/StampFrAk01Job.php`, `StampFrAk14Job.php` | Pembubuhan e-meterai (idempoten) |
