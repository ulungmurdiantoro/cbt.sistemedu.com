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
      Manager/       ← controller portal Pengambil Keputusan (role teknis `manager_sertifikasi`, URL /manager)
      CertificationManager/ ← controller portal Manager Sertifikasi (role `certification_manager`, URL /manager-sertifikasi)
      Student/       ← controller ujian siswa (BaseExamController: timer & penjaga jawaban)
      Peserta/       ← controller portal peserta sertifikasi
    Middleware/
      AuthStudent.php
      AuthParticipant.php
      EnsureAdmin.php / EnsureAsesor.php / EnsureManagerSertifikasi.php (PK) / EnsureCertificationManager.php
      EnsureAppointedPengambilKeputusan.php  ← pk_session: PK hanya sesi yang ditunjuk
    Responses/
      LoginResponse.php   ← redirect post-login sesuai role
      LogoutResponse.php
  Services/
    DocumentGeneratorService.php  ← semua PDF (FR.APL/FR.AK, SK, sertifikat) + ZIP export dokumen
    StudentEnrollmentService.php  ← buat akun ujian, enrollment ExamGroup, reissue, pindah batch
    ResultCalculatorService.php   ← nilai akhir (PG + esai + wawancara, berbobot)
    SertifikasiReviewService.php  ← baris Tinjau Sertifikasi (PK & Manager Sertifikasi) + finalize()
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
    Manager.vue / CertificationManager.vue ← layout portal Pengambil Keputusan / Manager Sertifikasi
    Student.vue
    Peserta.vue
  Pages/
    Admin/
      ExamSessions/
        Show.vue    ← detail sesi: tabel peserta + status permohonan + penugasan asesor
      Penilaian/    ← halaman per sesi turunan: VerifikasiTuk/, Dokumen/ (TTD AK.01)
      Reports/      ← Show.vue = detail jawaban peserta (dibuka dari Rekap Hasil); isi per jenis ujian di
                      Components/AnswerReview/{PgReview,EssayReview}.vue. Jawaban dicocokkan lewat question_id/essay_id + sesi
      Results/      ← Hasil Penilaian: daftar sesi + Rekap Hasil (nilai, Laporan Nilai, kirim SP/SK, status materai
                      FR.AK.14 + PDF-nya — ParticipantResult::frAk14State(), route admin.results.fr-ak-14).
                      Progres per peserta (tahap AK.01 → TUK → Ujian → Nilai → K/BK → PK → MS → Final → SP → SK, siapa & kapan,
                      tahap yang ditunggu, riwayat, dokumen) dari App\Services\ResultProgressService — hanya membaca data
                      yang ada; tahap yang belum tercatat padahal tahap sesudahnya selesai = 'skipped' (sesi lama).
                      Daftar sesi: progres per tahap dari AdminDashboardService::pipelineRow() (sama dengan Pipeline dashboard)
                      Laporan Nilai → "Hasil Ujian Lengkap (ZIP)" (`admin.results.hasil-ujian`, `?peserta=ID` = satu peserta,
                      juga di panel detail peserta): App\Services\ExamResultsArchiveService — per peserta PDF jawaban PG/Esai + wawancara (kriteria, catatan, asesor) +
                      ringkasan Praktik (view `documents/hasil_ujian`, logo kecil `logo_edukia_kecil`), berkas Esai Migas &
                      unggahan praktik/tugas, plus `Rekap Hasil Ujian.xlsx`. Hanya membaca (calculateForSession). ±1 dtk/peserta
                      (mPDF) — sesi besar bisa kena timeout web server; pakai unduhan per peserta
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
| `web` (Pengambil Keputusan) | `User` (role manager_sertifikasi) | `/login` (Fortify) | `/manager/dashboard` |
| `web` (Manager Sertifikasi) | `User` (role certification_manager) | `/login` (Fortify) | `/manager-sertifikasi/dashboard` |
| `student` | `Student` | `/` (hanya No. Peserta, tanpa password) | `/student/dashboard` |
| `participant` | `Participant` | `/peserta/login` | `/peserta/dashboard` |

Middleware alias (di `bootstrap/app.php`):
- `auth` → Fortify default (guard web)
- `admin` → `EnsureAdmin`, `asesor` → `EnsureAsesor`, `manager` → `EnsureManagerSertifikasi` (Pengambil Keputusan),
  `pk_session` → `EnsureAppointedPengambilKeputusan`, `cert_manager` → `EnsureCertificationManager`
- `student` → `AuthStudent`
- `participant` → `AuthParticipant`

Role disimpan di tabel **`user_roles`** (`user_id`, `role` enum `admin|asesor|manager_sertifikasi|certification_manager`) —
satu user bisa punya beberapa role. Cek dengan `$user->hasRole(UserRole::Asesor)` (enum `App\Enums\UserRole`).
Kolom `users.role` sudah dihapus (migrasi `2026_08_25_000001`).
**Awas nama:** `manager_sertifikasi` / `UserRole::ManagerSertifikasi` / URL `/manager` = **Pengambil Keputusan** (nama teknis
lama). **Manager Sertifikasi** yang sebenarnya = `certification_manager` / `UserRole::CertificationManager` / `/manager-sertifikasi`.
Prioritas dashboard bila punya beberapa role: admin > Manager Sertifikasi > Pengambil Keputusan > asesor.

Sudah login lalu membuka `/login` atau `/` → langsung ke dashboard sesuai role (`LoginResponse::dashboardUrl`, dipakai
juga oleh `redirectUsersTo` di `bootstrap/app.php`). Peserta portal sertifikasi **tidak** dialihkan dari `/` (mereka login
ujian di sana). "Remember me" aktif untuk staf (`/login`, 30 hari — `AUTH_REMEMBER_DAYS`) dan peserta (`/peserta/login`);
sengaja **tidak** untuk login ujian siswa (komputer ruang ujian sering dipakai bergantian).

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
1. Admin masuk ke **Sesi Ujian** → buka sesi (`/admin/exam_sessions/{id}`, tab **Peserta & Asesor**)
2. Pilih asesor per peserta di kolom Asesor → **Simpan Penugasan** (POST `admin.penilaian.saveAssignments`)
   — menu "Penugasan Asesor" sudah dilebur ke sini; `/admin/penilaian[/{id}]` hanya mengalihkan
3. Admin melihat hasil nilai di **Hasil Penilaian** (`/admin/results/{id}`) — menu "Laporan Nilai" sudah digabung ke sini:
   tombol Laporan Nilai (Excel / PDF, route `admin.reports.export*`) dan angka PG/Esai → detail jawaban (`admin.reports.show`).
   `/admin/reports` dan `/admin/reports/filter` hanya mengalihkan.

### Alur Asesor
1. Asesor login di URL yang sama dengan admin (`/login`)
2. Otomatis diarahkan ke `/asesor/dashboard`
3. Dashboard menampilkan sesi ujian yang ditugaskan beserta jumlah peserta, dibagi Sesi Aktif / Sesi Selesai.
   Sesi ID ≥ `Asesor\DashboardController::PROGRESS_FROM_SESSION_ID` (75) baru pindah ke Selesai setelah TTD AK.01,
   nilai esai & wawancara peserta asesor itu lengkap (progres tampil di kartu; peserta yang tidak mulai ujian tidak
   dihitung); sesi sebelumnya tetap memakai jam selesai sesi
