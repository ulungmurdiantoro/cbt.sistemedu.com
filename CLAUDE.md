# CLAUDE.md — cbt.sistemedu.com

Dokumen ini menjelaskan arsitektur, konvensi, dan fitur yang telah dibangun pada proyek ini.

---

## Stack Teknologi

| Layer | Teknologi |
|---|---|
| Backend | Laravel 11 (PHP) |
| Frontend | Inertia.js + Vue 3 (Options API) |
| Auth | Laravel Fortify (multi-guard) |
| CSS | Bootstrap 5 (Volt theme) |
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
      Asesor/        ← controller portal asesor (baru)
      Student/       ← controller ujian siswa
      Peserta/       ← controller portal peserta sertifikasi
    Middleware/
      AuthStudent.php
      AuthParticipant.php
      EnsureAsesor.php    ← (baru) cek role asesor
    Responses/
      LoginResponse.php   ← (baru) redirect post-login sesuai role
      LogoutResponse.php
  Models/
    User.php              ← memiliki kolom role enum(admin, asesor)
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
| `web` (admin) | `User` | `/login` (Fortify) | `/admin/dashboard` |
| `web` (asesor) | `User` (role=asesor) | `/login` (Fortify) | `/asesor/dashboard` |
| `student` | `Student` | `/` | `/student/dashboard` |
| `participant` | `Participant` | `/peserta/login` | `/peserta/dashboard` |

Middleware alias (di `bootstrap/app.php`):
- `auth` → Fortify default (guard web)
- `student` → `AuthStudent`
- `participant` → `AuthParticipant`
- `asesor` → `EnsureAsesor` (cek `auth()->user()->role === 'asesor'`)

Role asesor ditentukan oleh kolom `users.role` enum `('admin', 'asesor')`.

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

**`users`** — tambah kolom:
```
role  enum('admin','asesor')  default 'admin'
```

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
- **Total Nilai** = rata-rata semua nilai jawaban (dihitung di frontend, disimpan ke `grades.grade`)
- Baris footer: rata-rata per kolom nilai + rata-rata sesi
- Tombol **Simpan Semua Nilai** mengirim semua nilai sekaligus (POST)

### Tampilan Penilaian Wawancara
Tabel kriteria tetap, satu baris per peserta:
```
No Peserta | Nama | Gaya Wawancara | Penguasaan Materi | Kemampuan Menghadapi Pertanyaan | Hasil Pengerjaan Worksheet Ujian Keterampilan | Total Nilai | Catatan
```
- **Total Nilai** = (sum 4 kriteria) × 0.075
  - Contoh: 88 + 86 + 86 + 88 = 348 × 0.075 = **26.1**
- Konstanta bobot: `InterviewAssessmentController::BOBOT = 0.075`

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
```

---

## Konvensi Kode

- Controller mengembalikan `inertia('Path/To/Page', ['key' => $value])`
- Vue pages menggunakan Options API (`export default { layout, components, props, data, methods }`)
- Layout di-set lewat `layout: LayoutAdmin` (bukan `<Layout>` wrapper)
- Inertia router: `router.post(url, data, { onSuccess, onFinish })`
- Nama route: `admin.resource.action`, `asesor.resource.action`
- Migrasi: timestamp `YYYY_MM_DD_NNNNNN_deskripsi.php`

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
User::create(['users_code' => 'ASR001', 'name' => 'Nama Asesor', 'email' => 'asesor@contoh.com', 'role' => 'asesor', 'password' => bcrypt('password')]);
```

---

## File Penting Lainnya

| File | Keterangan |
|---|---|
| `app/Providers/FortifyServiceProvider.php` | Konfigurasi login view & redirect |
| `app/Exports/GradesEssayExport.php` | Export Excel nilai esai (sudah pakai `score` nyata) |
| `resources/js/Components/Sidebar.vue` | Navigasi sidebar admin (ada menu Penugasan Asesor) |
| `config/auth.php` | Definisi guards: web, student, participant |
