<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Certificate - {{ $certNumber }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: "Times New Roman", Times, serif;
    color: #000;
}

@page {
    size: A4 portrait;
    margin: 0;
}

.page {
    position: relative;
    width: 210mm;
    height: 297mm;
    overflow: hidden;
}
.page-break {
    page-break-after: always;
}
.bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 210mm;
    height: 297mm;
}
.overlay {
    position: absolute;
    z-index: 2;
}
.center {
    text-align: center;
}

.cert-body {
    left: 31mm;
    top: 59mm;
    width: 171mm;
}
.cert-number {
    font-size: 14pt;
    font-weight: bold;
    letter-spacing: .3pt;
}
.cert-intro {
    margin-top: 7mm;
    font-size: 14pt;
    font-style: italic;
}
.cert-name {
    margin-top: 8mm;
    font-size: 21pt;
    font-weight: bold;
    line-height: 1.1;
}
.cert-label {
    margin-top: 8mm;
    font-size: 13pt;
}
.cert-scheme-abbrev {
    margin-top: 5mm;
    font-size: 14.5pt;
    font-weight: bold;
    line-height: 1.25;
}
.cert-based-label {
    margin-top: 19mm;
    font-size: 15pt;
    font-weight: bold;
}
.cert-scheme-name {
    margin-top: 5mm;
    font-size: 15pt;
    font-weight: bold;
    line-height: 1.25;
}
.cert-batch {
    margin-top: 20mm;
    font-size: 15pt;
    font-weight: bold;
    letter-spacing: .6pt;
}
.cert-held-on {
    margin-top: 4mm;
    font-size: 12.5pt;
}
.cert-validity {
    left: 32mm;
    top: 214mm;
    width: 76mm;
    font-size: 12.5pt;
    line-height: 1.75;
}
.cert-validity table {
    border-collapse: collapse;
}
.cert-validity td {
    padding: 0;
    vertical-align: top;
}
.cert-validity .label {
    width: 32mm;
}
.cert-validity .colon {
    width: 5mm;
    text-align: center;
}
.signature-qr {
    left: 121mm;
    top: 219mm;
    width: 30mm;
    height: 30mm;
}

.unit-head {
    left: 29mm;
    top: 39mm;
    width: 176mm;
}
.unit-title {
    font-size: 16.5pt;
    font-weight: bold;
    text-transform: uppercase;
    line-height: 1.2;
}
.unit-number {
    margin-top: 1mm;
    font-size: 13.5pt;
    font-weight: bold;
}
.unit-table-wrap {
    left: 38mm;
    top: 52mm;
    width: 162mm;
}
.unit-table {
    width: 100%;
    border-collapse: collapse;
    color: #000;
}
.unit-table th {
    background: #1f3f7a;
    border: 1.2pt solid #1f3f7a;
    color: #fff;
    font-size: 12pt;
    font-weight: bold;
    padding: 5.5mm 3mm 3mm;
    text-align: center;
    line-height: 1.1;
}
.unit-table th .en {
    display: block;
    margin-top: 3mm;
    font-size: 9pt;
    font-style: italic;
    font-weight: normal;
}
.unit-table td {
    border: 1.2pt solid #1f3f7a;
    padding: 4mm 4mm;
    vertical-align: middle;
}
.unit-table .kode {
    width: 42mm;
    text-align: center;
    font-size: 12pt;
}
.unit-table .judul {
    font-size: 12pt;
    line-height: 1.3;
}
.unit-table .judul .en {
    display: block;
    margin-top: 1.5mm;
    font-size: 10pt;
    font-style: italic;
    line-height: 1.25;
}
</style>
</head>
<body>

@php
    $bgDepan = $bgSertifikatDepanPath ?? base_path('resources/lsp-assets/bg-sertifikat-depan-kan.png');
    $bgBelakang = $bgSertifikatKanPath ?? base_path('resources/lsp-assets/bg-sertifikat-kan.png');
    $schemeEn = $classroom?->title_en ?? $classroom?->title ?? '';
    $schemeName = $classroom?->title ?? '';
    $schemeAbbrev = $classroom?->gelar
        ? $classroom->gelar . ' (' . $schemeEn . ')'
        : $schemeEn;
    $ordinal = function ($date) {
        return preg_replace('/(\d+)(st|nd|rd|th)\b/', '$1<sup>$2</sup>', e($date));
    };
@endphp

<div class="page page-break">
    @if(file_exists($bgDepan))
        <img class="bg" src="{{ $bgDepan }}">
    @endif

    <div class="overlay cert-body center">
        <div class="cert-number">Number : {{ $certNumber }}</div>
        <div class="cert-intro">This is to certify that:</div>
        <div class="cert-name">{{ $student?->name }}</div>
        <div class="cert-label">Has followed and successfully passed the exam of:</div>
        <div class="cert-scheme-abbrev">{{ $schemeAbbrev }}</div>
        <div class="cert-based-label">The Certification Based on Scheme :</div>
        <div class="cert-scheme-name">{{ $schemeName }}</div>

        @if($session?->kode_batch)
            <div class="cert-batch">{{ $session->kode_batch }}</div>
        @endif

        <div class="cert-held-on">Held on {!! $ordinal($heldOn) !!}</div>
    </div>

    <div class="overlay cert-validity">
        <table>
            <tr>
                <td class="label">Certification date</td>
                <td class="colon">:</td>
                <td>{!! $ordinal($certDate) !!}</td>
            </tr>
            <tr>
                <td class="label">Valid until</td>
                <td class="colon">:</td>
                <td>{!! $ordinal($validUntil) !!}</td>
            </tr>
        </table>
    </div>

    @if($qrSertifPath ?? null)
        <img class="overlay signature-qr" src="{{ $qrSertifPath }}">
    @endif
</div>

<div class="page">
    @if(file_exists($bgBelakang))
        <img class="bg" src="{{ $bgBelakang }}">
    @endif

    <div class="overlay unit-head center">
        <div class="unit-title">{{ strtoupper($schemeEn) }}</div>
        <div class="unit-number">Number : {{ $certNumber }}</div>
    </div>

    @if($competencyUnits->isNotEmpty())
        <div class="overlay unit-table-wrap">
            <table class="unit-table">
                <thead>
                    <tr>
                        <th style="width:42mm;">
                            KODE UNIT
                            <span class="en">UNIT CODES</span>
                        </th>
                        <th>
                            JUDUL UNIT KOMPETENSI
                            <span class="en">UNITS OF COMPETENCY TITLES</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($competencyUnits as $unit)
                        <tr>
                            <td class="kode">{{ $unit->kode_unit }}</td>
                            <td class="judul">
                                {{ $unit->judul_unit }}
                                @if($unit->judul_unit_en)
                                    <span class="en">{{ $unit->judul_unit_en }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($qrSertifPath ?? null)
        <img class="overlay signature-qr" src="{{ $qrSertifPath }}">
    @endif
</div>

</body>
</html>
