# BLUEPRINT Pembenahan — cbt.sistemedu.com

Dokumen ini memetakan apa yang perlu dibenahi pada codebase agar lebih **efisien secara flow & tampilan**, serta menghapus **fitur/kode yang redundan**. Disusun berdasarkan audit struktur controller, service, route, dan komponen Vue.

> Status: usulan (belum dieksekusi). Centang `[x]` saat item selesai.

---

## Ringkasan Temuan

Codebase terdiri dari **dua generasi kode**:

- **Kode baru** — alur Result / Penilaian / Peserta beserta `app/Services` & `app/Jobs`. Sudah memakai service layer, dependency injection, dan struktur rapi. **Ini menjadi standar acuan.**
- **Kode lama** — alur ujian siswa (`Exam` / `Essay` / `EssayMigas`). Duplikatif, menyisakan kode debug, query N+1, dan masalah verifikasi kepemilikan (ownership).

**Target:** mengangkat kualitas kode lama ke standar kode baru dan menghilangkan triplikasi backend maupun frontend.

---

## 🔴 Prioritas 1 — Bug & Keamanan (wajib)

### 1.1 Ownership tidak diverifikasi (celah keamanan)
- **Lokasi:** `app/Http/Controllers/Student/ExamController.php:211` (`updateDuration`) dan kembarannya di `EssayController`, `EssayMigasController`.
- **Masalah:** `Grade::find($grade_id)` tanpa cek pemilik → siswa mana pun bisa mengubah durasi grade milik siswa lain. Method `answerQuestion` / `endExam` juga menerima `exam_id` & `exam_session_id` mentah dari request tanpa memastikan siswa benar-benar ter-enroll.
- **Fix:** selalu filter dengan `student_id = auth()->guard('student')->id()` dan scope `whereHas('examGroup')`. Gunakan route-model binding + policy bila memungkinkan.

### 1.2 Sisa kode debug di produksi
- `Log::info/warning/error` bertaburan di `Student/EssayController.php:164-222` dan `Student/EssayMigasController` (membocorkan jawaban siswa ke log).
- `// dd($exam)` di 7 lokasi pada `Admin/ExamController.php`.
- **Fix:** hapus seluruhnya. Jika butuh audit, gunakan channel log khusus pada level `debug`, bukan `info`.

### 1.3 N+1 query pada laporan
- **Lokasi:** `Admin/ReportController.php` — `exportPdf` (137), `exportPdf2` (191), `show`.
- **Masalah:** setelah eager-load, masih memanggil `setRelation(...->get())` sebanyak **4 query per grade** di dalam loop. 1 sesi 100 peserta ≈ 400+ query.
- **Fix:** muat relasi sekali lewat eager-load, lalu filter di memori (collection) — bukan query ulang per baris.

---

## 🟠 Prioritas 2 — Redundansi (efisiensi flow)

### 2.1 Tiga controller ujian siswa ~90% identik
- **Lokasi:** `Student/ExamController`, `EssayController`, `EssayMigasController`.
- **Masalah:** struktur method sama persis (`confirmation → start → show → updateDuration → answerQuestion → endExam → resultExam`). Pola `Grade::where(exam_id)->where(exam_session_id)->where(student_id)->first()` diulang **40×**. `auth()->guard('student')->user()->id` diulang **40×**.
- **Blueprint refactor:**

```
app/Services/Student/
  ExamSessionService.php   ← currentGrade(), enrollAnswers(), saveDuration()
app/Http/Controllers/Student/
  BaseExamController.php    ← logika bersama (abstract: model soal & path view)
    ├ ExamController        (Pilihan Ganda)
    ├ EssayController       (Essay)
    └ EssayMigasController  (Essay Migas + upload)
```

- Tambahkan helper `$this->student()` pengganti `auth()->guard('student')->user()`.
- Tambahkan query scope `Grade::forStudent(...)`.

