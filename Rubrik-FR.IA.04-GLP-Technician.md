# Rekomendasi Matriks Penilaian — FR.IA.04 Ujian Praktik GLP Technician (V1)

## 1. Prinsip matriks

Satu format yang sama untuk semua stasiun dan bisa dipakai untuk soal lain:

**Skema → Unit → KUK → Item penilaian (indikator yang bisa diamati)**

Setiap item punya:

| Kolom | Isi |
|---|---|
| ID | Kode unik, misal `S2-03` (Stasiun 2, item 3) |
| Unit / KUK | Unit kompetensi dan KUK yang dibuktikan |
| Indikator | Kalimat yang bisa diamati: "Asesi ... " |
| Sumber bukti | **OBS** = observasi asesor, **LK** = Lembar Kerja tertulis, **LIS** = pertanyaan lisan |
| Kritis | **Ya** = wajib skor 2. Jika gagal, unit langsung BK |
| Skor 2 / 1 / 0 | Deskriptor tertulis untuk tiap skor, jadi asesor tidak menebak |
| AI | **Ya** = bisa dinilai AI dari Lembar Kerja. **Tidak** = asesor mengisi dari observasi |

**Skala skor (sama untuk semua item):**

- **2 = Sesuai**: dilakukan lengkap dan benar sesuai SOP/kunci
- **1 = Sebagian**: dilakukan tetapi tidak lengkap atau ada kesalahan kecil yang tidak mengubah hasil
- **0 = Tidak sesuai**: tidak dilakukan, salah, atau membahayakan integritas data

**Aturan keputusan (dihitung otomatis, bukan dinilai ulang):**

1. Unit **K (Kompeten)** jika **semua item Kritis = 2** **dan** total skor unit **≥ 75 %** dari skor maksimal unit.
2. Selain itu **BK (Belum Kompeten)**. Sistem menampilkan item mana yang gagal sebagai umpan balik.
3. Rekomendasi akhir asesi **K** hanya jika **semua unit K**.
4. Skor AI selalu berstatus **usulan**. Keputusan final tetap ditandatangani asesor.

---

## 2. Matriks per stasiun

### Stasiun 1 — Persiapan & Facility Walkthrough · SP.GLP.001.01 (KUK 1.1–1.4)

| ID | KUK | Indikator | Bukti | Kritis | Skor 2 | Skor 1 | Skor 0 | AI |
|---|---|---|---|---|---|---|---|---|
| S1-01 | 1.4 | Menemukan T1 (timbangan kalibrasi tidak berlaku) dan memberi tanda "Tidak Boleh Digunakan" serta memisahkannya | OBS + LK | **Ya** | Tercatat di LK 4.1 **dan** alat diberi tanda + dipisahkan | Tercatat, tetapi tidak diberi tanda/dipisahkan | Tidak ditemukan atau alat tetap dipakai | Sebagian (LK) |
| S1-02 | 1.3 | Menemukan T2: suhu 27,8 °C di luar 20–25 °C tanpa tindakan **dan** satu hari kosong | LK | Tidak | Kedua poin + tindakan (lapor/tindak lanjut) | Satu poin saja, atau tanpa tindakan | Tidak ditemukan | Ya |
| S1-03 | 1.2 | Menemukan T3: entri logbook pakai pensil dan tanpa paraf (ALCOA) | LK | Tidak | Kedua poin + alasan ALCOA | Satu poin | Tidak ditemukan | Ya |
| S1-04 | 1.1 | Menemukan T4: orang yang sama menjabat PJ Analisis dan QA (konflik kepentingan) | LK | Tidak | Ditemukan + alasan QA tidak independen | Ditemukan tanpa alasan | Tidak ditemukan | Ya |
| S1-05 | 1.3 | Membaca dan mencatat termohigrometer (suhu, %RH, jam) | OBS + LK | Tidak | Ketiga nilai tercatat | 1–2 nilai | Tidak dicatat | Ya |
| S1-06 | 1.1–1.4 | Melaporkan ketidaksesuaian kepada penyelia sebelum lanjut | OBS + LK | Tidak | Lapor + kolom "Dilaporkan ke / jam" terisi | Lapor, kolom tidak diisi | Tidak lapor | Tidak |

**Aturan tambahan:** minimal 3 dari 4 temuan (T1–T4) dengan skor ≥ 1. T1 wajib.

---

### Stasiun 2 — Pelaksanaan Pengecekan Silang · SP.GLP.002.01 (KUK 1.1–1.4), SP.GLP.003.01 (KUK 1.1)

