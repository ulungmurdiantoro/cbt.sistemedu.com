{{--
    Kop surat LSP: logo kiri, teks tengah, opsional logo KAN kanan.
    Variables:
      $logoEdukiaPath  — absolute file path logo Edukia
      $logoKanPath     — absolute file path logo KAN (null = tidak ditampilkan)
      $lsp             — array dari config('lsp_documents.lsp')
--}}
<table style="width:100%; border-collapse:collapse; border-bottom:2px solid #000; padding-bottom:6pt; margin-bottom:4pt;">
    <tr>
        {{-- Logo Edukia --}}
        <td style="width:100pt; vertical-align:middle;">
            @if(file_exists($logoEdukiaPath))
                <img src="{{ $logoEdukiaPath }}" style="max-height:42pt; max-width:100pt; object-fit:contain;">
            @endif
        </td>

        {{-- Teks LSP (tengah) --}}
        <td style="text-align:center; vertical-align:middle; padding:0 8pt;">
            <div style="font-family:Cambria,serif; font-size:16pt; font-weight:bold; letter-spacing:0.5pt;">
                {{ $lsp['nama'] }}
            </div>
            <div style="font-family:Cambria,serif; font-size:8pt; margin-top:2pt; line-height:1.4;">
                {{ $lsp['alamat'] }}<br>
                Telp. {{ $lsp['telp'] }} &nbsp;|&nbsp; {{ $lsp['web'] }}
            </div>
        </td>

        {{-- Logo KAN (opsional) --}}
        <td style="width:80pt; vertical-align:middle; text-align:right;">
            @if(!empty($logoKanPath) && file_exists($logoKanPath))
                <img src="{{ $logoKanPath }}" style="max-height:42pt; max-width:80pt; object-fit:contain;">
            @endif
        </td>
    </tr>
</table>
