<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    @page {
        size: A4 portrait;
        margin: 25mm 25mm 25mm 25mm;
    }
    body { font-family: Cambria, serif; font-size: 12pt; color: #000; margin: 0; }
    .page-break { page-break-after: always; }

    /* Kop */
    .kop-table { width:100%; border-collapse:collapse; }
    .kop-table td { vertical-align:middle; }
    .kop-garis { border-bottom: 1.5pt solid #000; margin-bottom: 6pt; padding-bottom: 4pt; }

    /* Konten */
    .mb-2 { margin-bottom: 6pt; }
    .mb-4 { margin-bottom: 12pt; }
    .mb-6 { margin-bottom: 18pt; }
    .text-center { text-align: center; }
    .text-right  { text-align: right; }
    .fw-bold { font-weight: bold; }
    .underline { text-decoration: underline; }

    /* Field 3 kolom */
    .field-table { border-collapse:collapse; }
    .field-table td { padding: 1pt 0; vertical-align: top; }
    .field-label { width: 90pt; }
    .field-colon { width: 8pt; }
    .field-value { }

    /* Tanda tangan */
    .ttd-block { margin-top: 12pt; }
    .ttd-img { max-height: 56pt; max-width: 197pt; }

    /* Lampiran (hal.2) */
    .section-title { font-weight:bold; text-decoration:underline; font-size:12pt; margin-bottom:6pt; }
    .tbl-fixed { width:100%; border-collapse:collapse; font-size:11pt; }
    .tbl th { border:0.5pt solid #000; padding:4pt 6pt; background-color:#BFCDE9; font-weight:bold; text-align:center; }
    .tbl td { border:0.5pt solid #000; padding:4pt 6pt; }
    .tbl .merged { text-align:center; font-weight:bold; }
</style>
</head>
<body>

{{-- ============================= HAL 1 ============================= --}}
<div>

    {{-- KOP --}}
    <div class="kop-garis">
        @include('documents.partials._kop-header', [
            'logoEdukiaPath' => $logoEdukiaPath,
            'logoKanPath'    => null,
            'lsp'            => $lsp,
        ])
    </div>

    {{-- Judul --}}
    <div class="text-center fw-bold underline mb-4" style="margin-top:10pt;">
        SURAT PEMBERITAHUAN
    </div>

    {{-- Nomor / Lampiran / Perihal --}}
    <table class="field-table mb-4">
        <tr>
            <td class="field-label">Nomor</td>
            <td class="field-colon">:</td>
            <td class="field-value">{{ $noSp }}</td>
        </tr>
        <tr>
            <td class="field-label">Lampiran</td>
            <td class="field-colon">:</td>
            <td class="field-value">1 Halaman</td>
        </tr>
        <tr>
            <td class="field-label">Perihal</td>
            <td class="field-colon">:</td>
            <td class="field-value">{{ $sp['perihal'] }}</td>
        </tr>
    </table>

    {{-- Kepada --}}
    <div class="fw-bold mb-2">Kepada Yth:</div>
    <div class="mb-2">
        {{ $student->name }}<br>
        Di Tempat
    </div>

    {{-- Pembuka --}}
    <div class="mb-4" style="text-align:justify; text-indent:28pt;">
        {!! str_replace(['{skema}','{held_on}'], [e($skema), e($heldOn)], $sp['pembuka']) !!}
    </div>

    {{-- Field Nama / No Reg / Skema --}}
    <table class="field-table mb-2">
        <tr>
            <td class="field-label">Nama</td>
            <td class="field-colon">:</td>
            <td class="field-value fw-bold">{{ $student->name }}</td>
        </tr>
        <tr>
            <td class="field-label">No Registrasi</td>
            <td class="field-colon">:</td>
            <td class="field-value fw-bold">{{ $student->no_participant }}</td>
        </tr>
        <tr>
            <td class="field-label">Skema</td>
            <td class="field-colon">:</td>
            <td class="field-value fw-bold">{{ $skema }}</td>
        </tr>
    </table>

    {{-- Tabel Nilai --}}
    @include('documents.partials._tabel-nilai', [
        'nilaiWawancara' => $result->nilai_wawancara !== null ? number_format($result->nilai_wawancara,2) : '-',
        'nilaiPg'        => $result->nilai_pg        !== null ? number_format($result->nilai_pg,2)        : '-',
        'nilaiEsai'      => $result->nilai_esai      !== null ? number_format($result->nilai_esai,2)      : '-',
        'nilaiAkhir'     => $result->nilai_akhir     !== null ? number_format($result->nilai_akhir,2)     : '-',
        'status'         => $statusKompeten,
    ])

    {{-- Penutup --}}
    <div class="mb-6 mt-4" style="text-align:justify; text-indent:28pt;">
        {{ $sp['penutup'] }}
    </div>

    {{-- Blok TTD --}}
    <table style="width:100%;">
        <tr>
            <td style="width:60%;"></td>
            <td style="width:40%;">
                <div>{{ $lsp['kota'] }}, {{ $tglSurat }}</div>
                <div>Mengetahui</div>
                <div style="margin:10pt 0;">
                    @if(file_exists($ttdPath))
                        <img src="{{ $ttdPath }}" class="ttd-img">
                    @else
                        <div style="height:56pt;"></div>
                    @endif
                </div>
                <div class="fw-bold">{{ $penandatangan['nama'] }}</div>
                <div>{{ $penandatangan['jabatan'] }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- ============================= HAL 2 (LAMPIRAN) ============================= --}}
<div class="page-break"></div>
<div>

    {{-- KOP --}}
    <div class="kop-garis">
        @include('documents.partials._kop-header', [
            'logoEdukiaPath' => $logoEdukiaPath,
            'logoKanPath'    => null,
            'lsp'            => $lsp,
        ])
    </div>

    <div class="text-center fw-bold mb-2" style="margin-top:10pt;">LAMPIRAN</div>

    <div class="section-title mb-4">
        STANDAR PEMBOBOTAN PENILAIAN DAN KELULUSAN
    </div>

    <div class="fw-bold mb-2">A. Pembobotan Penilaian</div>

    {{-- a. Tabel metode ujian --}}
    <div class="mb-2" style="padding-left:14pt; font-weight:bold;">
        a. &nbsp; Bobot Penilaian Pada Setiap Metode Ujian
    </div>
    <div style="padding-left:22pt; margin-bottom:10pt;">
        <table class="tbl" style="width:100%; border-collapse:collapse; font-size:11pt;">
            <thead>
                <tr>
                    <th class="tbl" style="border:0.5pt solid #000; padding:4pt 6pt; background-color:#BFCDE9; text-align:center;">Metode Ujian</th>
                    <th class="tbl" style="border:0.5pt solid #000; padding:4pt 6pt; background-color:#BFCDE9; text-align:center;">Jumlah Soal</th>
                    <th class="tbl" style="border:0.5pt solid #000; padding:4pt 6pt; background-color:#BFCDE9; text-align:center;">Lama Pengerjaan</th>
                    <th class="tbl" style="border:0.5pt solid #000; padding:4pt 6pt; background-color:#BFCDE9; text-align:center;">Proporsi Nilai</th>
                </tr>
                <tr>
                    <td colspan="4" style="border:0.5pt solid #000; padding:4pt 6pt; text-align:center; font-weight:bold; background-color:#f0f0f0;">
                        Evaluasi per Unit Kompetensi
                    </td>
                </tr>
            </thead>
            <tbody>
                @foreach($sp['pembobotan_a'] as $row)
                <tr>
                    <td style="border:0.5pt solid #000; padding:4pt 6pt;">{{ $row['metode'] }}</td>
                    <td style="border:0.5pt solid #000; padding:4pt 6pt; text-align:center;">{{ $row['jumlah_soal'] }}</td>
                    <td style="border:0.5pt solid #000; padding:4pt 6pt; text-align:center;">{{ $row['durasi'] }}</td>
                    <td style="border:0.5pt solid #000; padding:4pt 6pt; text-align:center;">{{ $row['proporsi'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- b. Tabel rekapitulasi --}}
    <div class="mb-2" style="padding-left:14pt; font-weight:bold;">
        b. &nbsp; Rekapitulasi Hasil Pembobotan Penilaian
    </div>
    <div style="padding-left:22pt; margin-bottom:14pt;">
        <table style="width:100%; border-collapse:collapse; font-size:11pt;">
            <thead>
                <tr>
                    <th style="border:0.5pt solid #000; padding:4pt 6pt; background-color:#BFCDE9; text-align:center; width:70%;">Metode Ujian</th>
                    <th style="border:0.5pt solid #000; padding:4pt 6pt; background-color:#BFCDE9; text-align:center; width:30%;">Proporsi Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sp['pembobotan_b'] as $row)
                <tr>
                    <td style="border:0.5pt solid #000; padding:4pt 6pt;">{{ $row['metode'] }}</td>
                    <td style="border:0.5pt solid #000; padding:4pt 6pt; text-align:center;">{{ $row['proporsi'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- B. Standar Kelulusan --}}
    <div class="fw-bold mb-2">B. Standar Kelulusan</div>
    <div style="padding-left:14pt;">
        @foreach($sp['standar_kelulusan'] as $i => $item)
        <table style="width:100%; margin-bottom:4pt; border-collapse:collapse;">
            <tr>
                <td style="width:14pt; vertical-align:top;">{{ $i + 1 }}.</td>
                <td style="vertical-align:top;">{!! $item !!}</td>
            </tr>
        </table>
        @endforeach
    </div>

</div>

</body>
</html>
