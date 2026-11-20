<template>
    <p class="mb-2">
        Halaman ini hanya muncul untuk skema yang menilai <strong>Ujian Praktik</strong> sebagai komponen sendiri
        (mis. Lifting Engineer: Praktek Calculation dan Praktek Gambar). Skema lain menilai keterampilan lewat kriteria
        <em>Hasil Pengerjaan Worksheet</em> di halaman Wawancara.
    </p>
    <ul class="mb-0 ps-3">
        <li>Satu kolom nilai per bagian praktik, skala <strong>0–100</strong>. Bobot tiap bagian tertulis di judul kolom
            (persen dari nilai akhir) dan baru diterapkan saat Nilai Akhir dihitung.</li>
        <li><strong>Total Praktik</strong> = rata-rata berbobot bagian yang sudah diisi, mis. Calculation 50% dan Gambar 30%:
            (80 × 50 + 70 × 30) / 80 = 76,25.</li>
        <li>Tombol <i class="fa fa-eye"></i> di samping isian membuka hasil yang diunggah peserta untuk bagian itu
            (tab baru). Tanda “-” berarti peserta belum mengunggah.</li>
        <li>Mengosongkan isian lalu menyimpan berarti nilai bagian itu dihapus.</li>
        <li>Klik <span class="badge bg-success">Simpan Semua Nilai</span> di kanan atas kartu Daftar Peserta untuk menyimpan seluruh tabel sekaligus.</li>
    </ul>
</template>