4. Dari setiap sesi, asesor bisa memilih:
   - **Nilai Esai** → `GET /asesor/penilaian/{session_id}/esai`
   - **Nilai Wawancara** → `GET /asesor/penilaian/{session_id}/wawancara`

### Tampilan Penilaian Esai
`/asesor/penilaian/{sesi}/esai` = daftar peserta (Progress, Total, tombol Nilai — baca saja). Menilai di halaman
per peserta `/asesor/penilaian/{sesi}/esai/peserta/{student_id}` (`asesor.esai.grade`, page `Asesor/Esai/Nilai`):
kartu per soal senada detail jawaban admin (pertanyaan lalu jawaban + nilai, atas-bawah), bar melayang Sebelumnya / Simpan / Simpan & Berikutnya (urut No. Peserta).
- **Total Nilai** = rata-rata nilai jawaban yang sudah dinilai (nilai 0 ikut dihitung). Frontend
  hanya menampilkan; server menghitung ulang dari `answer_essays.score`
  (`EssayAssessmentController::averageScore`) lalu menyimpan ke `grades.grade`
- Simpan mengirim nilai satu peserta (POST `asesor.esai.store`, format `scores[]` tetap bisa banyak peserta)
- Berkas Essay Migas lewat `asesor.esai.file` (hanya peserta yang ditugaskan). Kunci/panduan jawaban per soal (`essays.answer`)
  tampil lewat tombol "Lihat poin / kisi jawaban" — **aktif** secara bawaan (`config('lsp.asesor_show_answer_key')`, env
  `ASESOR_SHOW_ANSWER_KEY`, default true; false = tidak dikirim ke browser asesor). Admin melihatnya di bank soal ujian dan
  detail jawaban peserta. Kunci kosong/"-" tidak ditampilkan (`RichText::hasContent`)
- Kunci soal esai yang sudah ada diperbarui dari dokumen FR.IA.06 revisi lewat
  `php artisan kunci-esai:perbarui {data} [--exam=ID] [--dry-run]` — data di `database/data/kunci-esai/{data}.json`
  (`classroom_code` + `esai[{question, answer}]`). Soal dicocokkan lewat teks pertanyaan (huruf/angka saja), bukan ID;
  hanya `essays.answer` yang diubah, plus `is_essay = true` pada soal yang cocok (label "Poin / kisi jawaban", bukan
  "Jawaban benar"; `"uraian": false` di data untuk melewati; kolom `is_essay` tidak dibuat migrasi, jadi dicek dulu).
  Contoh: `tot-obe-rev01` (TOT OBE, 26 soal; ujian 15 memakai 10 di antaranya)
- **Rubrik FR.IA.06** (paket V2 HSE, Corporate Legal, ESG): `exams.esai_panduan` = Panduan Penilaian Umum (HTML, tag
  `Exam::PANDUAN_ESAI_TAGS`, `$hidden`) → kartu yang bisa dibuka di atas halaman Nilai; `essays.rubrik` = matriks per soal
  `[{min, max, label, deskripsi}]` (HSE/Legal 90–100 … 10–29, ESG per soal sendiri) → pita rentang di kartu soal, klik =
  nilai tertinggi rentang terisi. Hanya panduan: server tetap menyimpan angka yang diketik. `rubrik` tidak masuk `STUDENT_COLUMNS`

### Tampilan Penilaian Wawancara
Tabel kriteria tetap, satu baris per peserta:
```
No Peserta | Nama | Gaya Wawancara | Penguasaan Materi | Kemampuan Menghadapi Pertanyaan | Hasil Pengerjaan Worksheet Ujian Keterampilan | Total Nilai | Catatan
```
- **Total Nilai** = rata-rata 4 kriteria (skala 0–100), sejajar dengan nilai PG & esai
  - Contoh: (88 + 86 + 86 + 88) / 4 = **87**
- Bobot wawancara diterapkan **sekali** di `ResultCalculatorService` (bukan di controller)
- **Rubrik Penilaian Wawancara** (sama untuk semua skema): `App\Support\RubrikWawancara` — deskripsi indikator kriteria A–C per
  pita N > 80 / 71–80 / 60–70 / < 60 (D Worksheet tanpa deskripsi). Hanya panduan: kartu lipat di atas tabel + rentang di bawah
  tiap isian (prop `rubrik`; `pita()` di Vue harus sama dengan PHP), juga kolom Kategori di PDF ZIP Hasil Ujian

### Nilai Praktik (komponen terpisah, mis. Lifting)
Komposisi Nilai skema (`grading_schemes`, form `/admin/classrooms/{id}/grading-scheme`) = Ujian Tulis (`bobot_ujian_tulis`,
`proporsi_pg`) + Lisan & Keterampilan (`bobot_wawancara`; keterampilan = kriteria Worksheet = 25% wawancara) +
**Ujian Praktik per bagian** (`bobot_praktik_1/2`, persen nilai akhir; total ketiganya 100). Bila bobot praktik > 0 dan sesi
memilih bagian praktik itu (`App\Support\NilaiPraktik::weights()`), asesor mengisi nilai per bagian di
`/asesor/penilaian/{sesi}/praktik` (`asesor.praktik.show/store`, tabel `praktik_scores` unik sesi+peserta+bagian, tombol
"Praktik" di kartu sesi + progres). `ResultCalculatorService` menghitung `participant_results.nilai_praktik` = rata-rata
berbobot bagian yang sudah dinilai (`NilaiPraktik::combine`) dan memakainya seperti komponen lain (normalisasi bobot
komponen yang ada). Tampil di Rekap Hasil (kolom Praktik, rincian per bagian), Rekap Nilai asesor, Tinjau Sertifikasi,
tahap "Nilai" (`ResultProgressService`) & Pipeline dashboard. SK: kolom pertama jadi "NILAI PRAKTIK" bila ada nilai praktik
tanpa wawancara; SP: kolom NILAI PRAKTIK berisi `nilai_praktik ?? nilai_wawancara`. Lifting V2: PG 20 + Calculation 50 +
Gambar 30, lulus ≥ 70 (sesi Lifting tanpa wawancara). CLO/K3L/ESG V2: Tulis 70 (PG 65/Esai 35) + Lisan 22,5 + Keterampilan
(praktik bagian 1) 7,5 (= 30% × 75/25% dokumen skema), lulus ≥ 60. Bila sesi memakai Ujian Praktik/Keterampilan (apa pun
bobotnya — `UjianPraktik::wawancaraTanpaWorksheet()`), wawancara tidak memakai kriteria (D) Worksheet (`praktik_terpisah`; total =
rata-rata 3 kriteria lisan, `hasil_worksheet` disimpan null; rincian wawancara Rekap Hasil juga tanpa D). Sesi Upload Tugas biasa tetap
memakai (D) Worksheet. Ujian praktik baru ikut nilai akhir bila Komposisi Nilai memberi bobot praktik.
Rubrik penilaian per ujian praktik di `exams.praktik_rubrik` (HTML tabel dari FR.IA.04, tag dibatasi `Exam::RUBRIK_TAGS`,
`$hidden` — hanya dikirim ke halaman Nilai Praktik asesor); diisi lewat paket (`praktik.rubrik`, contoh Corporate Legal
Studi Kasus — Surat Kuasa Khusus). **Penilaian per aspek**: `exams.praktik_aspek` = `[{no, nama, bobot}]` (total 100, dari tabel Rekapitulasi
Nilai FR.IA.04; paket `praktik.aspek`) → asesor mengisi nilai per aspek (`praktik_scores.aspek` = `{no: nilai}`), server
menghitung `score` = Σ nilai × bobot / Σ bobot (`NilaiPraktik::aspekScore`, null selama ada aspek kosong — nilai langsung
dari browser diabaikan untuk bagian beraspek). Aspek membawa `kriteria` per rentang skor (`[{min, max, label, deskripsi}]`)
dan catatan per aspek (`praktik_scores.catatan`). Halaman: daftar `/asesor/penilaian/{sesi}/praktik` (baca saja, progres
aspek + tombol Nilai) → nilai per peserta `/praktik/peserta/{student_id}` (`asesor.praktik.grade`, page `Asesor/Praktik/Nilai`:
kartu per aspek + rentang skor yang bisa diklik, bar melayang Sebelumnya / Simpan / Simpan & Berikutnya senada Nilai Esai).
Aspek **per butir** (Lifting, Lampiran Rubrik Penilaian Asesor FR.IA.04): aspek punya `item` `[{no, nama}]` + `pilihan`
`[{label, nilai}]` (Ada & Benar 10 / Ada tapi Salah 5 / Tidak Ada 0; Sequence 20/10/0; butir × nilai tertinggi = 100);
pilihan disimpan di `praktik_scores.rincian` `{no aspek: {no butir: nilai}}`, nilai aspek = jumlah butir
(`NilaiPraktik::itemScore`, server menolak nilai di luar pilihan). Lifting Calculation: B1 45 / B2 20 / B3 20 / B4 15;
Gambar: C Lifting Plan Drawing 100. Aspek boleh membawa `sumber` (sumber bukti), `poin_kunci` dan `gerbang` (gerbang kritis)
+ `maks_gerbang` (50): asesor mencentang "gerbang gagal" → disimpan `rincian[no] = {gerbang: true}` dan server menolak nilai
aspek > maks (ESG: Tugas 1–4 @25%, rentang 76–100 / 51–75 / 26–50 / 0–25; template = soal PDF hal. 1–3 + Lembar Kerja xlsx).
HSE: "RUBRIK PENILAIAN PRAKTIK (KRITERIA UNJUK KERJA)" — aspek = KUK dengan `kelompok` (judul tugas di halaman nilai);
Tugas 1 35% (1 KUK), Tugas 2 35% (2 KUK @17,5), Tugas 3 30% (3 KUK @10) = nilai tugas rata-rata KUK-nya.

