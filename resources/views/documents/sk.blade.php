<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SK - {{ $certNumber }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; }

@page {
    size: A4 portrait;
    margin-top: 0;
    margin-bottom: 0;
    margin-left: 0;
    margin-right: 0;
}

/* ──────────────────────────────────────────
   SIDEBAR — fixed posisi, muncul di tiap halaman
   ────────────────────────────────────────── */
#sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 20mm;
    height: 297mm;
    background-color: #1a3157;
    overflow: hidden;
}
#sidebar-text {
    position: absolute;
    top: 0;
    left: 0;
    width: 297mm;
    height: 20mm;
    writing-mode: vertical-rl;
    color: #ffffff;
    font-size: 7pt;
    letter-spacing: 4pt;
    font-family: Arial, Helvetica, sans-serif;
    font-weight: normal;
    padding-top: 6mm;
    white-space: nowrap;
    overflow: hidden;
}

/* ──────────────────────────────────────────
   MAIN AREA (kanan sidebar)
   ────────────────────────────────────────── */
.main-area {
    margin-left: 20mm;
    padding: 0;
}

/* ──────────────────────────────────────────
   HEADER (kop)
   ────────────────────────────────────────── */
.kop-table {
    width: 100%;
    border-collapse: collapse;
    padding: 5mm 10mm 3mm 8mm;
}
.kop-table td {
    vertical-align: middle;
    padding: 0;
}
.kop-logo-left {
    width: 42mm;
    text-align: left;
}
.kop-logo-left img {
    max-width: 38mm;
    max-height: 18mm;
}
.kop-spacer {
    /* flexible middle space */
}
.kop-right-block {
    width: 52mm;
    text-align: right;
    vertical-align: top;
}
.kop-logo-right img {
    max-width: 48mm;
    max-height: 20mm;
}
.kop-right-label {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 7pt;
    color: #000;
    text-align: right;
    margin-top: 2pt;
    line-height: 1.3;
}
.kop-divider {
    border: none;
    border-top: 2pt solid #000;
    margin: 0 10mm 0 8mm;
}

/* ──────────────────────────────────────────
   FOOTER — fixed posisi, tiap halaman
   ────────────────────────────────────────── */
#footer {
    position: fixed;
    bottom: 0;
    left: 20mm;
    right: 0;
    padding: 0 10mm 3mm 8mm;
}
#footer-divider {
    border: none;
    border-top: 1pt solid #000;
    margin-bottom: 3pt;
}
.footer-table {
    width: 100%;
    border-collapse: collapse;
}
.footer-table td {
    vertical-align: middle;
    padding: 0;
}
.footer-logo img {
    max-height: 12mm;
    max-width: 24mm;
}
.footer-text {
    padding-left: 6pt;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 7.5pt;
    line-height: 1.35;
}
.footer-text .lsp-name {
    font-weight: bold;
    font-size: 8pt;
}
.footer-web {
    text-align: center;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 7.5pt;
    margin-top: 2pt;
}

/* ──────────────────────────────────────────
   CONTENT AREA
   ────────────────────────────────────────── */
.content-area {
    margin-left: 20mm;
    padding: 2mm 10mm 40mm 8mm;
}

/* ──────────────────────────────────────────
   PAGE BREAK
   ────────────────────────────────────────── */
.page-break {
    page-break-after: always;
}

/* ──────────────────────────────────────────
   PAGE 1 — Certificate of Competence
   ────────────────────────────────────────── */
.cert-title {
    text-align: center;
    font-size: 26pt;
    font-weight: bold;
    font-family: 'Times New Roman', Times, serif;
    letter-spacing: 1pt;
    margin: 8mm 0 4mm;
    text-transform: uppercase;
}
.cert-number {
    text-align: center;
    font-size: 13pt;
    font-weight: bold;
    margin-bottom: 8mm;
}
.cert-intro {
    text-align: center;
    font-size: 12pt;
    font-style: italic;
    margin-bottom: 5mm;
}
.cert-name {
    text-align: center;
    font-size: 20pt;
    font-weight: bold;
    margin-bottom: 8mm;
    text-transform: uppercase;
}
.cert-label {
    text-align: center;
    font-size: 12pt;
    margin-bottom: 3mm;
}
.cert-scheme-abbrev {
    text-align: center;
    font-size: 14pt;
    font-weight: bold;
    margin-bottom: 2mm;
}
.cert-based-label {
    text-align: center;
    font-size: 12pt;
    margin-bottom: 3mm;
}
.cert-scheme-name {
    text-align: center;
    font-size: 12pt;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 3mm;
}
.cert-batch {
    text-align: center;
    font-size: 12pt;
    font-weight: bold;
    margin-bottom: 3mm;
}
.cert-held-on {
    text-align: center;
    font-size: 12pt;
    margin-bottom: 8mm;
}

