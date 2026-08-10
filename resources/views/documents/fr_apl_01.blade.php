<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>FR.APL.01 - {{ $namaPeserta }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: cambria, 'Times New Roman', Times, serif; font-size: 9.5pt; color: #000; }

.title { font-size: 11pt; font-weight: bold; margin-bottom: 8pt; }
.section-title { font-weight: bold; margin: 8pt 0 3pt; font-size: 10pt; }
.section-desc { margin-bottom: 6pt; text-align: justify; }

.dl-table { width: 100%; border-collapse: collapse; margin-bottom: 6pt; }
.dl-table td { padding: 2pt 4pt; vertical-align: top; }
.dl-label { width: 145pt; }
.dl-colon { width: 8pt; }
.dl-value { border-bottom: 0.5pt solid #999; }
.strike { text-decoration: line-through; color: #888; }

.skema-table { width: 100%; border-collapse: collapse; margin-bottom: 8pt; font-size: 9.5pt; }
.skema-table td, .skema-table th { border: 0.75pt solid #000; padding: 3pt 5pt; vertical-align: middle; }
.skema-label { font-weight: bold; width: 20%; }

.bukti-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin: 6pt 0; }
.bukti-table th, .bukti-table td { border: 0.75pt solid #000; padding: 3pt 5pt; vertical-align: middle; }
.bukti-table th { font-weight: bold; text-align: center; background-color: #cfe6c8; }
.bukti-num { text-align: center; width: 5%; }
.bukti-check { text-align: center; width: 12%; }

.page-break { page-break-before: always; }

.rekom-table { width: 100%; border-collapse: collapse; margin-top: 8pt; font-size: 9.5pt; }
.rekom-table td { border: 0.75pt solid #000; padding: 5pt 6pt; vertical-align: top; }
.rekom-label { font-weight: bold; }
.ttd-cell { text-align: center; }
.ttd-img img { display: inline-block; margin: 3pt 0; }
.ttd-name { font-weight: bold; margin-top: 3pt; }
</style>
</head>
<body>

<div class="title">FR.APL.01. PERMOHONAN SERTIFIKASI KOMPETENSI</div>

<div class="section-title">Bagian 1 : Rincian Data Pemohon Sertifikasi</div>
<div class="section-desc">Pada bagian ini, cantumkan data pribadi, data pendidikan formal serta data pekerjaan anda pada saat ini.</div>

<div class="section-title">a. Data Pribadi</div>
<table class="dl-table">
    <tr>
        <td class="dl-label">Nama lengkap</td><td class="dl-colon">:</td>
        <td class="dl-value">{{ $pribadi['name'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="dl-label">No. KTP/NIK/Paspor</td><td class="dl-colon">:</td>
        <td class="dl-value">{{ $pribadi['nik'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="dl-label">Tempat / tgl. Lahir</td><td class="dl-colon">:</td>
        <td class="dl-value">{{ $pribadi['tempat_lahir'] ?? '-' }}, {{ $tanggalLahir }}</td>
    </tr>
    <tr>
        <td class="dl-label">Jenis kelamin</td><td class="dl-colon">:</td>
        <td class="dl-value">
            <span @if(($pribadi['jenis_kelamin'] ?? null) !== 'L') class="strike" @endif>Laki-laki</span> /
            <span @if(($pribadi['jenis_kelamin'] ?? null) !== 'P') class="strike" @endif>Wanita</span>
        </td>
    </tr>
    <tr>
        <td class="dl-label">Kebangsaan</td><td class="dl-colon">:</td>
        <td class="dl-value">{{ $pribadi['kebangsaan'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="dl-label">Alamat rumah</td><td class="dl-colon">:</td>
        <td class="dl-value">{{ $pribadi['alamat_rumah'] ?? '-' }} &nbsp;&nbsp; Kode pos: {{ $pribadi['kode_pos_rumah'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="dl-label">No. Telepon/E-mail</td><td class="dl-colon">:</td>
        <td class="dl-value">Rumah: {{ $pribadi['telp_rumah'] ?? '-' }} &nbsp;&nbsp; HP: {{ $pribadi['hp'] ?? '-' }} &nbsp;&nbsp; E-mail: {{ $pribadi['email'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="dl-label">Kualifikasi Pendidikan</td><td class="dl-colon">:</td>
        <td class="dl-value">{{ $pribadi['kualifikasi_pendidikan'] ?? '-' }}</td>
    </tr>
</table>

<div class="section-title">b. Data Pekerjaan Sekarang</div>
<table class="dl-table">
    <tr>
        <td class="dl-label">Nama Institusi / Perusahaan</td><td class="dl-colon">:</td>
        <td class="dl-value">{{ $pekerjaan['institusi'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="dl-label">Jabatan</td><td class="dl-colon">:</td>
        <td class="dl-value">{{ $pekerjaan['jabatan'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="dl-label">Alamat Kantor</td><td class="dl-colon">:</td>
        <td class="dl-value">{{ $pekerjaan['alamat_kantor'] ?? '-' }} &nbsp;&nbsp; Kode pos: {{ $pekerjaan['kode_pos_kantor'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="dl-label">No. Telp/Fax/E-mail</td><td class="dl-colon">:</td>
        <td class="dl-value">Telp: {{ $pekerjaan['telp_kantor'] ?? '-' }} &nbsp;&nbsp; Fax: {{ $pekerjaan['fax_kantor'] ?? '-' }} &nbsp;&nbsp; E-mail: {{ $pekerjaan['email_kantor'] ?? '-' }}</td>
    </tr>
</table>

<div class="section-title">Bagian 2 : Data Sertifikasi</div>
<table class="skema-table">
    <tr>
        <td class="skema-label" rowspan="2">Skema Sertifikasi</td>
        <td style="width:12%">Judul</td><td>:</td>
        <td>{{ $namaSkema }}</td>
    </tr>
    <tr>
        <td>Nomor</td><td>:</td>
        <td>{{ $kodeSkema }}</td>
    </tr>
    <tr>
        <td colspan="2"></td><td>:</td>
        <td><img src="{{ $tujuanAsesmen === 'Sertifikasi Ulang' ? $checkboxEmptyPath : $checkboxCheckedPath }}" style="width:9pt;height:9pt;vertical-align:middle;"> Sertifikasi</td>
    </tr>
    <tr>
        <td colspan="2"></td><td></td>
        <td><img src="{{ $tujuanAsesmen === 'Sertifikasi Ulang' ? $checkboxCheckedPath : $checkboxEmptyPath }}" style="width:9pt;height:9pt;vertical-align:middle;"> Sertifikasi Ulang</td>
    </tr>
</table>

<div class="page-break"></div>

<div class="section-title">Bagian 3 : Bukti Kelengkapan Pemohon</div>
<table class="bukti-table">
    <thead>
        <tr>
            <th class="bukti-num" rowspan="2">No.</th>
            <th rowspan="2">Bukti Persyaratan</th>
            <th colspan="2">Ada</th>
            <th class="bukti-check" rowspan="2">Tidak Ada</th>
        </tr>
        <tr>
            <th class="bukti-check">Memenuhi Syarat</th>
            <th class="bukti-check">Tidak Memenuhi Syarat</th>
        </tr>
    </thead>
    <tbody>
        @foreach($buktiList as $i => $bukti)
            <tr>
                <td class="bukti-num">{{ $i + 1 }}</td>
                <td>{{ $bukti['label'] }}</td>
                <td class="bukti-check"><img src="{{ $bukti['status'] === 'verified' ? $checkboxCheckedPath : $checkboxEmptyPath }}" style="width:9pt;height:9pt;vertical-align:middle;"></td>
                <td class="bukti-check"><img src="{{ $bukti['status'] === 'rejected' ? $checkboxCheckedPath : $checkboxEmptyPath }}" style="width:9pt;height:9pt;vertical-align:middle;"></td>
                <td class="bukti-check"><img src="{{ $bukti['status'] === 'none' ? $checkboxCheckedPath : $checkboxEmptyPath }}" style="width:9pt;height:9pt;vertical-align:middle;"></td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="rekom-table">
    <tr>
        <td style="width:35%" rowspan="4">
            <div class="rekom-label">Rekomendasi (diisi oleh LSP):</div>
            <div style="margin-top:4pt">
                Berdasarkan ketentuan persyaratan dasar, maka pemohon:<br>
                <strong>
                    <span @if(!$diterima) class="strike" @endif>Diterima</span> /
                    <span @if($diterima) class="strike" @endif>Tidak diterima</span>
                </strong><br>
                sebagai peserta sertifikasi
            </div>
        </td>
        <td colspan="2"><strong>Pemohon / Kandidat :</strong></td>
    </tr>
    <tr>
        <td style="width:20%">Nama</td>
        <td>{{ $namaPeserta }}</td>
    </tr>
    <tr>
        <td>Tanda tangan/Tanggal</td>
        <td class="ttd-cell">
            @if($ttdPemohon['path'])
                <div class="ttd-img"><img src="{{ $ttdPemohon['path'] }}" style="width:{{ $ttdPemohon['w'] }}mm;height:{{ $ttdPemohon['h'] }}mm;"></div>
            @endif
            <div>{{ $tanggalPemohon }}</div>
        </td>
    </tr>
    <tr><td colspan="2"></td></tr>

    <tr>
        <td rowspan="3"><strong>Catatan :</strong></td>
        <td style="width:32%"><strong>Admin LSP :</strong></td>
        <td><strong>Asesor :</strong></td>
    </tr>
    <tr>
        <td>{{ $namaAdmin }}</td>
        <td>{{ $namaAsesor }}</td>
    </tr>
    <tr>
        <td class="ttd-cell">
            @if($ttdAdmin['path'])
                <div class="ttd-img"><img src="{{ $ttdAdmin['path'] }}" style="width:{{ $ttdAdmin['w'] }}mm;height:{{ $ttdAdmin['h'] }}mm;"></div>
            @endif
            <div>{{ $tanggalAdmin }}</div>
        </td>
        <td class="ttd-cell">
            @if($ttdAsesor['path'])
                <div class="ttd-img"><img src="{{ $ttdAsesor['path'] }}" style="width:{{ $ttdAsesor['w'] }}mm;height:{{ $ttdAsesor['h'] }}mm;"></div>
            @endif
            <div>{{ $tanggalAsesor }}</div>
        </td>
    </tr>
</table>

</body>
</html>
