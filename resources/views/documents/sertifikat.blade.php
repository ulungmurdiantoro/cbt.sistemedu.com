@php
    // Kepadatan tabel unit halaman 2 (0 = ukuran normal). DocumentGeneratorService::sertifikatBackLayout()
    // menaikkan levelnya bila tabel akan menimpa blok TTD/QR yang tercetak di background.
    $unitDensities = [
        ['th' => '12pt',   'thPad' => '5.5mm 3mm 3mm',   'thEn' => '11pt',  'td' => '4mm',   'body' => '12pt', 'lh' => 1.3,  'en' => '10pt',  'gap' => '5mm'],
        ['th' => '11pt',   'thPad' => '3.5mm 2.5mm 2mm', 'thEn' => '10pt',  'td' => '2.6mm', 'body' => '11pt', 'lh' => 1.25, 'en' => '9pt',   'gap' => '4mm'],
        ['th' => '10.5pt', 'thPad' => '2.5mm 2mm 1.5mm', 'thEn' => '9pt',   'td' => '1.8mm', 'body' => '10pt', 'lh' => 1.2,  'en' => '8.5pt', 'gap' => '3.5mm'],
        ['th' => '9.5pt',  'thPad' => '2mm 1.5mm 1.2mm', 'thEn' => '8.5pt', 'td' => '1.2mm', 'body' => '9pt',  'lh' => 1.15, 'en' => '8pt',   'gap' => '3mm'],
        ['th' => '8.5pt',  'thPad' => '1.5mm 1.2mm 1mm', 'thEn' => '8pt',   'td' => '0.8mm', 'body' => '8pt',  'lh' => 1.1,  'en' => '7.5pt', 'gap' => '2.5mm'],
    ];
    $ud = $unitDensities[min($unitDensity ?? 0, count($unitDensities) - 1)];