| ID | KUK | Indikator | Bukti | Kritis | Skor 2 | Skor 1 | Skor 0 | AI |
|---|---|---|---|---|---|---|---|---|
| S2-01 | 002-1.1 | Memakai APD dan mengisi identitas pengecekan (tanggal, jam, titik, petugas, daftar timbangan) | OBS + LK | Tidak | APD lengkap + semua kolom identitas terisi | Ada 1–2 kolom kosong atau APD tidak lengkap | Tanpa APD atau identitas kosong | Sebagian (LK) |
| S2-02 | 002-1.1 | Menata timbangan: datar/stabil, kipas dimatikan/dijauhkan, waterpass di tengah | OBS | Tidak | Ketiganya dilakukan | 1–2 dilakukan | Tidak dilakukan | Tidak |
| S2-03 | 002-1.2 | Membersihkan piringan, menyalakan dan menunggu ±5 menit sebelum mengukur | OBS | **Ya** | Menunggu ≥ 5 menit + piringan bersih | Menunggu < 5 menit tapi ada pemanasan | Langsung mengukur | Tidak |
| S2-04 | 002-1.2 | Cek nol setiap timbangan dan mencatat tampilan sebelum/sesudah ZERO/TARE | OBS + LK | Tidak | Semua timbangan, sebelum & sesudah tercatat | Dilakukan, catatan tidak lengkap | Tidak dilakukan | Sebagian (LK) |
| S2-05 | 002-1.3 | Uji 3 titik beban (rendah/sedang/tinggi), beban di tengah, catat saat stabil, cek kembali ke nol | OBS + LK | Tidak | 3 titik × semua timbangan, kolom "Kembali ke nol" & jam terisi | Ada titik/timbangan terlewat | Tidak dilakukan | Sebagian (LK) |
| S2-06 | 002-1.3 | Uji silang: menentukan timbangan acuan (selisih terkecil), 2 putaran dengan urutan dibalik | OBS + LK | Tidak | Acuan benar + 2 putaran | Hanya 1 putaran atau acuan salah | Tidak dilakukan | Sebagian (LK) |
| S2-07 | 002-1.4 | Uji pojok di tengah + 4 pojok setiap timbangan meja | OBS + LK | Tidak | 5 posisi × semua timbangan + rentang Maks−Min | Ada posisi terlewat | Tidak dilakukan | Ya |
| S2-08 | 002-1.4 | Menangani anak timbangan dengan pinset/sarung tangan | OBS | Tidak | Selalu | Sesekali dengan tangan kosong | Selalu tangan kosong | Tidak |
| S2-09 | 003-1.1 | Pencatatan sesuai ALCOA: pulpen permanen, koreksi coret satu garis + paraf + tanggal + alasan, tidak memakai pensil/tip-ex/kertas buram | OBS + LK | **Ya** | Tidak ada pelanggaran | Koreksi kurang lengkap (mis. tanpa alasan) | Pakai pensil/tip-ex/kertas buram/menimpa angka | Sebagian (foto LK) |
| S2-10 | 002-1.1 | Tidak memakai timbangan yang sudah dinyatakan tidak layak (T1) | OBS | **Ya** | Tidak dipakai | — | Dipakai | Tidak |

---

### Stasiun 3 — Pengendalian Mutu & Data · SP.GLP.003.01 (KUK 1.2–1.4)

| ID | KUK | Indikator | Bukti | Kritis | Skor 2 | Skor 1 | Skor 0 | AI |
|---|---|---|---|---|---|---|---|---|
| S3-01 | 1.2 | Menghitung selisih thd standar, thd acuan (rata-rata 2 putaran), dan rentang uji pojok dari data sendiri | LK | Tidak | Semua benar | 1 kesalahan hitung | ≥ 2 salah / tidak dihitung | Ya |
| S3-02 | 1.3 | Menetapkan zona & status tiap timbangan sesuai toleransi SOP 5.1 (<0,05 aman; 0,05–0,10 waspada; >0,10 atau tidak kembali nol = TIDAK LAYAK) | LK | **Ya** | Semua status konsisten dengan data | — | Ada status yang salah | Ya |
| S3-03 | 1.2 | TG-02: status mingguan + plot grafik (M1–M3 LAYAK; M4–M7 waspada; M8 TIDAK LAYAK) | LK | Tidak | 8 status benar + plot | 6–7 benar atau tanpa plot | ≤ 5 benar | Ya |
| S3-04 | 1.3 | Mengenali pola drift negatif dan bahwa M5–M7 tidak diuji ulang ≤ 24 jam | LK | Tidak | Kedua poin | Satu poin | Tidak ada | Ya |
| S3-05 | 1.4 | Menyusun tindakan koreksi SOP 5.2 secara berurutan (6 langkah kunci) | LK | Tidak | ≥ 5 langkah benar & urut | 3–4 langkah | ≤ 2 langkah | Ya |
| S3-06 | 1.4 | Menghitung kekurangan berat petani (berat × 20/19,88) | LK | Tidak | Total 1,22–1,24 kg, tiap baris ±0,01 kg | Metode benar, ada salah hitung | Metode salah | Ya |
| S3-07 | 1.4 | Menyebut alur kompensasi: diusulkan koordinator → disetujui manajer operasional → diinformasikan ke petani | LK | Tidak | Lengkap | Sebagian | Salah/kosong | Ya |
| S3-08 | 1.2–1.3 | Memeriksa F-01 Petugas X (C1–C8) | LK | **Ya** | ≥ 6 dari 8 **termasuk C5 dan C6** | 4–5 temuan, atau ≥ 6 tanpa C5/C6 | ≤ 3 | Ya |

