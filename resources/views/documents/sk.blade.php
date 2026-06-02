<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keputusan - {{ $result->sk_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Cambria, 'Times New Roman', serif; font-size: 12pt; margin: 0; color: #000; line-height: 1.35; }
        @page { margin: 0; size: A4; }

        /* ────── KOP ────── */
        .kop { padding: 4mm 12.7mm 5.5mm 12.7mm; display: table; width: 100%; }
        .kop-logo-left  { display: table-cell; vertical-align: middle; text-align: left;  width: 42mm; }
        .kop-logo-right { display: table-cell; vertical-align: middle; text-align: right; width: 55mm; }
        .kop-mid        { display: table-cell; vertical-align: middle; text-align: center; }
        .kop-logo-left  img { max-width: 39.9mm; max-height: 16.7mm; }
        .kop-logo-right img { max-width: 52mm;   max-height: 18.1mm; }
        .kop-divider    { border: none; border-top: 3px solid #000; margin: 0 12.7mm 1mm; }
        .kop-divider2   { border: none; border-top: 1px solid #000; margin: 0 12.7mm 3mm; }

        /* ────── CONTENT ────── */
        .content { padding: 0 25.4mm 25.4mm 25.4mm; }
        .page-break { page-break-after: always; }

        /* ────── SK TITLES ────── */
        .sk-judul       { text-align: center; font-weight: bold; font-size: 12pt; margin: 0 0 2pt; text-transform: uppercase; }
        .sk-nomor       { text-align: center; font-weight: bold; font-size: 12pt; margin: 0 0 14pt; }
        .sk-tentang     { text-align: center; font-weight: bold; font-size: 12pt; margin: 0 0 2pt; }
        .sk-judul-skema { text-align: center; font-weight: bold; font-size: 12pt; margin: 0 0 14pt; text-transform: uppercase; }

        /* ────── MENIMBANG / MENGINGAT ────── */
        .section-label  { font-weight: bold; font-size: 12pt; margin-bottom: 7pt; }
        .numbered-list  { width: 100%; border-collapse: collapse; margin-bottom: 21pt; }
        .numbered-list td { vertical-align: top; padding: 0 0 6pt 0; font-size: 12pt; line-height: 16pt; }
        .numbered-list td.num { width: 8mm; }

        /* ────── MEMUTUSKAN ────── */
        .memutuskan-tbl { width: 100%; border-collapse: collapse; margin-bottom: 4pt; }
        .memutuskan-tbl td { vertical-align: top; padding: 0 0 4pt 0; font-size: 12pt; line-height: 18pt; text-align: justify; }
        .memutuskan-tbl td.lbl { width: 32mm; }
        .memutuskan-tbl td.col { width: 4mm; }
        .penutup { font-size: 12pt; line-height: 16pt; text-align: justify; margin: 8pt 0 10pt; }

        /* ────── TTD / QR ────── */
        .ttd-block { width: 100%; margin-top: 10pt; }
        .ttd-tabel { width: 100%; border-collapse: collapse; margin-bottom: 6pt; }
        .ttd-tabel td { vertical-align: top; padding: 0 0 2pt 0; font-size: 12pt; line-height: 16pt; }
        .ttd-tabel td.lbl { width: 36mm; }
        .ttd-tabel td.sep { width: 4mm; }
        .qr-row img.qr { width: 32mm; height: 32mm; }
        .qr-note { font-size: 12pt; line-height: 18pt; font-style: italic; }
        .signer { font-size: 12pt; font-weight: bold; line-height: 16pt; margin-top: 8pt; }

        /* ────── LAMPIRAN (HAL 3) ────── */
        .lamp-title { font-weight: bold; font-size: 14pt; margin-bottom: 22pt; }
        .ident-tbl  { width: 100%; border-collapse: collapse; margin-bottom: 18pt; }
        .ident-tbl td { padding: 0 0 2pt 0; font-size: 12pt; line-height: 14pt; vertical-align: top; }
        .ident-tbl td.lbl { width: 45mm; }
        .ident-tbl td.sep { width: 4mm; }
        .nilai-tbl  { width: 100%; border-collapse: collapse; margin-bottom: 28pt; }
        .nilai-tbl th, .nilai-tbl td { border: 1px solid #000; text-align: center; vertical-align: middle; padding: 6pt 4pt; font-size: 11pt; line-height: 12pt; }
        .nilai-tbl th { background-color: #BFCDE9; font-weight: bold; }
        .nilai-tbl .status-cell { background-color: #D9EAD3; font-weight: bold; }
        .kat-label { font-size: 12pt; margin-bottom: 6pt; }
        .kat-tbl { width: 100%; border-collapse: collapse; }
        .kat-tbl td { padding: 1pt 6pt 1pt 0; font-size: 12pt; line-height: 14pt; vertical-align: top; }
        .kat-tbl td.no  { width: 6mm; }
        .kat-tbl td.rng { width: 46mm; }
    </style>
</head>
<body>

@php
    $statusRaw   = strtoupper($result->keputusan ?? '');
    $statusKomp  = $statusRaw === 'LULUS' ? 'KOMPETEN' : 'TIDAK KOMPETEN';
    $statusFmt   = $statusRaw === 'LULUS' ? 'Kompeten' : 'Tidak Kompeten';
    $statusLulus = $statusRaw === 'LULUS' ? 'Lulus' : 'Tidak Lulus';
    $hakKata     = $statusRaw === 'LULUS' ? 'berhak' : 'tidak berhak';
    $gelar       = $classroom?->gelar ?? '';
    $namaSkema   = $classroom?->title ?? '';
@endphp

{{-- ═══════════════ HAL 1 — Menimbang + Mengingat ═══════════════ --}}
<div class="kop">
    <div class="kop-logo-left">
        @if(file_exists($logoEdukiaPath))
            <img src="{{ $logoEdukiaPath }}">
        @endif
    </div>
    <div class="kop-mid"></div>
    <div class="kop-logo-right">
        {{-- Logo KAN hanya tampil jika varian $kan = true --}}
        @if($kan && file_exists($logoKanPath))
            <img src="{{ $logoKanPath }}">
        @endif
    </div>
</div>
<hr class="kop-divider"><hr class="kop-divider2">

<div class="content">
    <div class="sk-judul">Surat Keputusan</div>
    <div class="sk-judul">Lembaga Sertifikasi Person Edukasi Global Cendekia</div>
    <div class="sk-nomor">Nomor: {{ $result->sk_number }}</div>
    <div class="sk-tentang">Tentang</div>
    <div class="sk-judul-skema">Sertifikasi {{ $namaSkema }}</div>

    <div class="section-label">Menimbang :</div>
    <table class="numbered-list">
        @foreach($menimbang as $i => $item)
        <tr>
            <td class="num">{{ $i + 1 }}.</td>
            <td>{!! str_replace('{skema}', e($namaSkema), $item) !!}</td>
        </tr>
        @endforeach
    </table>

    <div class="section-label">Mengingat :</div>
    <table class="numbered-list">
        @foreach($mengingat as $i => $item)
        <tr>
            <td class="num">{{ $i + 1 }}.</td>
            <td>{{ $item }}</td>
        </tr>
        @endforeach
    </table>
</div>

{{-- ═══════════════ HAL 2 — Memutuskan + QR ═══════════════ --}}
<div class="page-break"></div>
<div class="kop">
    <div class="kop-logo-left">
        @if(file_exists($logoEdukiaPath))<img src="{{ $logoEdukiaPath }}">@endif
    </div>
    <div class="kop-mid"></div>
    <div class="kop-logo-right">
        @if($kan && file_exists($logoKanPath))<img src="{{ $logoKanPath }}">@endif
    </div>
</div>
<hr class="kop-divider"><hr class="kop-divider2">

<div class="content">
    <div class="sk-judul" style="margin-bottom:16pt">Memutuskan</div>

    <table class="memutuskan-tbl">
        <tr>
            <td class="lbl">Menetapkan</td>
            <td class="col">:</td>
            <td></td>
        </tr>
        <tr>
            <td class="lbl">PERTAMA</td>
            <td class="col">:</td>
            <td>
                Atas nama : {{ $student->name }}<br>
                Telah mengikuti Uji Kompetensi {{ $namaSkema }} melalui penilaian langsung
                dan sertifikasi langsung dinyatakan :
                <strong>{{ $statusFmt }}/{{ $statusLulus }}</strong><br>
                dengan Kategori: <strong>{{ $kategori }}</strong>
            </td>
        </tr>
        <tr>
            <td class="lbl">KEDUA</td>
            <td class="col">:</td>
            <td>
                @if($gelar)
                    Kepada peserta Uji Kompetensi yang dinyatakan {{ $statusFmt }}/{{ $statusLulus }}
                    {{ $hakKata }} mencantumkan gelar non akademik <strong>{{ $gelar }}</strong>
                    di belakang nama selama masa berlakunya sertifikat
                @else
                    Kepada peserta Uji Kompetensi yang dinyatakan Lulus/Kompeten
                    berhak menggunakan sertifikat sertifikasi sebagai alat bukti keahlian
                    sesuai jenis skema sertifikasinya selama masa berlakunya sertifikat
                @endif
            </td>
        </tr>
        <tr>
            <td class="lbl">KETIGA</td>
            <td class="col">:</td>
            <td>
                Sehubungan dengan hal tersebut pada poin PERTAMA ditetapkan sebagai peserta
                Uji Kompetensi {{ $namaSkema }} Perguruan Tinggi melalui Keputusan Ketua LSP Edukasi Global Cendekia
            </td>
        </tr>
    </table>

    <p class="penutup">{{ $penutupHal2 }}</p>

    <table style="width:100%; margin-top:10pt;">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%; vertical-align:top;">
                <table class="ttd-tabel">
                    <tr>
                        <td class="lbl">Ditetapkan di</td>
                        <td class="sep">:</td>
                        <td>{{ $lsp['kota'] }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Pada tanggal</td>
                        <td class="sep">:</td>
                        <td>{{ $tglDitetapkan }}</td>
                    </tr>
                </table>
                <table style="width:100%; margin-top:4pt;">
                    <tr>
                        <td style="vertical-align:middle; width:36mm;">
                            @if($qrSkPath)
                                <img class="qr" src="{{ $qrSkPath }}">
                            @endif
                        </td>
                        <td style="vertical-align:middle; padding-left:8pt;">
                            <span class="qr-note">{!! nl2br(e($catatanQr)) !!}</span>
                        </td>
                    </tr>
                </table>
                <div class="signer">
                    {{ $penandatangan['nama'] }}<br>
                    <span style="font-weight:normal;font-size:11pt;">{{ $penandatangan['jabatan'] }}</span>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ═══════════════ HAL 3 — Lampiran Hasil Penilaian ═══════════════ --}}
<div class="page-break"></div>
<div class="kop">
    <div class="kop-logo-left">
        @if(file_exists($logoEdukiaPath))<img src="{{ $logoEdukiaPath }}">@endif
    </div>
    <div class="kop-mid"></div>
    <div class="kop-logo-right">
        @if($kan && file_exists($logoKanPath))<img src="{{ $logoKanPath }}">@endif
    </div>
</div>
<hr class="kop-divider"><hr class="kop-divider2">

<div class="content">
    <div class="lamp-title">Lampiran Hasil Penilaian:</div>

    <table class="ident-tbl">
        <tr><td class="lbl">Nama Peserta</td><td class="sep">:</td><td>{{ $student->name }}</td></tr>
        <tr><td class="lbl">NIK</td><td class="sep">:</td><td>{{ $student->nik ?? '-' }}</td></tr>
        <tr>
            <td class="lbl">Pelaksanaan</td>
            <td class="sep">:</td>
            <td>{{ $namaSkema }}@if($session?->kode_batch) <em>Batch</em> {{ $session->kode_batch }}@endif</td>
        </tr>
        <tr>
            <td class="lbl">Hari/Tanggal Ujian</td>
            <td class="sep">:</td>
            <td>{{ $session ? \Carbon\Carbon::parse($session->start_time)->translatedFormat('l, d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Tempat Uji Kompetensi</td>
            <td class="sep">:</td>
            <td>{{ $session?->tempat_ujian ?? 'Daring/<em>Online</em>' }}</td>
        </tr>
    </table>

    <table class="nilai-tbl">
        <thead>
            <tr>
                <th rowspan="2">NILAI WAWANCARA</th>
                <th rowspan="2">NILAI PILIHAN<br>GANDA</th>
                <th rowspan="2">NILAI ESAI</th>
                <th colspan="2">REKAPITULASI HASIL ASESMEN</th>
                <th rowspan="2">KATEGORI</th>
            </tr>
            <tr>
                <th>HASIL NILAI</th>
                <th>STATUS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $result->nilai_wawancara !== null ? number_format($result->nilai_wawancara,2) : '-' }}</td>
                <td>{{ $result->nilai_pg        !== null ? number_format($result->nilai_pg,2)        : '-' }}</td>
                <td>{{ $result->nilai_esai      !== null ? number_format($result->nilai_esai,2)      : '-' }}</td>
                <td><strong>{{ $result->nilai_akhir !== null ? number_format($result->nilai_akhir,2) : '-' }}</strong></td>
                <td class="status-cell"><strong>{{ $statusKomp }}</strong></td>
                <td>{{ $kategori }}</td>
            </tr>
        </tbody>
    </table>

    <div class="kat-label">Kategori:</div>
    <table class="kat-tbl">
        @foreach($kategoriList as $kat)
        <tr>
            <td class="no">{{ $loop->iteration }}.</td>
            <td class="rng">{!! $kat['range'] !!}</td>
            <td>: {!! $kat['label'] !!}</td>
        </tr>
        @endforeach
    </table>
</div>

</body>
</html>
