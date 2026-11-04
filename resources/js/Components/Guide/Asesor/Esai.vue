<template>
    <ol class="mb-2 ps-3">
        <li>Klik baris peserta (atau tombol <span class="badge bg-primary">Nilai</span>). Halaman penilaian peserta itu terbuka:
            satu kartu per soal, pertanyaan di kiri, jawaban peserta + kolom nilai di kanan (di layar kecil atas-bawah).</li>
        <li>Untuk <strong>Essay Migas</strong>, berkas jawaban peserta ada di kartu paling atas (Pratinjau / Unduh).</li>
        <li>Isi nilai <strong>0–100</strong> untuk tiap soal. <strong>Total Nilai</strong> = rata-rata nilai soal yang sudah diisi;
            nilai 0 ikut dihitung, soal yang dikosongkan tidak.</li>
        <li>Bar di kanan bawah: <span class="badge bg-success">Simpan</span> menyimpan nilai peserta ini,
            <span class="badge bg-primary">Simpan &amp; Berikutnya</span> menyimpan lalu membuka peserta berikutnya,
            <span class="badge bg-gray-200 text-gray-800 border">Sebelumnya</span> kembali ke peserta sebelumnya.
            <span class="badge bg-primary">Kembali</span> di atas membuka daftar peserta lagi.</li>
    </ol>
    <div class="alert alert-warning py-2 mb-2">
        Nilai baru tersimpan setelah tombol Simpan diklik. Bila masih ada nilai yang belum disimpan, bar menampilkan
        "Belum disimpan" dan Anda diminta konfirmasi sebelum meninggalkan halaman.
    </div>
    <p class="mb-0 text-muted">
        Kolom <strong>Progress</strong> di daftar peserta = jumlah soal yang sudah dinilai dari total soal. Badge
        <span class="badge bg-warning text-gray-800">Remidi</span> menandai peserta yang mengikuti ujian ulang.
    </p>
</template>
