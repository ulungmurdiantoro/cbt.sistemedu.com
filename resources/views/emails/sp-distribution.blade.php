<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Pernyataan Hasil Asesmen</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #333; background: #f4f4f4; margin: 0; }
        .wrapper { max-width: 580px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #1f2937; color: #fff; padding: 24px 32px; }
        .header h2 { margin: 0; font-size: 16px; }
        .header p { margin: 4px 0 0; font-size: 12px; opacity: .7; }
        .body { padding: 28px 32px; }
        .detail { background: #f8f9fa; border-radius: 6px; padding: 14px 16px; margin-bottom: 16px; font-size: 12px; }
        .detail p { margin: 4px 0; }
        .detail strong { display: inline-block; width: 140px; }
        .notice { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: 6px; padding: 14px 16px; margin-bottom: 16px; font-size: 12px; }
        .footer { background: #f1f5f9; padding: 16px 32px; font-size: 11px; color: #666; text-align: center; }
        .btn { display: inline-block; background: #1f2937; color: #fff; padding: 10px 22px; border-radius: 5px; text-decoration: none; font-size: 13px; margin-top: 12px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h2>Surat Pernyataan (SP) Hasil Asesmen</h2>
        <p>LSP Edukasi Global Cendekia</p>
    </div>
    <div class="body">
        <p>Yth. <strong>{{ $student->name }}</strong>,</p>
        <p>Berikut Surat Pernyataan (SP) hasil asesmen Anda pada sesi <strong>{{ $session->title }}</strong>, terlampir pada email ini.</p>

        <div class="detail">
            <p><strong>No. Peserta:</strong> {{ $student->no_participant }}</p>
            <p><strong>Sesi Ujian:</strong> {{ $session->title }}</p>
            <p><strong>Nomor SP:</strong> {{ $result->sp_number }}</p>
        </div>

        <div class="notice">
            <strong>Mohon periksa kembali data pada dokumen ini</strong>, terutama penulisan nama Anda.
            Jika ada kesalahan ketik (typo) atau data lain yang perlu dikoreksi, segera hubungi LSP
            Edukasi Global Cendekia sebelum SK dan Sertifikat resmi diterbitkan — perbaikan setelah
            dokumen final terbit akan lebih sulit diproses.
        </div>

        <p>SK dan Sertifikat resmi akan menyusul dikirimkan terpisah setelah masa koreksi ini berakhir.</p>

        <p>Mohon luangkan waktu untuk mengisi umpan balik asesmen kami:
            <a href="https://tinyurl.com/UmpanbalikAsesmen-Edukia">https://tinyurl.com/UmpanbalikAsesmen-Edukia</a></p>

        <a href="{{ url('/peserta/dashboard') }}" class="btn">Buka Dashboard</a>
    </div>
    <div class="footer">
        Email ini dikirim otomatis oleh sistem. Jangan balas email ini — hubungi LSP Edukasi Global Cendekia
        secara langsung untuk pengajuan revisi data.
    </div>
</div>
</body>
</html>
