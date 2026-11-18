# Perbandingan Matriks Penilaian: GLP Technician (FR.IA.04) vs Ujian Praktek HSE Soal No. 1

## 1. Ringkasan perbedaan

| Aspek | HSE Soal No. 1 (sekarang) | GLP FR.IA.04 (rekomendasi) |
|---|---|---|
| Jenis rubrik | **Holistik**: 1 aspek besar dinilai sekaligus | **Analitik**: dipecah menjadi item kecil yang bisa diamati |
| Jumlah aspek | 1 ("Penyusunan Dokumen HIRADC/JSA") | 29 item di 4 unit |
| Skala | 100 / 75 / 50 / 25 | 2 / 1 / 0 per item |
| Batas Kompeten | Tidak tegas. Nilai 75 disebut "Kompeten dengan Catatan", nilai 50 "Cukup / Kurang Kompeten" | Tegas: semua item kritis = 2 **dan** skor ≥ 75 % |
| Item kritis | Tidak ada | Ada (6 item) |
| Kata kunci deskriptor | "ketidaktepatan minor", "banyak kesalahan", "secara umum" (tidak terukur) | Terukur: "≥ 6 dari 8", "3 titik × semua timbangan", "total 1,22–1,24 kg" |
| Pemetaan Unit/KUK | Tidak ada | Ada per item |
| Sumber bukti | Hanya dokumen tertulis | Observasi, Lembar Kerja, lisan |
| Kunci jawaban | Ada, tetapi ada yang tidak konsisten (lihat bagian 3) | Ada, sudah dijadikan deskriptor skor |
| Siap dinilai AI | Sulit: AI harus menafsirkan "minor" dan "banyak", hasilnya bisa berubah-ubah | Siap: tiap item punya kunci dan deskriptor yang jelas |

**Kesimpulan:** rubrik HSE yang sekarang membuat asesor dan AI harus menilai berdasarkan kesan umum. Dua asesor bisa memberi 75 dan 50 untuk jawaban yang sama, karena batas antara "minor" dan "banyak kesalahan" tidak didefinisikan. Rubrik HSE sebaiknya diubah ke format analitik yang sama dengan GLP.

---

## 2. Usulan matriks HSE Soal No. 1 dengan format standar

Semua item dinilai dari Lembar Kerja (formulir HIRADC/JSA), jadi semuanya bisa dinilai AI.

| ID | Indikator | Kritis | Skor 2 | Skor 1 | Skor 0 |
|---|---|---|---|---|---|
| H-01 | Mengidentifikasi bahaya **kimia**: uap pelarut toksik (inhalasi/kontak kulit) dan sifat mudah terbakar | **Ya** | Toksik **dan** flammable disebut | Salah satu saja | Tidak ada / salah kategori |
| H-02 | Mengidentifikasi bahaya **fisik**: tumpahan (lantai licin), potensi kebakaran/sumber api, pecahnya alat gelas | Tidak | ≥ 2 bahaya fisik yang relevan | 1 bahaya | Tidak ada |
| H-03 | Mengidentifikasi bahaya **ergonomi**: postur membungkuk / berdiri lama di depan lemari asam | Tidak | Bahaya + penyebabnya disebut | Disebut tanpa penyebab | Tidak ada |
| H-04 | Menentukan Likelihood dan Severity tiap bahaya sesuai matriks risiko yang diberikan, dengan nilai yang masuk akal | Tidak | Semua bahaya dinilai dan nilainya masuk rentang kunci | Ada 1 nilai di luar rentang | ≥ 2 di luar rentang / tidak diisi |
| H-05 | Menghitung Tingkat Risiko (L × S) dan kategorinya dengan benar | **Ya** | Semua perhitungan dan kategori benar | — | Ada hitungan atau kategori salah |
| H-06 | Menyusun pengendalian sesuai **urutan hierarki** (Eliminasi → Substitusi → Rekayasa → Administratif → APD) | **Ya** | Urutan benar, kelima level dipertimbangkan (boleh "tidak dapat dilakukan" + alasan) | Urutan benar tetapi ada level terlewat tanpa alasan | Urutan salah / langsung APD saja |
| H-07 | Pengendalian **spesifik dan relevan** dengan bahayanya | Tidak | ≥ 3 kontrol spesifik, mis. fume hood tersertifikasi dengan face velocity sesuai, sarung tangan nitril/neoprene, goggles, jas lab katun/anti-statis, jauhkan sumber api, spill kit, SOP + warning sign | 1–2 kontrol spesifik, sisanya umum ("pakai APD") | Kontrol tidak relevan |
| H-08 | Setiap bahaya punya pengendalian yang sesuai (kimia, fisik, dan ergonomi masing-masing tertangani) | Tidak | Ketiga kategori tertangani | 2 kategori | ≤ 1 kategori |

