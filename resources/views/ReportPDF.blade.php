<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Jawaban Ujian</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11pt;
            margin: 0 25px;
        }
        h2, h4 {
            text-align: center;
            margin: 0;
        }
        p {
            /* text-align: center; */
            margin: 0;
        }
        .section {
					margin-top: 30px;
					page-break-after: always;
					page-break-inside: avoid;
			}
        .question {
            margin-top: 12px;
            font-weight: bold;
            page-break-inside: avoid;
        }
        .answer {
            margin-top: 4px;
            white-space: pre-line;
            page-break-inside: avoid;
        }
        .line {
            border-bottom: 1px solid #999;
            margin: 15px 0;
        }
        .no-break {
            page-break-before: avoid;
        }
    </style>
</head>
<body>

    <div class="no-break">
        <h2>LAPORAN JAWABAN PESERTA</h2>
        <h4>{{ $exam->title }}</h4>
        <p>Sesi: {{ $exam_session->title }} | Kelas: {{ $exam->classroom->title }} | Tipe: {{ $exam->type }}</p>
    </div>

    {{-- Bagian 1: Tabel ringkasan nilai --}}
    <table border="1" cellspacing="0" cellpadding="4" width="100%">
        <thead>
            <tr>
            <th>No Peserta</th>
            <th>Nama Siswa</th>
            @for ($i = 1; $i <= 10; $i++)
                <th>{{ $i }}</th>
            @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($grades as $grade)
            <tr>
                <td>{{ $grade->student->no_participant }}</td>
                <td>{{ $grade->student->name }}</td>
                @for ($i = 0; $i < 10; $i++)
                <td>
                    @if(isset($grade->essaysanswers[$i]))
                    {!! $grade->essaysanswers[$i]->answer ?? '-' !!}
                    @else
                    -
                    @endif
                </td>
                @endfor
            </tr>
            @endforeach
        </tbody>
    </table>


</body>
</html>
