<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate - {{ $result->sertifikat_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Times New Roman', serif; color: #000; }

        /* ── @page named pages for per-page backgrounds (mPDF 8.x) ── */
        @page page1 {
            margin: 0;
            size: 210mm 297mm;
            @if($bgPage1Path)
            background-image: url("{{ $bgPage1Path }}");
            background-size: 210mm 297mm;
            background-repeat: no-repeat;
            background-position: 0 0;
            @endif
        }
        @page page2 {
            margin: 0;
            size: 210mm 297mm;
            @if($bgPage2Path)
            background-image: url("{{ $bgPage2Path }}");
            background-size: 210mm 297mm;
            background-repeat: no-repeat;
            background-position: 0 0;
            @endif
        }

        /* ── Halaman wrapper ── */
        .cert-page {
            position: relative;
            width: 210mm;
            height: 297mm;
            overflow: hidden;
        }

        /* ── Page break ── */
        .page-break { page-break-after: always; }

        /* ── Text-shift helper (TEXT_SHIFT_X_IN = 0.6in = 15.24mm) ──
           Centered text with left-margin shift.
           width = 210mm - 15.24mm = 194.76mm, left = 15.24mm           */
        .shifted-cx {
            position: absolute;
            text-align: center;
            left: 15.24mm;
            width: 194.76mm;
        }

        /* ── Unit kompetensi table (page 2) ── */
        .unit-table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        .unit-table thead th {
            background-color: #243e7b;
            color: #fff;
            border: 1pt solid #243e7b;
            padding: 6pt 4pt;
            text-align: center;
            font-size: 10.5pt;
        }
        .unit-table thead .sub-hdr { font-size: 8.5pt; font-style: italic; }
        .unit-table tbody td {
            border: 1pt solid #243e7b;
            padding: 8pt 9pt;
            vertical-align: middle;
            color: #0f0f0f;
        }
        .unit-table tbody td.kode { text-align: center; width: 42mm; font-size: 10pt; }
        .unit-table tbody td.judul { font-size: 10pt; line-height: 13pt; }
        .unit-table tbody td.judul .en { font-size: 9.5pt; font-style: italic; }
    </style>
</head>
<body>

{{-- ═══════════════════════════════════
     PAGE 1 — text overlay (background via @page page1)
═══════════════════════════════════ --}}
<div class="cert-page" style="page: page1;">

    {{-- QR Code (top-right area) — x=4.74in+shift=5.34in=135.6mm, y=1.90in=48.3mm --}}
    @if($qrSertifPath)
    <img style="position:absolute; top:48.3mm; left:135.6mm; width:31.5mm; height:31.5mm;"
         src="{{ $qrSertifPath }}">
    @endif

    {{-- Number — y=2.49in=63.2mm --}}
    <div class="shifted-cx" style="top:63.2mm;">
        <span style="font-size:17pt;">Number : {{ $result->sertifikat_number }}</span>
    </div>

    {{-- "This is to certify that:" — y=3.23in=82.0mm --}}
    <div class="shifted-cx" style="top:82.0mm;">
        <span style="font-size:13pt; font-style:italic;">This is to certify that:</span>
    </div>

    {{-- Nama Lengkap — y=3.58in=90.9mm --}}
    <div class="shifted-cx" style="top:90.9mm;">
        <span style="font-size:20pt;">{{ $student->name }}</span>
    </div>

    {{-- "The Certification Based on Scheme :" — y=4.38in=111.3mm --}}
    <div class="shifted-cx" style="top:111.3mm;">
        <span style="font-size:14pt;">The Certification Based on Scheme :</span>
    </div>

    {{-- Skema (English name) — y=4.68in=118.9mm --}}
    <div class="shifted-cx" style="top:118.9mm;">
        <span style="font-size:17pt;">{{ $classroom?->title_en ?? $classroom?->title ?? '' }}</span>
    </div>

    {{-- No Skema — y=5.38in=136.7mm --}}
    <div class="shifted-cx" style="top:136.7mm;">
        <span style="font-size:14pt;">{{ $classroom?->kode_skema ?? '' }}</span>
    </div>

    {{-- Held on — y=5.68in=144.3mm --}}
    <div class="shifted-cx" style="top:144.3mm;">
        <span style="font-size:14pt;">Held on {{ $heldOn }}</span>
    </div>

    {{-- Certification date + Valid until (bottom-left)
         x = (0.85+0.6)in = 1.45in = 36.8mm, y = 8.52in = 216.4mm --}}
    <div style="position:absolute; top:216.4mm; left:36.8mm; font-size:11pt; line-height:17pt;">
        Certification date &nbsp;&nbsp;: {{ $certDate }}<br>
        Valid until &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $validUntil }}
    </div>

</div>

{{-- ═══════════════════════════════════
     PAGE 2 — unit kompetensi + QR (background via @page page2)
═══════════════════════════════════ --}}
<div class="page-break"></div>

<div class="cert-page" style="page: page2;">

    {{-- Skema name (bold, top of page) — y=1.72in=43.7mm --}}
    @if($hasUnitKomp)
    <div class="shifted-cx" style="top:43.7mm;">
        <span style="font-size:13pt; font-weight:bold; text-transform:uppercase;">
            {{ $classroom?->title_en ?? $classroom?->title ?? '' }}
        </span>
    </div>
    @endif

    {{-- Number — y=1.92in=48.8mm --}}
    <div class="shifted-cx" style="top:48.8mm;">
        <span style="font-size:13pt;">Number : {{ $result->sertifikat_number }}</span>
    </div>

    {{-- Unit Kompetensi table
         left = TABLE_LEFT_X_IN(1.51)+shift(0.6) = 2.11in = 53.6mm
         y    = TABLE_Y_TOP_IN = 2.05in = 52.1mm
         width = 16.21cm = 162.1mm                                  --}}
    @if($hasUnitKomp && count($competencyUnits))
    <div style="position:absolute; top:52.1mm; left:53.6mm; width:162.1mm;">
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

    {{-- QR Code (page 2) — x=135.6mm, y=8.55in=217.2mm --}}
    @if($qrSertifPath)
    <img style="position:absolute; top:217.2mm; left:135.6mm; width:31.5mm; height:31.5mm;"
         src="{{ $qrSertifPath }}">
    @endif

</div>

</body>
</html>
