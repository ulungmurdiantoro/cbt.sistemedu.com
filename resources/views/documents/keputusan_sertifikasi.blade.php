<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Keputusan Sertifikasi - {{ $nomor }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Cambria, 'Times New Roman', Times, serif; font-size: 11pt; color: #000; }

/* KOP */
.kop-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.kop-table td { vertical-align: middle; padding: 0; }
.kop-logo { width: 39mm; text-align: left; }
.kop-teks { text-align: center; padding: 0 6pt; }
.kop-nama { font-weight: normal; font-size: 20pt; letter-spacing: 0.5pt; line-height: 1.2; }
.kop-alamat { font-size: 10.5pt; margin-top: 2pt; line-height: 1.35; }
.kop-garis-atas { width: 100%; height: 1.5pt; background-color: #000; margin: 3pt 0 6pt; font-size: 0; line-height: 0; }

.content-wrap { margin-left: 0; margin-right: 0; }

.text-center { text-align: center; }
.fw-bold { font-weight: bold; }
.underline { border-bottom: 0.75pt solid #000; padding-bottom: 2pt; display: inline; }
.mt-3 { margin-top: 10pt; }
.mb-3 { margin-bottom: 10pt; }
.mb-4 { margin-bottom: 14pt; }
.justify { text-align: justify; }

.field-table { border-collapse: collapse; margin: 10pt 0; }
.field-table td { padding: 1.5pt 0; vertical-align: top; font-size: 11pt; }
.field-label { white-space: nowrap; width: 70pt; padding-right: 4pt; }
.field-colon { width: 8pt; }

.hasil-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin: 10pt 0; }
.hasil-table th, .hasil-table td { border: 0.75pt solid #000; padding: 4pt 5pt; vertical-align: middle; }
.hasil-table th { font-weight: bold; text-align: center; background: #eef2f6; }
.hasil-table td.center { text-align: center; }

.ket-note { font-size: 9.5pt; margin: 6pt 0 14pt; }

.dok-title { font-weight: bold; margin: 10pt 0 4pt; }
.dok-list { margin: 0 0 14pt 18pt; }
.dok-list li { margin-bottom: 2pt; }

.ttd-outer { width: 100%; border-collapse: collapse; margin-top: 16pt; }
.ttd-outer td { vertical-align: top; padding: 0; }
.ttd-inner { font-size: 11pt; line-height: 1.5; }
.ttd-label { width: 60pt; }
.ttd-img img { height: 14mm; width: auto; margin: 4pt 0; }
</style>
</head>
<body>

<table class="kop-table">
    <tr>
        <td class="kop-logo">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}" style="width:34mm;">
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

@if($preview ?? false)
<div class="text-center fw-bold" style="font-size:11pt; color:#b3283d; border:1pt dashed #b3283d; padding:4pt; margin-top:8pt;">
    PRATINJAU — DOKUMEN INI BELUM DITERBITKAN (belum difinalisasi)
</div>
@endif

<div class="text-center fw-bold mt-3" style="font-size:13pt;">
    <span class="underline">KEPUTUSAN SERTIFIKASI</span>
</div>

<table class="field-table" style="margin-left:auto;margin-right:auto;">
    <tr><td class="field-label">Nomor</td><td class="field-colon">:</td><td>{{ $nomor }}</td></tr>
</table>

<table class="field-table">
    <tr><td class="field-label">Hari</td><td class="field-colon">:</td><td>{{ $hari }}</td></tr>
    <tr><td class="field-label">Tanggal</td><td class="field-colon">:</td><td>{{ $tanggal }}</td></tr>
</table>

<div class="justify mb-3">
    Telah dilaksanakan pengambilan keputusan sertifikasi {{ $lsp['nama'] }} dengan berdasarkan
    hasil verifikasi dokumen permohonan sertifikasi dan hasil asesmen diperoleh hasil keputusan
    sebagai berikut:
</div>

<table class="hasil-table">
    <thead>
        <tr>
            <th rowspan="2" style="width:5%;white-space:nowrap;">No.</th>
            <th rowspan="2" style="width:22%">Nama Skema</th>
            <th rowspan="2" style="width:19%">Nama Peserta</th>
            <th rowspan="2" style="width:14%">Tanggal Asesmen/ Remidi</th>
            <th colspan="2">Pertimbangan</th>
            <th rowspan="2" style="width:15%">Keputusan Sertifikasi</th>
        </tr>
        <tr>
            <th style="width:13%">Aplikasi Permohonan</th>
            <th style="width:12%">Hasil Asesmen*)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $i => $row)
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td>{{ $row['nama_skema'] }}</td>
            <td>{{ $row['nama_peserta'] }}</td>
            <td class="center">{{ $row['tanggal_asesmen'] }}</td>
            <td class="center">{{ $row['aplikasi'] }}</td>
            <td class="center">{{ $row['hasil_asesmen'] }}</td>
            <td class="center">{{ $row['keputusan'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="ket-note">Keterangan: *) Diisi dengan K (Kompeten) atau BK (Belum Kompeten)</div>

<div class="dok-title">Dokumen terkait:</div>
<ol class="dok-list">
    <li>FR.APL.01 Permohonan Sertifikasi</li>
    <li>FR.APL.03 Standar Kriteria dan Penilaian Awal Pemohon</li>
    <li>FR.AK.01 Persetujuan Asesmen, Ketidakberpihakan, Kerahasiaan dan Keamanan Sertifikasi</li>
    <li>FR.AK.02 Rekaman Asesmen</li>
    <li>FR.AK.05 Laporan Asesmen</li>
    <li>Persyaratan Pemohon</li>
</ol>

<div class="justify">
    Demikian berita acara ini dibuat dengan sebenarnya, untuk digunakan sebagaimana mestinya.
</div>

<table class="ttd-outer">
    <tr>
        <td style="width:50%;"></td>
        <td style="width:50%;" class="ttd-inner">
            <div class="fw-bold">Penanggungjawab Keputusan:</div>
            <table class="field-table">
                <tr><td class="ttd-label">Nama</td><td class="field-colon">:</td><td>{{ $namaPenanggungjawab }}</td></tr>
                <tr><td class="ttd-label">Jabatan</td><td class="field-colon">:</td><td>{{ $jabatanPenanggungjawab }}</td></tr>
                <tr>
                    <td class="ttd-label" style="vertical-align:top">Tanda tangan</td>
                    <td class="field-colon">:</td>
                    <td>
                        @if($ttdPenanggungjawab['path'])
                            <div class="ttd-img"><img src="{{ $ttdPenanggungjawab['path'] }}" style="height:{{ $ttdPenanggungjawab['h'] }}mm;width:{{ $ttdPenanggungjawab['w'] }}mm;"></div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</div>

</body>
</html>