---

### Stasiun 4 — Pasca Pengujian · SP.GLP.004.01 (KUK 1.1–1.3)

| ID | KUK | Indikator | Bukti | Kritis | Skor 2 | Skor 1 | Skor 0 | AI |
|---|---|---|---|---|---|---|---|---|
| S4-01 | 1.1 | Menempel label status sesuai hasil 4.3-A sendiri (TG-04 "JANGAN DIPAKAI") | OBS + LK | **Ya** | Semua label cocok dengan data | — | Ada label tidak cocok | Sebagian (LK) |
| S4-02 | 1.1 | Membersihkan piringan, mematikan/standby, membuang sampah, mengisi log pembersihan | OBS + LK | Tidak | Semua + log terisi | Log tidak lengkap | Tidak dilakukan | Sebagian (LK) |
| S4-03 | 1.2 | Rekonsiliasi anak timbangan (jumlah & kode keluar = kembali), 5 kg cacat dipisah & berlabel, beban uji tertutup | OBS + LK | Tidak | Ketiganya | 1–2 | Tidak dilakukan | Sebagian (LK) |
| S4-04 | 1.3 | Mencatat log ketidaksesuaian ICS (minimal TG-04 dan anak timbangan 5 kg cacat) | LK | Tidak | Kedua poin | Satu poin | Tidak ada | Ya |
| S4-05 | 1.3 | Menandatangani LK/F-01, menyerahkan untuk verifikasi, mengarsipkan | OBS + LK | Tidak | Ketiganya | 1–2 | Tidak dilakukan | Sebagian (LK) |

---

## 3. Ringkasan skor & keputusan

| Unit | Jumlah item | Skor maks | Batas 75 % | Item kritis |
|---|---|---|---|---|
| SP.GLP.001.01 | 6 | 12 | 9 | S1-01 |
| SP.GLP.002.01 | 9 (S2-01…S2-08, S2-10) | 18 | 14 | S2-03, S2-10 |
| SP.GLP.003.01 | 9 (S2-09 + S3-01…S3-08) | 18 | 14 | S2-09, S3-02, S3-08 |
| SP.GLP.004.01 | 5 | 10 | 8 | S4-01 |

> Catatan: S2-09 diamati di Stasiun 2 tetapi menilai KUK 003-1.1, jadi skornya masuk ke SP.GLP.003.01. Lembar observasi tetap per stasiun; pengelompokan per unit dilakukan oleh aplikasi. Batas 75 % dibulatkan ke atas.

---

## 4. Alur penilaian dengan AI

1. **Asesor** mengisi item OBS langsung di aplikasi saat ujian (tablet/HP), cukup klik 2 / 1 / 0.
2. **Lembar Kerja** asesi difoto/di-scan dan diunggah.
3. **AI** (mis. Gemini di Google AI Studio) menerima: rubrik JSON + kunci jawaban + gambar/teks LK. AI hanya menilai item `ai = Ya/Sebagian`, dan mengembalikan JSON: `skor`, `bukti` (kutipan dari LK), `alasan`, `keyakinan`.
4. **Kode aplikasi** (bukan AI) menjumlah skor dan menerapkan aturan K/BK. Ini membuat keputusan konsisten dan bisa diaudit.
5. **Asesor** meninjau usulan AI. Item dengan keyakinan rendah atau item kritis selalu ditandai untuk dicek manual. Asesor menyetujui atau mengubah, lalu menandatangani.

Contoh prompt dan skema output ada di file `rubrik-FR.IA.04-GLP.json` (bagian `ai_prompt` dan `ai_output_schema`).

---

## 5. Hal di dokumen yang sebaiknya diperbaiki dulu

Ini akan membuat penilaian AI tidak konsisten jika dibiarkan:

1. **Waktu Stasiun 2**: tabel ringkasan 40 menit, bagian 3 menulis 45 menit. Total 120 menit hanya cocok dengan 45 menit.
2. **Jenis timbangan bercampur**: bagian 2.1 menyebut timbangan analitik d = 0,1 mg dan anak timbang 10/50/100 g, sedangkan Stasiun 2–4 memakai timbangan meja (kg), anak timbangan 10 kg, dan TG-01…TG-04.
3. **TG-04 dan anak timbangan 5 kg cacat** muncul di kunci Stasiun 4 tetapi tidak ada di daftar persiapan atau temuan yang ditanam.
4. **"Timbangan B"** disebut di observasi Stasiun 1, sedangkan bagian 2.1 hanya menyebut "Timbangan A" dan "timbangan berlabel kalibrasi tidak berlaku".
5. **Lembar rekonsiliasi** mencantumkan anak timbangan 1/2/5/10 kg, tidak sama dengan daftar alat.
6. **Kunci Stasiun 1 dan 2** hanya dua baris. Matriks di atas sudah mengisinya, tetapi sebaiknya dimasukkan juga ke dokumen.
7. **Teks KUK** (judul elemen 1.1–1.4 tiap unit) belum tertulis di dokumen. Pemetaan KUK di matriks ini perlu dicocokkan dengan standar skema yang resmi.
