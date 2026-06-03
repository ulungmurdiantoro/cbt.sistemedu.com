{{--
    Tabel rekap nilai 5 kolom (dipakai SP hal.1 & SK hal.3).
    Variables:
      $nilaiWawancara, $nilaiPg, $nilaiEsai, $nilaiAkhir, $status (KOMPETEN/TIDAK KOMPETEN)
      $statusBg — '#D9EAD3' (kompeten) | '#E06666' (tidak) | '#FFF2CC' (lain)
      $statusColor — '#274E13' | '#fff' | '#000'
--}}
@php
    $statusUp = strtoupper($status ?? '-');
    if ($statusUp === 'KOMPETEN') {
        $bg    = '#D9EAD3';
        $color = '#274E13';
    } elseif ($statusUp === 'TIDAK KOMPETEN') {
        $bg    = '#E06666';
        $color = '#ffffff';
    } else {
        $bg    = '#FFF2CC';
        $color = '#000000';
    }
    $headerBg = '#BFCDE9';
@endphp

<table style="width:100%; border-collapse:collapse; font-family:Cambria,serif; font-size:10pt; margin-top:6pt;">
    {{-- Header baris 1 --}}
    <tr style="background-color:{{ $headerBg }}; font-weight:bold; text-align:center;">
        <td rowspan="2" style="border:0.5pt solid #000; padding:4pt; width:18%; vertical-align:middle;">NILAI<br>WAWANCARA</td>
        <td rowspan="2" style="border:0.5pt solid #000; padding:4pt; width:20%; vertical-align:middle;">NILAI PILIHAN<br>GANDA</td>
        <td rowspan="2" style="border:0.5pt solid #000; padding:4pt; width:16%; vertical-align:middle;">NILAI ESAI</td>
        <td colspan="2" style="border:0.5pt solid #000; padding:4pt; width:46%;">REKAPITULASI HASIL ASESMEN</td>
    </tr>
    {{-- Header baris 2 --}}
    <tr style="background-color:{{ $headerBg }}; font-weight:bold; text-align:center;">
        <td style="border:0.5pt solid #000; padding:4pt; width:23%;">HASIL NILAI</td>
        <td style="border:0.5pt solid #000; padding:4pt; width:23%;">STATUS</td>
    </tr>
    {{-- Baris nilai --}}
    <tr style="text-align:center;">
        <td style="border:0.5pt solid #000; padding:4pt;">{{ $nilaiWawancara ?? '-' }}</td>
        <td style="border:0.5pt solid #000; padding:4pt;">{{ $nilaiPg ?? '-' }}</td>
        <td style="border:0.5pt solid #000; padding:4pt;">{{ $nilaiEsai ?? '-' }}</td>
        <td style="border:0.5pt solid #000; padding:4pt; font-weight:bold;">{{ $nilaiAkhir ?? '-' }}</td>
        <td style="border:0.5pt solid #000; padding:4pt; font-weight:bold; background-color:{{ $bg }}; color:{{ $color }};">
            {!! $statusUp !!}
        </td>
    </tr>
</table>