/* Validity + TTD row */
.bottom-row {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4mm;
}
.bottom-row td {
    vertical-align: bottom;
    padding: 0;
}
.validity-block {
    font-size: 11pt;
    line-height: 1.6;
}
.ttd-block {
    text-align: center;
    width: 75mm;
}
.ttd-lsp-name {
    font-weight: bold;
    font-size: 10pt;
    margin-bottom: 4pt;
}
.ttd-qr-row {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 4pt;
}
.ttd-qr-row td {
    vertical-align: middle;
    padding: 0;
}
.ttd-qr-img img {
    width: 22mm;
    height: 22mm;
}
.ttd-qr-note {
    padding-left: 6pt;
    font-size: 8pt;
    font-style: italic;
    line-height: 1.35;
    text-align: left;
}
.ttd-underline {
    border-bottom: 1pt solid #000;
    margin-bottom: 4pt;
}
.ttd-signer-name {
    font-weight: bold;
    font-size: 11pt;
}
.ttd-signer-title {
    font-size: 10pt;
}

/* ──────────────────────────────────────────
   PAGE 2 — Unit Kompetensi
   ────────────────────────────────────────── */
.unit-page-title {
    text-align: center;
    font-size: 13pt;
    font-weight: bold;
    text-transform: uppercase;
    margin: 6mm 0 2mm;
    line-height: 1.4;
}
.unit-page-number {
    text-align: center;
    font-size: 12pt;
    margin-bottom: 5mm;
}
.unit-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10pt;
}
.unit-table thead th {
    background-color: #1a3157;
    color: #ffffff;
    border: 1pt solid #1a3157;
    padding: 6pt 5pt;
    text-align: center;
    font-size: 10.5pt;
    font-weight: bold;
}
.unit-table thead .sub-hdr {
    font-size: 8.5pt;
    font-style: italic;
    font-weight: normal;
}
.unit-table tbody td {
    border: 1pt solid #1a3157;
    padding: 7pt 8pt;
    vertical-align: middle;
}
.unit-table tbody td.kode {
    text-align: center;
    width: 44mm;
    font-size: 10pt;
}
.unit-table tbody td.judul {
    font-size: 10pt;
    line-height: 13pt;
}
.unit-table tbody td.judul .en {
    font-size: 9pt;
    font-style: italic;
}
</style>
</head>
<body>

@php
    $kodeAkreditasi = config('lsp_documents.lsp.kode_akreditasi', 'LSP-033-IDN');
    $sidebarRepeat  = str_repeat($kodeAkreditasi . '   ', 30);
@endphp

{{-- ═══════ SIDEBAR (fixed — tiap halaman) ═══════ --}}
<div id="sidebar">
    <div id="sidebar-text">{{ $sidebarRepeat }}</div>
</div>

{{-- ═══════ FOOTER (fixed — tiap halaman) ═══════ --}}
<div id="footer">
    <div id="footer-divider"></div>
    <table class="footer-table">
        <tr>
            <td class="footer-logo" style="width:26mm;">
                @if(file_exists($logoEdukiaPath))
                    <img src="{{ $logoEdukiaPath }}">
                @endif
            </td>
            <td class="footer-text">
                <div class="lsp-name">{{ $lsp['nama'] }}</div>
                <div>{{ $lsp['alamat'] }}</div>
            </td>
        </tr>
    </table>
    <div class="footer-web">{{ $lsp['web'] }}</div>
</div>

{{-- ══════════════════════════════════════════════════════
     HAL 1 — Certificate of Competence
     ══════════════════════════════════════════════════════ --}}
