<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>FR.TUK.06 - {{ $namaPeserta }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: cambria, 'Times New Roman', Times, serif; font-size: 9.5pt; color: #000; }

.title { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 8pt; }
.section-title { font-weight: bold; margin: 8pt 0 3pt; }

table.grid { width: 100%; border-collapse: collapse; }
table.grid td, table.grid th { border: 0.75pt solid #000; padding: 3pt 5pt; vertical-align: top; }
table.grid th { font-weight: bold; text-align: center; background: #e7e6e6; }
.label { width: 32%; }
.c { text-align: center; vertical-align: middle; }

.option { margin: 2pt 0 2pt 4pt; }
.notes { border-bottom: 0.5pt dotted #000; min-height: 14pt; padding: 2pt 0; margin-top: 2pt; }
</style>
</head>
<body>
@php
    $cb = fn (bool $on) => '<img src="' . e($on ? $checkboxCheckedPath : $checkboxEmptyPath) . '" style="width:9pt;height:9pt;vertical-align:middle;">';
@endphp

<div class="title">CEKLIST VERIFIKASI TEMPAT UJI KOMPETENSI (TUK) ONLINE</div>

<div class="section-title">A. IDENTITAS PELAKSANAAN</div>
<table class="grid">
    <tr><td class="label">Nama Peserta</td><td>{{ $namaPeserta }}</td></tr>
    <tr><td class="label">Skema Sertifikasi</td><td>{{ $namaSkema }}</td></tr>
    <tr><td class="label">Tanggal Asesmen</td><td>{{ $tanggalAsesmen }}</td></tr>
    <tr><td class="label">Waktu</td><td>{{ $v->waktu_asesmen }}</td></tr>
    <tr><td class="label">Metode Asesmen</td><td>{{ $metodeAsesmen }}</td></tr>
    <tr><td class="label">Lokasi Peserta</td><td>{{ $v->lokasi_peserta }}</td></tr>
    <tr><td class="label">Nama Pengawas Ujian</td><td>{{ $v->pengawas_name }}</td></tr>
</table>

@foreach($sections as $section)
<div style="page-break-inside: avoid">
    <div class="section-title">{{ $section['key'] }}. {{ $section['title'] }}</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width:6%">No.</th>
                <th>Kriteria Verifikasi</th>
                <th style="width:10%">Sesuai</th>
                <th style="width:10%">Tidak Sesuai</th>
                <th style="width:24%">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($section['items'] as $i => $item)
                @php $answer = $items[$item['key']] ?? []; $status = $answer['status'] ?? null; @endphp
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $item['label'] }}</td>
                    <td class="c">{!! $cb($status === 'sesuai') !!}</td>
                    <td class="c">{!! $cb($status === 'tidak_sesuai') !!}</td>
                    <td>{{ $answer['catatan'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endforeach

<div class="section-title">G. KESIMPULAN VERIFIKASI AWAL</div>
@foreach(\App\Support\TukChecklist::KESIMPULAN_AWAL as $key => $label)
    <div class="option">{!! $cb($v->kesimpulan_awal === $key) !!} <strong>{{ $label }}</strong> - {{ \App\Support\TukChecklist::KESIMPULAN_AWAL_KETERANGAN[$key] }}</div>
@endforeach
<div style="margin-top:4pt">Catatan ketidaksesuaian/tindakan perbaikan:</div>
<div class="notes">{!! nl2br(e($v->catatan_awal)) !!}</div>

<div class="section-title">H. HASIL PEMANTAUAN SELAMA ASESMEN</div>
@foreach(\App\Support\TukChecklist::HASIL_PEMANTAUAN as $key => $label)
    <div class="option">{!! $cb($v->hasil_pemantauan === $key) !!} {{ $label }}</div>
@endforeach
<div style="margin-top:4pt">Uraian kejadian dan tindak lanjut:</div>
<div class="notes">{!! nl2br(e($v->uraian_pemantauan)) !!}</div>

<div class="section-title">I. VALIDASI PENGAWAS UJIAN</div>
<table class="grid" style="page-break-inside:avoid">
    <tr><td class="label">Nama Pengawas Ujian</td><td>{{ $v->pengawas_name }}</td></tr>
    <tr><td class="label">Tanggal/Waktu Verifikasi</td><td>{{ $tanggalVerifikasi }}</td></tr>
    <tr>
        <td class="label">Kesimpulan Akhir</td>
        <td>
            @foreach(\App\Support\TukChecklist::KESIMPULAN_AKHIR as $key => $label)
                {!! $cb($v->kesimpulan_akhir === $key) !!} {{ $label }} &nbsp;&nbsp;&nbsp;
            @endforeach
        </td>
    </tr>
    <tr>
        <td class="label" style="height:22mm; vertical-align:middle">Tanda Tangan/Validasi</td>
        <td style="vertical-align:middle">
            @if($ttdPengawas['path'])
                <img src="{{ $ttdPengawas['path'] }}" style="width:{{ $ttdPengawas['w'] }}mm;height:{{ $ttdPengawas['h'] }}mm;">
            @endif
        </td>
    </tr>
</table>

</body>
</html>
