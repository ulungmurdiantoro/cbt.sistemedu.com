<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Asesmen</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #333; background: #f4f4f4; margin: 0; }
        .wrapper { max-width: 580px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #1f2937; color: #fff; padding: 24px 32px; }
        .header h2 { margin: 0; font-size: 16px; }
        .header p { margin: 4px 0 0; font-size: 12px; opacity: .7; }
        .body { padding: 28px 32px; }
        .keputusan { font-size: 18px; font-weight: bold; padding: 10px 20px; border-radius: 6px; display: inline-block; margin-bottom: 16px; }
        .lulus { background: #d1fae5; color: #065f46; }
        .tidak-lulus { background: #fee2e2; color: #991b1b; }
        .detail { background: #f8f9fa; border-radius: 6px; padding: 14px 16px; margin-bottom: 16px; font-size: 12px; }
        .detail p { margin: 4px 0; }
        .detail strong { display: inline-block; width: 140px; }
        .footer { background: #f1f5f9; padding: 16px 32px; font-size: 11px; color: #666; text-align: center; }
        .btn { display: inline-block; background: #1f2937; color: #fff; padding: 10px 22px; border-radius: 5px; text-decoration: none; font-size: 13px; margin-top: 12px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h2>Hasil Asesmen Sertifikasi</h2>
        <p>LSP Edukasi Global Cendekia</p>
    </div>
    <div class="body">
        <p>Yth. <strong>{{ $student->name }}</strong>,</p>
        <p>Berikut adalah hasil asesmen Anda pada sesi <strong>{{ $session->title }}</strong>:</p>

        <div class="keputusan {{ $result->keputusan === 'LULUS' ? 'lulus' : 'tidak-lulus' }}">
            {{ $result->keputusan === 'LULUS' ? '✓ KOMPETEN (LULUS)' : '✗ BELUM KOMPETEN (TIDAK LULUS)' }}
        </div>

        <div class="detail">
            <p><strong>No. Peserta:</strong> {{ $student->no_participant }}</p>
            <p><strong>Sesi Ujian:</strong> {{ $session->title }}</p>
            <p><strong>Nomor SK:</strong> {{ $result->sk_number }}</p>
            @if($result->keputusan === 'LULUS')
            <p><strong>Nomor Sertifikat:</strong> {{ $result->sertifikat_number }}</p>
            <p><strong>Berlaku Hingga:</strong> {{ $result->valid_until?->isoFormat('D MMMM YYYY') }}</p>
            @endif
        </div>

        <p>Dokumen terlampir pada email ini.
            Anda juga dapat mengunduhnya melalui dashboard peserta.</p>

        @if($result->keputusan === 'TIDAK_LULUS')
        <p>Jika Anda memenuhi syarat, Anda dapat mengikuti <strong>ujian remidi</strong> melalui dashboard peserta.</p>
        @endif

        <a href="{{ url('/peserta/dashboard') }}" class="btn">Buka Dashboard</a>
    </div>
    <div class="footer">
        Email ini dikirim otomatis oleh sistem. Jangan balas email ini.
    </div>
</div>
</body>
</html>
