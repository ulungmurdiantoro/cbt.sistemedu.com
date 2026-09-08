<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>CV - {{ $namaLengkap }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: cambria, 'Times New Roman', Times, serif; font-size: 10.5pt; color: #000; }

.kop { text-align: center; font-size: 9.5pt; font-weight: bold; margin-bottom: 4pt; }
.title { text-align: center; font-size: 15pt; font-weight: bold; margin-bottom: 12pt; }

.photo-wrap { text-align: center; margin-bottom: 14pt; }
.photo-wrap img { border: 0.75pt solid #000; }

.sec-title { font-weight: bold; font-size: 11pt; margin: 14pt 0 6pt; }
.sub-title { font-weight: bold; margin: 8pt 0 4pt; }

.field-table { border-collapse: collapse; width: 100%; margin-bottom: 4pt; }
.field-table td { padding: 2pt 0; vertical-align: top; font-size: 10.5pt; }
.field-label { white-space: nowrap; width: 130pt; }
.field-colon { width: 10pt; }

.data-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-bottom: 6pt; }
.data-table th, .data-table td { border: 0.75pt solid #000; padding: 4pt 6pt; vertical-align: top; }
.data-table th { font-weight: bold; text-align: center; background: #eef2f6; }
.data-table td.center { text-align: center; }
.data-table td.no { text-align: center; width: 22pt; }

.keahlian-list { margin: 0 0 6pt 16pt; }
.keahlian-list li { margin-bottom: 2pt; }

.empty-note { color: #666; font-style: italic; font-size: 9.5pt; margin-bottom: 6pt; }
</style>
</head>
<body>

<div class="kop">LSP EDUKASI GLOBAL CENDEKIA</div>
<div class="title">CURRICULUM VITAE</div>

@if($foto['path'])
<div class="photo-wrap">
    <img src="{{ $foto['path'] }}" style="width:{{ $foto['w'] }}mm;height:{{ $foto['h'] }}mm;">
</div>
@endif

<div class="sec-title">1. Data Pribadi (Personal Identification)</div>
<table class="field-table">
    <tr><td class="field-label">Nama Lengkap</td><td class="field-colon">:</td><td>{{ $namaLengkap }}</td></tr>
    <tr><td class="field-label">Tempat Tanggal Lahir</td><td class="field-colon">:</td><td>{{ $tempatTanggalLahir }}</td></tr>
    <tr><td class="field-label">Jenis Kelamin</td><td class="field-colon">:</td><td>{{ $jenisKelamin }}</td></tr>
    <tr><td class="field-label">Alamat Rumah</td><td class="field-colon">:</td><td>{{ $alamatRumah }}</td></tr>
    <tr><td class="field-label">Nama Institusi/Kantor</td><td class="field-colon">:</td><td>{{ $namaInstitusi }}</td></tr>
    <tr><td class="field-label">Alamat Institusi/Kantor</td><td class="field-colon">:</td><td>{{ $alamatInstitusi }}</td></tr>
    <tr><td class="field-label">Nomor Handphone</td><td class="field-colon">:</td><td>{{ $noHandphone }}</td></tr>
    <tr><td class="field-label">Email</td><td class="field-colon">:</td><td>{{ $email }}</td></tr>
</table>

<div class="sec-title">2. Deskripsi Pendidikan (Description of Education)</div>
<div class="sub-title">A. Pendidikan Formal (Formal Education)</div>
@if(count($pendidikanFormal))
<table class="data-table">
    <thead>
        <tr><th style="width:22pt">No</th><th>Jenjang Pendidikan</th><th>Sekolah/Institusi/Pendidikan Tinggi</th><th>Bidang Ilmu</th><th style="width:60pt">Tahun Lulus</th></tr>
    </thead>
    <tbody>
        @foreach($pendidikanFormal as $i => $row)
        <tr>
            <td class="no">{{ $i + 1 }}</td>
            <td>{{ $row['jenjang'] ?? '' }}</td>
            <td>{{ $row['sekolah'] ?? '' }}</td>
            <td>{{ $row['bidang_ilmu'] ?? '' }}</td>
            <td class="center">{{ $row['tahun_lulus'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="empty-note">Belum ada data pendidikan formal.</div>
@endif

<div class="sub-title">B. Pelatihan (Training)</div>
@if(count($pelatihan))
<table class="data-table">
    <thead>
        <tr><th style="width:22pt">No</th><th>Judul Kegiatan</th><th>Penyelenggara</th><th style="width:50pt">Tahun</th><th>Lokasi</th></tr>
    </thead>
    <tbody>
        @foreach($pelatihan as $i => $row)
        <tr>
            <td class="no">{{ $i + 1 }}</td>
            <td>{{ $row['judul_kegiatan'] ?? '' }}</td>
            <td>{{ $row['penyelenggara'] ?? '' }}</td>
            <td class="center">{{ $row['tahun'] ?? '' }}</td>
            <td>{{ $row['lokasi'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="empty-note">Belum ada data pelatihan.</div>
@endif

<div class="sec-title">3. Pengalaman Kerja (Job Experience)</div>
@if(count($pengalamanKerja))
<table class="data-table">
    <thead>
        <tr><th style="width:22pt">No</th><th>Jabatan</th><th style="width:60pt">Tahun</th><th>Perusahaan/Institusi</th><th>Lokasi</th></tr>
    </thead>
    <tbody>
        @foreach($pengalamanKerja as $i => $row)
        <tr>
            <td class="no">{{ $i + 1 }}</td>
            <td>{{ $row['jabatan'] ?? '' }}</td>
            <td class="center">{{ $row['tahun'] ?? '' }}</td>
            <td>{{ $row['perusahaan'] ?? '' }}</td>
            <td>{{ $row['lokasi'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="empty-note">Belum ada data pengalaman kerja.</div>
@endif

<div class="sec-title">4. Keahlian (Expertise)</div>
@if(count($keahlian))
<ol type="a" class="keahlian-list">
    @foreach($keahlian as $item)
    <li>{{ $item }}</li>
    @endforeach
</ol>
@else
<div class="empty-note">Belum ada data keahlian.</div>
@endif

<div class="sec-title">5. Pengalaman Profesional (Professional Experience)</div>
@if(count($pengalamanProfesional))
<table class="data-table">
    <thead>
        <tr><th style="width:22pt">No</th><th>Pengalaman</th><th>Penyelenggara</th><th style="width:60pt">Tahun</th></tr>
    </thead>
    <tbody>
        @foreach($pengalamanProfesional as $i => $row)
        <tr>
            <td class="no">{{ $i + 1 }}</td>
            <td>{{ $row['pengalaman'] ?? '' }}</td>
            <td>{{ $row['penyelenggara'] ?? '' }}</td>
            <td class="center">{{ $row['tahun'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="empty-note">Belum ada data pengalaman profesional.</div>
@endif

<div class="sec-title">6. Sertifikasi Kompetensi</div>
@if(count($sertifikasiKompetensi))
<table class="data-table">
    <thead>
        <tr><th style="width:22pt">No</th><th>Jenis Sertifikasi</th><th>Bidang Ilmu</th><th>Penyelenggara</th><th style="width:45pt">Tahun</th><th style="width:55pt">Masa Berlaku</th></tr>
    </thead>
    <tbody>
        @foreach($sertifikasiKompetensi as $i => $row)
        <tr>
            <td class="no">{{ $i + 1 }}</td>
            <td>{{ $row['jenis_sertifikasi'] ?? '' }}</td>
            <td>{{ $row['bidang_ilmu'] ?? '' }}</td>
            <td>{{ $row['penyelenggara'] ?? '' }}</td>
            <td class="center">{{ $row['tahun'] ?? '' }}</td>
            <td class="center">{{ $row['masa_berlaku'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="empty-note">Belum ada data sertifikasi kompetensi.</div>
@endif

</body>
</html>
