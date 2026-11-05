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
table.grid th { font-weight: bold; text-align: center; background: #5b9bd5; color: #fff; }
.label { width: 32%; font-weight: bold; }
.c { text-align: center; vertical-align: middle; }

.option { margin: 2pt 0 2pt 4pt; }
.notes { border-bottom: 0.5pt dotted #000; min-height: 14pt; padding: 2pt 0; margin-top: 2pt; }
.petunjuk { margin-top: 5pt; text-align: justify; }
</style>
</head>
<body>
{{-- FR.TUK.06 Rev.1 — checklist Rev.0 dicetak dengan documents/fr_tuk_06_rev0. --}}
@php
    $cb = fn (bool $on) => '<img src="' . e($on ? $checkboxCheckedPath : $checkboxEmptyPath) . '" style="width:9pt;height:9pt;vertical-align:middle;">';
    // Bagian H: minimal 4 baris seperti di dokumen
    $ketidaksesuaian = array_values($v->ketidaksesuaian ?? []);
    while (count($ketidaksesuaian) < 4) {
        $ketidaksesuaian[] = [];
    }
@endphp

<div class="title">CEKLIST VERIFIKASI TEMPAT UJI KOMPETENSI (TUK) ONLINE</div>

<div class="section-title">A. IDENTITAS PELAKSANAAN</div>
<table class="grid">
    <tr><td class="label">Nama Peserta</td><td>{{ $namaPeserta }}</td></tr>
    <tr><td class="label">Skema Sertifikasi</td><td>{{ $namaSkema }}</td></tr>
    <tr><td class="label">Tanggal Asesmen</td><td>{{ $tanggalAsesmen }}</td></tr>
    <tr><td class="label">Waktu</td><td>{{ $v->waktu_asesmen }}</td></tr>
    <tr><td class="label">Metode Asesmen</td><td>{{ $metodeAsesmen }}</td></tr>
    <tr>
        <td class="label" style="vertical-align:middle">Lokasi Peserta</td>
        <td>
            @foreach($def['lokasi_options'] as $opt)
                <div>{!! $cb($v->lokasi_peserta === $opt) !!} {{ $opt }}{{ $opt === 'Lainnya' ? ':' : '' }}
                    @if($opt === 'Lainnya' && $v->lokasi_peserta === 'Lainnya') {{ $v->lokasi_lainnya }} @endif
                </div>
            @endforeach
        </td>
    </tr>
    <tr><td class="label">Nama Pengawas Ujian</td><td>{{ $v->pengawas_name }}</td></tr>
</table>
<div class="petunjuk"><strong>Petunjuk pengisian:</strong> {{ $def['petunjuk'] }}</div>

@foreach($def['sections'] as $section)
<div style="page-break-inside: avoid">
    <div class="section-title">{{ $section['key'] }}. {{ $section['title'] }}</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width:6%">No.</th>
                <th>{{ $def['item_header'] }}</th>
                <th style="width:13%">{{ $def['status_labels']['sesuai'] }}</th>
                <th style="width:13%">{{ $def['status_labels']['tidak_sesuai'] }}</th>
                <th style="width:22%">Catatan</th>
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

<div style="page-break-inside: avoid">
<div class="section-title">G. KESIMPULAN VERIFIKASI AWAL</div>
@foreach($def['kesimpulan_awal'] as $key => $label)
    <div class="option">{!! $cb($v->kesimpulan_awal === $key) !!} {{ $label }} - {{ $def['kesimpulan_awal_keterangan'][$key] }}</div>
@endforeach
<div class="notes">{!! nl2br(e($v->catatan_awal)) !!}</div>
</div>

<div style="page-break-inside: avoid">
<div class="section-title">H. KETIDAKSESUAIAN, PERBAIKAN DAN VERIFIKASI ULANG</div>
<table class="grid">
    <thead>
        <tr>
            <th style="width:6%">No.</th>
            <th>Uraian Persyaratan yang Tidak Memenuhi</th>
            <th style="width:22%">Tindakan Perbaikan yang Dipersyaratkan</th>
            <th style="width:16%">Batas Waktu</th>
            <th style="width:19%">Status Verifikasi Ulang</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ketidaksesuaian as $i => $row)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>{{ $row['uraian'] ?? '' }}</td>
                <td>{{ $row['tindakan'] ?? '' }}</td>
                <td>{{ $row['batas_waktu'] ?? '' }}</td>
                <td>
                    @foreach($statusUlang as $key => $label)
                        <div>{!! $cb(($row['status'] ?? null) === $key) !!} {{ $label }}</div>
                    @endforeach
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
<div style="margin-top:3pt"><strong>Kesimpulan setelah verifikasi ulang:</strong>
    @foreach($statusUlang as $key => $label)
        &nbsp;{!! $cb($v->kesimpulan_ulang === $key) !!} {{ mb_strtoupper($label) }}
    @endforeach
</div>
</div>

<div style="page-break-inside: avoid">
<div class="section-title">I. HASIL PEMANTAUAN SELAMA ASESMEN</div>
@foreach($def['hasil_pemantauan'] as $key => $label)
    <div class="option">{!! $cb($v->hasil_pemantauan === $key) !!} {{ $label }}</div>
@endforeach
<table class="grid" style="margin-top:3pt">
    <tr><td class="label">Uraian kejadian/ketidaksesuaian</td><td>{!! nl2br(e($v->uraian_pemantauan)) !!}</td></tr>
    <tr><td class="label">Tindakan yang dilakukan</td><td>{!! nl2br(e($v->tindakan_pemantauan)) !!}</td></tr>
    <tr>
        <td class="label">Dampak terhadap kelanjutan asesmen</td>
        <td>
            @foreach($def['dampak_options'] as $key => $label)
                {!! $cb($v->dampak_pemantauan === $key) !!} {{ $label }}{{ $key === 'lainnya' ? ':' : '' }}
                @if($key === 'lainnya') {{ $v->dampak_pemantauan === 'lainnya' ? $v->dampak_lainnya : '________' }} @endif
                &nbsp;
            @endforeach
        </td>
    </tr>
</table>
</div>

{{-- div (bukan table) yang dijaga utuh: page-break-inside pada table membuat mPDF mengecilkan tabelnya --}}
<div style="page-break-inside: avoid">
<div class="section-title">J. VALIDASI PENGAWAS UJIAN</div>
<table class="grid">
    <tr><td class="label">Nama Pengawas Ujian</td><td>{{ $v->pengawas_name }}</td></tr>
    <tr><td class="label">Tanggal/Waktu Verifikasi</td><td>{{ $tanggalVerifikasi }}</td></tr>
    <tr>
        <td class="label">Kesimpulan Akhir</td>
        <td>
            @foreach($def['kesimpulan_akhir'] as $key => $label)
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
</div>

</body>
</html>
