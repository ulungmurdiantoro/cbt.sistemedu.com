<template>
    <p class="mb-2">Halaman ini punya tiga bagian, masing-masing dengan tombol simpan sendiri:</p>
    <ol class="mb-2 ps-3">
        <li class="mb-1"><strong>Tanda Tangan Anda</strong>: gambar atau upload sekali, lalu
            <span class="badge bg-primary">Simpan Tanda Tangan</span>. TTD ini dipakai otomatis di FR.AK.05 dan di halaman
            TTD AK.01, untuk semua sesi.</li>
        <li class="mb-1"><strong>Rekomendasi Asesi</strong>: klik <span class="badge bg-success">K</span> (Kompeten) atau
            <span class="badge bg-danger">BK</span> (Belum Kompeten) per peserta, lalu
            <span class="badge bg-success">Simpan Rekomendasi</span>. Rekomendasi masih bisa diubah sampai Pengambil Keputusan
            memfinalisasi peserta itu. Setelah itu barisnya <i class="fa fa-lock"></i> terkunci.</li>
        <li><strong>Catatan Laporan Asesmen</strong>: tanggal dan empat kolom catatan sudah berisi teks contoh. Sunting
            seperlunya, lalu <span class="badge bg-success">Simpan Laporan</span>.</li>
    </ol>
    <p class="mb-0 text-muted">
        Tombol <span class="badge bg-light text-dark border">Download PDF</span> (FR.AK.05) muncul setelah laporan disimpan
        pertama kali. Isinya rekomendasi semua peserta Anda di sesi ini, catatan, dan TTD Anda. “Tidak ada aplikasi” berarti
        peserta belum punya permohonan sertifikasi, jadi belum bisa diberi rekomendasi.
    </p>
</template>
