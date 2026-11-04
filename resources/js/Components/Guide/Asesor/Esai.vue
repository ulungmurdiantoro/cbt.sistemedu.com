<template>
    <ol class="mb-2 ps-3">
        <li>Klik baris peserta (atau tombol <span class="badge bg-primary">Nilai</span>). Panel penilaian terbuka hampir selebar layar,
            satu kartu per soal: pertanyaan di kiri, jawaban peserta + kolom nilai di kanan (di layar kecil atas-bawah).</li>
        <li>Untuk <strong>Essay Migas</strong>, berkas jawaban peserta ada di kartu paling atas panel (Pratinjau / Unduh).</li>
        <li>Isi nilai <strong>0–100</strong> untuk tiap soal. <strong>Total</strong> = rata-rata nilai soal yang sudah diisi;
            nilai 0 ikut dihitung, soal yang dikosongkan tidak.</li>
        <li>Pindah peserta dengan <span class="badge bg-gray-200 text-gray-800 border">Sebelumnya</span> /
            <span class="badge bg-gray-200 text-gray-800 border">Berikutnya</span> di bawah panel. Nilai yang sudah diketik tetap ada.</li>
        <li>Klik <span class="badge bg-success">Simpan Semua Nilai</span> di bawah panel atau tombol yang sama
            di kanan atas kartu Daftar Peserta. Keduanya menyimpan nilai <strong>semua</strong> peserta sekaligus.</li>
    </ol>
    <div class="alert alert-warning py-2 mb-2">
        Nilai baru tersimpan setelah tombol Simpan diklik. Kalau halaman ditinggalkan atau di-refresh sebelum itu, ketikan hilang.
    </div>
    <p class="mb-0 text-muted">
        Kolom <strong>Progress</strong> = jumlah soal yang sudah dinilai dari total soal. Badge
        <span class="badge bg-warning text-gray-800">Remidi</span> menandai peserta yang mengikuti ujian ulang.
    </p>
</template>