<div class="main-area">

    {{-- KOP --}}
    <table class="kop-table">
        <tr>
            <td class="kop-logo-left">
                @if(file_exists($logoEdukiaPath))
                    <img src="{{ $logoEdukiaPath }}">
                @endif
            </td>
            <td class="kop-spacer"></td>
            <td class="kop-right-block">
                @if(file_exists($logoKanPath))
                    <div class="kop-logo-right"><img src="{{ $logoKanPath }}"></div>
                @endif
                <div class="kop-right-label">
                    <em>Lembaga Sertifikasi Person</em><br>
                    <strong>{{ $kodeAkreditasi }}</strong>
                </div>
            </td>
        </tr>
    </table>
    <hr class="kop-divider">

    {{-- KONTEN HAL 1 --}}
    <div class="content-area">

        <div class="cert-title">Certificate of Competence</div>

        <div class="cert-number">Number : {{ $certNumber }}</div>

        <div class="cert-intro">This is to certify that:</div>

        <div class="cert-name">{{ $student?->name }}</div>

        <div class="cert-label">Has followed and successfully passed the exam of:</div>

        <div class="cert-scheme-abbrev">
            {{ $classroom?->gelar ? $classroom->gelar . ' (' . ($classroom->title_en ?? $classroom->title) . ')' : ($classroom?->title_en ?? $classroom?->title ?? '') }}
        </div>

        <div class="cert-based-label">The Certification Based on Scheme :</div>

        <div class="cert-scheme-name">{{ $classroom?->title ?? '' }}</div>

        @if($session?->kode_batch)
        <div class="cert-batch">{{ $session->kode_batch }}</div>
        @endif

        <div class="cert-held-on">Held on {{ $heldOn }}</div>

        {{-- Validity + TTD --}}
        <table class="bottom-row">
            <tr>
                <td class="validity-block">
                    Certification date &nbsp;: {{ $certDate }}<br>
                    Valid until &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $validUntil }}
                </td>
                <td class="ttd-block">
                    <div class="ttd-lsp-name">{{ $lsp['nama'] }}</div>
                    <table class="ttd-qr-row">
                        <tr>
                            @if($qrSkPath ?? null)
                            <td class="ttd-qr-img">
                                <img src="{{ $qrSkPath }}">
                            </td>
                            @endif
                            <td class="ttd-qr-note">
                                This document has been signed electronically using an integrated digital system
                            </td>
                        </tr>
                    </table>
                    <div class="ttd-underline"></div>
                    <div class="ttd-signer-name">{{ $penandatangan['nama'] }}</div>
                    <div class="ttd-signer-title">Executive Director</div>
                </td>
            </tr>
        </table>

    </div>{{-- /content-area --}}
</div>{{-- /main-area --}}

{{-- ══════════════════════════════════════════════════════
     HAL 2 — Unit Kompetensi
     ══════════════════════════════════════════════════════ --}}
<div class="page-break"></div>
<div class="main-area">

    {{-- KOP --}}
    <table class="kop-table">
        <tr>
            <td class="kop-logo-left">
                @if(file_exists($logoEdukiaPath))
                    <img src="{{ $logoEdukiaPath }}">
                @endif
            </td>
            <td class="kop-spacer"></td>
            <td class="kop-right-block">
                @if(file_exists($logoKanPath))
                    <div class="kop-logo-right"><img src="{{ $logoKanPath }}"></div>
                @endif
                <div class="kop-right-label">
                    <em>Lembaga Sertifikasi Person</em><br>
                    <strong>{{ $kodeAkreditasi }}</strong>
                </div>
            </td>
        </tr>
    </table>
    <hr class="kop-divider">

    {{-- KONTEN HAL 2 --}}
    <div class="content-area">

        <div class="unit-page-title">{{ strtoupper($classroom?->title_en ?? $classroom?->title ?? '') }}</div>

        <div class="unit-page-number">Number : {{ $certNumber }}</div>

        @if($competencyUnits->isNotEmpty())
        <table class="unit-table">
            <thead>
                <tr>
                    <th style="width:44mm;">
                        KODE UNIT<br>
                        <span class="sub-hdr">UNIT CODES</span>
                    </th>
                    <th>
                        JUDUL UNIT KOMPETENSI<br>
                        <span class="sub-hdr">UNITS OF COMPETENCY TITLES</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($competencyUnits as $unit)
                <tr>
                    <td class="kode">{{ $unit->kode_unit }}</td>
                    <td class="judul">
                        <strong>{{ $unit->judul_unit }}</strong><br>
                        <span class="en">{{ $unit->judul_unit_en }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- TTD bawah kanan --}}
        <table style="width:100%; border-collapse:collapse; margin-top:10mm;">
            <tr>
                <td></td>
                <td class="ttd-block" style="width:75mm; text-align:center; vertical-align:bottom;">
                    <div class="ttd-lsp-name">{{ $lsp['nama'] }}</div>
                    <table class="ttd-qr-row">
                        <tr>
                            @if($qrSkPath ?? null)
                            <td class="ttd-qr-img">
                                <img src="{{ $qrSkPath }}">
                            </td>
                            @endif
                            <td class="ttd-qr-note">
                                This document has been signed electronically using an integrated digital system
                            </td>
                        </tr>
                    </table>
                    <div class="ttd-underline"></div>
                    <div class="ttd-signer-name">{{ $penandatangan['nama'] }}</div>
                    <div class="ttd-signer-title">Executive Director</div>
                </td>
            </tr>
        </table>

    </div>{{-- /content-area --}}
</div>{{-- /main-area --}}

</body>
</html>