---

## Alur Kelulusan: Pengambil Keputusan → Manager Sertifikasi

1. Admin **menunjuk Pengambil Keputusan per sesi** (bukan per peserta) di kartu Detail Sesi
   (`/admin/exam_sessions/{id}`, POST `admin.exam_sessions.pengambilKeputusan` → `exam_sessions.pengambil_keputusan_id`).
   Hanya user ber-role `manager_sertifikasi` yang bisa ditunjuk.
2. **Pengambil Keputusan** (`/manager/sertifikasi/{sesi}`) hanya melihat sesi yang ditunjuk untuknya (dashboard + middleware
   `pk_session` di semua route per sesi, termasuk dokumen & FR.APL/AK; FR.AK.05 dicek di controller). Mencentang Verifikasi
   tahap 1 (`participant_results.manager_verified_at/by`). Tidak ada Finalisasi di portal ini. Centang PK tidak bisa dilepas
   selama Manager Sertifikasi masih mencentang peserta itu.
3. **Manager Sertifikasi** (`/manager-sertifikasi/sertifikasi/{sesi}`, semua sesi) melihat halaman yang sama, mencentang
   Verifikasi tahap 2 (`cert_manager_verified_at/by`, hanya bila tahap 1 sudah) lalu **Finalisasi** — hanya peserta yang
   diverifikasi keduanya (`SertifikasiReviewService::finalize`). `finalized_by` = Manager Sertifikasi.
4. **Keputusan Sertifikasi** tetap atas nama & TTD Pengambil Keputusan: `keputusan_issued_by` diisi PK sesi saat finalisasi
   pertama (dibekukan — ganti penunjukan belakangan tidak mengubah dokumen terbit).

Kedua portal memakai `Components/Sertifikasi/TinjauSertifikasi.vue` (`stage` pk|ms, `base` /manager|/manager-sertifikasi);
route kedua portal sengaja sejajar supaya cukup ganti prefix. Remidi mereset kedua centang. Sesi lama di-backfill
`pengambil_keputusan_id` dari `keputusan_issued_by` / centang PK terakhir (migrasi `2026_10_26_000002`); peserta yang
Final sebelum alur ini tidak punya centang Manager Sertifikasi (ditampilkan "—").

---

## Keamanan Ujian Siswa

Aturan di `Student\BaseExamController` (dipakai PG, Essay, Essay Migas):
- **Kunci jawaban tidak boleh sampai ke browser.** Relasi soal dimuat dengan kolom terbatas
  (`Question::STUDENT_COLUMNS`, `Essay::STUDENT_COLUMNS`); `is_correct`/`score` disembunyikan.
  Setiap query baru di halaman siswa wajib memakai `questionForStudent()` / `essayForStudent()`.
- **Timer dijaga server.** `grades.duration` (sisa ms) hanya boleh berkurang (`syncDuration`), dan dibatasi
  jam dinding `start_time + durasi ujian + toleransi koneksi` (`remainingMs`; `config('lsp.toleransi_koneksi_menit')`, env
  `TOLERANSI_KONEKSI_MENIT`, bawaan **30** — khusus PG/Esai; dibagikan ke halaman lewat prop Inertia `toleransi_koneksi_menit`.
  Ujian Praktik/Keterampilan memakai waktu unggah, lihat di bawah). Timer klien berhenti saat
  peserta offline, jadi toleransi ini menampung gangguan koneksi.
- **Satu ujian berjalan per sesi** (`App\Support\UjianBerjalan`): urutan ujian bebas, tetapi selama satu ujian berjalan — PG/Esai/
  Esai Migas dimulai, belum diakhiri & `remainingMs` > 0, atau bagian praktik dimulai, belum diunggah & waktunya belum habis — ujian
  lain di sesi itu tidak bisa dimulai: `start*` → `otherExamRunningRedirect` (kembali ke dashboard + flash), konfirmasi → prop
  `lock.blocked`, praktik → `UjianPraktik::startBlockedReason`, dashboard → `running_by_session` (kartu PG/Esai) & `praktik_cards.*.locked`.
  Melanjutkan ujian yang sudah dimulai selalu boleh. Memulai ujian lain menutup bagian praktik yang sudah diunggah tetapi waktunya
  masih ada (`laterPartStarted` juga melihat `grades.start_time`; peringatan lewat `lock.closes` / `timer.closes_parts`).
- **Kompensasi kerjakan ulang** (mis. lupa menyimpan jawaban esai): `php artisan ujian:beri-ulang {sesi} {No. Peserta}
  --ujian=esai|pg --alasan="…" [--menit=N] [--dry-run]` — `grades.start_time/end_time` dikosongkan & durasi penuh (peserta menekan
  Kerjakan lagi), jawaban tersimpan **tetap** (startEssay/startExam tidak menimpa jawaban yang ada), nilai esai asesor & hasil sesi
  (nilai, keputusan, verifikasi PK/MS) dikosongkan, Final ditolak, dicatat di `exam_retakes`. Jendela sesi hanya dicek di browser →
  bila jam selesai sesi sudah lewat, perpanjang di Edit Sesi.
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
(pdf, office, gambar, zip/rar; maks 20 MB — **php.ini server harus `upload_max_filesize` ≥ 20M dan `post_max_size` > 20M**, kalau
tidak PHP menolak file lebih dulu: pesan `uploaded` / HTTP 413; batas efektif `AnswerFile::maxUploadMb()` dikirim ke halaman tugas
sebagai `file_max_mb` dan dicek di browser) dan disimpan di disk **`private`**, diunduh lewat controller
(`student.essaysmigas.download`, `admin.essay_migas.download`). File lama di disk `public`
dipindah dengan `php artisan answer-files:move-private` (`--dry-run` untuk cek dulu).

