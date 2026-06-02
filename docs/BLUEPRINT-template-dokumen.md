# Blueprint — Rekonstruksi Template Dokumen Sertifikasi

Status: **Disetujui** · Tanggal: 2026-06-02

## Keputusan Terkunci

| # | Topik | Keputusan |
|---|---|---|
| 1 | Istilah status | **KOMPETEN / TIDAK KOMPETEN** (map: `LULUS`→KOMPETEN, `TIDAK_LULUS`→TIDAK KOMPETEN) |
| 2 | `CertificateTemplate` | **Hapus total** — model, migrasi, form upload, controller `save`. Halaman admin jadi read-only + preview |
| 3 | Asset gambar | **Disediakan user**. Implementasi pakai placeholder + struktur folder `resources/lsp-assets/` |
| 4 | Unit kompetensi | **Dari DB `ClassroomCompetencyUnit`** (tetap dinamis per skema) |

## Cakupan

3 dokumen PDF dari menu Template Dokumen:
- **SP** (Surat Pemberitahuan) — 2 halaman, tanpa varian
- **SK** (Surat Keputusan) — 3 halaman, varian **dengan KAN / tanpa KAN**
- **Sertifikat** — 2 halaman, varian **dengan KAN / tanpa KAN**

Semua teks boilerplate (kop, menimbang, mengingat, penandatangan, pembobotan, kategori, standar kelulusan) **hardcode** di `config/lsp_documents.php` + blade. Data per-peserta tetap dari `ParticipantResult` & relasinya.

## Referensi Python
- `generate_sp.py` → SP
- `generate_sk_tanpa_kan.py` → SK
- `generate_sertif_serkom_v2_tanpa_unit_komp.py` → Sertifikat

## Struktur File

```
resources/lsp-assets/        logo-edukia.png, logo-kan.png, ttd.png,
                             bg-sertifikat-depan.png, bg-sertifikat-kan.png, bg-sertifikat-tanpa-kan.png
resources/views/documents/   sp.blade.php (BARU), sk.blade.php (REVISI), sertifikat.blade.php (REVISI)
  partials/                  _kop-header.blade.php, _tabel-nilai.blade.php
config/lsp_documents.php      BARU — teks fix
app/Services/DocumentGeneratorService.php  generateSp(), generateSk($r,$kan), generateSertifikat($r,$kan)
```

## Signature Service

```php
public function generateSp(ParticipantResult $r): string
public function generateSk(ParticipantResult $r, bool $kan): string
public function generateSertifikat(ParticipantResult $r, bool $kan): string
```

Varian KAN:
- SK → logo KAN di kop hal.1 (kondisional)
- Sertifikat → background hal.2 `bg-sertifikat-kan.png` vs `bg-sertifikat-tanpa-kan.png`

Cache key: `md5($number.'|'.($kan?'kan':'nokan'))`.

## Tahapan Implementasi
1. Asset fix di `resources/lsp-assets/` (placeholder dulu)
2. `config/lsp_documents.php`
3. Partial blade `_kop-header`, `_tabel-nilai`
4. `sp.blade.php` + `NumberingService::nextSpNumber()`
5. Revisi `sk.blade.php` (varian KAN)
6. Revisi `sertifikat.blade.php` (varian KAN)
7. Revisi `DocumentGeneratorService`
8. Update pemanggil (Admin/Peserta ResultController, ResultDistributionMail)
9. Halaman admin read-only + 5 tombol preview (SP, SK-KAN, SK-noKAN, Sertif-KAN, Sertif-noKAN)
10. Cleanup: hapus `CertificateTemplate` (model, migrasi drop kolom, form Vue)

## Penomoran SP
Tambah `NumberingService::nextSpNumber()` format `NNN/SP/LSP-EDUKIA/{romawi-bulan}/{tahun}`. Opsional kolom `participant_results.sp_number`.
