<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Pemberitahuan - {{ $spNumber }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Cambria, 'Times New Roman', Times, serif; font-size: 11pt; color: #000; }

@page {
    size: A4 portrait;
    margin: 20mm 20mm 20mm 20mm;
}

/* ──────────────────────────────────────────
   KOP
   ────────────────────────────────────────── */
.kop-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}
.kop-table td {
    vertical-align: middle;
    padding: 0;
}
.kop-logo {
    width: 32mm;
    text-align: left;
}
.kop-logo img {
    max-width: 30mm;
    max-height: 16mm;
}
.kop-teks {
    text-align: center;
    padding: 0 6pt;
}
.kop-nama {
    font-weight: bold;
    font-size: 15pt;
    letter-spacing: 0.5pt;
    line-height: 1.2;
}
.kop-alamat {
    font-size: 8pt;
    margin-top: 2pt;
    line-height: 1.35;
}
.kop-garis-atas {
    border: none;
    border-top: 2.5pt solid #000;
    margin: 3pt 0 1pt;
}
.kop-garis-bawah {
    border: none;
    border-top: 1pt solid #000;
    margin: 0 0 6pt;
}

/* ──────────────────────────────────────────
   UTILITAS
   ────────────────────────────────────────── */
.text-center { text-align: center; }
.fw-bold     { font-weight: bold; }
.underline   { text-decoration: underline; }
.mt-2  { margin-top: 6pt; }
.mt-4  { margin-top: 12pt; }
.mb-2  { margin-bottom: 6pt; }
.mb-4  { margin-bottom: 12pt; }
.mb-6  { margin-bottom: 18pt; }
.indent { text-indent: 28pt; text-align: justify; }

/* ──────────────────────────────────────────
   FIELD TABLE (Nomor / Lampiran / Perihal dsb)
   ────────────────────────────────────────── */
.field-table {
    border-collapse: collapse;
    margin-bottom: 10pt;
}
.field-table td {
    padding: 1.5pt 0;
    vertical-align: top;
    font-size: 11pt;
}
.field-label { width: 90pt; }
.field-colon { width: 8pt; }

/* ──────────────────────────────────────────
   TABEL NILAI — hitam putih (tanpa background warna)
   ────────────────────────────────────────── */
.nilai-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10pt;
    margin: 8pt 0 12pt;
}
.nilai-table th,
.nilai-table td {
    border: 0.75pt solid #000;
    padding: 4pt 5pt;
    text-align: center;
    vertical-align: middle;
}
.nilai-table th {
    font-weight: bold;
}

/* ──────────────────────────────────────────
   TTD
   ────────────────────────────────────────── */
.ttd-outer {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12pt;
}
.ttd-outer td {
    vertical-align: bottom;
    padding: 0;
}
.ttd-inner {
    font-size: 11pt;
    line-height: 1.5;
}
.ttd-img img {
    max-height: 18mm;
    max-width: 50mm;
    margin: 4pt 0;
}
.ttd-name {
    font-weight: bold;
    font-size: 11pt;
}
.ttd-jabatan {
    font-size: 10pt;
}

/* ──────────────────────────────────────────
   PAGE BREAK
   ────────────────────────────────────────── */
.page-break {
    page-break-after: always;
}

/* ──────────────────────────────────────────
   LAMPIRAN (HAL 2)
   ────────────────────────────────────────── */
.lamp-title {
    font-weight: bold;
    font-size: 12pt;
    text-decoration: underline;
    margin-bottom: 8pt;
}
.section-bold {
    font-weight: bold;
    font-size: 11pt;
    margin: 8pt 0 4pt;
}
.sub-section {
    padding-left: 14pt;
    font-weight: bold;
    font-size: 11pt;
    margin: 4pt 0;
}
.lamp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10pt;
    margin-bottom: 10pt;
}
.lamp-table th,
.lamp-table td {
    border: 0.5pt solid #000;
    padding: 4pt 6pt;
    vertical-align: middle;
}
.lamp-table th {
    font-weight: bold;
    text-align: center;
}
.lamp-table td.center {
    text-align: center;
}
.lamp-table td.merged {
    text-align: center;
    font-weight: bold;
}
</style>
</head>
<body>