**Ujian Praktik/Keterampilan** = jenis ujian `Exam::TYPE_PRAKTIK` ('Ujian Praktik/Keterampilan' — dulu 'Ujian Praktik', diganti
  migrasi `2026_11_21_000001` beserta judul ujiannya; label tampilan "Praktik" juga jadi "Praktik/Keterampilan"; database yang belum
  dimigrasi tetap jalan: accessor `Exam::type` membaca "Ujian Praktik" sebagai tipe baru, validasi pakai `Exam::TYPES_PRAKTIK`) di admin/exams, dipilih per sesi lewat
`exam_sessions.exam_id_praktik` (setiap versi soal = ujian tersendiri, sesi lama tetap memakai versinya). Ujiannya berisi
panduan (`exams.praktik_panduan`, teks biasa) + template (`praktik_template_path`, disk private, wajib; `$hidden`).
Sesi tanpa ujian praktik = "Upload Tugas" biasa. Tidak dibuatkan ExamGroup: peserta mengerjakannya di halaman tugas
(`/student/praktik-confirmation/{sesi}` = `student.praktik.confirmation`, URL senada exam-confirmation; Upload Tugas biasa tetap
`/student/tugas/{sesi}` = `student.tugas.show`; keduanya `StudentTaskController::show`, URL yang salah dialihkan; tampilan + tabel
informasi senada konfirmasi PG/Esai lewat `Components/Student/{ExamInfoTable,ExamConfirmation}`) — membaca panduan, unduh template (`student.tugas.template` / `peserta.application.tugas.template`,
cek terdaftar di sesi), lalu hasilnya diunggah lewat alur `StudentTask` yang sama (asesor melihatnya di halaman wawancara).
Data ke halaman peserta lewat `UjianPraktik::examFor($sesi)` + `tugasProps()`. Dashboard portal ujian (`Student/Dashboard/Index`):
Ujian Praktik/Keterampilan = kartu sendiri per bagian (`praktik_cards`, Kerjakan / Lanjut / Sudah Diunggah / Waktu Habis), kartu
PG/Esai sesi itu **tidak dikunci** (urutan bebas; `tugasRedirectIfRequired` melewati sesi berujian praktik). Hanya Upload Tugas biasa yang tetap syarat sebelum ujian. Skema di
`Student::TUGAS_EXEMPT_CLASSROOM_CODES` tetap tidak wajib tugas.
Durasi = `exams.duration` (0 = tanpa batas) — aturannya di `App\Support\UjianPraktik`: waktu mulai saat peserta
menekan Mulai (POST `student.tugas.start`, sekali per sesi di `student_praktik_attempts`, durasi dibekukan saat mulai);
**dua durasi**: waktu pengerjaan = durasi (jam dinding, tidak dijeda; panduan & template hanya selama ini) lalu **waktu unggah**
`UjianPraktik::uploadMinutes()` (`config('lsp.praktik_waktu_unggah_menit')`, env `PRAKTIK_WAKTU_UNGGAH_MENIT`, bawaan 10) — hasil
masih bisa diunggah/diganti, setelah itu ditolak. Prop `timer`: `expired` = pengerjaan habis, `closed` = waktu unggah habis.
Beberapa bagian: **urutan bebas, tetapi hanya satu yang berjalan** (dua waktu tidak pernah berjalan bersamaan): selama satu bagian
berjalan (dimulai, belum diunggah, waktu belum habis — `UjianPraktik::runningPart`), bagian lain tidak bisa dimulai
(`startBlockedReason`, prop `timer.start_blocked`, kartu dashboard `locked` = nomor bagian yang berjalan); memulai bagian lain menutup
bagian yang dimulai lebih dulu (`laterPartStarted`, dilihat dari id percobaan → template & unggahan ditutup, `timer.closed_by_next`;
peringatan sebelum Mulai dari `timer.closes_parts`). Halaman
praktik tidak punya tab antar-bagian — tiap bagian dibuka dari kartunya di dashboard (`?bagian=N`). Halaman dipakai ulang Inertia
saat pindah bagian / setelah Mulai: timer disetel ulang lewat watcher props (`initTimer`).
`started_at` = DATETIME (migrasi `2026_11_22_000001`): sebagai TIMESTAMP pertama, MySQL/MariaDB lama (explicit_defaults_for_timestamp
OFF) memberinya ON UPDATE CURRENT_TIMESTAMP → setiap UPDATE baris menggeser waktu mulai ke jam server (+7 jam). Jangan kembalikan
ke `timestamp()`. (Kolom serupa yang belum diperbaiki: `initial_assessments.assessed_at`.)
panduan & template baru dikirim setelah mulai; waktu unggah habis
tanpa unggahan = bagian selesai (`UjianPraktik::closed()`). Praktik berwaktu
hanya dari portal ujian — portal peserta menolak unduh/unggah dan menampilkan petunjuk.
**Dua bagian per sesi** (mis. Lifting: Calculation 300 mnt lalu Gambar 180 mnt): bagian 1 = `exam_id_praktik`, bagian 2 =
`exam_id_praktik_2` (relasi `examPraktik2`, di JSON `exam_praktik2`; hanya bila bagian 1 ada). Setiap bagian punya Mulai,
waktu & unggahan sendiri — kolom `bagian` di `student_praktik_attempts` & `student_tasks` (unik per peserta+sesi+bagian;
Upload Tugas biasa = bagian 1). Semua route/aksi per bagian lewat `?bagian=N` / field `bagian` (bawaan 1); praktik selesai
= semua bagian diunggah atau habis waktunya (`UjianPraktik::tugasStatus()/tugasDone()`). Multi-bagian juga hanya dari portal
ujian (`examPortalOnly`). Rekap Hasil & halaman wawancara asesor: `tugas[student_id]` = daftar `{bagian, original_filename}`.
**Sesi dengan Ujian Praktik wajib untuk semua skema** — termasuk yang dikecualikan dari Upload Tugas (LEM, LHC, LDT, DLD):
cek wajib tugas per sesi pakai `Student::requiresTugasFor($sesi)`, bukan `requiresTugas()`.

Admin bisa mengganti/mengunggah dokumen persyaratan peserta di detail permohonan (POST
`admin.applications.documents.replace`, file baru langsung Terverifikasi, file lama dihapus) — hanya bila saklar
`ADMIN_REPLACE_DOCUMENTS=true` (`config('lsp.admin_replace_documents')`, default mati: tombol tersembunyi & route 403).

Bukti dokumen CV asesor (opsional, per baris Pendidikan/Pelatihan/Pengalaman Kerja/Pengalaman Profesional/Sertifikasi)
disimpan di baris JSON-nya sendiri (`bukti => [id, path, name]`, disk `private`). Browser hanya menerima `id` + `name`
dan mengirim balik `bukti_id` — jangan kirim/terima `path` dari browser.

---