**Keputusan:** skor maksimal 16, batas 75 % = **12**. **K** jika H-01, H-05, H-06 = 2 **dan** total ≥ 12.

**Jika format nilai 100/75/50/25 tetap dibutuhkan** (misalnya untuk rapor), nilai itu bisa dihitung otomatis dari skor analitik:

| Persentase skor | Nilai | Keterangan |
|---|---|---|
| ≥ 90 % dan semua item kritis = 2 | 100 | Sepenuhnya Kompeten |
| 75–89 % dan semua item kritis = 2 | 75 | Kompeten dengan Catatan |
| 50–74 %, atau ada item kritis < 2 | 50 | Belum Kompeten |
| < 50 % | 25 | Belum Kompeten |

Dengan cara ini 100/75/50/25 bukan lagi tebakan asesor, melainkan hasil hitungan.

---

## 3. Masalah pada soal dan kunci HSE yang perlu diperbaiki

1. **Matriks risiko tidak diberikan kepada asesi.** Skala Likelihood (1–5?), Severity (1–5?), dan batas kategori (mis. 1–4 Rendah, 5–9 Sedang, 10–16 Tinggi, 20–25 Ekstrem) harus dicantumkan di soal. Tanpa ini, jawaban "12 = High Risk" tidak bisa dinilai benar atau salah secara adil.
2. **Severity ergonomi = 4 (cedera berat/keracunan/kebakaran)** di kunci kemungkinan besar salah salin dari baris kimia. Postur membungkuk biasanya Severity 2 (cedera ringan/keluhan otot), sehingga tingkat risikonya sekitar 6 (Sedang). Saat ini ketiga bahaya punya nilai identik 3 × 4 = 12, yang terlihat seperti disalin.
3. **Bahaya "Fisik" di kunci tercampur** dengan kebakaran (yang juga sifat kimia pelarut). Sebaiknya kunci memisahkan dengan jelas apa yang masuk kimia dan apa yang masuk fisik, atau menerima keduanya.
4. **Kolom pengendalian tidak dipetakan per bahaya.** Di kunci, eliminasi/substitusi/rekayasa ditulis di baris kimia, sedangkan APD ditulis di baris ergonomi, padahal APD itu untuk bahaya kimia. Pengendalian ergonomi hanya administratif; bisa ditambah rekayasa (mengatur tinggi kerja, kursi/pijakan).
5. **Tidak ada risiko sisa (residual risk).** HIRADC umumnya meminta penilaian ulang setelah pengendalian. Putuskan apakah ini diminta; jika ya, tambahkan item penilaian.
6. **Unit kompetensi dan KUK tidak tercantum.** Perlu ditambahkan supaya hasil bisa dilaporkan per unit seperti GLP.
7. **Nama pelarut tidak disebut** ("pelarut organik"). Lebih baik sebut contoh (mis. n-heksana atau diklorometana) agar Severity dan pilihan sarung tangan bisa dinilai dengan kunci yang pasti.

---

## 4. Kesimpulan untuk penerapan di aplikasi

Kedua soal bisa memakai **satu struktur data yang sama** (file JSON rubrik), walaupun jenis tugasnya berbeda:

- GLP = praktik + observasi + lembar kerja (campuran asesor dan AI)
- HSE = dokumen tertulis saja (seluruhnya bisa diusulkan oleh AI, lalu disahkan asesor)

Field yang sama: `id, unit, kuk, indikator, bukti, kritis, ai, skor{2,1,0}, kunci`. Aturan keputusannya juga sama: semua item kritis = 2 dan skor ≥ 75 %. Perbedaannya hanya pada isi item. Rubrik HSE dalam format ini ada di `rubrik-HSE-soal-1.json`.
