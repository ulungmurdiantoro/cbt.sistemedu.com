<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>FR.APL.03 - {{ $namaPeserta }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Cambria, 'Times New Roman', Times, serif; font-size: 11pt; color: #000; }

.kop-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.kop-table td { vertical-align: middle; padding: 0; }
.kop-logo { width: 28mm; text-align: left; }
.kop-logo img { width: 26mm; height: auto; }
.kop-teks { text-align: center; padding: 0 6pt; }
.kop-nama { font-weight: normal; font-size: 15pt; letter-spacing: 0.5pt; line-height: 1.15; }
.kop-alamat { font-size: 8.5pt; margin-top: 1pt; line-height: 1.2; }
.kop-garis-atas { width: 100%; height: 1.5pt; background-color: #000; margin: 2pt 0 5pt; font-size: 0; line-height: 0; }

.content-wrap { margin-left: 6mm; margin-right: 6mm; }

.text-center { text-align: center; }
.fw-bold { font-weight: bold; }

.judul { font-size: 11.5pt; margin-top: 2pt; margin-bottom: 1pt; }
.subjudul { font-size: 9pt; margin-bottom: 6pt; }

.rl-title { font-weight: bold; margin-bottom: 2pt; font-size: 10pt; }
.rl-list { margin-bottom: 6pt; font-size: 9.5pt; }
.rl-list div { line-height: 1.25; }

.field-table { border-collapse: collapse; margin-bottom: 6pt; }
.field-table td { padding: 1pt 0; vertical-align: top; font-size: 10.5pt; }
.field-label { white-space: nowrap; padding-right: 4pt; width: 60pt; }
.field-colon { width: 6pt; }

.crit-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 5pt; }
.crit-table th, .crit-table td { border: 0.75pt solid #000; padding: 1.5pt 4pt; vertical-align: middle; }
.crit-table th { font-weight: bold; text-align: center; background-color: #d9e6f5; padding: 3pt 4pt; }
.crit-num { text-align: center; width: 5%; }
.crit-item { width: 55%; }
.crit-item.sub { padding-left: 12pt; }
.crit-score { text-align: center; width: 12%; }
.crit-hasil { text-align: center; width: 12%; font-weight: bold; }
.crit-group-label { font-weight: bold; }

.jumlah-row td { font-weight: bold; background-color: #f2f2f2; }

.catatan { font-size: 8.5pt; margin-bottom: 2pt; }
.ketentuan { font-size: 9.5pt; font-weight: bold; margin-bottom: 10pt; }

.ttd-table { width: 100%; border-collapse: collapse; margin-top: 6pt; }
.ttd-table td { vertical-align: top; padding: 0; }
.ttd-right { width: 55%; text-align: center; font-size: 10pt; line-height: 1.5; }
.ttd-img { height: 14mm; margin: 3pt 0; }
.ttd-img img { display: inline-block; }
.ttd-name { font-weight: bold; margin-top: 4pt; text-decoration: underline; }
</style>
</head>
<body>

<table class="kop-table">
    <tr>
        <td class="kop-logo">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}" style="width:30mm;">
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

<div class="text-center judul fw-bold">STANDAR KRITERIA DAN PENILAIAN AWAL PEMOHON</div>
<div class="text-center subjudul">No: FR.APL.03 Rev.03</div>

<div class="rl-title">Ruang Lingkup:</div>
<div class="rl-list">
    @foreach($rubric['ruang_lingkup'] as $item)
        <div>{{ $item }}</div>
    @endforeach
</div>

<table class="field-table">
    <tr>
        <td class="field-label">Nama</td>
        <td class="field-colon">:</td>
        <td class="fw-bold">{{ $namaPeserta }}</td>
    </tr>
    <tr>
        <td class="field-label">Skema</td>
        <td class="field-colon">:</td>
        <td class="fw-bold">{{ $namaSkema }}</td>
    </tr>
</table>

<table class="crit-table">
    <thead>
        <tr>
            <th class="crit-num">No</th>
            <th class="crit-item">Item Check</th>
            <th class="crit-score">Score</th>
            <th class="crit-hasil">Hasil Evaluasi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rubric['criteria'] as $i => $criterion)
            @php
                $given    = $assessment->answers[$criterion['key']] ?? null;
                $selected = $criterion['type'] === 'multi' ? (is_array($given) ? $given : []) : [$given];
            @endphp
            <tr>
                <td class="crit-num">{{ $i + 1 }}</td>
                <td class="crit-item crit-group-label" colspan="3">{{ $criterion['label'] }}</td>
            </tr>
            @foreach($criterion['options'] as $option)
                @php $isSelected = in_array($option['key'], $selected, true); @endphp
                <tr>
                    <td></td>
                    <td class="crit-item sub">{{ $option['label'] }}</td>
                    <td class="crit-score">{{ $option['score'] }}</td>
                    <td class="crit-hasil">{{ $isSelected ? $option['score'] : '' }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr class="jumlah-row">
            <td colspan="3" style="text-align:right;">Jumlah</td>
            <td class="crit-hasil">{{ $assessment->total_score }}</td>
        </tr>
    </tbody>
</table>

<div class="catatan">Catatan: Relevansi pelatihan adalah judgment dari penyelenggara sertifikasi</div>
<div class="ketentuan">Ketentuan: {{ $resultSentence }}</div>

<table class="ttd-table">
    <tr>
        <td style="width:45%;"></td>
        <td class="ttd-right">
            <div>Diperiksa Oleh:</div>
            <div>{{ $tanggalPeriksa }}</div>
            <div class="ttd-img">
                @if($ttdPath && file_exists($ttdPath) && $ttdWidthMm && $ttdHeightMm)
                    <img src="{{ $ttdPath }}" style="width:{{ $ttdWidthMm }}mm; height:{{ $ttdHeightMm }}mm;">
                @else
                    <div style="height:16mm;"></div>
                @endif
            </div>
            <div class="ttd-name">{{ $namaPenilai }}</div>
            <div>Admin LSP</div>
        </td>
    </tr>
</table>

</div>
</body>
</html>