## Penomoran SK / SP

`NumberingService` memakai counter global per tahun di `numbering_counters` (lintas sesi). Nomor dibagikan
saat Manager Sertifikasi klik Finalisasi, urut No. Peserta. Aturan supaya nomor tidak loncat:
- Peserta remidi **memakai ulang** nomor SK/SP lamanya (`RemidiService` tidak mengosongkannya).
- Sesi / peserta / skema yang sudah memegang nomor tidak bisa dihapus (`ParticipantResult::preventLosingNumbers`).
- Nomor disimpan tanpa spasi (mutator di `ParticipantResult`).
- Jangan ubah nomor/counter lewat SQL manual. Pakai `numbering:audit` (cek celah), `numbering:renumber-session`
  (nomori ulang satu sesi + sesuaikan counter) dan `numbering:normalize` (buang spasi) — semua punya `--dry-run`.
- **No. Sertifikat** (`003-010-10-2026-00082` = kode skema-batch-bulan-tahun-urut) diurutkan per **kode skema + tahun**;
  counter-nya per kelas (`numbering_counters.scope` = classroom_id). Nomor berikutnya = maks(counter, nomor tertinggi
  yang sudah tersimpan untuk kode skema+tahun itu) + 1, jadi counter yang hilang/tertinggal tidak membuat urutan mulai
  dari 00001 lagi. Audit: `numbering:audit --type=sertifikat`; perbaiki sesi: `numbering:renumber-session {sesi} --sertifikat=N`
  (menolak bila sertifikat sudah terkirim / FR.AK.14 sudah bermeterai, kecuali `--allow-distributed`).
- `numbering:renumber-session` menerima beberapa sesi (`77 78`): dinomori berurutan sesuai urutan yang ditulis, dan
  nomor lama sesi-sesi itu tidak dianggap bentrok satu sama lain. Pakai ini bila sesi berikutnya melanjutkan nomor yang salah.

---

## Unit Kompetensi per Skema

Daftar unit (kode, judul, urutan) ada di `CompetencyUnitsSeeder::skemas()` dan mengikuti tabel Kemasan pada
dokumen skema. Untuk database yang sudah berisi data, jangan jalankan seeder (kelas dicocokkan lewat `title`,
yang di produksi sudah berbeda → kelas dobel). Pakai `php artisan competency-units:sync` (`--dry-run` untuk cek
dulu): mencocokkan kelas lewat `classrooms_code` (alias kode lama FSI→FMO, ISL→LQO), hanya mengubah kode/judul/urutan
unit, tidak membuat kelas dan tidak menyentuh `judul_unit_en`/`kode_unit_asli`. Unit dibaca langsung saat PDF
dibuat — sertifikat/SK yang sudah ter-cache tetap, PDF yang dibuat ulang memakai unit terbaru.

---

## Paket Soal (tanpa import Excel)

`php artisan paket-soal:pasang v2 [--dry-run] [--skema=esg] [--templates=folder]` memasang soal hasil ekstraksi dokumen
FR.IA.05/06/04 sebagai ujian **baru** per skema: "{judul kelas} - Pilihan Ganda V2", "- Esai V2", "- Ujian Praktik V2".
- Data: `database/data/paket-soal/{versi}/{skema}.json` (+ `images/`). Kelas dicocokkan lewat `classrooms_code`
  (V2: LHC, K3L, CLO, ESG dari folder "24-25"; GLP, QCA, FMO, RDO, PSP, LQO dari folder "27-28" — kunci JSON glp, qc, fsms, rnd,
  panelis, iso17025; esai Panelis = soal + kunci dari FR.IA.06 Panelis, matriks per soal dari "panduan skoringi soal esai - SENSORI.docx", Panduan Penilaian Umum dari
  "FR.IA.06 Pertanyaan dan Jawaban Esai Sensory.pdf" (soal/kunci/matriksnya sama); kelas dicari lewat `classroom_code`, lalu `kode_lama` (FMO←FSI, LQO←ISL seperti competency-units:sync), lalu `judul_cocok`
  (judul kelas memuat kata itu, hanya bila tepat satu kelas) — dry-run menampilkan kode yang dipakai "(paket: FMO)"; koreksi pengguna: GLP PG 26 membawa konteks soal 25, QC PG 99 kunci C, R&D soal 101 dihapus → 100 soal). PG = blok teks/gambar + opsi + kunci (kunci = opsi berstabilo di dokumen sumber).
  Skema 27-28: `durasi`/`komposisi` disamakan dengan CLO/HSE/ESG atas permintaan pengguna (dokumen skema belum diterima): PG 120 / Esai 90,
  Tulis 70 (PG 65/Esai 35) + Lisan 22,5 + Praktik 7,5, lulus 60.
  Praktik skema 27-28: template = bagian untuk asesi saja (bagian asesor, kunci, rubrik dibuang; GLP tanpa lembar F-01 "milik Petugas X"
  yang dibuat fisik asesor; Panelis = Lembar Kerja Asesi DOCX dari `FR.IA.04 Ujian Praktik - Panelis-V2.docx` (root repo, standar
  soal A–K): petunjuk + instruksi 7 tes + Lembar 1–7, 24 pelat Ishihara sesuai lampiran, garis skoring tepat 10 cm). Rubrik bergerbang (GLP, QC, Food Safety, Panelis) seperti ESG; ISO 17025 = KUK
  seperti HSE; R&D = rubrik per nomor soal (5 pita) dikelompokkan per tugas.
  **Revisi V1.1 (21 Nov 2026, disetujui pengguna)** — dokumen asesor `FR.IA.04 … -V1.1.docx` di root repo (dasar = V1 folder 27-28,
  bagian yang tidak konsisten diambil dari V2 root): GLP skenario timbangan meja kg (TG-01–04, AT-xx, BU-01, T5), Stasiun 2 45 mnt,
  Lembar Kerja 4.1–4.5; QC gula 1 kg lot 20 ambil 5, neraca ≥ 2 kg 0,1 g (NR-01/02), label AM-A/AM-B, isian kulkas, waktu 10/20/25/25/40,
  rubrik Tugas 3 = draf LHU; Food Safety bobot 15/20/25/10/30 (= skor maks petunjuk); R&D rujukan Bagian 3.A/3.C + huruf A–H;
  ISO 17025 Soal 3 = control chart 25 uji CRM (±2/±3 SD dari data uji), formulir 25 kolom, template ZIP (DOCX + XLSX data).
  Template peserta GLP/QC dibangun dari dokumen V1.1 (petunjuk + instruksi per stasiun + dokumen + Lembar Kerja; tanpa bagian asesor).
  Skema 24-25 (revisi 21 Nov 2026): HSE V1.1 (`FR.IA.04 Ujian Keterampilan - HSE-V1.1.docx`: "ketiga tugas", Matriks Risiko V2 sebagai Data
  Acuan Tugas 1, Lembar Kerja Tugas 2 lima baris kosong, kunci risiko fisik/ergonomi + rentang dari V2); Legal template ZIP = soal +
  Lembar Jawaban; Lifting: kedua ZIP (Calculation & Gambar) = PDF soal bagiannya + **seluruh** folder "LAMPIRAN SOAL HEAVY LIFTING"
  dari folder 24-25 dengan struktur asli, termasuk .gem (permintaan pengguna 22 Nov 2026; file kunci Office `~$…` dilewati), crane
  utama = Kobelco SL6000; ESG rubrik + tabel "Rentang acuan Tugas 2" (dari ESG V2 G.2) menggantikan rujukan "Bagian G".
  Gambar soal disalin ke disk public dan dirujuk `/storage/paket-soal/{versi}/…` (kolom `questions.question` TEXT —
  jangan simpan gambar base64). Skema tanpa `esai` (Lifting V2) tidak dibuatkan ujian esai.