### 2.2 `exportPdf` vs `exportPdf2` — duplikat penuh
- **Lokasi:** `Admin/ReportController.php:118` & `:165`.
- **Masalah:** dua method identik; beda hanya **view** (`EssayReportPDF` vs `ReportPDF`) dan **format** (A4-Portrait vs A0-Landscape). Total 3 route untuk hal yang sama.
- **Fix:** satukan menjadi `exportPdf(Request $request, string $layout)` dengan map konfigurasi:

```php
$layouts = [
    'ringkas' => ['view' => 'EssayReportPDF', 'format' => 'A4', 'orientation' => 'P'],
    'lebar'   => ['view' => 'ReportPDF',      'format' => 'A0', 'orientation' => 'L'],
];
```

  Hapus satu route yang berlebih.

### 2.3 Triplikasi frontend (tampilan)
- **Lokasi:** `Student/{Exams,Essays,EssaysMigas}/Show.vue` (342–559 baris), plus `Confirmation.vue` & `Result.vue` masing-masing ber-3.
- **Masalah:** berbagi logika sama — timer `setInterval` + PUT `updateDuration`, grid navigasi nomor soal, auto-save jawaban — tetapi disalin tiga kali.
- **Blueprint komponen bersama:**

```
resources/js/
  Composables/useExamTimer.js     ← satu sumber logika timer + autosave durasi
  Components/Exam/
    ExamTimer.vue
    QuestionNavigator.vue         ← grid nomor + status terjawab
    ExamShell.vue                 ← kerangka layout konfirmasi/kerjakan/hasil
```

- Tiap halaman cukup mengisi slot konten soal. Perkiraan: ~1.300 baris Vue → ~500 baris.

---

## 🟡 Prioritas 3 — Kerapian & Konvensi

| Item | Tindakan |
|---|---|
| Komentar bahasa-campur & komentar "jelas" (`//get exam group`) | Hapus yang redundan |
| PHPDoc `@return void` salah (sebenarnya `Response`/`Redirect`) | Perbaiki atau hapus |
| Route ujian siswa manual sangat verbose (`web.php:249-343`) | Kelompokkan via prefix group; pertimbangkan controller invokable per langkah |
| `answerQuestion` menghitung `is_correct` untuk Essay (tak relevan, dinilai asesor) | Hapus logika skor pada alur essay |
| Konsistensi penilaian: PG menyimpan `grade`, Essay/Migas tidak | Pindahkan kalkulasi akhir ke `ResultCalculatorService` yang sudah ada (satu pintu) |
| Penempatan route `students/import` & `exams/questions/*` sebelum `Route::resource` | Dokumentasikan agar tidak tertimpa |

---

## Urutan Eksekusi yang Disarankan

1. **Fase A (cepat & aman):** hapus debug (1.2) → perbaiki ownership (1.1) → gabung `exportPdf` (2.2) → perbaiki N+1 (1.3). Risiko rendah, dampak langsung.
2. **Fase B (refactor backend):** `BaseExamController` + `ExamSessionService` (2.1).
3. **Fase C (refactor frontend):** composable + komponen ujian (2.3).
4. **Fase D:** kerapian konvensi (Prioritas 3).

---

## Checklist Progres

- [x] 1.1 Verifikasi ownership pada `updateDuration` / `answerQuestion` / `endExam`
- [x] 1.2 Hapus `Log::*` & `// dd()` sisa debug
- [x] 1.3 Hilangkan N+1 di `ReportController`
- [x] 2.1 `BaseExamController` (tiga controller siswa extend base)
- [x] 2.2 Satukan `exportPdf` / `exportPdf2` (param `layout=ringkas|lebar`)
- [x] 2.3 `Composables/useExamTimer.js` + `Components/Exam/ExamEndModals.vue`
- [x] 3.x Kerapian konvensi & route (`web.php` dirapikan, komentar redundan dihapus)
