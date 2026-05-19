<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate - {{ $result->sertifikat_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Times New Roman', serif; color: #000; }
        @page { margin: 0; size: A4 portrait; }

        /* ────── FULL-PAGE BACKGROUND ────── */
        .cert-page {
            position: relative;
            width: 210mm;
            height: 297mm;
            overflow: hidden;
        }
        .cert-bg {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 0;
        }
        .cert-bg img { width: 100%; height: 100%; }

        /* ────── CONTENT OVERLAY ────── */
        /* TEXT_SHIFT_X_IN = 0.6 inch = ~15.24mm → shift all text right by 15mm */
        .cert-content {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 1;
            /* offset matches TEXT_SHIFT_X_IN = 0.6 inch */
        }

        /* Coordinates converted from Python inch-based to mm (1in = 25.4mm) */
        /* Python uses top-to-bottom canvas coords; we use top/left with position:absolute */

        /* TEXT_SHIFT_X_IN = 0.6in = 15.24mm.
           X_CENTER_IN = 8.27/2 = 4.135in
           shifted center = 4.135 + 0.6 = 4.735in = 120.3mm */
        .shifted-cx { /* helper: text-align:center with left offset */
            position: absolute;
            text-align: center;
            left: 15.24mm;
            width: calc(100% - 15.24mm);
        }

        /* PAGE 1 text positions (from Python, y is canva top-to-bottom) */
        /* Number: y=9.2in from BOTTOM → canva_top = PAGE_H - y = 11.69-9.2=2.49in = 63.2mm from TOP */
        .p1-number   { top: 63.2mm;  font-size: 17pt; }
        /* "This is to certify that:" y = 9.2-0.74 = 8.46in → top = 11.69-8.46 = 3.23in = 82.0mm */
        .p1-certify  { top: 82.0mm;  font-size: 13pt; font-style: italic; }
        /* Nama y = 8.46-0.35 = 8.11in → top = 11.69-8.11 = 3.58in = 90.9mm */
        .p1-nama     { top: 90.9mm;  font-size: 20pt; }
        /* "The Certification Based on Scheme :" y ≈ 8.11-0.8 = 7.31in → top = 11.69-7.31 = 4.38in = 111.3mm */
        .p1-schemehdr { top: 111.3mm; font-size: 14pt; }
        /* skema_eng y = 7.31-0.3 = 7.01in → top = 11.69-7.01 = 4.68in = 118.9mm */
        .p1-scheme   { top: 118.9mm; font-size: 17pt; }
        /* no_skema y = 7.01-0.7 = 6.31in → top = 11.69-6.31 = 5.38in = 136.7mm */
        .p1-noskema  { top: 136.7mm; font-size: 14pt; }
        /* held_on y = 6.31-0.3 = 6.01in → top = 11.69-6.01 = 5.68in = 144.3mm */
        .p1-heldon   { top: 144.3mm; font-size: 14pt; }
        /* cert date y_left=3.17in → top = 11.69-3.17 = 8.52in = 216.4mm */
        .p1-dates    { top: 216.4mm; position: absolute; left: 15.24mm+21.59mm; font-size: 11pt; line-height: 17pt; }
        /* QR: x=4.74in, y canva=8.55in → top = 11.69-8.55-1.24 = 1.90in = 48.3mm */
        .p1-qr       { top: 48.3mm; left: calc(4.74in + 15.24mm); width: 1.24in; height: 1.24in; }

        /* ────── PAGE BREAK ────── */
        .page-break { page-break-after: always; }

        /* ────── PAGE 2 ────── */
        /* Number on page 2: y_canva=1.92in → top = 11.69-1.92 = 9.77in = 248.2mm */
        .p2-number   { top: 248.2mm; font-size: 13pt; }
        /* skema_eng: y_canva=1.72in → top = 11.69-1.72 = 9.97in = 253.3mm
           wait, that'd be below p2-number which is at 248.2mm...
           From Python: NUMBER2_Y_CANVA_IN=1.92, SKEMA_ENG2_Y=1.72
           Canva coords are from TOP. 1.72in from top = 43.7mm from top. That's near the top.
           So top: 43.7mm for skema_eng. */
        .p2-number-pos  { top: 48.8mm; } /* 1.92in from top */
        .p2-skema-pos   { top: 43.7mm; } /* 1.72in from top */

        /* Unit table: TABLE_Y_TOP_IN=2.05in → 52.1mm from top */
        .p2-table-area {
            position: absolute;
            top: 52.1mm;
            /* TABLE_LEFT_X_IN=1.51in shifted by TEXT_SHIFT_X_IN: total=2.11in=53.6mm */
            left: 53.6mm;
            /* TABLE_W_IN = 16.21/2.54 = 6.381in = 162mm */
            width: 162mm;
        }
        /* QR2: x=4.74in, y_canva=8.55in → top = 8.55in = 217.2mm from top */
        .p2-qr { top: 217.2mm; left: calc(4.74in + 15.24mm); width: 1.24in; height: 1.24in; }

        /* Unit kompetensi table styling */
        .unit-table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        .unit-table thead th {
            background-color: #243e7b;
            color: #fff;
            border: 1.2pt solid #243e7b;
            padding: 6pt 4pt;
            text-align: center;
            font-size: 10.5pt;
        }
        .unit-table thead .sub-hdr {
            font-size: 8.5pt;
            font-style: italic;
        }
        .unit-table tbody td {
            border: 1.2pt solid #243e7b;
            padding: 8pt 9pt;
            vertical-align: middle;
            color: #0f0f0f;
        }
        .unit-table tbody td.kode {
            text-align: center;
            width: 42mm;
            font-size: 10pt;
        }
        .unit-table tbody td.judul {
            font-size: 10pt;
            line-height: 13pt;
        }
        .unit-table tbody td.judul .en {
            font-size: 9.5pt;
            font-style: italic;
        }
    </style>
</head>
<body>

{{-- ═══════════════════════════════════════════
     PAGE 1 — Full-page background + text overlay
═══════════════════════════════════════════ --}}
<div class="cert-page">
    {{-- Background --}}
    @if($template?->bg_sertifikat_path)
    <div class="cert-bg">
        <img src="{{ storage_path('app/public/' . $template->bg_sertifikat_path) }}">
    </div>
    @endif

    <div class="cert-content">

        {{-- QR Code (top right area, above Number) --}}
        @if($qrSertifPath)
        <img style="position:absolute; top:48.3mm; left:{{ 4.74 * 25.4 + 15.24 }}mm; width:31.5mm; height:31.5mm;"
             src="{{ $qrSertifPath }}">
        @endif

        {{-- Number --}}
        <div class="shifted-cx" style="top:63.2mm; position:absolute;">
            <span style="font-size:17pt; font-family:'Times New Roman',serif;">Number : {{ $result->sertifikat_number }}</span>
        </div>

        {{-- This is to certify that: --}}
        <div class="shifted-cx" style="top:82.0mm; position:absolute;">
            <span style="font-size:13pt; font-style:italic; font-family:'Times New Roman',serif;">This is to certify that:</span>
        </div>

        {{-- Nama Lengkap --}}
        <div class="shifted-cx" style="top:90.9mm; position:absolute;">
            <span style="font-size:20pt; font-family:'Times New Roman',serif;">{{ $student->name }}</span>
        </div>

        {{-- "The Certification Based on Scheme :" --}}
        <div class="shifted-cx" style="top:111.3mm; position:absolute;">
            <span style="font-size:14pt; font-family:'Times New Roman',serif;">The Certification Based on Scheme :</span>
        </div>

        {{-- Skema (English name) --}}
        <div class="shifted-cx" style="top:118.9mm; position:absolute;">
            <span style="font-size:17pt; font-family:'Times New Roman',serif;">{{ $classroom?->title_en ?? $classroom?->title ?? '' }}</span>
        </div>

        {{-- No Skema --}}
        <div class="shifted-cx" style="top:136.7mm; position:absolute;">
            <span style="font-size:14pt; font-family:'Times New Roman',serif;">{{ $classroom?->kode_skema ?? '' }}</span>
        </div>

        {{-- Held on --}}
        <div class="shifted-cx" style="top:144.3mm; position:absolute;">
            <span style="font-size:14pt; font-family:'Times New Roman',serif;">
                Held on {{ $heldOn }}
            </span>
        </div>

        {{-- Certification date + Valid until (bottom left) --}}
        {{-- 0.85+0.6 = 1.45in = 36.8mm from left --}}
        <div style="position:absolute; top:216.4mm; left:36.8mm; font-size:11pt; font-family:'Times New Roman',serif; line-height:17pt;">
            Certification date &nbsp;&nbsp;: {{ $certDate }}<br>
            Valid until &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $validUntil }}
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════
     PAGE 2 — Background + Unit Kompetensi table + QR
