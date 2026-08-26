<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>FR.AK.05 - Laporan Asesmen</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: cambria, 'Times New Roman', Times, serif; font-size: 9.5pt; color: #000; }

.title { font-size: 11pt; font-weight: bold; margin-bottom: 8pt; }

.info-table { width: 100%; border-collapse: collapse; margin-bottom: 10pt; font-size: 9.5pt; }
.info-table td { border: 0.75pt solid #000; padding: 4pt 6pt; vertical-align: top; }
.info-label { font-weight: bold; width: 22%; }
.info-colon { width: 4%; }

.asesi-table { width: 100%; border-collapse: collapse; margin-bottom: 4pt; font-size: 9pt; }
.asesi-table th, .asesi-table td { border: 0.75pt solid #000; padding: 4pt 5pt; vertical-align: top; }
.asesi-table th { text-align: center; font-weight: bold; background: #f0f0f0; }
.asesi-table td.center { text-align: center; }
.asesi-note { font-size: 8pt; font-style: italic; margin-bottom: 10pt; }

.narasi-table { width: 100%; border-collapse: collapse; margin-bottom: 10pt; font-size: 9.5pt; }
.narasi-table td { border: 0.75pt solid #000; padding: 5pt 6pt; vertical-align: top; text-align: justify; }
.narasi-label { font-weight: bold; width: 30%; }

.catatan-table { width: 100%; border-collapse: collapse; }
.catatan-table td { border: 0.75pt solid #000; padding: 6pt; vertical-align: top; }
.catatan-label { width: 35%; }
.asesor-table { width: 100%; border-collapse: collapse; }
.asesor-table td { padding: 3pt 0; vertical-align: top; }
.asesor-row-label { width: 30%; font-weight: normal; }
.ttd-img { display: block; margin-top: 3pt; }
</style>
</head>
<body>

<div class="title">FR.AK.05. LAPORAN ASESMEN</div>

<table class="info-table">
    <tr>
        <td class="info-label" rowspan="2">Skema Sertifikasi</td>
        <td style="width:10%">Judul</td><td class="info-colon">:</td>
        <td>{{ $namaSkema }}</td>
    </tr>
    <tr>
        <td>Nomor</td><td>:</td>
        <td>{{ $kodeSkema }}</td>
    </tr>
    <tr>
        <td class="info-label">TUK</td><td colspan="2">:</td>
        <td>{{ $tuk }}</td>
    </tr>
    <tr>
        <td class="info-label">Nama Asesor</td><td colspan="2">:</td>
        <td>{{ $namaAsesor }}</td>
    </tr>
    <tr>
        <td class="info-label">Tanggal</td><td colspan="2">:</td>
        <td>{{ $tanggalAsesmen }}</td>
    </tr>
</table>

<table class="asesi-table">
    <tr>
        <th rowspan="2" style="width:5%">No.</th>
        <th rowspan="2">Nama Asesi</th>
        <th colspan="2">Rekomendasi</th>
        <th rowspan="2">Keterangan**</th>
    </tr>
    <tr>
        <th style="width:8%">K</th>
        <th style="width:8%">BK</th>
    </tr>
    @foreach($rows as $i => $row)
    <tr>
        <td class="center">{{ $i + 1 }}.</td>
        <td>{{ $row['name'] }}</td>
        <td class="center"><img src="{{ $row['rekomendasi'] === 'K' ? $checkboxCheckedPath : $checkboxEmptyPath }}" style="width:9pt;height:9pt;"></td>
        <td class="center"><img src="{{ $row['rekomendasi'] === 'BK' ? $checkboxCheckedPath : $checkboxEmptyPath }}" style="width:9pt;height:9pt;"></td>
        <td>{{ $row['keterangan'] }}</td>
    </tr>
    @endforeach
</table>
<div class="asesi-note">** tuliskan Kode dan Judul Unit Kompetensi yang dinyatakan BK bila mengases satu skema</div>

<table class="narasi-table">
    <tr>
        <td class="narasi-label">Aspek Negatif dan Positif dalam Asesemen</td>
        <td>{{ $aspekNegatifPositif }}</td>
    </tr>
    <tr>
        <td class="narasi-label">Pencatatan Penolakan Hasil Asesmen</td>
        <td>{{ $pencatatanPenolakan }}</td>
    </tr>
    <tr>
        <td class="narasi-label">Saran Perbaikan :<br>(Asesor/Personil Terkait)</td>
        <td>{{ $saranPerbaikan }}</td>
    </tr>
</table>

<table class="catatan-table">
    <tr>
        <td class="catatan-label">
            <strong>Catatan :</strong><br><br>
            {{ $catatan }}
        </td>
        <td>
            <table class="asesor-table">
                <tr><td colspan="2" style="font-weight:bold;">Asesor :</td></tr>
                <tr>
                    <td class="asesor-row-label">Nama</td>
                    <td>{{ $namaAsesor }}</td>
                </tr>
                <tr>
                    <td class="asesor-row-label">Tanda tangan/<br>Tanggal</td>
                    <td>
                        @if($ttdAsesor['path'])
                            <img class="ttd-img" src="{{ $ttdAsesor['path'] }}" style="width:{{ $ttdAsesor['w'] }}mm;height:{{ $ttdAsesor['h'] }}mm;">
                        @endif
                        <div style="margin-top:4pt;">{{ $tanggalTtd }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
