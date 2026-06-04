<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Pemberitahuan - {{ $spNumber }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Cambria, 'Times New Roman', Times, serif; font-size: 12pt; color: #000; }



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
    width: 39mm;
    text-align: left;
}
.kop-logo img {
    width: 36mm;
    height: auto;
}
.kop-teks {
    text-align: center;
    padding: 0 6pt;
}
.kop-nama {
    font-weight: normal;
    font-size: 22pt;
    letter-spacing: 0.5pt;
    line-height: 1.2;
}
.kop-alamat {
    font-size: 12pt;
    margin-top: 2pt;
    line-height: 1.35;
}
.kop-garis-atas {
    width: 100%;
    height: 1.5pt;
    background-color: #000;
    margin: 3pt 0 6pt;
    font-size: 0;
    line-height: 0;
}

/* ──────────────────────────────────────────
   UTILITAS
   ────────────────────────────────────────── */
.text-center { text-align: center; }
.fw-bold     { font-weight: bold; }
.underline   { border-bottom: 0.75pt solid #000; padding-bottom: 2pt; display: inline; }
.mt-2  { margin-top: 6pt; }
.mt-4  { margin-top: 12pt; }
.mb-2  { margin-bottom: 6pt; }
.mb-4  { margin-bottom: 12pt; }
.mb-6  { margin-bottom: 18pt; }
.indent { text-indent: 28pt; text-align: justify; }

/* ──────────────────────────────────────────
   CONTENT WRAPPER
   Halaman: margin 1.72cm kiri/kanan (HEADER_MARGIN)
   Konten : indent 0.78cm tambahan → total 2.5cm (CONTENT_MARGIN)
   ────────────────────────────────────────── */
.content-wrap {
    margin-left: 8mm;
    margin-right: 8mm;
}

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
    font-size: 12pt;
}
.field-label   { white-space: nowrap; padding-right: 4pt; }
.field-label-s { width: 70pt; }   /* Nomor / Lampiran / Perihal */
.field-label-p { width: 90pt; }   /* Nama / No Registrasi / Skema */
.field-colon   { width: 6pt; }

/* ──────────────────────────────────────────
   TABEL NILAI
   ────────────────────────────────────────── */
.nilai-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12pt;
    margin: 8pt 0 12pt;
}
.nilai-table th,
.nilai-table td {
    border: 0.75pt solid #000;
    padding: 4pt 5pt;
    text-align: center;
    vertical-align: middle;
}
.nilai-table th,
.nilai-table .nth {
    font-weight: bold;
    background-color: #B8D6F0;
    color: #000000;
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
    font-size: 12pt;
    line-height: 1.5;
}
.ttd-img img {
    height: 13mm;
    width: auto;
    margin: 4pt 0;
}
.ttd-name {
    font-weight: normal;
    font-size: 12pt;
}
.ttd-jabatan {
    font-size: 12pt;
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
    margin-bottom: 8pt;
}
.section-bold {
    font-weight: bold;
    font-size: 12pt;
    margin: 8pt 0 4pt;
}
.sub-section {
    padding-left: 14pt;
    font-weight: bold;
    font-size: 12pt;
    margin: 4pt 0;
}
.lamp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12pt;
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
        'Asesi (Peserta) dinyatakan lulus uji kompetensi apabila nilai minimal &ge;' . $nilaiKelulusan,
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
                <img src="{{ $logoEdukiaPath }}" style="width:36mm;">
            @endif
        </td>
        <td class="kop-teks">
            <div class="kop-nama">{{ $lsp['nama'] }}</div>
            <div class="kop-alamat">
                {{ $lsp['alamat'] }} Telp. {{ $lsp['telp'] }}<br>
                {{ $lsp['web'] }}
            </div>
        </td>
    </tr>
</table>
<div class="kop-garis-atas"></div>

<div class="content-wrap">
{{-- Judul --}}
<div class="text-center fw-bold mb-4" style="margin-top:8pt; font-size:12pt;">
    <span class="underline">SURAT PEMBERITAHUAN</span>
</div>

{{-- Nomor / Lampiran / Perihal --}}
<table class="field-table mb-4">
    <tr>
        <td class="field-label field-label-s">Nomor</td>
        <td class="field-colon">:</td>
        <td>{{ $spNumber }}</td>
    </tr>
    <tr>
        <td class="field-label field-label-s">Lampiran</td>
        <td class="field-colon">:</td>
        <td>1 Halaman</td>
    </tr>
    <tr>
        <td class="field-label field-label-s">Perihal</td>
        <td class="field-colon">:</td>
        <td>{{ $spConf['perihal'] }}</td>
    </tr>
</table>