@php
    $skema    = $classroom?->title ?? '-';
    $spConf   = $sp;
    $namaKota = $lsp['kota'] ?? 'Semarang';

    // Pembobotan dari GradingScheme jika tersedia, fallback ke config
    $pembobotan_a = $spConf['pembobotan_a'] ?? [];
    if ($scheme) {
        $pembobotan_a = [
            [
                'metode'      => 'Pilihan Ganda',
                'jumlah_soal' => $scheme->jumlah_soal_pg ?? ($spConf['pembobotan_a'][0]['jumlah_soal'] ?? '-'),
                'durasi'      => isset($scheme->durasi_pg_menit) ? $scheme->durasi_pg_menit . ' Menit' : ($spConf['pembobotan_a'][0]['durasi'] ?? '-'),
                'proporsi'    => isset($scheme->proporsi_pg) ? number_format($scheme->proporsi_pg, 0) . '%' : ($spConf['pembobotan_a'][0]['proporsi'] ?? '-'),
            ],
            [
                'metode'      => 'Esai',
                'jumlah_soal' => $scheme->jumlah_soal_esai ?? ($spConf['pembobotan_a'][1]['jumlah_soal'] ?? '-'),
                'durasi'      => isset($scheme->durasi_esai_menit) ? $scheme->durasi_esai_menit . ' Menit' : ($spConf['pembobotan_a'][1]['durasi'] ?? '-'),
                'proporsi'    => isset($scheme->proporsi_pg) ? number_format(100 - $scheme->proporsi_pg, 0) . '%' : ($spConf['pembobotan_a'][1]['proporsi'] ?? '-'),
            ],
        ];
    }
    $pembobotan_b = $spConf['pembobotan_b'] ?? [];
    if ($scheme) {
        $pembobotan_b = [
            [
                'metode'   => 'Ujian Tulis (Pilihan Ganda + Esai)',
                'proporsi' => isset($scheme->bobot_ujian_tulis) ? number_format($scheme->bobot_ujian_tulis, 0) . '%' : ($spConf['pembobotan_b'][0]['proporsi'] ?? '-'),
            ],
            [
                'metode'   => 'Ujian Lisan + Keterampilan',
                'proporsi' => isset($scheme->bobot_wawancara) ? number_format($scheme->bobot_wawancara, 0) . '%' : ($spConf['pembobotan_b'][1]['proporsi'] ?? '-'),
            ],
        ];
    }
    $nilaiKelulusan = $scheme?->nilai_kelulusan ?? 60;
    $standarKelulusan = [
        'Asesi dinyatakan lulus apabila nilai minimal &ge;' . $nilaiKelulusan,
        'Asesi (Peserta) dinyatakan tidak lulus jika tidak memenuhi poin 1',
        'Asesi (Peserta) yang dinyatakan tidak lulus dapat mengikuti kegiatan remidial',
    ];
@endphp

{{-- ══════════════════════════════════════════════════════
     HAL 1 — Surat Pemberitahuan
     ══════════════════════════════════════════════════════ --}}

{{-- KOP --}}
<table class="kop-table">
    <tr>
        <td class="kop-logo">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}">
            @endif
        </td>
        <td class="kop-teks">
            <div class="kop-nama">{{ $lsp['nama'] }}</div>
            <div class="kop-alamat">
                {{ $lsp['alamat'] }}<br>
                Telp. {{ $lsp['telp'] }} &nbsp;|&nbsp; {{ $lsp['web'] }}
            </div>
        </td>
    </tr>
</table>
<hr class="kop-garis-atas">
<hr class="kop-garis-bawah">

{{-- Judul --}}
<div class="text-center fw-bold underline mb-4" style="margin-top:8pt; font-size:12pt;">
    SURAT PEMBERITAHUAN
</div>

{{-- Nomor / Lampiran / Perihal --}}
<table class="field-table mb-4">
    <tr>
        <td class="field-label">Nomor</td>
        <td class="field-colon">:</td>
        <td>{{ $spNumber }}</td>
    </tr>
    <tr>
        <td class="field-label">Lampiran</td>
        <td class="field-colon">:</td>
        <td>1 Halaman</td>
    </tr>
    <tr>
        <td class="field-label">Perihal</td>
        <td class="field-colon">:</td>
        <td>{{ $spConf['perihal'] }}</td>
    </tr>
</table>

{{-- Kepada --}}
<div class="fw-bold mb-2">Kepada Yth:</div>
<div class="mb-4" style="line-height:1.5;">
    {{ $student?->name }}<br>
    Di Tempat
</div>

{{-- Paragraf pembuka --}}
<div class="mb-4 indent">
    {!! str_replace(['{skema}', '{held_on}'], [e($skema), e($heldOn)], $spConf['pembuka']) !!}
</div>

{{-- Field Nama / No Reg / Skema --}}
<table class="field-table mb-2">
    <tr>
        <td class="field-label fw-bold">Nama</td>
        <td class="field-colon">:</td>
        <td class="fw-bold">{{ $student?->name }}</td>
    </tr>
    <tr>
        <td class="field-label fw-bold">No Registrasi</td>
        <td class="field-colon">:</td>
        <td class="fw-bold">{{ $student?->no_participant ?? '-' }}</td>
    </tr>
    <tr>
        <td class="field-label fw-bold">Skema</td>
        <td class="field-colon">:</td>
        <td class="fw-bold">{{ $skema }}</td>
    </tr>
</table>