@endphp
<style>
body {
    font-family: 'Radley', serif;
    color: #000;
}
.center {
    text-align: center;
}
.cert-page {
    position: relative;
    padding-top: 38mm;
    margin-left: 31mm;
    width: 171mm;
}
.cert-title {
    font-size: 24pt;
    font-weight: normal;
    letter-spacing: 1.5pt;
}
.cert-number {
    font-weight: normal;
    letter-spacing: .3pt;
}
.cert-intro {
    font-style: italic;
}
.cert-name {
    font-weight: normal;
    line-height: 1.1;
}
.cert-label {
    font-weight: normal;
}
.cert-scheme-abbrev {
    font-weight: normal;
    line-height: 1.25;
}
.cert-based-label {
    font-weight: normal;
}
.cert-scheme-name {
    font-weight: normal;
    line-height: 1.25;
}
.cert-scheme-kode {
    font-weight: normal;
}
.cert-held-on {}
.cert-bottom {
    margin-top: 72mm;
    width: 160mm;
    border-collapse: collapse;
}
.cert-bottom td {
    padding: 0;
    vertical-align: top;
}
.cert-validity {
    width: 80mm;
    font-size: 10pt;
    line-height: 1.75;
    text-align: left;
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
.unit-page {
    padding-top: 39mm;
    margin-left: 29mm;
    width: 176mm;
}
.unit-title {
    font-size: 13pt;
    font-weight: normal;
    text-transform: uppercase;
    line-height: 1.2;
}
.unit-number {
    margin-top: 1mm;
    font-size: 13pt;
    font-weight: normal;
}
.unit-table {
    margin-top: {{ $ud['gap'] }};
    margin-left: 9mm;
    width: 162mm;
    border-collapse: collapse;
    color: #000;
}
.unit-table th {
    background: #1f3f7a;
    border: 1.2pt solid #1f3f7a;
    color: #fff;
    font-size: {{ $ud['th'] }};
    font-weight: normal;
    padding: {{ $ud['thPad'] }};
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
    padding: {{ $ud['td'] }};
    vertical-align: middle;
}
.unit-table .kode {
    width: 42mm;
    text-align: center;
    font-size: {{ $ud['body'] }};
}
.unit-table .judul {
    font-size: {{ $ud['body'] }};
    line-height: {{ $ud['lh'] }};
}
.unit-table .judul .en {
    display: block;
    margin-top: 1.5mm;
    font-size: {{ $ud['en'] }};
    font-style: italic;
    line-height: 1.25;
}
</style>

@php
    $renderPage = $renderPage ?? 'all';
    $schemeEn   = $classroom?->title_en ?? $classroom?->title ?? '';
    $kodeSkema  = $classroom?->kode_skema ?? '';
    $hasGelar   = !empty($classroom?->gelar);
    $ordinal    = function ($date) {
        return preg_replace('/(\d+)(st|nd|rd|th)\b/', '$1<sup>$2</sup>', e($date));
    };

    // Font sizes & spacings — dua kondisi: dengan/tanpa gelar
    if ($hasGelar) {
        $fsNumber      = '13pt'; $fsCertify = '12pt'; $fsName    = '18pt';
        $fsExam        = '11pt'; $fsGelar   = '13pt';
        $fsSchemeLabel = '11pt'; $fsScheme  = '13pt';
        $fsNoskema     = '13pt'; $fsHeld    = '11pt';
        $sp0 = '1mm';   // CERTIFICATE OF COMPETENCE → Number
        $sp1 = '9mm';   // Number → This is to certify that
        $sp2 = '11mm';  // certify → Nama
        $sp3 = '3mm';   // Gelar → The Certification Based on Scheme
        $sp4 = '3mm';   // Kode skema → Held on
        $sp5 = '5mm';   // Nama → Has followed
        $sp6 = '3mm';   // Has followed → Gelar
        $sp7 = '3mm';   // The Certification → Nama skema
        $sp8 = '2mm';   // Nama skema → Kode skema
    } else {
        $fsNumber      = '17pt'; $fsCertify = '13pt'; $fsName    = '20pt';
        $fsExam        = '13pt'; $fsGelar   = '14pt';
        $fsSchemeLabel = '15pt'; $fsScheme  = '17pt';
        $fsNoskema     = '14pt'; $fsHeld    = '14pt';
        $sp0 = '0.5mm';   // CERTIFICATE OF COMPETENCE → Number
        $sp1 = '12mm';  // Number → This is to certify that
        $sp2 = '0.75mm';   // certify → Nama
        $sp3 = '15mm';  // Nama → The Certification Based on Scheme
        $sp4 = '1mm';   // Kode skema → Held on
        $sp5 = '0mm'; $sp6 = '0mm'; // tidak dipakai
        $sp7 = '1mm';   // The Certification → Nama skema
        $sp8 = '7.5mm';   // Nama skema → Kode skema
    }
@endphp

@if($renderPage === 'front' || $renderPage === 'all')
<div class="cert-page center">
    <div class="cert-title">CERTIFICATE OF COMPETENCE</div>
    <div class="cert-number" style="margin-top:{{ $sp0 }};font-size:{{ $fsNumber }}">Number : {{ $certNumber }}</div>
    <div class="cert-intro" style="margin-top:{{ $sp1 }};font-size:{{ $fsCertify }}">This is to certify that:</div>
    <div class="cert-name" style="margin-top:{{ $sp2 }};font-size:{{ $fsName }}">{{ $student?->name }}</div>

    @if($hasGelar)
        <div class="cert-label" style="margin-top:{{ $sp5 }};font-size:{{ $fsExam }}">Has followed and successfully passed the exam of:</div>
        <div class="cert-scheme-abbrev" style="margin-top:{{ $sp6 }};font-size:{{ $fsGelar }}">{{ $classroom?->gelar }}</div>
    @endif

    <div class="cert-based-label" style="margin-top:{{ $sp3 }};font-size:{{ $fsSchemeLabel }}">The Certification Based on Scheme :</div>
    <div class="cert-scheme-name" style="margin-top:{{ $sp7 }};font-size:{{ $fsScheme }}">{{ $schemeEn }}</div>
    @if($kodeSkema)
        <div class="cert-scheme-kode" style="margin-top:{{ $sp8 }};font-size:{{ $fsNoskema }}">{{ $kodeSkema }}</div>
    @endif

    <div class="cert-held-on" style="margin-top:{{ $sp4 }};font-size:{{ $fsHeld }}">Held on {!! $ordinal($heldOn) !!}</div>

    <table class="cert-bottom">
        <tr>
            <td class="cert-validity">
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
            </td>
        </tr>
    </table>

</div>
@endif

@if($renderPage === 'all')
<pagebreak />
@endif

@if($renderPage === 'back' || $renderPage === 'all')
<div class="unit-page center">
    <div class="unit-title">{{ strtoupper($schemeEn) }}</div>
    <div class="unit-number">Number : {{ $certNumber }}</div>

    @if($competencyUnits->isNotEmpty())
        <table class="unit-table">
            <thead>
                <tr>
                    <th style="width:42mm;">
                        KODE UNIT<br>
                        <span style="font-size:{{ $ud['thEn'] }};font-style:italic;font-weight:normal;">UNIT CODES</span>
                    </th>
                    <th>
                        JUDUL UNIT KOMPETENSI<br>
                        <span style="font-size:{{ $ud['thEn'] }};font-style:italic;font-weight:normal;">UNITS OF COMPETENCY TITLES</span>
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
    @endif
</div>
@endif