{{-- Kepada --}}
<div class="fw-bold">Kepada Yth:</div>
<div class="mb-4" style="line-height:1.5;">
    {{ $student?->gender === 'L' ? 'Bapak' : 'Ibu' }} {{ $student?->name }}<br>
    Di Tempat
</div>

{{-- Paragraf pembuka --}}
<div class="mb-4 indent">
    {!! str_replace(['{skema}', '{held_on}'], [e($skema), e($heldOn)], $spConf['pembuka']) !!}
</div>

{{-- Field Nama / No Reg / Skema --}}
<table class="field-table mb-2">
    <tr>
        <td class="field-label field-label-p">Nama</td>
        <td class="field-colon">:</td>
        <td class="fw-bold">{{ $student?->name }}</td>
    </tr>
    <tr>
        <td class="field-label field-label-p">No Registrasi</td>
        <td class="field-colon">:</td>
        <td class="fw-bold">{{ $student?->no_participant ?? '-' }}</td>
    </tr>
    <tr>
        <td class="field-label field-label-p">Skema</td>
        <td class="field-colon">:</td>
        <td class="fw-bold">{{ $skema }}</td>
    </tr>
</table>

{{-- Tabel Nilai --}}
<table class="nilai-table">
    <tbody>
        <tr>
            <td rowspan="2" class="nth" style="width:20%;">NILAI<br>PRAKTIK</td>
            <td rowspan="2" class="nth" style="width:22%;">NILAI PILIHAN<br>GANDA</td>
            <td rowspan="2" class="nth" style="width:16%;">NILAI ESAI</td>
            <td colspan="2" class="nth" style="width:42%;">REKAPITULASI HASIL ASESMEN</td>
        </tr>
        <tr>
            <td class="nth" style="width:21%;">HASIL NILAI</td>
            <td class="nth" style="width:21%;">STATUS</td>
        </tr>
        <tr>
            <td>{{ $result->nilai_wawancara !== null ? number_format($result->nilai_wawancara, 2) : '-' }}</td>
            <td>{{ $result->nilai_pg        !== null ? number_format($result->nilai_pg, 2)        : '-' }}</td>
            <td>{{ $result->nilai_esai      !== null ? number_format($result->nilai_esai, 2)      : '-' }}</td>
            <td><strong>{{ $result->nilai_akhir !== null ? number_format($result->nilai_akhir, 2) : '-' }}</strong></td>
            <td style="background-color:{{ $statusKompeten === 'KOMPETEN' ? '#c6efce' : '#ffc7ce' }};">
                <strong>{{ $statusKompeten }}</strong>
            </td>
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
                    <img src="{{ $ttdPath }}" style="height:18mm; width:auto;">
                @else
                    <div style="height:10mm;"></div>
                @endif
            </div>
            <div class="ttd-name">{{ $penandatangan['nama'] }}</div>
            <div class="ttd-jabatan">Ketua LSP Edukia</div>
        </td>
    </tr>
</table>
</div>{{-- /content-wrap hal.1 --}}

{{-- ══════════════════════════════════════════════════════
     HAL 2 — LAMPIRAN
     ══════════════════════════════════════════════════════ --}}
<div class="page-break"></div>

{{-- KOP ulang --}}
<table class="kop-table">
    <tr>
        <td class="kop-logo">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}" style="width:36mm;">
            @endif
        </td>
        <td class="kop-teks">
            <div class="kop-nama">{{ $lsp['nama'] }}</div>
            <div class="kop-alamat">
                {{ $lsp['alamat'] }} Telp. {{ $lsp['telp'] }}<br>
                {{ $lsp['web'] }}
            </div>
        </td>
    </tr>
</table>
<div class="kop-garis-atas"></div>

<div class="content-wrap">
{{-- Judul Lampiran --}}
<div class="text-center fw-bold mb-2" style="margin-top:8pt; font-size:12pt;">LAMPIRAN</div>
<div class="lamp-title mb-4">
    <span style="border-bottom: 0.75pt solid #000; padding-bottom: 2pt;">STANDAR PEMBOBOTAN PENILAIAN DAN KELULUSAN</span>
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
        </thead>
        <tbody>
            <tr>
                <td colspan="4" class="merged">Evaluasi per Unit Kompetensi</td>
            </tr>
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
<div style="padding-left:20pt; margin-top:4pt;">
    <table style="width:100%; border-collapse:collapse;">
        @foreach($standarKelulusan as $i => $item)
        <tr>
            <td style="width:18pt; vertical-align:top; font-size:12pt; line-height:1.5; padding:0 0 1pt 0;">{{ $i + 1 }}.</td>
            <td style="vertical-align:top; font-size:12pt; line-height:1.5; padding:0 0 1pt 0;">{!! $item !!}</td>
        </tr>
        @endforeach
    </table>
</div>
</div>{{-- /content-wrap hal.2 --}}

</body>
</html>
