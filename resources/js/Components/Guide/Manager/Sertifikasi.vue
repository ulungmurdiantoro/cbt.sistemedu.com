<template>
    <p class="fw-bolder mb-1">1. Tinjau tiap peserta</p>
    <p class="text-muted mb-2">Satu baris per peserta. Semua yang perlu dicek sebelum meluluskan seseorang ada di satu layar:</p>
    <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered mb-0">
            <tbody>
                <tr><td style="width:190px" class="fw-bolder">Dokumen Persyaratan</td><td><span class="badge bg-success">Lengkap</span> atau hitungan x/y dokumen terverifikasi. <em>Lihat Dokumen</em> membuka daftar berkas (baca saja).</td></tr>
                <tr><td class="fw-bolder">FR.APL.01 · FR.AK.01</td><td>Unduh dokumen PDF peserta.</td></tr>
                <tr><td class="fw-bolder">FR.APL.03</td><td><span class="badge bg-success">Layak</span> / <span class="badge bg-danger">Tidak Layak</span> beserta skor penilaian awal, dan PDF-nya.</td></tr>
                <tr><td class="fw-bolder">Laporan Asesmen</td><td>Asesor yang ditugaskan, rekomendasi <span class="badge bg-success">Kompeten</span> / <span class="badge bg-danger">Belum Kompeten</span>, dan unduh FR.AK.05.</td></tr>
                <tr><td class="fw-bolder">PG · Esai · Wawancara · Nilai Akhir</td><td>Nilai per komponen dan hasil gabungannya.</td></tr>
                <tr><td class="fw-bolder">Keputusan</td><td><span class="badge bg-success">LULUS</span> / <span class="badge bg-danger">TIDAK LULUS</span>, dihitung otomatis dari nilai akhir dan KKM skema.</td></tr>
            </tbody>
        </table>
    </div>
    <p class="text-muted mb-3">Dokumen persyaratan dan FR.APL.03 sudah diverifikasi admin saat memproses pendaftaran. Di sini Anda meninjau ulang.</p>

    <p class="fw-bolder mb-1">2. Centang Verifikasi</p>
    <p class="text-muted mb-3">
        Centang kolom <strong>Verifikasi</strong> untuk peserta yang sudah beres. Nama Anda tercatat di bawah centang. Centang
        bisa dilepas lagi selama peserta belum Final. Peserta yang belum siap (mis. berhalangan hadir) biarkan tidak dicentang;
        mereka tetap Draft dan bisa difinalisasi belakangan, mis. saat ikut batch susulan.
    </p>

    <p class="fw-bolder mb-1">3. Preview, lalu Finalisasi</p>
    <ul class="text-muted mb-2 ps-3">
        <li><span class="badge bg-gray-200 text-gray-800 border">Preview Keputusan</span> membuka draf berita acara (berisi peserta
            yang sudah dicentang) di tab baru. Tombol ini hanya ada sebelum sesi pertama kali difinalisasi.</li>
        <li><span class="badge bg-warning text-gray-800">Finalisasi Semua</span> aktif bila minimal satu peserta tercentang dan
            belum Final. Konfirmasi dengan <span class="badge bg-gray-800 text-white">Ya, Finalisasi</span>.</li>
    </ul>
    <div class="alert alert-danger py-2 mb-3">
        <strong>Tidak bisa dibatalkan</strong> untuk peserta yang difinalisasi: nilai dan keputusan terkunci, nomor SK dan SP
        terbit (peserta remidi memakai nomor lamanya), nomor Sertifikat terbit untuk yang LULUS, dan nomor Keputusan Sertifikasi
        sesi terbit pada finalisasi pertama. Peserta yang tidak dicentang tidak terpengaruh.
    </div>

    <p class="fw-bolder mb-1">4. Setelah finalisasi</p>
    <ul class="text-muted mb-0 ps-3">
        <li>Status berubah dari <span class="badge bg-secondary">Draft</span> menjadi <span class="badge bg-gray-800 text-white">Final</span>
            dan centangnya terkunci. Satu sesi boleh berisi campuran Final dan Draft.</li>
        <li>Tombol <span class="badge bg-gray-200 text-gray-800 border">Keputusan Sertifikasi</span> mengunduh berita acara resmi.</li>
        <li>Pengiriman SK/SP/Sertifikat ke peserta ditangani admin di <strong>Rekap Hasil</strong>. Peserta LULUS perlu
            menandatangani FR.AK.14 di portalnya sebelum bisa mengunduh SK &amp; Sertifikat.</li>
    </ul>
</template>
