{{-- Lembar hasil ujian satu peserta — isi ZIP "Unduh Hasil Ujian" (App\Services\ExamResultsArchiveService). --}}
@php
    $fmt = fn ($v) => $v === null || $v === '' ? '—' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $statusLabel = ['benar' => 'Benar', 'salah' => 'Salah', 'kosong' => 'Tidak dijawab'];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 9.5pt; color: #222; }
        h1 { font-size: 13pt; text-align: center; margin: 8pt 0 2pt; }
        h2 { font-size: 11pt; margin: 14pt 0 5pt; padding: 3pt 6pt; background: #1F2937; color: #fff; }
        h3 { font-size: 10pt; margin: 10pt 0 4pt; }
        table { width: 100%; border-collapse: collapse; }
        .info td { padding: 2pt 4pt; vertical-align: top; }
        .info td.k { width: 30%; font-weight: bold; }
        .grid th { background: #E5E7EB; border: 0.5pt solid #9CA3AF; padding: 3pt; text-align: left; font-size: 9pt; }
        .grid td { border: 0.5pt solid #9CA3AF; padding: 3pt; vertical-align: top; }
        .c { text-align: center; }
        .benar { color: #047857; font-weight: bold; }
        .salah { color: #BE123C; font-weight: bold; }
        .kosong { color: #6B7280; }
        .soal { margin: 0; }
        .soal p { margin: 0 0 2pt; }
        .soal img, .jawaban img { max-width: 100%; max-height: 60mm; }
        .item { border: 0.5pt solid #D1D5DB; padding: 5pt 6pt; margin-bottom: 6pt; }
        .item .head { font-weight: bold; margin-bottom: 3pt; }
        .item .head td { padding: 0; font-weight: bold; }
        .jawaban { background: #F9FAFB; border-left: 2pt solid #2361CE; padding: 4pt 6pt; margin-top: 4pt; }
        .muted { color: #6B7280; }
        .note { font-size: 8.5pt; color: #6B7280; margin-top: 3pt; }
    </style>
</head>
<body>
    @include('documents.partials._kop-header', ['logoKanPath' => null])

    <h1>HASIL UJIAN PESERTA</h1>

    <table class="info" style="margin-top:6pt;">
        <tr><td class="k">Nama Peserta</td><td>{{ $student->name }}</td></tr>
        <tr><td class="k">No. Peserta</td><td>{{ $student->no_participant ?: '—' }}</td></tr>
        <tr><td class="k">Skema</td><td>{{ $skema ?: '—' }}</td></tr>
        <tr><td class="k">Sesi Ujian</td><td>{{ $session->title }}</td></tr>
    </table>

    <h2>Ringkasan Nilai</h2>
    <table class="grid">
        <tr>
            @if($pg)<th class="c">Pilihan Ganda</th>@endif
            @if($esai)<th class="c">Esai</th>@endif
            @if($hasWawancara)<th class="c">Wawancara</th>@endif
            @if($praktik && $praktik[0]['title'])<th class="c">Praktik/Keterampilan</th>@endif
            <th class="c">Nilai Akhir</th>
            <th class="c">Keputusan</th>
        </tr>
        <tr>
            @if($pg)<td class="c">{{ $fmt($result?->nilai_pg) }}</td>@endif
            @if($esai)<td class="c">{{ $fmt($result?->nilai_esai) }}</td>@endif
            @if($hasWawancara)<td class="c">{{ $fmt($result?->nilai_wawancara) }}</td>@endif
            @if($praktik && $praktik[0]['title'])<td class="c">{{ $fmt($result?->nilai_praktik) }}</td>@endif
            <td class="c"><b>{{ $fmt($result?->nilai_akhir) }}</b></td>
            <td class="c">{{ $result?->keputusan ?: '—' }}</td>
        </tr>
    </table>
    <div class="note">Nilai dihitung saat berkas ini dibuat ({{ now()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i') }} WIB) dengan komposisi nilai sesi.</div>

    {{-- ───────── Pilihan Ganda ───────── --}}
    @if($pg)
        <h2>Pilihan Ganda — {{ $pg->title }}</h2>
        @php $items = collect($pgItems); @endphp
        <p>
            Terjawab <b>{{ $items->where('status', '!=', 'kosong')->count() }}</b> dari {{ $items->count() }} soal ·
            Benar <b>{{ $items->where('status', 'benar')->count() }}</b> ·
            Salah <b>{{ $items->where('status', 'salah')->count() }}</b> ·
            Nilai <b>{{ $fmt($result?->nilai_pg) }}</b>
        </p>
        <table class="grid">
            <thead>
                <tr><th class="c" style="width:7%;">No</th><th>Soal</th><th style="width:22%;">Jawaban Peserta</th><th style="width:22%;">Kunci</th><th class="c" style="width:11%;">Hasil</th></tr>
            </thead>
            <tbody>
                @foreach($pgItems as $item)
                    <tr>
                        <td class="c">{{ $item['number'] }}</td>
                        <td><div class="soal">{!! $item['question'] !!}</div></td>
                        <td>@if($item['chosen'])<b>{{ $item['chosen']['key'] }}.</b> {!! $item['chosen']['text'] !!}@else<span class="kosong">—</span>@endif</td>
                        <td>@if($item['correct'])<b>{{ $item['correct']['key'] }}.</b> {!! $item['correct']['text'] !!}@else—@endif</td>
                        <td class="c {{ $item['status'] }}">{{ $statusLabel[$item['status']] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ───────── Esai ───────── --}}
    @if($esai)
        <h2>Esai — {{ $esai->title }}</h2>
        <p>Nilai esai <b>{{ $fmt($result?->nilai_esai) }}</b> (rata-rata nilai jawaban yang sudah dinilai asesor).</p>
        @if($esaiFiles)
            <p>Dijawab lewat berkas (terlampir di folder <i>Esai - Berkas Jawaban</i>): {{ implode(', ', $esaiFiles) }}</p>
        @endif
        @foreach($esaiItems as $item)
            <div class="item">
                <table class="head"><tr>
                    <td>Soal {{ $item['number'] }}</td>
                    <td style="text-align:right;">Nilai: {{ $fmt($item['score']) }}@if($item['assessor']) <span class="muted">({{ $item['assessor'] }})</span>@endif</td>
                </tr></table>
                <div class="soal">{!! $item['question'] !!}</div>
                <div class="jawaban">
                    @if($item['answer'])
                        {!! $item['answer'] !!}
                    @elseif($item['by_file'])
                        <span class="muted">Dijawab lewat berkas (lihat lampiran).</span>
                    @else
                        <span class="kosong">Tidak dijawab.</span>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    {{-- ───────── Ujian Praktik/Keterampilan / Upload Tugas ───────── --}}
    @if($praktik)
        <h2>{{ $praktik[0]['title'] ? 'Ujian Praktik/Keterampilan' : 'Upload Tugas' }}</h2>
        @foreach($praktik as $item)
            @if(count($praktik) > 1)
                <h3>{{ $item['label'] }}@if($item['title']) — {{ $item['title'] }}@endif</h3>
            @elseif($item['title'])
                <h3>{{ $item['title'] }}</h3>
            @endif
            <table class="info">
                @if($item['title'])
                    <tr><td class="k">Mulai mengerjakan</td><td>{{ $item['started_at'] ?: 'Belum dimulai' }}@if($item['durasi']) (durasi {{ $item['durasi'] }} menit)@endif</td></tr>
                @endif
                <tr>
                    <td class="k">Berkas unggahan</td>
                    <td>
                        @if($item['task'])
                            {{ $item['file_name'] }} — diunggah {{ $item['uploaded_at'] ?: '—' }}
                            @if($item['file_missing'])<br><span class="salah">Berkas tidak ditemukan di server.</span>@else<br><span class="muted">Terlampir di folder <i>{{ $item['folder'] }}</i>.</span>@endif
                        @else
                            <span class="kosong">Tidak ada unggahan.</span>
                        @endif
                    </td>
                </tr>
                @if($item['title'])
                    <tr><td class="k">Nilai</td><td><b>{{ $fmt($item['score']) }}</b>@if($item['assessor']) <span class="muted">({{ $item['assessor'] }})</span>@endif</td></tr>
                @endif
            </table>

            @if($item['aspek'])
                <table class="grid" style="margin-top:4pt;">
                    <thead><tr><th class="c" style="width:8%;">No</th><th>Aspek</th><th class="c" style="width:10%;">Bobot</th><th class="c" style="width:10%;">Nilai</th><th style="width:30%;">Catatan</th></tr></thead>
                    <tbody>
                        @foreach($item['aspek'] as $a)
                            <tr>
                                <td class="c">{{ $a['no'] }}</td>
                                <td>@if($a['kelompok'])<span class="muted">{{ $a['kelompok'] }} — </span>@endif{{ $a['nama'] }}</td>
                                <td class="c">{{ $fmt($a['bobot']) }}</td>
                                <td class="c">{{ $fmt($a['nilai']) }}@if($a['gerbang'])<br><span class="salah">gerbang gagal</span>@endif</td>
                                <td>{{ is_string($a['catatan']) ? $a['catatan'] : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    @endif
</body>
</html>