- Ujian dikenali lewat `exams_code` `exms-{versi}-{skema}-{pg|esai|praktik}` → aman dijalankan ulang (yang sudah ada
  dilewati). Durasi/acak mencontoh ujian sejenis terakhir di kelas itu (bawaan 120 menit). Ujian/sesi lama tidak disentuh.
- Template Ujian Praktik (Lifting ±34 MB, di atas batas upload) **tidak** ada di git: dibaca dari
  `storage/app/paket-soal/{versi}/templates/` lalu disalin ke disk private. **Di server jalankan sebagai user web server**
  (`sudo -u www-data php artisan paket-soal:pasang …`): disk `private` membuat folder baru ber-izin 700, jadi folder yang dibuat
  user SSH (`PadmaForClient`) tak terbaca php-fpm → unduh template 404 (terjadi 24 Nov 2026; diperbaiki `chown -R www-data`).
  Server juga perlu `php artisan storage:link` (tanpa `public/storage` semua gambar soal paket 404). Template belum ada → ujian praktik tetap
  dibuat; jalankan ulang setelah folder diunggah untuk melengkapinya. Bagian praktik dilewati bila migrasi Ujian Praktik belum ada.
- Ubah materi praktik di paket (panduan/durasi di JSON, file template) → `--perbarui-praktik` menyamakan ujian praktik
  yang sudah terpasang (template lama dihapus), **hanya** bila belum dipakai peserta (`student_praktik_attempts` /
  `student_tasks` di sesi yang memakainya); yang sudah dipakai dilewati — revisinya jadi versi paket baru.
  Corporate Legal V2 praktik = Studi Kasus 2 dokumen sumber (Surat Kuasa Khusus) saja, ditampilkan sebagai "Studi Kasus" (tanpa nomor) (durasi ujian terbatas). Durasi praktik V2 = 120 menit,
  kecuali Lifting: `praktik` di JSON = **daftar bagian** (`nama` wajib) → bagian 1 Calculation 300 mnt (kode lama
  `exms-v2-lifting-praktik`, judul "… - Ujian Praktik V2 - Calculation"), bagian 2 Gambar 180 mnt (`exms-v2-lifting-praktik-2`).
  Template `lifting-calculation.zip` & `lifting-gambar.zip`. `--perbarui-praktik` juga menyamakan judul, jadi ujian Lifting
  gabungan yang sudah terpasang berubah jadi bagian 1 dan bagian 2 dibuat baru.
- Paket juga membawa `durasi` (`pg`/`esai`, menit) dan `komposisi` (isian form Komposisi Nilai) per skema dari dokumen skema R2.
  Ujian baru memakai durasi paket; Komposisi Nilai dibuat bila skema belum punya. Yang sudah ada & berbeda disamakan dengan
  **`--perbarui`** (alias lama `--perbarui-praktik`): durasi PG/Esai hanya bila belum ada peserta yang mulai (`grades.start_time`).
  **Komposisi Nilai beku per sesi** (`exam_sessions.komposisi_nilai`, migrasi `2026_11_23_000001`): sebelum `--perbarui` mengubah
  komposisi skema, sesi skema itu yang belum memakai ujian paket versi ini dibekukan dengan komposisi lama — skema yang sama bisa
  punya sesi lama (ujian & komposisi lama, mis. PSP sesi 86) dan sesi paket baru (sesi 98). Semua perhitungan/tampilan nilai per sesi
  lewat `GradingScheme::forSession($sesi)` (bukan `GradingScheme::where('classroom_id', …)`); Rekap Hasil menandai "komposisi khusus
  sesi ini". Lihat/ubah: `php artisan komposisi-nilai:sesi [sesi…] [--skema=PSP] [--lepas|--bekukan] [--dry-run]` — mis. sesi yang
  baru diarahkan ke ujian V2 setelah `--perbarui` ikut beku → `--lepas`; komposisi lama yang sudah tertimpa (`--perbarui` dijalankan
  sebelum fitur ini) → `--bekukan --tulis= --pg= --lisan= --praktik1= --praktik2= --lulus=` (semua wajib, total 100; menimpa yang beku). Form Komposisi Nilai admin tetap mengubah semua sesi yang tidak beku.
- Esai membawa `esai_panduan` (paket) + `esai[].rubrik`: ikut dipasang pada ujian esai baru; ujian esai yang sudah terpasang
  disamakan dengan `--perbarui` (soal dicocokkan lewat teks pertanyaan seperti `kunci-esai:perbarui`), **termasuk** yang jawabannya
  sudah dinilai — rubrik hanya tampilan asesor, nilai tersimpan tidak berubah.
- Data rusak (kunci di luar opsi, gambar hilang, dll.) → perintah gagal tanpa memasang apa pun.

---

## Verifikasi TUK Online (FR.TUK.06)

Checklist per peserta per sesi (`tuk_verifications`), diisi **admin sebagai Pengawas Ujian** di
`/admin/penilaian/{sesi}/verifikasi-tuk` (tab muncul di navigasi sesi `<SessionNav>` bila saklar sesi
`exam_sessions.verifikasi_tuk` aktif). **Hanya pencatatan — tidak mengunci ujian peserta.**
- Butir & pilihan per **revisi dokumen** di `App\Support\TukChecklist::definition()` (`Rev.0`, `Rev.1` = `CURRENT`);
  jawaban per kunci butir (`B1`…`F6`) di kolom JSON `items`. Setiap checklist menyimpan `tuk_verifications.revision`:
  checklist lama tetap Rev.0 (form, validasi & PDF `documents/fr_tuk_06_rev0`), checklist baru Rev.1 (`documents/fr_tuk_06`).
  Kunci butir Rev.0 & Rev.1 sama tetapi isinya beda — jangan pindahkan checklist antar-revisi. Dokumen direvisi lagi →
  tambah revisi di `definition()` + `REVISIONS`, naikkan `CURRENT`, buat view PDF-nya; jangan ubah butir revisi lama.
- Rev.1: status butir tetap disimpan `sesuai|tidak_sesuai` (label Memenuhi/Tidak Memenuhi); kesimpulan `memenuhi|tidak_memenuhi`
  (Rev.0: `layak|layak_perbaikan|tidak_layak`); Lokasi Peserta = pilihan (+ `lokasi_lainnya`); bagian H baru = tabel
  `ketidaksesuaian` (JSON) + `kesimpulan_ulang`; bagian I = `uraian_pemantauan` + `tindakan_pemantauan` + `dampak_pemantauan`.
  F6 Rev.1 memang sama dengan F1 (begitu di dokumen sumber).
- Nama Pengawas Ujian dipilih dari dropdown user ber-role admin (bisa lebih dari satu pengawas). Default:
  pengawas checklist itu, lalu pilihan terakhir admin tsb. di sesi yang sama (session `tuk_pengawas.{sesi}`), lalu
  admin yang login. Nama + TTD (`users.signature_path`, dibuat di Kelola User atau saat menyetujui permohonan)
  disalin ke `pengawas_name`/`pengawas_signature_path` setiap kali disimpan. `verified_at` diisi saat
  kesimpulan verifikasi awal pertama kali disimpan dan tidak bergeser saat F/H/I dilengkapi setelah ujian.
