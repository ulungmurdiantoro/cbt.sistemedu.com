<template>
    <p class="fw-bolder mb-1">1. Tinjau tiap peserta</p>
    <p class="text-muted mb-2">
        Halamannya sama dengan halaman Pengambil Keputusan: satu baris per peserta berisi dokumen persyaratan, FR.APL.01,
        FR.APL.03, FR.AK.01, laporan asesmen (FR.AK.05), nilai PG / Esai / Wawancara / Nilai Akhir, dan keputusan
        <span class="badge bg-success">LULUS</span> / <span class="badge bg-danger">TIDAK LULUS</span>.
        <em>Lihat Dokumen</em> membuka daftar berkas peserta (baca saja).
    </p>

    <p class="fw-bolder mb-1">2. Centang Verifikasi Manager Mutu</p>
    <ul class="text-muted mb-3 ps-3">
        <li>Kolom <strong>Verifikasi Pengambil Keputusan</strong> hanya tampilan: <i class="fa fa-check-circle text-success"></i> + nama PK bila
            sudah dicentang, <em>Belum</em> bila belum.</li>
        <li>Centang di kolom <strong>Verifikasi Manager Mutu</strong> baru aktif setelah PK memverifikasi peserta itu.
            Nama Anda tercatat di bawah centang, dan centang bisa dilepas lagi selama peserta belum Final.</li>
        <li>Selama Anda masih mencentang seorang peserta, PK tidak bisa melepas centangnya. Kalau PK perlu mengubah
            keputusannya, lepas centang Anda dulu.</li>
        <li>Peserta yang belum siap (mis. berhalangan hadir) biarkan tidak dicentang; mereka tetap Draft dan bisa
            difinalisasi belakangan, mis. saat ikut batch susulan.</li>
    </ul>

    <p class="fw-bolder mb-1">3. Preview, lalu Finalisasi</p>
    <ul class="text-muted mb-2 ps-3">
        <li><span class="badge bg-gray-200 text-gray-800 border">Preview Keputusan</span> membuka draf berita acara (peserta yang
            sudah dicentang PK dan Anda) di tab baru. Tombol ini hanya ada sebelum sesi pertama kali difinalisasi.</li>
        <li><span class="badge bg-warning text-gray-800">Finalisasi Semua</span> aktif bila minimal satu peserta sudah dicentang
            PK dan Anda, dan belum Final. Konfirmasi dengan <span class="badge bg-gray-800 text-white">Ya, Finalisasi</span>.</li>
        <li>Keputusan Sertifikasi terbit atas nama dan tanda tangan <strong>Pengambil Keputusan</strong> sesi, bukan nama Anda.
            Bila muncul peringatan TTD PK belum tersimpan, minta PK menyimpan tanda tangannya dulu.</li>
    </ul>
    <div class="alert alert-danger py-2 mb-3">
        <strong>Tidak bisa dibatalkan</strong> untuk peserta yang difinalisasi: nilai dan keputusan terkunci, nomor SK dan SP
        terbit (peserta remidi memakai nomor lamanya), nomor Sertifikat terbit untuk yang LULUS, dan nomor Keputusan Sertifikasi
        sesi terbit pada finalisasi pertama. Peserta yang tidak dicentang tidak terpengaruh.
    </div>

    <p class="fw-bolder mb-1">4. Setelah finalisasi</p>
    <ul class="text-muted mb-0 ps-3">
        <li>Status berubah dari <span class="badge bg-secondary">Draft</span> menjadi <span class="badge bg-gray-800 text-white">Final</span>
            dan kedua centang terkunci. Satu sesi boleh berisi campuran Final dan Draft.</li>
        <li>Tombol <span class="badge bg-gray-200 text-gray-800 border">Keputusan Sertifikasi</span> mengunduh berita acara resmi.</li>
        <li>Pengiriman SK/SP/Sertifikat ke peserta ditangani admin di <strong>Rekap Hasil</strong>. Peserta LULUS perlu
            menandatangani FR.AK.14 di portalnya sebelum bisa mengunduh SK &amp; Sertifikat.</li>
    </ul>
</template>
