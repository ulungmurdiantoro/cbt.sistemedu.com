<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keputusan - {{ $result->sk_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Cambria, 'Times New Roman', serif; font-size: 12pt; margin: 0; color: #000; line-height: 1.35; }
        @page { margin: 0; size: A4; }

        /* ────── KOP HEADER ────── */
        .kop {
            padding: 4mm 12.7mm 5.5mm 12.7mm;
            display: table;
            width: 100%;
        }
        .kop-logo-left  { display: table-cell; vertical-align: middle; text-align: left;  width: 42mm; }
        .kop-logo-right { display: table-cell; vertical-align: middle; text-align: right; width: 55mm; }
        .kop-mid        { display: table-cell; vertical-align: middle; text-align: center; }
        .kop-logo-left  img { max-width: 39.9mm; max-height: 16.7mm; }
        .kop-logo-right img { max-width: 52mm;   max-height: 18.1mm; }
        .kop-text-main  { font-size: 12pt; font-weight: bold; margin: 0; text-transform: uppercase; }
        .kop-text-sub   { font-size: 9pt; margin: 2px 0 0; }
        .kop-divider    { border: none; border-top: 3px solid #000; margin: 0 12.7mm 1mm; }
        .kop-divider2   { border: none; border-top: 1px solid #000; margin: 0 12.7mm 3mm; }

        /* ────── CONTENT AREA ────── */
        .content { padding: 0 25.4mm 25.4mm 25.4mm; }

        /* ────── PAGE BREAK ────── */
        .page-break { page-break-after: always; }

        /* ────── SK TITLES ────── */
        .sk-judul { text-align: center; font-weight: bold; font-size: 12pt; margin: 0 0 2pt; text-transform: uppercase; }
        .sk-nomor { text-align: center; font-weight: bold; font-size: 12pt; margin: 0 0 14pt; }
        .sk-tentang { text-align: center; font-weight: bold; font-size: 12pt; margin: 0 0 2pt; }
        .sk-judul-skema { text-align: center; font-weight: bold; font-size: 12pt; margin: 0 0 14pt; text-transform: uppercase; }

        /* ────── MENIMBANG / MENGINGAT ────── */
        .section-label { font-weight: bold; font-size: 12pt; margin-bottom: 7pt; }
        .numbered-list { width: 100%; border-collapse: collapse; margin-bottom: 21pt; }
        .numbered-list td { vertical-align: top; padding: 0 0 6pt 0; font-size: 12pt; line-height: 16pt; }
        .numbered-list td.num { width: 8mm; }
        .numbered-list td.col { width: 2mm; padding-right: 4pt; }

        /* ────── MEMUTUSKAN ────── */
        .memutuskan-tbl { width: 100%; border-collapse: collapse; margin-bottom: 4pt; }
        .memutuskan-tbl td { vertical-align: top; padding: 0 0 4pt 0; font-size: 12pt; line-height: 18pt; text-align: justify; }
        .memutuskan-tbl td.lbl { width: 32mm; font-weight: normal; }
        .memutuskan-tbl td.col { width: 4mm; }

        .penutup { font-size: 12pt; line-height: 16pt; text-align: justify; margin: 8pt 0 10pt; }

        /* ────── TTD / QR BLOCK ────── */
        .ttd-block { width: 100mm; float: right; margin-top: 10pt; }
        .ttd-tabel { width: 100%; border-collapse: collapse; margin-bottom: 6pt; }
        .ttd-tabel td { vertical-align: top; padding: 0 0 2pt 0; font-size: 12pt; line-height: 16pt; }
        .ttd-tabel td.lbl { width: 36mm; }
        .ttd-tabel td.sep { width: 4mm; }
        .qr-row { margin-top: 6pt; }
        .qr-row img.qr  { width: 32mm; height: 32mm; display: inline-block; vertical-align: middle; }
        .qr-note { display: inline-block; vertical-align: middle; margin-left: 8pt; font-size: 12pt; line-height: 18pt; font-style: italic; width: 52mm; }
        .signer { font-size: 12pt; font-weight: bold; line-height: 16pt; margin-top: 8pt; }
        .clearfix::after { content: ''; display: table; clear: both; }

        /* ────── LAMPIRAN (PAGE 3) ────── */
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

{{-- ═══════════════════════════════════════════
     PAGE 1 — Menimbang + Mengingat
═══════════════════════════════════════════ --}}
<div class="kop">
    <div class="kop-logo-left">
        @if($template?->kop_path)
            <img src="{{ storage_path('app/public/' . $template->kop_path) }}">
        @endif
    </div>
    <div class="kop-mid"></div>
    <div class="kop-logo-right">
        @if($template?->kop_logo2_path)
            <img src="{{ storage_path('app/public/' . $template->kop_logo2_path) }}">
        @endif
    </div>
</div>
<hr class="kop-divider">
<hr class="kop-divider2">

<div class="content">

    <div class="sk-judul">Surat Keputusan</div>
    <div class="sk-judul">Lembaga Sertifikasi Person Edukasi Global Cendekia</div>
    <div class="sk-nomor">Nomor: {{ $result->sk_number }}</div>

    <div class="sk-tentang">Tentang</div>
    <div class="sk-judul-skema">Sertifikasi {{ $classroom?->title ?? '' }}</div>

    {{-- MENIMBANG — items dari config/lsp.php, {skema} di-replace --}}
    <div class="section-label">Menimbang :</div>
    <table class="numbered-list">
        @foreach($menimbang as $idx => $item)
        <tr>
            <td class="num">{{ $idx + 1 }}.</td>
            <td>{!! str_replace('{skema}', e($classroom?->title ?? ''), $item) !!}</td>
        </tr>
        @endforeach
    </table>

    {{-- MENGINGAT — items dari config/lsp.php --}}
    <div class="section-label">Mengingat :</div>
    <table class="numbered-list">
        @foreach($mengingat as $idx => $item)
        <tr>
            <td class="num">{{ $idx + 1 }}.</td>
            <td>{{ $item }}</td>
        </tr>
        @endforeach
    </table>

</div>

{{-- ═══════════════════════════════════════════
     PAGE 2 — Memutuskan + TTD + QR
═══════════════════════════════════════════ --}}
<div class="page-break"></div>

<div class="kop">
    <div class="kop-logo-left">
        @if($template?->kop_path)
            <img src="{{ storage_path('app/public/' . $template->kop_path) }}">
        @endif
    </div>
    <div class="kop-mid"></div>
    <div class="kop-logo-right">
        @if($template?->kop_logo2_path)
            <img src="{{ storage_path('app/public/' . $template->kop_logo2_path) }}">
        @endif
    </div>
</div>
<hr class="kop-divider">
<hr class="kop-divider2">

<div class="content">

    <div class="sk-judul" style="margin-bottom:16pt">Memutuskan</div>

    @php
        $statusRaw = strtoupper($result->keputusan ?? '');
        $statusFmt = $statusRaw === 'LULUS' ? 'Lulus' : 'Tidak Lulus';
        $kompFmt   = $statusRaw === 'LULUS' ? 'Kompeten' : 'Tidak Kompeten';
        $hakKata   = $statusRaw === 'LULUS' ? 'berhak' : 'tidak berhak';
        $gelar     = $classroom?->gelar ?? '';
        $namaSkema = $classroom?->title ?? '';
    @endphp

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
                <strong>{{ $kompFmt }}/{{ $statusFmt }}</strong><br>
                dengan Kategori: <strong>{{ $kategori }}</strong>
            </td>
        </tr>
        <tr>
            <td class="lbl">KEDUA</td>
            <td class="col">:</td>
            <td>
                @if($gelar)
                    Kepada peserta Uji Kompetensi yang dinyatakan {{ $kompFmt }}/{{ $statusFmt }}
                    {{ $hakKata }} mencantumkan gelar non akademik <strong>{{ $gelar }}</strong>
                    di belakang nama selama masa berlakunya sertifikat
                @else
                    Kepada peserta Uji Kompetensi yang dinyatakan Lulus/ Kompeten
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

    <p class="penutup">Demikian surat keputusan ini ditetapkan, apabila terdapat kekeliruan dalam penerbitan
    surat keputusan ini maka akan diperbaiki sebagaimana mestinya.</p>

    <div class="ttd-block">
        <table class="ttd-tabel">
            <tr>
                <td class="lbl">Ditetapkan di</td>
                <td class="sep">:</td>
                <td>{{ $template?->kota ?? 'Semarang' }}</td>
            </tr>
            <tr>
                <td class="lbl">Pada tanggal</td>
                <td class="sep">:</td>
                <td>{{ $tglDitetapkan }}</td>
            </tr>
        </table>

        <div class="qr-row">
            @if($qrSkPath)
                <img class="qr" src="{{ $qrSkPath }}">
            @endif
            <span class="qr-note">Dokumen ini telah ditandatangani<br>secara elektronik menggunakan<br>system digital yang terintegrasi</span>
        </div>

        <div class="signer">
            {{ $template?->nama_penandatangan ?? 'Dr. Agung Yulianto, M.Si.' }}<br>
            <span style="font-weight:normal;font-size:11pt;">{{ $template?->jabatan_penandatangan ?? 'Ketua LSP Edukasi Global Cendekia' }}</span>
        </div>
    </div>

    <div class="clearfix"></div>

</div>

{{-- ═══════════════════════════════════════════
     PAGE 3 — Lampiran Hasil Penilaian
═══════════════════════════════════════════ --}}
<div class="page-break"></div>

<div class="kop">
    <div class="kop-logo-left">
        @if($template?->kop_path)
            <img src="{{ storage_path('app/public/' . $template->kop_path) }}">
        @endif
    </div>
    <div class="kop-mid"></div>
    <div class="kop-logo-right">
        @if($template?->kop_logo2_path)
            <img src="{{ storage_path('app/public/' . $template->kop_logo2_path) }}">
        @endif
    </div>
</div>
<hr class="kop-divider">
<hr class="kop-divider2">

<div class="content">

    <div class="lamp-title">Lampiran Hasil Penilaian:</div>

    {{-- Identitas --}}
    <table class="ident-tbl">
        <tr>
            <td class="lbl">Nama Peserta</td>
            <td class="sep">:</td>
            <td>{{ $student->name }}</td>
        </tr>
        <tr>
            <td class="lbl">NIK</td>
            <td class="sep">:</td>
            <td>{{ $student->nik ?? '-' }}</td>
        </tr>
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
            <td>Daring/<em>Online</em></td>
        </tr>
    </table>

    {{-- Tabel Nilai --}}
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
                <td>{{ $result->nilai_wawancara !== null ? number_format($result->nilai_wawancara, 2) : '-' }}</td>
                <td>{{ $result->nilai_pg !== null ? number_format($result->nilai_pg, 2) : '-' }}</td>
                <td>{{ $result->nilai_esai !== null ? number_format($result->nilai_esai, 2) : '-' }}</td>
                <td><strong>{{ $result->nilai_akhir !== null ? number_format($result->nilai_akhir, 2) : '-' }}</strong></td>
                <td class="status-cell"><strong>{{ $kompFmt }}</strong></td>
                <td>{{ $kategori }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Keterangan Kategori --}}
    <div class="kat-label">Kategori:</div>
    <table class="kat-tbl">
        <tr>
            <td class="no">1.</td>
            <td class="rng">&lt; Batas Minimal (60)</td>
            <td>: Remidial <em>(Re-Assessment)</em></td>
        </tr>
        <tr>
            <td class="no">2.</td>
            <td class="rng">&gt; 60.00 – &lt; 70.00</td>
            <td>: Cukup <em>(Average)</em></td>
        </tr>
        <tr>
            <td class="no">3.</td>
            <td class="rng">&gt; 70.01 – &lt; 80.00</td>
            <td>: Bagus <em>(Good)</em></td>
        </tr>
        <tr>
            <td class="no">4.</td>
            <td class="rng">&gt; 80.01 – &lt; 100.00</td>
            <td>: Bagus Sekali <em>(Excellence)</em></td>
        </tr>
    </table>

</div>

</body>
</html>