- Bagian B menampilkan **Dokumen Identitas Diri (KTP/SIM/Paspor)** dari permohonan peserta (pratinjau + status
  verifikasinya) sebagai pembanding — hanya tampilan, tidak masuk checklist/PDF. Persyaratannya dikenali dari
  label/kode (`ClassroomDocumentRequirement::isIdentityDocument()`: "identitas", "KTP", "paspor").
- PDF: `DocumentGeneratorService::generateFrTuk06()` → view `documents/fr_tuk_06`.

---

## Penilaian Awal Kelayakan (FR.APL.03)

Diisi admin di detail permohonan; `is_eligible` (skor ≥ threshold) syarat untuk menyetujui permohonan.
Rubrik per skema (dipetakan lewat `classroom_id` produksi 3–28) ditanam per **revisi dokumen**:
`App\Support\InitialAssessmentRubric\Rev03` (beku) dan `Rev04` (berlaku; satu halaman per skema).
- Setiap penilaian menyimpan `initial_assessments.revision`. Penilaian lama tetap ditampilkan/dicetak dengan
  revisinya (`InitialAssessment::rubric()`, view `fr_apl_03_rev03` vs `fr_apl_03`) — skor, threshold & PDF
  tidak ikut berubah. "Ubah Penilaian" menyimpan ulang dengan revisi terbaru (`InitialAssessmentRubric::CURRENT`).
- Dokumen direvisi lagi → buat kelas `Rev05` + view, daftarkan di `REVISIONS`, naikkan `CURRENT`. Jangan ubah
  kunci/skor kelas revisi lama.
- Jawaban dari browser disaring `InitialAssessmentRubric::normalize()`; isian "lainnya sebutkan" di
  `answers['sebutkan'][kriteria]`. Kriteria `single` wajib, `multi` boleh kosong.
- Rev.04 PDF: kotak "Dokumen Pendukung" = Lengkap bila semua persyaratan **wajib** sudah terverifikasi;
  "Hasil Verifikasi Pengalaman" & "Keputusan" mengikuti `is_eligible`.
- Salah salin skor di revisi yang **berlaku** (bukan revisi baru) boleh dibetulkan di kelasnya selama kunci opsi tetap
  (contoh: Prodi Corporate Legal Rev.04 = Sosial 2 / Sains 1, `prodiSosialSains()`). Penilaian yang sudah tersimpan
  menyimpan `total_score`/`is_eligible`, jadi hitung ulang dengan `php artisan initial-assessment:rescore [--classroom=28]`
  (`--dry-run` dulu; hanya revisi `CURRENT`, status permohonan tidak diubah — yang sudah disetujui tapi jadi tidak layak
  hanya dilaporkan).

---

## Routes Baru

```php
// Admin — penugasan asesor (middleware: auth). Halamannya = detail sesi (admin.exam_sessions.show)
GET   /admin/penilaian                              → admin.penilaian.index  (redirect → admin.exam_sessions.index)
GET   /admin/penilaian/{exam_session_id}            → admin.penilaian.show   (redirect → admin.exam_sessions.show)
POST  /admin/penilaian/{exam_session_id}/penugasan  → admin.penilaian.saveAssignments

// Asesor — portal penilaian (middleware: auth + asesor)
GET   /asesor/dashboard                                    → asesor.dashboard
GET   /asesor/penilaian/{exam_session_id}/esai            → asesor.esai.show
POST  /asesor/penilaian/{exam_session_id}/esai            → asesor.esai.store
GET   /asesor/penilaian/{exam_session_id}/esai/peserta/{student_id} → asesor.esai.grade (nilai esai satu peserta)
GET   /asesor/penilaian/{exam_session_id}/wawancara       → asesor.wawancara.show
POST  /asesor/penilaian/{exam_session_id}/wawancara       → asesor.wawancara.store
GET   /asesor/penilaian/{exam_session_id}/rekap           → asesor.rekap.show (Rekap Nilai, baca saja)

// Penunjukan PK per sesi (admin) & portal Manager Sertifikasi (middleware: auth + cert_manager)
POST  /admin/exam_sessions/{exam_session}/pengambil-keputusan → admin.exam_sessions.pengambilKeputusan
GET   /manager-sertifikasi/dashboard                        → cert_manager.dashboard
GET   /manager-sertifikasi/sertifikasi/{examSession}        → cert_manager.sertifikasi.show
POST  /manager-sertifikasi/sertifikasi/{examSession}/verifikasi/{studentId} → cert_manager.sertifikasi.toggle_verify
POST  /manager-sertifikasi/sertifikasi/{examSession}/finalize → cert_manager.sertifikasi.finalize
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
- Halaman admin per sesi (Peserta & Asesor = detail sesi, Permohonan `?exam_session_id=`, Verifikasi TUK, TTD AK.01)
  memakai tab `Components/SessionNav.vue` di atasnya; halaman per sesi baru → tambahkan **tab**, bukan menu sidebar.
  Rekap Hasil sengaja **tidak** jadi tab (permintaan pengguna) — tetap di menu Hasil Penilaian. Detail permohonan yang dibuka dari tab sesi membawa `?sesi={id}` supaya Kembali/hapus kembali ke tab itu.
  Daftar sesi (menu Sesi Ujian) memakai `Components/SessionTable.vue` (kolom Aksi lewat slot `#actions`).
- PDF SP/SK/Sertifikat yang sudah dikirim di-cache permanen (`DocumentGeneratorService::cachedPdf`). Kuncinya memuat
  nomor, tanggal kirim, versi template, dan data peserta yang tercetak (`printedStudentKey`: nama, jenis kelamin,
  No. Peserta, NIK) — koreksi di menu Peserta langsung tampil. Menambah data peserta baru ke dokumen → tambahkan ke kunci itu.
  Varian dengan KAN juga memuat isi file logo KAN (`config/lsp_documents.php` → `logo_kan`), jadi ganti logo = PDF dibuat
  ulang; `php artisan documents:regenerate-kan [sesi...] [--dry-run]` membuatnya sekarang (varian tanpa KAN tidak disentuh).
- Simpan sesi (`ExamSessionController::update`) menyamakan ExamGroup peserta dengan ujian PG/Esai sesi: ujian baru ditambahkan,
  ujian yang tidak lagi dipilih dilepas (beserta baris `grades` kosong) — kecuali yang sudah dimulai peserta (`grades.start_time`),
  dilaporkan lewat flash. Tidak ada yang dilepas bila sesi tanpa PG/Esai (ExamGroup juga penanda peserta terdaftar).
- No. Peserta `{kode}.{batch}.{tahun}.{urut}` (`StudentEnrollmentService::nextNoParticipant`): batch = **Kode Batch sesi yang berlaku**
  (`batchFor()`), bukan salinan `assessment_applications.kode_batch` — reissue setelah Kode Batch dibetulkan (02 → 002) ikut batch baru.
  Simpan sesi dengan Kode Batch berubah menyamakan `kode_batch` semua permohonan sesi itu. Nomor yang sudah terbit dengan batch lama:
  `php artisan no-peserta:samakan-batch [sesi…] [--kirim-email] [--dry-run]` (urut dipertahankan bila belum dipakai; yang Final
  dilewati; No. Peserta = login ujian → `--kirim-email` mengirim `NoPesertaChangedMail` (nomor lama → baru) ke email peserta,
  gagal kirim tidak membatalkan perubahan dan tampil di kolom Email; dry-run tidak mengirim apa pun).
