<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>FR.APL.03 - {{ $namaPeserta }}</title>
{{-- FR.APL.03 Rev.04. Penilaian yang disimpan dengan Rev.03 memakai fr_apl_03_rev03. --}}
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: cambria, 'Times New Roman', Times, serif; font-size: 9.5pt; color: #000; }

.kop-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.kop-table td { vertical-align: middle; padding: 0; }
.kop-logo { width: 28mm; text-align: left; }
.kop-teks { text-align: center; padding: 0 6pt; }
.kop-nama { font-weight: normal; font-size: 15pt; letter-spacing: 0.5pt; line-height: 1.15; }
.kop-alamat { font-size: 8.5pt; margin-top: 1pt; line-height: 1.2; }
.kop-garis-atas { width: 100%; height: 1.5pt; background-color: #000; margin: 2pt 0 5pt; font-size: 0; line-height: 0; }

.content-wrap { margin-left: 6mm; margin-right: 6mm; }

.text-center { text-align: center; }
.fw-bold { font-weight: bold; }

.judul { font-size: 11.5pt; margin-top: 2pt; margin-bottom: 1pt; }
.subjudul { font-size: 10pt; margin-bottom: 4pt; }

.rl-title { margin-bottom: 1pt; font-size: 9pt; }
.rl-list { font-size: 9pt; padding-bottom: 3pt; border-bottom: 0.75pt solid #000; margin-bottom: 4pt; }
.rl-list div { line-height: 1.25; }

.field-table { width: 100%; border-collapse: collapse; margin-bottom: 4pt; }
.field-table td { padding: 0.5pt 0; vertical-align: top; font-size: 9pt; }
.field-label { white-space: nowrap; padding-right: 4pt; width: 42pt; }
.field-label-r { white-space: nowrap; padding-right: 4pt; width: 92pt; }

.crit-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 5pt; }
.crit-table th, .crit-table td { border: 0.75pt solid #000; padding: 0.6pt 4pt; vertical-align: middle; }
.crit-table th { font-weight: bold; text-align: center; background-color: #bfbfbf; padding: 2pt 4pt; }
.crit-num { text-align: center; width: 5%; }
.crit-item { width: 51%; }
.crit-score { text-align: center; width: 22%; }
.crit-hasil { text-align: center; width: 22%; font-weight: bold; }
.crit-group td { font-weight: bold; }

.jumlah-row td { font-weight: bold; }

.ketentuan-title { font-weight: bold; font-size: 9pt; }
.ketentuan { font-size: 9pt; margin-bottom: 6pt; line-height: 1.25; }

.box-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 6pt; }
.box-table td { border: 0.75pt solid #000; padding: 1pt 4pt; vertical-align: middle; }
.box-label { width: 46%; }
.box-cb { width: 5%; text-align: center; }
.box-opt { width: 22%; }

.ttd-table { width: 70%; border-collapse: collapse; font-size: 9pt; page-break-inside: avoid; }
.ttd-table th, .ttd-table td { border: 0.75pt solid #000; padding: 2pt 4pt; vertical-align: top; }
.ttd-table th { font-weight: bold; text-align: center; background-color: #ddebf7; }
.ttd-left { width: 62%; }
.ttd-right { width: 38%; text-align: center; }
.ttd-img { height: 16mm; text-align: center; }
</style>
</head>
<body>
@php
    $cb       = fn (bool $on) => '<img src="' . e($on ? $checkboxCheckedPath : $checkboxEmptyPath) . '" style="width:9pt;height:9pt;vertical-align:middle;">';
    $answers  = $assessment->answers ?? [];
    $sebutkan = $answers['sebutkan'] ?? [];
    // Ketentuan Rev.04: ≥ ambang batas = memenuhi persyaratan dasar & boleh lanjut.
    $memenuhi = (bool) $assessment->is_eligible;
@endphp

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
<div class="text-center subjudul">No: FR.APL.03 Rev.04</div>

<div class="rl-title">Ruang Lingkup:</div>
<div class="rl-list">
    @forelse($rubric['ruang_lingkup'] as $item)
        <div>{{ $item }}</div>
    @empty
        <div>{{ $namaSkema }}</div>
    @endforelse
</div>

<table class="field-table">
    <tr>
        <td class="field-label">Nama:</td>
        <td class="fw-bold">{{ $namaPeserta }}</td>
        <td class="field-label-r">Tanggal Pemeriksaan:</td>
        <td>{{ $tanggalPeriksa }}</td>
    </tr>
    <tr>
        <td class="field-label">Skema:</td>
        <td class="fw-bold">{{ $namaSkema }}</td>
        <td class="field-label-r">Pemeriksa:</td>
        <td>{{ $namaPenilai }}</td>
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
                $given    = $answers[$criterion['key']] ?? null;
                $selected = $criterion['type'] === 'multi' ? (is_array($given) ? $given : []) : [$given];
            @endphp
            <tr class="crit-group">
                <td class="crit-num">{{ $i + 1 }}</td>
                <td class="crit-item">{{ $criterion['label'] }}</td>
                <td></td>
                <td></td>
            </tr>
            @foreach($criterion['options'] as $option)
                @php
                    $isSelected = in_array($option['key'], $selected, true);
                    $isian      = $isSelected && !empty($option['sebutkan']) ? ($sebutkan[$criterion['key']] ?? null) : null;
                @endphp
                <tr>
                    <td></td>
                    <td class="crit-item">{{ $option['label'] }}@if($isian): <em>{{ $isian }}</em>@endif</td>
                    <td class="crit-score">{{ $option['score'] ?? '' }}</td>
                    <td class="crit-hasil">{{ $isSelected ? ($option['score'] ?? '') : '' }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr class="jumlah-row">
            <td colspan="2" class="text-center">Jumlah</td>
            <td></td>
            <td class="crit-hasil">{{ $assessment->total_score }}</td>
        </tr>
    </tbody>
</table>

<div class="ketentuan-title">Ketentuan:</div>
<div class="ketentuan">
    &lt; {{ $assessment->threshold }} maka dinyatakan {{ $rubric['outcome']['fail'] }}<br>
    &#8805; {{ $assessment->threshold }} maka dinyatakan {{ $rubric['outcome']['pass'] }}
</div>

<table class="box-table">
    <tr>
        <td class="box-label">Hasil Verifikasi Pengalaman</td>
        <td class="box-cb">{!! $cb($memenuhi) !!}</td>
        <td class="box-opt">Memenuhi</td>
        <td class="box-cb">{!! $cb(!$memenuhi) !!}</td>
        <td class="box-opt">Belum Memenuhi</td>
    </tr>
    <tr>
        <td class="box-label">Dokumen Pendukung</td>
        <td class="box-cb">{!! $cb($dokumenLengkap) !!}</td>
        <td class="box-opt">Lengkap</td>
        <td class="box-cb">{!! $cb(!$dokumenLengkap) !!}</td>
        <td class="box-opt">Perlu dilengkapi</td>
    </tr>
    <tr>
        <td class="box-label">Keputusan</td>
        <td class="box-cb">{!! $cb($memenuhi) !!}</td>
        <td class="box-opt">Lanjut</td>
        <td class="box-cb">{!! $cb(!$memenuhi) !!}</td>
        <td class="box-opt">Tidak Lanjut</td>
    </tr>
</table>

<table class="ttd-table">
    <tr>
        <th class="ttd-left">Diperiksa oleh</th>
        <th class="ttd-right">Tanggal</th>
    </tr>
    <tr>
        <td class="ttd-left">Nama Pemeriksa: <strong>{{ $namaPenilai }}</strong></td>
        <td class="ttd-right">{{ $tanggalAngka }}</td>
    </tr>
    <tr>
        <td class="ttd-left">Jabatan: Admin LSP</td>
        <td class="ttd-right ttd-img">
            @if($ttdPath && file_exists($ttdPath) && $ttdWidthMm && $ttdHeightMm)
                <img src="{{ $ttdPath }}" style="width:{{ $ttdWidthMm }}mm; height:{{ $ttdHeightMm }}mm;">
            @endif
        </td>
    </tr>
</table>

</div>
</body>
</html>