{{-- Tabel Nilai (hitam-putih) --}}
<table class="nilai-table">
    <thead>
        <tr>
            <th rowspan="2" style="width:20%;">NILAI<br>WAWANCARA</th>
            <th rowspan="2" style="width:22%;">NILAI PILIHAN<br>GANDA</th>
            <th rowspan="2" style="width:16%;">NILAI ESAI</th>
            <th colspan="2" style="width:42%;">REKAPITULASI HASIL ASESMEN</th>
        </tr>
        <tr>
            <th style="width:21%;">HASIL NILAI</th>
            <th style="width:21%;">STATUS</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $result->nilai_wawancara !== null ? number_format($result->nilai_wawancara, 2) : '-' }}</td>
            <td>{{ $result->nilai_pg        !== null ? number_format($result->nilai_pg, 2)        : '-' }}</td>
            <td>{{ $result->nilai_esai      !== null ? number_format($result->nilai_esai, 2)      : '-' }}</td>
            <td><strong>{{ $result->nilai_akhir !== null ? number_format($result->nilai_akhir, 2) : '-' }}</strong></td>
            <td><strong>{{ $statusKompeten }}</strong></td>
        </tr>
    </tbody>
</table>

{{-- Penutup --}}
<div class="mb-6 indent">{{ $spConf['penutup'] }}</div>

{{-- Blok TTD --}}
<table class="ttd-outer">
    <tr>
        <td style="width:55%;"></td>
        <td style="width:45%;" class="ttd-inner">
            <div>{{ $namaKota }}, {{ $tglSurat }}</div>
            <div>Mengetahui</div>
            <div class="ttd-img">
                @if(file_exists($ttdPath))
                    <img src="{{ $ttdPath }}">
                @else
                    <div style="height:18mm;"></div>
                @endif
            </div>
            <div class="ttd-name">{{ $penandatangan['nama'] }}</div>
            <div class="ttd-jabatan">Ketua LSP Edukia</div>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════════
     HAL 2 — LAMPIRAN
     ══════════════════════════════════════════════════════ --}}
<div class="page-break"></div>

{{-- KOP ulang --}}
<table class="kop-table">
    <tr>
        <td class="kop-logo">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}">
            @endif
        </td>
        <td class="kop-teks">
            <div class="kop-nama">{{ $lsp['nama'] }}</div>
            <div class="kop-alamat">
                {{ $lsp['alamat'] }}<br>
                Telp. {{ $lsp['telp'] }} &nbsp;|&nbsp; {{ $lsp['web'] }}
            </div>
        </td>
    </tr>
</table>
<hr class="kop-garis-atas">
<hr class="kop-garis-bawah">

{{-- Judul Lampiran --}}
<div class="text-center fw-bold mb-2" style="margin-top:8pt; font-size:12pt;">LAMPIRAN</div>
<div class="text-center lamp-title mb-4">
    STANDAR PEMBOBOTAN PENILAIAN DAN KELULUSAN
</div>

{{-- A. Pembobotan Penilaian --}}
<div class="section-bold">A. Pembobotan Penilaian</div>

<div class="sub-section">a. &nbsp; Bobot Penilaian Pada Setiap Metode Ujian</div>
<div style="padding-left:20pt; margin-bottom:10pt;">
    <table class="lamp-table">
        <thead>
            <tr>
                <th>Metode Ujian</th>
                <th>Jumlah Soal</th>
                <th>Lama Pengerjaan</th>
                <th>Proporsi Nilai</th>
            </tr>
            <tr>
                <td colspan="4" class="merged">Evaluasi per Unit Kompetensi</td>
            </tr>
        </thead>
        <tbody>
            @foreach($pembobotan_a as $row)
            <tr>
                <td>{{ $row['metode'] }}</td>
                <td class="center">{{ $row['jumlah_soal'] }}</td>
                <td class="center">{{ $row['durasi'] }}</td>
                <td class="center">{{ $row['proporsi'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="sub-section">b. &nbsp; Rekapitulasi Hasil Pembobotan Penilaian</div>
<div style="padding-left:20pt; margin-bottom:14pt;">
    <table class="lamp-table">
        <thead>
            <tr>
                <th style="width:70%;">Metode Ujian</th>
                <th style="width:30%;">Proporsi Nilai</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pembobotan_b as $row)
            <tr>
                <td>{{ $row['metode'] }}</td>
                <td class="center">{{ $row['proporsi'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- B. Standar Kelulusan --}}
<div class="section-bold">B. Standar Kelulusan</div>
<div style="padding-left:14pt; margin-top:4pt;">
    @foreach($standarKelulusan as $i => $item)
    <table style="width:100%; border-collapse:collapse; margin-bottom:3pt;">
        <tr>
            <td style="width:16pt; vertical-align:top; font-size:11pt;">{{ $i + 1 }}.</td>
            <td style="vertical-align:top; font-size:11pt; line-height:1.5;">{!! $item !!}</td>
        </tr>
    </table>
    @endforeach
</div>

</body>
</html>
