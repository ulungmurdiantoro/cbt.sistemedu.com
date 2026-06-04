<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>SK - {{ $certNumber }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: cambria, serif; font-size: 12pt; color: #000; line-height: 1; text-align: justify; }

/* ── KOP ── */
.kop-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.kop-table td { vertical-align: middle; padding: 0; }
.kop-logo-left { width: 18%; text-align: left; }
.kop-logo-left img { width: 22mm; height: auto; }
.kop-spacer { width: 62%; }
.kop-right-block { width: 20%; text-align: right; vertical-align: top; }
.kop-right-block img { width: 20mm; height: auto; }
.kop-right-label { font-family: Arial, sans-serif; font-size: 7pt; text-align: right; margin-top: 2pt; line-height: 1.3; }
.kop-divider { margin-bottom: 8pt; }

/* ── CONTENT WRAPPER ── */
.wrap { margin-left: 8mm; margin-right: 8mm; }

/* ── FIELD TABLE ── */
.ft { border-collapse: collapse; width: 100%; }
.ft td { padding: 1.5pt 0; vertical-align: top; font-size: 12pt; }
.ft .lbl { white-space: nowrap; padding-right: 4pt; }
.ft .col { width: 6pt; }

/* ── NILAI TABLE ── */
.nilai-table { width: 100%; border-collapse: collapse; font-size: 12pt; margin: 8pt 0 10pt; }
.nilai-table td { border: 0.5pt solid #000; padding: 4pt 5pt; text-align: center; vertical-align: middle; }
.nilai-table .nth { font-weight: bold; background-color: #B8D6F0; }

/* ── KATEGORI LIST ── */
.kat-table { width: 100%; border-collapse: collapse; margin-top: 4pt; }
.kat-table td { padding: 1pt 0; vertical-align: top; font-size: 12pt; }
.kat-table .num { width: 20pt; }
.kat-table .range { width: 120pt; white-space: nowrap; }
.kat-table .sep { width: 10pt; padding: 0 6pt 0 14pt; }

/* ── SK TITLE BLOCK ── */
.sk-title { text-align: center; font-weight: bold; font-size: 12pt; line-height: 1.5; margin: 6pt 0 4pt; }
.sk-tentang { text-align: center; font-size: 12pt; margin: 8pt 0; }
.sk-tentang .label { font-weight: bold; }
.sk-tentang .value { font-weight: bold; }

/* ── MENIMBANG / MENGINGAT ── */
.section-head { font-weight: bold; font-size: 12pt; margin: 10pt 0 4pt; }
.item-table { width: 100%; border-collapse: collapse; margin-bottom: 2pt; }
.item-table td { padding: 1.5pt 0; vertical-align: top; font-size: 12pt; line-height: 1.3; text-align: justify; }
.item-table .num { width: 18pt; }

/* ── MEMUTUSKAN ── */
.mem-title { text-align: center; font-weight: bold; font-size: 12pt; margin: 6pt 0 10pt; }
.mem-wrap { text-align: center; margin-top: 16pt; margin-bottom: 8pt; }
.mem-table { width: 100%; border-collapse: collapse; margin-bottom: 4pt; }
.mem-table td { padding: 2pt 0; vertical-align: top; font-size: 12pt; line-height: 1.5; text-align: justify; }
.mem-table .lbl { width: 55pt; white-space: nowrap; }
.mem-table .col { width: 8pt; }

/* ── TTD ── */
.ttd-table { width: 100%; border-collapse: collapse; margin-top: 124pt; }
.ttd-table td { vertical-align: bottom; padding: 0; }
.ttd-left { width: 45%; }
.ttd-right { width: 55%; text-align: left; }
.ttd-date { font-size: 12pt; margin-bottom: 4pt; }
.qr-row { width: 100%; border-collapse: collapse; margin-bottom: 4pt; }
.qr-row td { vertical-align: middle; padding: 0; }
.qr-img img { width: 31.5mm; height: 31.5mm; }
.qr-note { padding-left: 14pt; font-size: 10.5pt; font-style: italic; line-height: 1.35; }
.signer-name { font-weight: bold; font-size: 12pt; }
.signer-title { font-weight: bold; font-size: 12pt; }

/* ── PAGE BREAK ── */
.page-break { page-break-after: always; display: block; }
</style>
</head>
<body>

@php
    $kodeAkreditasi = $lsp['kode_akreditasi'] ?? 'LSP-033-IDN';
    $namaKota       = $lsp['kota'] ?? 'Semarang';
    $skema          = $namaSkema;

    // Menimbang — ganti placeholder {skema}
    $menimbang = array_map(
        fn($t) => str_replace('{skema}', $skema, $t),
        $skConf['menimbang'] ?? []
    );
    $mengingat = $skConf['mengingat'] ?? [];
    $kategori  = $skConf['kategori']  ?? [];

    // MEMUTUSKAN items
    $gelar = $classroom?->gelar ?? '';
    $titleEn = $classroom?->title_en ?? $classroom?->title ?? '';

    $pertama = 'Atas nama : ' . e($student?->name) . '<br>'
        . 'Telah mengikuti Uji Kompetensi ' . e($skema) . ' melalui penilaian langsung '
        . 'dan sertifikasi langsung dinyatakan : <strong>' . $statusKompeten . '/Lulus</strong><br>'
        . 'dengan Kategori: <strong>' . e($kategoriLabel) . '</strong>';

    $kedua = 'Kepada peserta Uji Kompetensi yang dinyatakan Kompeten/Lulus berhak '
        . 'mencantumkan gelar non akademik <strong>' . e($gelar) . ' (' . e($titleEn) . ')</strong> '
        . 'di belakang nama selama masa berlakunya sertifikat';

    $ketiga = 'Sehubungan dengan hal tersebut pada poin PERTAMA ditetapkan sebagai peserta '
        . 'Uji Kompetensi ' . e($skema) . ' Perguruan Tinggi melalui Keputusan Ketua '
        . 'LSP Edukasi Global Cendekia';

    $penutup = $skConf['penutup_hal2'] ?? '';
    $catatanQr = $skConf['catatan_qr'] ?? '';
@endphp

{{-- ══════════════════════════════════════════════════════
     HAL 1 — SURAT KEPUTUSAN
     ══════════════════════════════════════════════════════ --}}

{{-- KOP --}}
<table class="kop-table">
    <tr>
        @if($withKan)
        <td class="kop-logo-left">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}" style="width:47mm; height:auto;">
            @endif
        </td>
        <td class="kop-spacer"></td>
        <td class="kop-right-block">
            @if(file_exists($logoKanPath))
                <div><img src="{{ $logoKanPath }}" style="width:62mm; height:auto;"></div>
            @endif
        </td>
        @else
        <td style="text-align:center;">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}" style="width:47mm; height:auto;">
            @endif
        </td>
        @endif
    </tr>
</table>
<div class="kop-divider"></div>

<div class="wrap">

{{-- Judul SK --}}
<div class="sk-title">
    SURAT KEPUTUSAN<br>
    LEMBAGA SERTIFIKASI PERSON EDUKASI GLOBAL CENDEKIA<br>
    Nomor: {{ $certNumber }}
</div>

{{-- TENTANG --}}
<div class="sk-tentang">
    <div class="label">TENTANG</div>
    <div class="value">{{ strtoupper($namaSkema) }}</div>
</div>

{{-- Menimbang --}}
<div class="section-head">Menimbang :</div>
<div>
    <table class="item-table">
        @foreach($menimbang as $i => $item)
        <tr>
            <td class="num">{{ $i + 1 }}.</td>
            <td>{!! $item !!}</td>
        </tr>
        @endforeach
    </table>
</div>

{{-- Mengingat --}}
<div class="section-head">Mengingat :</div>
<div>
    <table class="item-table">
        @foreach($mengingat as $i => $item)
        <tr>
            <td class="num">{{ $i + 1 }}.</td>
            <td>{!! $item !!}</td>
        </tr>
        @endforeach
    </table>
</div>

</div>{{-- /wrap --}}

{{-- ══════════════════════════════════════════════════════
     HAL 2 — MEMUTUSKAN
     ══════════════════════════════════════════════════════ --}}
<div class="page-break"></div>

{{-- KOP --}}
<table class="kop-table">
    <tr>
        @if($withKan)
        <td class="kop-logo-left">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}" style="width:47mm; height:auto;">
            @endif
        </td>
        <td class="kop-spacer"></td>
        <td class="kop-right-block">
            @if(file_exists($logoKanPath))
                <div><img src="{{ $logoKanPath }}" style="width:62mm; height:auto;"></div>
            @endif
        </td>
        @else
        <td style="text-align:center;">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}" style="width:47mm; height:auto;">
            @endif
        </td>
        @endif
    </tr>
