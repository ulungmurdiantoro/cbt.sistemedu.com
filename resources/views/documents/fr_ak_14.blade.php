<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>FR.AK.14 - {{ $namaPeserta }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: cambria, 'Times New Roman', Times, serif; font-size: 10.5pt; color: #000; }

.title { font-size: 12pt; font-weight: bold; text-align: center; margin-bottom: 12pt; text-transform: uppercase; }
.intro { text-align: justify; margin-bottom: 8pt; }

.info-table { border-collapse: collapse; margin: 8pt 0 12pt; }
.info-table td { padding: 2pt 0; vertical-align: top; }
.info-label { white-space: nowrap; width: 90pt; }
.info-colon { width: 10pt; }

.klausul { margin-bottom: 5pt; }
.klausul-title { font-weight: bold; }
.klausul-body { text-align: justify; margin: 1pt 0 4pt 14pt; }
.sub-list { margin: 1pt 0 4pt 26pt; }
.sub-list li { text-align: justify; margin-bottom: 2pt; }

.penutup { text-align: justify; margin-top: 10pt; }

.ttd-table { width: 100%; border-collapse: collapse; margin-top: 18pt; }
.ttd-table td { padding: 0; vertical-align: top; width: 50%; }
.ttd-place { margin-bottom: 4pt; }
.ttd-heading { margin-bottom: 4pt; }
.ttd-space { height: 60pt; }
.ttd-img { display: inline-block; }
.ttd-name { border-top: 0.75pt solid #000; padding-top: 2pt; display: inline-block; min-width: 160pt; }

/* Area ini disisakan kosong untuk QR e-meterai — koordinat visLLX/LLY/URX/URY
   di StampFrAk14Job dikalibrasi lewat https://e-form.peruri.co.id/pdfviewer/
   supaya jatuh persis di sini, di sebelah blok TTD "Yang menyatakan,". */
.meterai-area { width: 90pt; height: 70pt; }
</style>
</head>
<body>

<div class="title">Surat Pernyataan Pemegang Sertifikat Kompetensi</div>

<div class="intro">Yang bertanda tangan dibawah ini:</div>

<table class="info-table">
    <tr><td class="info-label">Nama</td><td class="info-colon">:</td><td>{{ $namaPeserta }}</td></tr>
    <tr><td class="info-label">NIK</td><td class="info-colon">:</td><td>{{ $nik }}</td></tr>
    <tr><td class="info-label">Skema</td><td class="info-colon">:</td><td>{{ $namaSkema }}</td></tr>
    <tr><td class="info-label">No. Sertifikat</td><td class="info-colon">:</td><td>{{ $noSertifikat }}</td></tr>
</table>

<div class="intro">Menyatakan bersedia memenuhi ketentuan-ketentuan yang dipersyaratkan dalam Skema Sertifikasi LSP Edukasi Global Cendekia, sebagai berikut:</div>

<div class="klausul">
    <div class="klausul-body">1. Sanggup menjaga kerahasiaan seluruh proses pelaksanaan uji kompetensi.</div>
</div>
<div class="klausul">
    <div class="klausul-body">2. Sanggup menjamin terpeliharannya kompetensi yang sesuai pada sertifikat kompetensi.</div>
</div>
<div class="klausul">
    <div class="klausul-body">3. Sertifikat kompetensi hanya berlaku untuk ruang lingkup sesuai dengan skema sertifikasi.</div>
</div>
<div class="klausul">
    <div class="klausul-title">4. Penggunaan Sertifikat, Logo dan Penanda</div>
    <ol class="sub-list" type="a">
        <li>Pemegang Sertifikat dapat menggunakan sertifikat, logo dan penanda dalam bidang pekerjaan dan kualifikasi/kompetensi yang tercantum pada sertifikat.</li>
        <li>Pemegang Sertifikat dapat menggandakan dan memberikan salinan sertifikat kepada pihak lain untuk kepentingan yang berkaitan dengan bidang pekerjaan dan kualifikasi/kompetensi yang tercantum pada sertifikat.</li>
        <li>Pemegang Sertifikat tidak diperkenankan menggunakan sertifikat, logo, dan penanda dengan cara yang tidak sesuai, tidak dapat dipertanggungjawabkan, atau dapat mencemarkan nama baik KAN dan LSP Edukasi Global Cendekia (Edukia), serta apabila sertifikat telah berakhir masa berlakunya, sedang dibekukan atau telah dicabut.</li>
    </ol>
</div>
<div class="klausul">
    <div class="klausul-title">5. Pemeliharaan Sertifikasi dan Sertifikasi Ulang</div>
    <ol class="sub-list" type="a">
        <li>Untuk memastikan terpeliharanya kompetensi, Pemegang Sertifikat wajib mengikuti program pemeliharaan sertifikasi yang ditetapkan LSP Edukasi Global Cendekia yang akan dimulai pada bulan ke-36 setelah tanggal terbit sertifikat.</li>
        <li>Untuk memperpanjang masa berlaku sertifikat, Pemegang Sertifikat dapat mengajukan permohonan kepada LSP Edukasi Global Cendekia, minimal 2 (dua) bulan sebelum berakhirnya masa berlaku sertifikat.</li>
    </ol>
</div>
<div class="klausul">
    <div class="klausul-title">6. Pembekuan dan Pencabutan Sertifikat</div>
    <ol class="sub-list" type="a">
        <li>Apabila Pemegang Sertifikat tidak memenuhi ketentuan-ketentuan yang disebutkan pada bagian 1 sampai 5 maka LSP Edukasi Global Cendekia dapat menerbitkan surat pembekuan sertifikat.</li>
        <li>LSP Edukasi Global Cendekia akan menerbitkan surat pengaktifan kembali sertifikat setelah Pemegang Sertifikat dinilai berhasil melakukan tindakan perbaikan dalam batas waktu yang ditetapkan LSP Edukasi Global Cendekia.</li>
        <li>Apabila Pemegang Sertifikat dinilai gagal dalam melakukan tindakan perbaikan, maka LSP Edukasi Global Cendekia dapat menerbitkan surat pencabutan Sertifikat. Pemegang Sertifikat diwajibkan mengembalikan sertifikat yang dicabut kepada LSP Edukasi Global Cendekia.</li>
    </ol>
</div>

<div class="penutup">Demikian pernyataan ini saya buat dengan sebenar-benarnya dengan penuh tanggung jawab. Apabila saya melanggar pasal-pasal dalam perjanjian diatas, maka saya bersedia menanggung semua tindakan yang diambil oleh LSP Edukasi Global Cendekia yang dapat berupa penundaan atau pencabutan sertifikat, pengumuman pelanggaran, dan jika perlu tindakan hukum lainnya.</div>

<table class="ttd-table">
    <tr>
        <td><div class="meterai-area"></div></td>
        <td>
            <div class="ttd-place">................................., {{ $tanggalTtd }}</div>
            <div class="ttd-heading">Yang menyatakan,</div>
            <div class="ttd-space">
                @if($ttdAsesi['path'])
                    <div class="ttd-img"><img src="{{ $ttdAsesi['path'] }}" style="width:{{ $ttdAsesi['w'] }}mm;height:{{ $ttdAsesi['h'] }}mm;"></div>
                @endif
            </div>
            <div class="ttd-name">{{ $namaPeserta }}</div>
        </td>
    </tr>
</table>

</body>
</html>
