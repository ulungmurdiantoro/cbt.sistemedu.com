<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>FR.AK.01 - {{ $namaPeserta }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: cambria, 'Times New Roman', Times, serif; font-size: 9.5pt; color: #000; }

.title { font-size: 11pt; font-weight: bold; margin-bottom: 8pt; }
.intro-box { border: 0.75pt solid #000; padding: 5pt 6pt; margin-bottom: 0; text-align: justify; }

.info-table { width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 9.5pt; }
.info-table td { border: 0.75pt solid #000; padding: 4pt 6pt; vertical-align: top; }
.info-label { font-weight: bold; width: 22%; }
.info-colon { width: 4%; }
.strike { text-decoration: line-through; color: #888; }

.pihak-title { font-weight: bold; margin: 8pt 0 4pt; }
.klausul { margin-bottom: 4pt; }
.klausul-title { font-weight: bold; }
.klausul-body { text-align: justify; margin: 1pt 0 4pt 14pt; }
.sub-list { margin: 1pt 0 4pt 26pt; }
.sub-list li { text-align: justify; margin-bottom: 1pt; }

/* Kotak centang digambar via CSS (bukan glyph Unicode ☐/☑) — Cambria tidak
   punya karakter ballot-box. Semua di sini kosong (belum ada data per-jenis
   bukti di sistem), sama seperti tampilan on-screen pakta integritas. */
.chk { display: inline-block; width: 9pt; height: 9pt; border: 1pt solid #000; text-align: center; font-size: 8pt; line-height: 8pt; vertical-align: middle; font-weight: bold; }

.page-break { page-break-before: always; }

.penutup-box { border: 0.75pt solid #000; padding: 6pt; margin-top: 8pt; text-align: justify; }

.ttd-table { width: 100%; border-collapse: collapse; margin-top: 10pt; }
.ttd-table td { padding: 6pt 0; vertical-align: middle; }
.ttd-label { width: 45%; font-weight: normal; }
.ttd-img { display: inline-block; vertical-align: middle; }
.ttd-tanggal { padding-left: 10pt; }
</style>
</head>
<body>

<div class="title">FR.AK.01. PERSETUJUAN ASESMEN, KETIDAKBERPIHAKAN, KERAHASIAAN DAN KEAMANAN SERTIFIKASI</div>

<div class="intro-box">Persetujuan Asesmen ini untuk menjamin bahwa Asesi telah diberi arahan secara rinci tentang perencanaan dan proses asesmen</div>

<table class="info-table">
    <tr>
        <td class="info-label" rowspan="2">Skema Sertifikasi</td>
        <td style="width:10%">Judul</td><td class="info-colon">:</td>
        <td>{{ $namaSkema }}</td>
    </tr>
    <tr>
        <td>Nomor</td><td>:</td>
        <td>{{ $kodeSkema }}</td>
    </tr>
    <tr>
        <td class="info-label">TUK</td><td colspan="2">:</td>
        <td>{{ $tuk }}</td>
    </tr>
    <tr>
        <td class="info-label">Nama Asesor</td><td colspan="2">:</td>
        <td>{{ $namaAsesor }}</td>
    </tr>
    <tr>
        <td class="info-label">Nama Asesi</td><td colspan="2">:</td>
        <td>{{ $namaPeserta }}</td>
    </tr>
    <tr>
        <td class="info-label" rowspan="3">Bukti yang akan dikumpulkan</td><td colspan="2" rowspan="3">:</td>
        <td><span class="chk"></span> TL : Verifikasi Portofolio &nbsp;&nbsp;&nbsp; <span class="chk">X</span> L : Observasi Langsung</td>
    </tr>
    <tr><td><span class="chk">X</span> T : Hasil Tes Tulis</td></tr>
    <tr><td><span class="chk">X</span> T : Hasil Wawancara</td></tr>
    <tr>
        <td class="info-label" rowspan="3">Pelaksanaan asesmen disepakati pada</td><td colspan="2" rowspan="3">:</td>
        <td>Hari/Tanggal &nbsp;&nbsp; : {{ $hariTanggal }}</td>
    </tr>
    <tr><td>Waktu &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $waktu }}</td></tr>
    <tr><td>TUK &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $tuk }}</td></tr>
</table>

<div class="pihak-title">LSP Edukasi Global Cendekia :</div>

<div class="klausul">
    <div class="klausul-title">1. Memastikan Tidak Ada Konflik Kepentingan</div>
    <div class="klausul-body">LSP dan pemohon menyatakan bahwa tidak ada kepentingan pribadi yang dapat memengaruhi hasil sertifikasi.</div>
</div>
<div class="klausul">
    <div class="klausul-title">2. Menjaga Objektivitas Proses Sertifikasi</div>
    <ol class="sub-list">
        <li>LSP Edukasi Global Cendekia berjanji untuk menerapkan standar dan kriteria yang konsisten bagi semua pemohon.</li>
        <li>Evaluasi akan dilakukan sesuai prosedur tanpa mempertimbangkan afiliasi, status sosial, atau hubungan pribadi pemohon.</li>
    </ol>
</div>
<div class="klausul">
    <div class="klausul-title">3. Menjaga Kerahasiaan Data Pribadi dan Dokumen</div>
    <div class="klausul-body">LSP tidak akan mengungkapkan data pemohon kepada pihak ketiga tanpa izin tertulis dari pemohon, kecuali untuk keperluan audit internal atau regulator yang sah.</div>
</div>
<div class="klausul">
    <div class="klausul-title">4. Pengelolaan Data Sertifikasi</div>
    <div class="klausul-body">Data hasil sertifikasi akan disimpan dengan pengamanan sesuai standar dan hanya akan diakses oleh pihak berwenang.</div>
</div>
<div class="klausul">
    <div class="klausul-title">5. Perlindungan Informasi Sensitif</div>
    <div class="klausul-body">LSP akan menggunakan sistem pengamanan fisik dan digital untuk melindungi data pemohon.</div>
</div>
<div class="klausul">
    <div class="klausul-title">6. Penggunaan Informasi</div>
    <div class="klausul-body">Informasi yang dikumpulkan hanya akan digunakan untuk tujuan sertifikasi.</div>
</div>
<div class="klausul">
    <div class="klausul-title">7. Durasi Perjanjian</div>
    <div class="klausul-body">Perjanjian ini berlaku selama proses sertifikasi dan tiga tahun setelah proses selesai. Ketentuan tentang kerahasiaan tetap berlaku tanpa batas waktu kecuali disepakati lain.</div>
</div>
<div class="klausul">
    <div class="klausul-title">8. Penyelesaian Sengketa</div>
    <div class="klausul-body">Setiap banding dan keluhan yang muncul akan diselesaikan melalui investigasi proses asesmen dan mediasi terlebih dahulu. Jika mediasi gagal, masalah akan dibawa ke badan hukum yang berwenang.</div>
</div>

<div class="page-break"></div>

<div class="pihak-title">Asesor :</div>
<div class="klausul">
    <div class="klausul-title">1. Menjalankan Asesmen Secara Objektif</div>
    <div class="klausul-body">Asesor akan menilai pemohon secara adil, menggunakan kriteria yang objektif dan berdasarkan standar kompetensi yang berlaku.</div>
</div>
<div class="klausul">
    <div class="klausul-title">2. Menghindari Konflik Kepentingan</div>
    <div class="klausul-body">Jika terdapat potensi konflik kepentingan yang dapat memengaruhi objektivitas asesmen, asesor wajib melaporkannya ke Lembaga Sertifikasi Person (LSP) dan menolak untuk melanjutkan proses asesmen.</div>
</div>
<div class="klausul">
    <div class="klausul-title">3. Perlindungan Informasi Pribadi dan Data Sertifikasi</div>
    <div class="klausul-body">Asesor akan menjaga kerahasiaan semua informasi yang diterima dari pemohon dan tidak akan membagikannya kepada pihak ketiga tanpa izin tertulis dari pemohon, kecuali jika diharuskan oleh hukum atau kebijakan LSP.</div>
</div>
<div class="klausul">
    <div class="klausul-title">4. Penggunaan Data Sertifikasi</div>
    <div class="klausul-body">Data sertifikasi hanya akan digunakan untuk tujuan penilaian dan tidak untuk kepentingan pribadi atau pihak ketiga yang tidak berkepentingan.</div>
</div>
<div class="klausul">
    <div class="klausul-title">5. Keamanan Data dan Dokumen</div>
    <div class="klausul-body">Asesor akan menyimpan dokumen yang berhubungan dengan asesmen secara aman dan hanya mengaksesnya jika diperlukan untuk keperluan sertifikasi.</div>
</div>

<div class="pihak-title">Asesi :</div>
<div class="klausul">
    <div class="klausul-title">1. Mengikuti Kegiatan Asesmen Berdasarkan Standar LSP Edukasi Global Cendekia</div>
    <ol class="sub-list">
        <li>Saya setuju mengikuti asesmen dengan pemahaman bahwa informasi yang dikumpulkan hanya digunakan untuk pengembangan profesional dan hanya dapat diakses oleh orang tertentu saja.</li>
        <li>Saya menyatakan dengan sungguh-sungguh bahwa saya akan merahasiakan semua dokumen dan/atau informasi dalam bentuk apapun sehubungan proses asesmen.</li>
        <li>Saya menyatakan tidak menggunakan Informasi Rahasia untuk tujuan pribadi atau komersial, termasuk tetapi tidak terbatas pada:
            <ul style="margin-left:16pt">
                <li>Membocorkan kepada pihak ketiga yang tidak berwenang.</li>
                <li>Menggandakan, menyalin, atau menyebarkan Informasi Rahasia.</li>
                <li>Menggunakan Informasi Rahasia untuk mendapatkan keuntungan pribadi.</li>
            </ul>
        </li>
        <li>Pelanggaran atas pernyataan saya di atas, saya bersedia diberi dan tunduk kepada sanksi apapun sesuai ketetapan Lembaga Sertifikasi Person Edukasi Global Cendekia.</li>
    </ol>
</div>
<div class="klausul">
    <div class="klausul-title">2. Memenuhi Standar Kompetensi yang Berlaku</div>
    <ol class="sub-list">
        <li>Saya akan terus menjaga dan meningkatkan keterampilan serta pengetahuan yang relevan untuk memastikan pemenuhan standar kompetensi sesuai sertifikat yang diterima.</li>
        <li>Saya menyatakan mematuhi segala aturan dan prosedur yang terkait dengan pemeliharaan kompetensi sebagaimana diatur oleh LSP.</li>
    </ol>
</div>
<div class="klausul">
    <div class="klausul-title">3. Memberikan Informasi Kendala dan Hambatan kepada LSP Edukasi Global Cendekia</div>
    <ol class="sub-list">
        <li>Saya akan segera menginformasikan kepada LSP Edukasi Global Cendekia apabila mengalami kendala yang dapat memengaruhi kemampuan saya dalam memenuhi standar kompetensi yang berlaku.</li>
        <li>Laporan mencakup rincian hambatan, alasan, dan perkiraan dampaknya terhadap pemenuhan kompetensi.</li>
    </ol>
</div>
<div class="klausul">
    <div class="klausul-title">4. Menyerahkan Bukti Pendukung Kendala dan Hambatan</div>
    <div class="klausul-body">Saya akan menyediakan dokumen atau bukti pendukung terkait hambatan yang dihadapi.</div>
</div>
<div class="klausul">
    <div class="klausul-title">5. Evaluasi Ulang Status Sertifikasi</div>
    <div class="klausul-body">LSP Edukasi Global Cendekia berhak melakukan evaluasi ulang status sertifikasi jika hambatan pemenuhan kompetensi tidak dapat diatasi atau jika saya tidak melaporkan kendala yang ada.</div>
</div>
<div class="klausul">
    <div class="klausul-title">6. Pencabutan atau Penangguhan Sertifikasi</div>
    <div class="klausul-body">Jika saya tidak dapat lagi memenuhi kompetensi yang diwajibkan dalam jangka waktu yang ditentukan, sertifikat kompetensi dapat ditangguhkan atau dicabut berdasarkan keputusan LSP Edukasi Global Cendekia.</div>
</div>

<div class="penutup-box">Dengan menandatangani formulir ini, LSP Edukasi Global Cendekia, Asesor dan Pemohon menyatakan setuju untuk mematuhi ketentuan ketidakberpihakan, kerahasiaan, dan keamanan yang disebutkan di atas dan untuk menjaga integritas proses sertifikasi.</div>

<table class="ttd-table">
    <tr>
        <td class="ttd-label">Tanda tangan LSP Edukasi Global Cendekia :</td>
        <td>
            @if($ttdLsp['path'])
                <div class="ttd-img"><img src="{{ $ttdLsp['path'] }}" style="width:{{ $ttdLsp['w'] }}mm;height:{{ $ttdLsp['h'] }}mm;"></div>
            @endif
        </td>
        <td class="ttd-tanggal">Tanggal : {{ $tanggalLsp }}</td>
    </tr>
    <tr>
        <td class="ttd-label">Tanda tangan Asesor :</td>
        <td>
            @if($ttdAsesor['path'])
                <div class="ttd-img"><img src="{{ $ttdAsesor['path'] }}" style="width:{{ $ttdAsesor['w'] }}mm;height:{{ $ttdAsesor['h'] }}mm;"></div>
            @endif
        </td>
        <td class="ttd-tanggal">Tanggal : {{ $tanggalAsesor }}</td>
    </tr>
    <tr>
        <td class="ttd-label">Tanda tangan Asesi :</td>
        <td>
            @if($ttdAsesi['path'])
                <div class="ttd-img"><img src="{{ $ttdAsesi['path'] }}" style="width:{{ $ttdAsesi['w'] }}mm;height:{{ $ttdAsesi['h'] }}mm;"></div>
            @endif
        </td>
        <td class="ttd-tanggal">Tanggal : {{ $tanggalAsesi }}</td>
    </tr>
</table>

<p style="margin-top:10pt; font-size:8.5pt; font-style:italic;">* Coret yang tidak perlu</p>

</body>
</html>