- Jumlah peserta per sesi di daftar mana pun: `ExamSession::withActiveStudentsCount()` (peserta unik terdaftar di
  `exam_groups`, akun aktif — sama dengan baris Rekap Hasil/Tinjau Sertifikasi). **Jangan** hitung `participant_results`:
  barisnya baru dibuat saat Rekap dibuka dan tertinggal saat peserta dipindah batch/dikeluarkan. Progres/cek berbasis
  `participant_results` (ditinjau, terkirim, finalisasi, syarat kirim SP/SK) pakai scope `ParticipantResult::enrolled()`.
- Kerangka halaman admin (Blueprint, `/admin/blueprint`): menu daftar = `Components/Admin/ListToolbar` (aksi kiri,
  filter + Cari kanan) -> kartu tabel `table-list` (kolom teks panjang `cell-wrap`, judul kolom boleh 2 baris) -> Pagination.
  Halaman detail/form/per sesi: baris atas `Components/Admin/BackBar` (Kembali kiri, aksi halaman kanan); tab per sesi
  = BackBar -> SessionNav -> isi. Kartu bagian = `Components/SectionCard` (juga dipakai portal asesor lewat
  `Components/Asesor/SessionHeader`); header tabel selalu gelap (`thead-dark`).
- Urutan resmi soal esai = `id` naik (`Exam::essays()`), sama dengan urutan yang dilihat peserta. Detail jawaban admin,
  penilaian asesor, PDF & Excel mencocokkan jawaban lewat `essay_id` — jangan lewat posisi/`essay_order`.
- Panduan portal Asesor, Pengambil Keputusan & Manager Sertifikasi: isi per halaman ada di
  `resources/js/Components/Guide/{Asesor,Manager,CertificationManager}/*.vue`,
  ditampilkan lewat `<PageGuide storage-key="...">` di atas halaman **dan** dirangkai di `Pages/*/Guide/Index.vue`.
  Ubah perilaku/tombol halaman → perbarui komponen Guide-nya juga.
- Panduan PDF untuk dibagikan (Peserta — Pendaftaran, Peserta — Mengerjakan Ujian, Asesor) ada di `docs/panduan/`:
  sumber HTML + CSS di `sumber/`, tangkapan layar (data uji lokal sesi 98, tanpa data peserta sungguhan) di `img/`,
  cetak ulang dengan `python docs/panduan/build.py` (PDF, Playwright + Chrome) dan `python docs/panduan/build_docx.py`
  (Word, python-docx + beautifulsoup4 — dari HTML yang sama). Alur peserta/asesor berubah → perbarui juga.

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
| `sticky-bottom`, juga `position-sticky`/`sticky-top` di halaman portal | `sticky-bottom` baru di 5.2; sticky apa pun tidak menempel di dalam `main.content` layout Admin/Asesor/Manager (Volt: `overflow: hidden`) | bar melayang: CSS sendiri `position: fixed` (contoh `.unsaved-bar` di `Pages/Admin/ExamSessions/Show.vue`) |
| `text-bg-*` (5.2), `*-subtle` mis. `bg-success-subtle` (5.3), `focus-ring` (5.3) | belum ada di 5.1 | badge lembut → `<StatusBadge tone="...">`; selain itu `bg-gray-100` / CSS sendiri |
| Accordion tanpa warna sendiri | tampil transparan di tema ini (tanpa `--bs-accordion-*`) | beri warna lewat CSS scoped (lihat `Pages/*/Guide/Index.vue`) |
| `<span>` berisi teks di dalam `.dropdown-menu` | Volt: `.dropdown-menu span { width: 30px }` (untuk ikon) → teks turun per kata | pakai `<a>`/`<div>`, atau timpa `width: auto` (lihat `Components/SearchSelect.vue`) |
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

Peserta dummy untuk mencoba paket soal V2 (alur peserta PG/Esai/Ujian Praktik/Keterampilan + penilaian asesor), setelah
`paket-soal:pasang v2`: `php artisan db:seed --class=DemoUjianPraktikSeeder` → sesi "DEMO V2 - …" per skema terpasang, peserta
`DEMO-{SKEMA}-01/02` (login ujian di `/`), asesor demo (email/password di seeder). Tidak jalan di production; aman diulang.

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
- Menambah/menghapus **foreign key** di tabel yang dirujuk view `v_sertifikasi_kelulusan` (`participant_results`, `students`,
  `classrooms`, `exam_sessions`): SQLite membangun ulang tabel dan RENAME-nya ditolak karena view. Pakai pola
  `alterBehindView()` (PRAGMA `legacy_alter_table`) seperti migrasi `2026_10_26_000002`/`000003`.
- CI (`.github/workflows/ci.yml`) menjalankan build frontend + test. Pint **tidak** dipaksakan — codebase
  memakai perataan `=>` yang tidak sesuai preset Pint.

---

## File Penting Lainnya

| File | Keterangan |
|---|---|
| `app/Services/AdminDashboardService.php` | Isi `/admin/dashboard`: kartu Perlu Tindakan, sesi berlangsung/terjadwal + kesiapan (asesor, PK, TUK), Pipeline Sertifikasi (sesi selesai yang SK-nya belum terkirim ke semua peserta: Ujian → Dinilai → PK → MS → Final → Terkirim), e-meterai, statistik bulanan, kelulusan per skema, aktivitas terbaru. Pipeline & hitungan tindakan hanya sesi ID ≥ `TRACK_FROM_SESSION_ID` (= batas progres dashboard asesor, 75) |
| `app/Providers/FortifyServiceProvider.php` | Konfigurasi login view & redirect |
| `app/Exports/GradesEssayExport.php` | Export Excel nilai esai (sudah pakai `score` nyata) |
| `resources/js/Components/Sidebar.vue` | Navigasi sidebar admin ("Sesi Ujian" juga menyala di semua halaman per sesi) |
| `config/auth.php` | Definisi guards: web, student, participant |
| `Admin/MateraiLogController.php` | Menu **Log Materai** (`/admin/materai`): riwayat pembubuhan FR.AK.14 + FR.AK.01, baca saja dari kolom `materai_*` (tidak ada tabel log) + sisa kuota Peruri (`/admin/materai/saldo`, cache 5 menit; kartunya `Components/MateraiSaldo.vue`, juga di tab TTD AK.01) |
| `config/materai.php` | E-meterai Peruri: `MATERAI_ENABLED` (saklar utama), `MATERAI_AUTO_STAMP`, `MATERAI_FIRST_SESSION_ID` (pembebasan FR.AK.14) |
| `app/Services/PeruriService.php` | Panggilan Peruri. Koneksi putus (cURL 56/timeout): **login & cek saldo** diulang otomatis (`RETRY_TIMES`, jeda `RETRY_SLEEP_MS`); **Generate SN & stamping tidak** (saldo bisa sudah terpakai). Semua putus koneksi jadi `PeruriStampingException` berpesan jelas (host + saran cek koneksi/whitelist IP) — tampil di kartu saldo & alasan gagal Log Materai |
| `app/Jobs/StampFrAk01Job.php`, `StampFrAk14Job.php` | Pembubuhan e-meterai (idempoten). FR.AK.14: kotak meterai di kiri TTD, halaman + koordinatnya diukur saat PDF dibuat (`DocumentGeneratorService::frAk14Document`), tidak dipatok |
