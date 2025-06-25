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

    @forelse($grades as $grade)
        <div class="section">
            <div class="line"></div>
            <strong>Nama Peserta:</strong> {{ $grade->student->name }}<br>
            <strong>No. Peserta:</strong> {{ $grade->student->no_participant ?? '-' }}<br>
            <strong>Nilai:</strong>

            @foreach($grade->essaysanswers as $answer)
                <div class="question">
									Soal {{ $loop->iteration }}: {{ strip_tags($answer->essay->question) ?? '-' }}
                </div>
                <div class="answer">
                    Jawaban: {!! $answer->answer ?? '-' !!}
                </div>
            @endforeach
        </div>
    @empty
    @endforelse

</body>
</html>