</table>
<div class="kop-divider"></div>

<div class="wrap">

{{-- MEMUTUSKAN --}}
<div class="mem-wrap">
    <span class="mem-title">MEMUTUSKAN</span>
</div>

<table class="mem-table">
    <tr>
        <td class="lbl">Menetapkan</td>
        <td class="col">:</td>
        <td></td>
    </tr>
    <tr>
        <td class="lbl">PERTAMA</td>
        <td class="col">:</td>
        <td>{!! $pertama !!}</td>
    </tr>
    <tr>
        <td class="lbl">KEDUA</td>
        <td class="col">:</td>
        <td>{!! $kedua !!}</td>
    </tr>
    <tr>
        <td class="lbl">KETIGA</td>
        <td class="col">:</td>
        <td>{!! $ketiga !!}</td>
    </tr>
</table>

{{-- Penutup --}}
<div style="font-size:12pt; line-height:1.5; margin: 10pt 0 14pt;">
    {!! $penutup !!}
</div>

{{-- TTD --}}
<table class="ttd-table">
    <tr>
        <td class="ttd-left"></td>
        <td class="ttd-right">
            <div class="ttd-date">
                Ditetapkan di &nbsp;: {{ $namaKota }}<br>
                Pada tanggal &nbsp;&nbsp;: {{ $tglDitetapkan }}
            </div>
            <table class="qr-row" style="margin-top:6pt;">
                <tr>
                    @if($qrSkPath)
                    <td class="qr-img" style="width:31.5mm;">
                        <img src="{{ $qrSkPath }}" style="width:31.5mm; height:31.5mm;">
                    </td>
                    @endif
                    <td class="qr-note" style="padding-left:3mm;">{!! nl2br(e($catatanQr)) !!}</td>
                </tr>
            </table>
            <div style="margin-top:4pt;">
                <div class="signer-name">{{ $penandatangan['nama'] }}</div>
                <div class="signer-title">Ketua LSP Edukasi Global Cendekia</div>
            </div>
        </td>
    </tr>