═══════════════════════════════════════════ --}}
<div class="page-break"></div>

<div class="cert-page">
    {{-- Background page 2 --}}
    @if($bgPage2Path)
    <div class="cert-bg">
        <img src="{{ $bgPage2Path }}">
    </div>
    @elseif($template?->bg_sertifikat_path)
    <div class="cert-bg">
        <img src="{{ storage_path('app/public/' . $template->bg_sertifikat_path) }}">
    </div>
    @endif

    <div class="cert-content">

        {{-- Number (page 2) --}}
        <div class="shifted-cx" style="top:48.8mm; position:absolute;">
            <span style="font-size:13pt; font-family:'Times New Roman',serif;">Number : {{ $result->sertifikat_number }}</span>
        </div>

        {{-- Skema English name (LibreBaskerville style → bold serif) --}}
        @if($hasUnitKomp)
        <div class="shifted-cx" style="top:43.7mm; position:absolute;">
            <span style="font-size:13pt; font-weight:bold; font-family:Georgia,serif; text-transform:uppercase;">
                {{ $classroom?->title_en ?? $classroom?->title ?? '' }}
            </span>
        </div>
        @endif

        {{-- Unit Kompetensi table --}}
        @if($hasUnitKomp && count($competencyUnits))
        <div class="p2-table-area">
            <table class="unit-table">
                <thead>
                    <tr>
                        <th style="width:42mm;">
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
                            {{ $unit->judul_unit }}<br>
                            <span class="en">{{ $unit->judul_unit_en }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- QR Code (page 2) --}}
        @if($qrSertifPath)
        <img style="position:absolute; top:217.2mm; left:{{ 4.74 * 25.4 + 15.24 }}mm; width:31.5mm; height:31.5mm;"
             src="{{ $qrSertifPath }}">
        @endif

    </div>
</div>

</body>
</html>