</table>

</div>{{-- /wrap --}}

{{-- ══════════════════════════════════════════════════════
     HAL 3 — LAMPIRAN HASIL PENILAIAN
     ══════════════════════════════════════════════════════ --}}
<div class="page-break"></div>

{{-- KOP --}}
<table class="kop-table">
    <tr>
        @if($withKan)
        <td class="kop-logo-left">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}" style="width:47mm; height:auto;">
            @endif
        </td>
        <td class="kop-spacer"></td>
        <td class="kop-right-block">
            @if(file_exists($logoKanPath))
                <div><img src="{{ $logoKanPath }}" style="width:62mm; height:auto;"></div>
            @endif
        </td>
        @else
        <td style="text-align:center;">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}" style="width:47mm; height:auto;">
            @endif
        </td>
        @endif
    </tr>
</table>
<div class="kop-divider"></div>

<div class="wrap">

<div style="font-weight:bold; font-size:12pt; margin-top:14pt; margin-bottom:10pt;">Lampiran Hasil Penilaian:</div>

<table class="ft" style="margin-bottom:10pt;">
    <tr>
        <td class="lbl">Nama Peserta</td>
        <td class="col">:</td>
        <td>{{ $student?->name }}</td>
    </tr>
    <tr>
        <td class="lbl">NIK</td>
        <td class="col">:</td>
        <td>{{ $student?->participant?->nik ?? '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">Pelaksanaan</td>
        <td class="col">:</td>
        <td>{{ $namaSkema }}@if($session?->kode_batch) Batch {{ $session->kode_batch }}@endif</td>
    </tr>
    <tr>
        <td class="lbl">Hari/Tanggal Ujian</td>
        <td class="col">:</td>
        <td>{{ $hariTanggalUjian }}</td>
    </tr>
    <tr>
        <td class="lbl">Tempat Uji Kompetensi</td>
        <td class="col">:</td>
        <td>{{ $session?->tempat_ujian ?? '-' }}</td>
    </tr>
</table>

</div>{{-- /wrap — tabel keluar dari indent --}}
<table class="nilai-table">
    <tbody>
        <tr>
            <td rowspan="2" class="nth" style="width:18%;">NILAI<br>WAWANCARA</td>
            <td rowspan="2" class="nth" style="width:20%;">NILAI PILIHAN<br>GANDA</td>
            <td rowspan="2" class="nth" style="width:14%;">NILAI ESAI</td>
            <td colspan="2" class="nth" style="width:34%;">REKAPITULASI HASIL ASESMEN</td>
            <td rowspan="2" class="nth" style="width:14%;">KATEGORI</td>
        </tr>
        <tr>
            <td class="nth" style="width:17%;">HASIL NILAI</td>
            <td class="nth" style="width:17%;">STATUS</td>
        </tr>
        <tr>
            <td>{{ $result->nilai_wawancara !== null ? number_format($result->nilai_wawancara, 2) : '-' }}</td>
            <td>{{ $result->nilai_pg !== null ? number_format($result->nilai_pg, 2) : '-' }}</td>
            <td>{{ $result->nilai_esai !== null ? number_format($result->nilai_esai, 2) : '-' }}</td>
            <td><strong>{{ $result->nilai_akhir !== null ? number_format($result->nilai_akhir, 2) : '-' }}</strong></td>
            <td style="background-color:{{ $statusKompeten === 'Kompeten' ? '#c6efce' : '#ffc7ce' }};">
                <strong>{{ strtoupper($statusKompeten) }}</strong>
            </td>
            <td>{{ $kategoriLabel }}</td>
        </tr>
    </tbody>
</table>

<div class="wrap">
<div style="font-size:12pt; margin-bottom:4pt;">Kategori:</div>
<div>
    <table class="kat-table">
        @foreach($kategori as $i => $kat)
        <tr>
            <td class="num">{{ $i + 1 }}.</td>
            <td class="range">{!! $kat['range'] !!}</td>
            <td class="sep">:</td>
            <td>{!! $kat['label'] !!}</td>
        </tr>
        @endforeach
    </table>
</div>

</div>{{-- /wrap --}}

</body>
</html>
